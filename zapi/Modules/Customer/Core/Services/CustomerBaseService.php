<?php

namespace Zapi\Modules\Customer\Core\Services;

use App\Models\AdminPackage;
use Zapi\Core\Support\Auth\JwtToken;
use App\Models\MovieServersModel;
use App\Models\News_notice;
use Zapi\Core\Base\BaseApiController;

class CustomerBaseService extends BaseApiController
{
    protected $payment_model, $News_notice, $user_model, $area_model, $ticket_model, $package_model, $router_model, $AdminPackage, $movieModel;
    protected ?array $rawInputCache = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->payment_model = model('App\Models\Payment');
        $this->AdminPackage = model('App\Models\AdminPackage');
        $this->movieModel = new MovieServersModel();
        $this->News_notice = new News_notice();
        $this->user_model = model('App\Models\User');
        $this->area_model = model('App\Models\Area');
        $this->ticket_model = model('App\Models\Ticket');
        $this->package_model = model('App\Models\Package');
        $this->router_model = model('App\Models\Router');
    }

    protected function getInputValue(string $key, $default = null)
    {
        if ($this->rawInputCache === null) {
            $parsed = $this->request->getJSON(true);
            if (!is_array($parsed)) {
                $parsed = $this->request->getRawInput();
            }
            $this->rawInputCache = is_array($parsed) ? $parsed : [];
        }

        $postValue = $this->request->getPost($key);
        if ($postValue !== null && $postValue !== '') {
            return $postValue;
        }

        if (array_key_exists($key, $this->rawInputCache) && $this->rawInputCache[$key] !== '') {
            return $this->rawInputCache[$key];
        }

        $getValue = $this->request->getGet($key);
        if ($getValue !== null && $getValue !== '') {
            return $getValue;
        }

        return $default;
    }

    protected function resolvePackagesForUser(object $userRow): array
    {
        $role = $userRow->role ?? '';
        $admin_id = $userRow->admin_id ?? null;
        $created_by = $userRow->created_by ?? '';

        if ($role === 'admin') {
            $packageModel = new AdminPackage();
            return $packageModel->where(['Activity' => 'active'])->findAll();
        }

        if ($role === 'user') {
            if ($created_by === 'resellerAdmin' && !empty($admin_id)) {
                $packageModel = model('App\Models\allResellerPackage');
                $rawPackages = $packageModel->where('user_id', $admin_id)->findAll();
                $packages = [];
                foreach ($rawPackages as $package) {
                    $row = is_array($package) ? $package : (array) $package;
                    $raw = $row['package_details'] ?? null;
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    if (!is_array($decoded)) {
                        continue;
                    }
                    foreach ($decoded as $detail) {
                        $packages[] = is_array($detail) ? $detail : (array) $detail;
                    }
                }
                return $packages;
            }

            $package_model = model('App\Models\Package');
            return $package_model->where(['user_id' => $admin_id])->findAll();
        }

        $packageModel = new AdminPackage();
        return $packageModel->findAll();
    }

    protected function statistics($role = null, $user_id = null, $admin_id = null)
    {
        $months = [];
        $success_payment = [];
        $pending_payment = [];
        $failed_payment = [];

        for ($i = 1; $i <= date('m'); $i++) {
            $successful = $this->transactionStatistics('successful', date('F', mktime(0, 0, 0, $i, 1, 2022)), $role, $user_id, $admin_id);
            $pending = $this->transactionStatistics('pending', date('F', mktime(0, 0, 0, $i, 1, 2022)), $role, $user_id, $admin_id);
            $failed = $this->transactionStatistics('failed', date('F', mktime(0, 0, 0, $i, 1, 2022)), $role, $user_id, $admin_id);

            $months[] = date('M', mktime(0, 0, 0, $i, 1, 2022));
            $success_payment[] = $successful;
            $pending_payment[] = $pending;
            $failed_payment[] = $failed;
        }

        return [
            'months' => $months,
            'successful' => $success_payment,
            'pending' => $pending_payment,
            'failed' => $failed_payment,
        ];
    }

    protected function transactionStatistics($status, $month, $role = null, $user_id = null, $admin_id = null)
    {
        $conditions = [
            'month' => $month,
            'status' => $status,
        ];

        if (!empty($role)) {
            $conditions['user_type'] = $role;
        }
        if (!empty($user_id)) {
            $conditions['user_id'] = $user_id;
        }
        if (!empty($admin_id)) {
            $conditions['admin_id'] = $admin_id;
        }

        $row = $this->payment_model->selectSum('amount')->where($conditions)->first();
        return (int) ($row->amount ?? 0);
    }

    protected function sumUserSelfPayments($userId, ?string $status = null): int
    {
        $builder = $this->payment_model->selectSum('amount')->where([
            'user_id' => $userId,
            'paidby' => $userId,
            'user_type' => 'user',
        ]);
        if ($status !== null && $status !== '') {
            $builder->where('status', $status);
        }
        $row = $builder->first();

        return (int) ($row->amount ?? 0);
    }

    /**
     * Numeric user id from Bearer JWT (`sub` / `user_id`), or null if missing/invalid.
     */
    protected function resolveAccessTokenUserId(): ?int
    {
        $authHeader = (string) $this->request->getHeaderLine('Authorization');
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return null;
        }
        $token = trim(substr($authHeader, 7));
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }
        $payload = JwtToken::verify($token, \Zapi\utils\JwtToken::secret());
        if (!is_array($payload)) {
            return null;
        }
        $uid = $payload['sub'] ?? $payload['user_id'] ?? null;

        return $uid !== null && $uid !== '' ? (int) $uid : null;
    }

    /**
     * Same scope as reseller payment list: row must reference the actor in one of these columns.
     */
    protected function actorOwnsResellerPayment(object $details, int $actorId): bool
    {
        $actorId = (int) $actorId;
        $uid = (int) ($details->user_id ?? 0);
        $paidby = (int) ($details->paidby ?? 0);
        $adminId = (int) ($details->admin_id ?? 0);
        $paidTo = (int) ($details->paid_to ?? 0);

        return $uid === $actorId
            || $paidby === $actorId
            || $adminId === $actorId
            || $paidTo === $actorId;
    }

    /* =====================================================================
     * Router-control contract methods
     * Shared by the Device / AutoFix / RouterControl customer modules.
     * Previously these were called but never defined (fatal); they now have
     * real implementations: ownership enforcement, audit logging, cooldowns.
     * ===================================================================== */

    /**
     * Load a customer (role = user) by id, or null. Returns the User object row.
     */
    protected function getUser($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return null;
        }

        return $this->user_model->where(['id' => $userId, 'role' => 'user'])->first();
    }

    /**
     * Whether the authenticated caller may act on $userId.
     *
     * - When a JWT is present (auth enabled): a customer may act only on their
     *   own id; the platform owner ('super_admin') may act on any; a tenant
     *   admin ('admin') may act on any customer belonging to THEIR OWN tenant
     *   only; a reseller may act on customers they directly own.
     * - When no token is resolvable (auth disabled for the env): defer to the
     *   route-level filter and allow, matching existing customer endpoints.
     *
     * Previously 'admin' (tenant tier, post role-rename) was granted an
     * unconditional bypass alongside 'super_admin' — a cross-tenant privilege
     * escalation letting any tenant admin act on ANY customer system-wide,
     * including router-control actions. getSAdminIdForUser() walks the
     * admin_id chain to the owning tenant admin, the same pattern used in
     * ResellerBaseService::canAccessReseller().
     */
    protected function actorCanAccessUser($userId): bool
    {
        $actorId = $this->resolveAccessTokenUserId();
        if ($actorId === null) {
            // No token context — the route filter is the gate (legacy behaviour).
            return true;
        }

        $userId = (int) $userId;
        if ($actorId === $userId) {
            return true;
        }

        $actor = $this->user_model->find($actorId);
        $role  = strtolower((string) ($actor->role ?? ''));

        if ($role === 'super_admin') {
            return true;
        }

        if ($role === 'admin') {
            helper('user');

            return (int) getSAdminIdForUser($userId) === $actorId;
        }

        if (in_array($role, ['reselleradmin'], true)) {
            $target = $this->user_model->find($userId);
            return $target && (int) ($target->admin_id ?? 0) === $actorId;
        }

        return false;
    }

    /**
     * Permission gate for a destructive/self-service router action.
     * Kept as a named method so call-sites read clearly and future
     * per-action policy can hook in here.
     */
    protected function canPerformAction($userId, string $action): bool
    {
        return $this->actorCanAccessUser($userId);
    }

    /**
     * Persist an audit record for a router-control action (best-effort; never throws).
     * Backed by the existing App\Models\AuditLogModel (audit_logs table).
     */
    protected function logAction($userId, string $action, string $description, array $context = []): void
    {
        try {
            $audit = model('App\Models\AuditLogModel');
            $audit->log([
                'user_id'    => (int) $userId,
                'action'     => $action,
                'entity'     => 'router_control',
                'client'     => $context['client'] ?? null,
                'router'     => isset($context['router']) ? (string) $context['router'] : null,
                'details'    => $description,
                'actor'      => (string) ($this->resolveAccessTokenUserId() ?? 'system'),
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => (string) $this->request->getUserAgent(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'router-control audit log failed: ' . $e->getMessage());
        }
    }

    /**
     * Simple per-user, per-action cooldown using the framework cache.
     * Returns true if the action may proceed (and arms the cooldown);
     * false if the caller is still within the cooldown window.
     */
    protected function withinCooldown(string $action, $userId, int $seconds): bool
    {
        try {
            $cache = \Config\Services::cache();
            $key   = "rc_cooldown_{$action}_" . (int) $userId;
            if ($cache->get($key)) {
                return false;
            }
            $cache->save($key, time(), $seconds);
            return true;
        } catch (\Throwable $e) {
            // If the cache backend is unavailable, do not block the action.
            log_message('warning', 'router-control cooldown check failed: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Admin/reseller id that owns (created) this customer account.
     */
    protected function resolveSupportAdminIdForUser(object $userRow): int
    {
        $adminId = (int) ($userRow->admin_id ?? 0);
        if ($adminId <= 0) {
            return 2;
        }

        return $adminId;
    }

    /**
     * @return array{admin_id: string, name: string, mobile: string, email: string, address: string, whatsapp_number: string}|null
     */
    protected function resolveSupportContactForUser(int $userId): ?array
    {
        $details = $this->getUser($userId);
        if (!$details) {
            return null;
        }

        $adminId = $this->resolveSupportAdminIdForUser($details);
        $adminDetails = $this->user_model->asObject()->where('id', $adminId)->first();
        if (empty($adminDetails)) {
            return null;
        }

        $mobile = trim((string) ($adminDetails->mobile ?? ''));
        $whatsapp = trim((string) ($adminDetails->whatsapp_number ?? ''));
        if ($whatsapp === '') {
            $whatsapp = $mobile;
        }

        return [
            'admin_id' => (string) $adminId,
            'name' => (string) ($adminDetails->name ?? ''),
            'mobile' => $mobile,
            'email' => (string) ($adminDetails->email ?? ''),
            'address' => (string) ($adminDetails->address ?? ''),
            'whatsapp_number' => $whatsapp,
        ];
    }
}
