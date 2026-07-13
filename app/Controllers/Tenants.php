<?php

namespace App\Controllers;

use App\Models\AdminPackage;
use App\Models\Registration;
use App\Models\TenantModel;
use App\Models\User;
use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * Platform super-admin (role=super_admin) tenant / portal management.
 */
class Tenants extends BaseController
{
    /** @var TenantModel */
    protected $tenants;

    /** @var User */
    protected $users;

    public function __construct()
    {
        helper(['tenant', 'utility', 'user', 'flag']);
        $this->tenants = model(TenantModel::class);
        $this->users   = model(User::class);
    }

    private function requirePlatformAdmin()
    {
        $isAjax = $this->request->isAJAX()
            || strtolower((string) $this->request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

        if (! isTenantingEnabled()) {
            if ($isAjax) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'   => 'error',
                    'response' => 'Tenant portals are currently disabled.',
                ]);
            }

            return redirect()->to(route_to('route.dashboard'))
                ->with('error', 'Tenant portals are currently disabled.');
        }

        if (! isPlatformSuperAdmin()) {
            if ($isAjax) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'   => 'error',
                    'response' => 'Only platform admin can manage tenants.',
                ]);
            }

            return redirect()->to(route_to('route.dashboard'))
                ->with('error', 'Only platform admin can manage tenants.');
        }

        // Tenant portals are not the control plane.
        if (function_exists('isTenantRequest') && isTenantRequest()) {
            if ($isAjax) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'   => 'error',
                    'response' => 'Manage tenants from the main platform domain.',
                ]);
            }

            return redirect()->to(route_to('route.dashboard'))
                ->with('error', 'Manage tenants from the main platform domain.');
        }

        return null;
    }

    public function index()
    {
        if ($deny = $this->requirePlatformAdmin()) {
            return $deny;
        }

        $status = $this->request->getGet('status');
        $q      = trim((string) $this->request->getGet('q'));

        $builder = $this->tenants->builder();
        $builder->select('tenants.*, users.name as owner_name, users.email as owner_email, users.mobile as owner_mobile, users.subscription_status as owner_subscription, users.status as owner_status');
        $builder->join('users', 'users.id = tenants.owner_user_id', 'left');

        if (in_array($status, ['active', 'suspended'], true)) {
            $builder->where('tenants.status', $status);
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('tenants.name', $q)
                ->orLike('tenants.slug', $q)
                ->orLike('users.email', $q)
                ->orLike('users.name', $q)
                ->orLike('users.mobile', $q)
                ->groupEnd();
        }

        $builder->orderBy('tenants.id', 'DESC');
        $rows = $builder->get()->getResult();

        $stats = [
            'total'     => (int) $this->tenants->builder()->countAllResults(),
            'active'    => (int) $this->tenants->builder()->where('status', 'active')->countAllResults(),
            'suspended' => (int) $this->tenants->builder()->where('status', 'suspended')->countAllResults(),
            'unlinked'  => $this->countUnlinkedSAdmins(),
        ];

        return view('tenants/index', [
            'title'   => 'Tenant Portals',
            'tenants' => $rows,
            'stats'   => $stats,
            'status'  => $status,
            'q'       => $q,
            'baseDomain' => tenantBaseDomain(),
        ]);
    }

    public function create()
    {
        if ($deny = $this->requirePlatformAdmin()) {
            return $deny;
        }

        return view('tenants/form', [
            'title'          => 'Create Tenant Portal',
            'tenant'         => null,
            'mode'           => 'create',
            'unlinkedOwners' => $this->unlinkedSAdmins(),
            'packages'       => $this->activePackages(),
            'baseDomain'     => tenantBaseDomain(),
        ]);
    }

    public function store()
    {
        if ($deny = $this->requirePlatformAdmin()) {
            return $deny;
        }

        $mode = (string) getPostInput('owner_mode'); // new | existing
        $slug = strtolower(trim((string) getPostInput('slug')));
        $name = trim((string) getPostInput('name'));
        $plan = trim((string) getPostInput('plan'));
        $primaryColor = trim((string) getPostInput('primary_color'));
        $notes = trim((string) getPostInput('notes'));
        $status = getPostInput('status') === 'suspended' ? 'suspended' : 'active';

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Company name is required.';
        }
        if (!TenantModel::isValidSlug($slug)) {
            $errors['slug'] = 'Enter a valid subdomain (2–63 chars, letters/numbers/hyphen). Reserved names are blocked.';
        } elseif ($this->tenants->findBySlug($slug)) {
            $errors['slug'] = 'This subdomain is already taken.';
        }

        if ($primaryColor !== '' && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $primaryColor)) {
            $errors['primary_color'] = 'Use a valid hex color (e.g. #2563eb).';
        }

        $ownerId = 0;
        $plainPassword = null;

        if ($mode === 'existing') {
            $ownerId = (int) getPostInput('owner_user_id');
            if ($ownerId <= 0) {
                $errors['owner_user_id'] = 'Select an existing Second Admin.';
            } else {
                $owner = $this->users->where(['id' => $ownerId, 'role' => 'admin'])->first();
                if (!$owner) {
                    $errors['owner_user_id'] = 'Selected owner is not a valid Second Admin.';
                } elseif ($this->tenants->findByOwner($ownerId)) {
                    $errors['owner_user_id'] = 'This admin already has a portal.';
                }
            }
        } else {
            $mode = 'new';
            $ownerName  = trim((string) getPostInput('owner_name'));
            $ownerEmail = trim((string) getPostInput('owner_email'));
            $ownerMobile = trim((string) getPostInput('owner_mobile'));
            $password   = (string) getPostInput('owner_password');
            $packageId  = (int) getPostInput('package_id');

            if ($ownerName === '') {
                $errors['owner_name'] = 'Owner name is required.';
            }
            if ($ownerEmail === '' || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['owner_email'] = 'Valid owner email is required.';
            } elseif ($this->users->where('email', $ownerEmail)->first()) {
                $errors['owner_email'] = 'Email is already registered.';
            }
            if ($ownerMobile === '') {
                $errors['owner_mobile'] = 'Owner mobile is required.';
            } elseif ($this->users->where('mobile', $ownerMobile)->first()) {
                $errors['owner_mobile'] = 'Mobile is already registered.';
            }
            if (strlen($password) < 4) {
                $errors['owner_password'] = 'Password must be at least 4 characters.';
            }
            if ($packageId > 0) {
                $pkg = model(AdminPackage::class)->find($packageId);
                if (!$pkg) {
                    $errors['package_id'] = 'Selected package is invalid.';
                }
            }
            $plainPassword = $password;
        }

        if (!empty($errors)) {
            return requestResponse('validation-error', $errors, 400);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            if ($mode === 'new') {
                $ownerName   = trim((string) getPostInput('owner_name'));
                $ownerEmail  = trim((string) getPostInput('owner_email'));
                $ownerMobile = trim((string) getPostInput('owner_mobile'));
                $password    = (string) getPostInput('owner_password');
                $packageId   = (int) getPostInput('package_id');
                $address     = trim((string) getPostInput('address'));

                $userData = [
                    'name'                => $ownerName,
                    'designation'         => 'Admin',
                    'mobile'              => $ownerMobile,
                    'email'               => $ownerEmail,
                    'code'                => $password,
                    'password'            => password_hash($password, PASSWORD_DEFAULT),
                    'address'             => $address !== '' ? $address : null,
                    'role'                => 'admin',
                    'status'              => 'active',
                    'subscription_status' => 'active',
                    'created_by'          => 'super_admin',
                ];

                if ($packageId > 0 && function_exists('calsAdminPackageExpireDate')) {
                    $userData['package_id']  = $packageId;
                    $userData['will_expire'] = calsAdminPackageExpireDate($packageId, date('Y-m-d H:i:s'));
                }

                if (!$this->users->insert($userData)) {
                    throw new \RuntimeException('Failed to create owner account.');
                }
                $ownerId = (int) $this->users->getInsertID();

                model(Registration::class)->insert([
                    'userid'            => $ownerId,
                    'organization_name' => $name,
                    'admin_name'        => $ownerName,
                    'mobile'            => $ownerMobile,
                    'email'             => $ownerEmail,
                    'password'          => password_hash($password, PASSWORD_DEFAULT),
                    'address'           => $address,
                    'package'           => $packageId > 0 ? $packageId : null,
                ]);

                seedDefaultTenantPermissions($ownerId);
            } else {
                // Keep org name in sync when linking an existing owner.
                $regModel = model(Registration::class);
                $reg      = $regModel->where('userid', $ownerId)->first();
                if ($reg) {
                    $regModel->builder()
                        ->where('userid', $ownerId)
                        ->update(['organization_name' => $name]);
                } else {
                    $owner = $this->users->find($ownerId);
                    $regModel->insert([
                        'userid'            => $ownerId,
                        'organization_name' => $name,
                        'admin_name'        => $owner->name ?? $name,
                        'mobile'            => $owner->mobile ?? '',
                        'email'             => $owner->email ?? '',
                    ]);
                }
            }

            $now = date('Y-m-d H:i:s');
            $tenantData = [
                'slug'          => $slug,
                'name'          => $name,
                'owner_user_id' => $ownerId,
                'status'        => $status,
                'plan'          => $plan !== '' ? $plan : null,
                'primary_color' => $primaryColor !== '' ? $primaryColor : '#2563eb',
                'notes'         => $notes !== '' ? $notes : null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            if (!$this->tenants->insert($tenantData)) {
                throw new \RuntimeException('Failed to create tenant.');
            }
            $tenantId = (int) $this->tenants->getInsertID();

            // Bind owner + propagate tenant_id when column exists.
            $this->bindUserTenant($ownerId, $tenantId);
            $this->propagateTenantToTree($ownerId, $tenantId);

            ensureTenantStorage($tenantId);

            $logoName = $this->handleLogoUpload($tenantId);
            if ($logoName) {
                $this->tenants->update($tenantId, ['logo' => $logoName, 'updated_at' => $now]);
            }

            $owner = $this->users->find($ownerId);
            seedTenantOwnerSettings($ownerId, [
                'name'     => $name,
                'app_name' => $name,
                'email'    => $owner->email ?? getPostInput('owner_email'),
                'phone'    => $owner->mobile ?? getPostInput('owner_mobile'),
                'address'  => $owner->address ?? getPostInput('address'),
                'app_logo' => $logoName,
            ]);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Tenant create failed: ' . $e->getMessage());

            return requestResponse('error', 'Could not create tenant. ' . $e->getMessage(), 500);
        }

        $portalUrl = tenantPortalUrl($slug);

        return requestResponse('success', [
            'msg'        => 'Tenant portal created successfully.',
            'tenant_id'  => $tenantId,
            'portal_url' => $portalUrl,
            'redirect'   => route_to('route.tenants.details', $tenantId),
            'password'   => $plainPassword,
        ], 200);
    }

    public function details($id)
    {
        if ($deny = $this->requirePlatformAdmin()) {
            return $deny;
        }

        $tenant = $this->findTenantWithOwner((int) $id);
        if (!$tenant) {
            return redirect()->to(route_to('route.tenants'))
                ->with('error', 'Tenant not found.');
        }

        $ownerId = (int) ($tenant->owner_user_id ?? 0);
        $counts  = [
            'customers' => 0,
            'resellers' => 0,
            'employees' => 0,
        ];
        if ($ownerId > 0) {
            $counts['customers'] = (int) $this->users->where(['role' => 'user', 'admin_id' => $ownerId])->countAllResults();
            $counts['resellers'] = (int) $this->users->where(['role' => 'resellerAdmin', 'admin_id' => $ownerId])->countAllResults();
            $counts['employees'] = (int) $this->users->where(['role' => 'employee', 'admin_id' => $ownerId])->countAllResults();
        }

        return view('tenants/details', [
            'title'      => 'Tenant Details',
            'tenant'     => $tenant,
            'counts'     => $counts,
            'portalUrl'  => tenantPortalUrl((string) $tenant->slug),
            'baseDomain' => tenantBaseDomain(),
            'logoUrl'    => tenantLogoUrl($tenant),
        ]);
    }

    public function edit($id)
    {
        if ($deny = $this->requirePlatformAdmin()) {
            return $deny;
        }

        $tenant = $this->findTenantWithOwner((int) $id);
        if (!$tenant) {
            return redirect()->to(route_to('route.tenants'))
                ->with('error', 'Tenant not found.');
        }

        return view('tenants/form', [
            'title'          => 'Edit Tenant Portal',
            'tenant'         => $tenant,
            'mode'           => 'edit',
            'unlinkedOwners' => [],
            'packages'       => [],
            'baseDomain'     => tenantBaseDomain(),
        ]);
    }

    public function update($id)
    {
        if ($deny = $this->requirePlatformAdmin()) {
            return $deny;
        }

        $id     = (int) $id;
        $tenant = $this->tenants->find($id);
        if (!$tenant) {
            return requestResponse('error', 'Tenant not found.', 404);
        }

        $name = trim((string) getPostInput('name'));
        $slug = strtolower(trim((string) getPostInput('slug')));
        $plan = trim((string) getPostInput('plan'));
        $primaryColor = trim((string) getPostInput('primary_color'));
        $notes = trim((string) getPostInput('notes'));
        $status = getPostInput('status') === 'suspended' ? 'suspended' : 'active';

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Company name is required.';
        }
        if (!TenantModel::isValidSlug($slug)) {
            $errors['slug'] = 'Enter a valid subdomain.';
        } else {
            $existing = $this->tenants->findBySlug($slug);
            if ($existing && (int) $existing->id !== $id) {
                $errors['slug'] = 'This subdomain is already taken.';
            }
        }
        if ($primaryColor !== '' && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $primaryColor)) {
            $errors['primary_color'] = 'Use a valid hex color (e.g. #2563eb).';
        }

        if (!empty($errors)) {
            return requestResponse('validation-error', $errors, 400);
        }

        $now  = date('Y-m-d H:i:s');
        $data = [
            'name'          => $name,
            'slug'          => $slug,
            'plan'          => $plan !== '' ? $plan : null,
            'primary_color' => $primaryColor !== '' ? $primaryColor : ($tenant->primary_color ?? '#2563eb'),
            'notes'         => $notes !== '' ? $notes : null,
            'status'        => $status,
            'updated_at'    => $now,
        ];

        $logoName = $this->handleLogoUpload($id);
        if ($logoName) {
            // Remove old logo file if present.
            if (!empty($tenant->logo)) {
                $old = FCPATH . 'assets/img/tenants/' . $id . '/' . $tenant->logo;
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            $data['logo'] = $logoName;
        }

        if (!$this->tenants->update($id, $data)) {
            return requestResponse('error', 'Failed to update tenant.', 500);
        }

        $ownerId = (int) ($tenant->owner_user_id ?? 0);
        if ($ownerId > 0) {
            seedTenantOwnerSettings($ownerId, [
                'name'     => $name,
                'app_name' => $name,
                'app_logo' => $logoName ?? null,
            ]);
            $this->bindUserTenant($ownerId, $id);
            $this->propagateTenantToTree($ownerId, $id);
        }

        return requestResponse('success', [
            'msg'        => 'Tenant updated successfully.',
            'portal_url' => tenantPortalUrl($slug),
            'redirect'   => route_to('route.tenants.details', $id),
        ], 200);
    }

    public function setStatus($id)
    {
        if ($deny = $this->requirePlatformAdmin()) {
            return $deny;
        }

        $id     = (int) $id;
        $tenant = $this->tenants->find($id);
        if (!$tenant) {
            return requestResponse('error', 'Tenant not found.', 404);
        }

        $status = getPostInput('status') === 'suspended' ? 'suspended' : 'active';
        $this->tenants->update($id, [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $label = $status === 'active' ? 'activated' : 'suspended';

        return requestResponse('success', [
            'msg'    => 'Portal ' . $label . ' successfully.',
            'status' => $status,
        ], 200);
    }

    public function destroy()
    {
        if ($deny = $this->requirePlatformAdmin()) {
            return $deny;
        }

        $id = (int) (getPostInput('id') ?? getRawInput('id') ?? 0);
        if ($id <= 0) {
            $ids = getRawInput('ids');
            if (is_array($ids) && count($ids) === 1) {
                $id = (int) $ids[0];
            }
        }

        $tenant = $this->tenants->find($id);
        if (!$tenant) {
            return requestResponse('error', 'Tenant not found.', 404);
        }

        // Soft-delete style: suspend + clear slug uniqueness by renaming slug.
        $archivedSlug = 'archived-' . $id . '-' . substr((string) $tenant->slug, 0, 40);
        $this->tenants->update($id, [
            'status'     => 'suspended',
            'slug'       => $archivedSlug,
            'updated_at' => date('Y-m-d H:i:s'),
            'notes'      => trim((string) ($tenant->notes ?? '') . "\n[Archived " . date('Y-m-d H:i') . ']'),
        ]);

        // Unbind tenant_id from users but keep accounts.
        $this->clearTenantBinding($id);

        return requestResponse('success', 'Tenant portal archived. Owner account was kept.', 200);
    }

    private function findTenantWithOwner(int $id)
    {
        return $this->tenants->builder()
            ->select('tenants.*, users.name as owner_name, users.email as owner_email, users.mobile as owner_mobile, users.subscription_status as owner_subscription, users.status as owner_status, users.will_expire as owner_will_expire')
            ->join('users', 'users.id = tenants.owner_user_id', 'left')
            ->where('tenants.id', $id)
            ->get()
            ->getRow();
    }

    private function countUnlinkedSAdmins(): int
    {
        $linked = $this->tenants->builder()
            ->select('owner_user_id')
            ->where('owner_user_id IS NOT NULL')
            ->where('owner_user_id >', 0)
            ->get()
            ->getResultArray();
        $linkedIds = array_filter(array_map('intval', array_column($linked, 'owner_user_id')));

        $builder = $this->users->builder()->where('role', 'admin');
        if (!empty($linkedIds)) {
            $builder->whereNotIn('id', $linkedIds);
        }

        return (int) $builder->countAllResults();
    }

    private function unlinkedSAdmins(): array
    {
        $linked = $this->tenants->builder()
            ->select('owner_user_id')
            ->where('owner_user_id IS NOT NULL')
            ->where('owner_user_id >', 0)
            ->get()
            ->getResultArray();
        $linkedIds = array_filter(array_map('intval', array_column($linked, 'owner_user_id')));

        $builder = $this->users->builder()
            ->select('id, name, email, mobile, status, subscription_status')
            ->where('role', 'admin')
            ->orderBy('name', 'ASC');
        if (!empty($linkedIds)) {
            $builder->whereNotIn('id', $linkedIds);
        }

        return $builder->get()->getResult();
    }

    private function activePackages(): array
    {
        try {
            return model(AdminPackage::class)->where(['Activity' => 'active'])->findAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function handleLogoUpload(int $tenantId): ?string
    {
        $file = $this->request->getFile('logo');
        if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (!$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $mime = (string) $file->getMimeType();
        $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($mime, $allowed, true) && !in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true)) {
            return null;
        }

        $dir = ensureTenantStorage($tenantId);
        $name = $file->getRandomName();
        $file->move($dir, $name);

        return $name;
    }

    private function bindUserTenant(int $userId, int $tenantId): void
    {
        if ($userId <= 0 || $tenantId <= 0) {
            return;
        }

        try {
            $db = \Config\Database::connect();
            if (!$db->fieldExists('tenant_id', 'users')) {
                return;
            }
            $db->table('users')->where('id', $userId)->update(['tenant_id' => $tenantId]);
        } catch (DatabaseException $e) {
            log_message('error', 'bindUserTenant: ' . $e->getMessage());
        }
    }

    private function propagateTenantToTree(int $ownerUserId, int $tenantId): void
    {
        if ($ownerUserId <= 0 || $tenantId <= 0) {
            return;
        }

        try {
            $db = \Config\Database::connect();
            if (!$db->fieldExists('tenant_id', 'users')) {
                return;
            }

            // Direct children of sAdmin.
            $db->table('users')
                ->where('admin_id', $ownerUserId)
                ->update(['tenant_id' => $tenantId]);

            // Reseller children (customers/employees under resellers).
            $resellerIds = $db->table('users')
                ->select('id')
                ->where('admin_id', $ownerUserId)
                ->where('role', 'resellerAdmin')
                ->get()
                ->getResultArray();
            $ids = array_map('intval', array_column($resellerIds, 'id'));
            if (!empty($ids)) {
                $db->table('users')
                    ->whereIn('admin_id', $ids)
                    ->update(['tenant_id' => $tenantId]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'propagateTenantToTree: ' . $e->getMessage());
        }
    }

    private function clearTenantBinding(int $tenantId): void
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->fieldExists('tenant_id', 'users')) {
                return;
            }
            $db->table('users')->where('tenant_id', $tenantId)->update(['tenant_id' => null]);
        } catch (\Throwable $e) {
            log_message('error', 'clearTenantBinding: ' . $e->getMessage());
        }
    }
}
