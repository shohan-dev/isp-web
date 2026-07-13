<?php

use App\Libraries\TenantContext;
use App\Models\TenantModel;
use App\Models\User;

if (!function_exists('platformDomain')) {
    /**
     * Apex / platform host (no tenant). Configurable via app.platformDomain.
     */
    function platformDomain(): string
    {
        $domain = (string) env('app.platformDomain', '');
        if ($domain === '') {
            $base = (string) (config('App')->baseURL ?? '');
            $host = parse_url($base, PHP_URL_HOST);
            $domain = is_string($host) ? $host : 'localhost';
        }

        return strtolower(preg_replace('/:\d+$/', '', $domain) ?? $domain);
    }
}

if (!function_exists('tenantBaseDomain')) {
    /**
     * Domain used to build portal URLs: {slug}.{baseDomain}
     */
    function tenantBaseDomain(): string
    {
        $domain = (string) env('app.tenantBaseDomain', '');
        if ($domain !== '') {
            return strtolower(preg_replace('/:\d+$/', '', $domain) ?? $domain);
        }

        return platformDomain();
    }
}

if (!function_exists('normalizeRequestHost')) {
    function normalizeRequestHost(?string $host = null): string
    {
        if ($host === null || $host === '') {
            $host = (string) (service('request')->getServer('HTTP_HOST') ?? '');
        }
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        return $host;
    }
}

if (!function_exists('isPlatformHost')) {
    function isPlatformHost(?string $host = null): bool
    {
        $host = normalizeRequestHost($host);
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        // Direct IP access is always treated as the platform control plane.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        $platform = platformDomain();
        if ($host === $platform || $host === 'www.' . $platform) {
            return true;
        }

        // Explicit platform aliases (comma-separated) for legacy hosts.
        $aliases = (string) env('app.platformAliases', '');
        if ($aliases !== '') {
            foreach (explode(',', $aliases) as $alias) {
                $alias = strtolower(trim($alias));
                if ($alias !== '' && $host === $alias) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('extractTenantSlugFromHost')) {
    function extractTenantSlugFromHost(?string $host = null): ?string
    {
        $host = normalizeRequestHost($host);
        if ($host === '' || isPlatformHost($host)) {
            return null;
        }

        $base = tenantBaseDomain();
        $suffix = '.' . $base;
        if ($base !== '' && substr($host, -strlen($suffix)) === $suffix) {
            $slug = substr($host, 0, -strlen($suffix));
            if ($slug !== '' && strpos($slug, '.') === false) {
                return strtolower($slug);
            }

            return null;
        }

        // Local / hosts-file style: abc.localhost
        if (substr($host, -10) === '.localhost') {
            $slug = substr($host, 0, -10);
            if ($slug !== '' && strpos($slug, '.') === false) {
                return strtolower($slug);
            }
        }

        return null;
    }
}

if (!function_exists('tenantPortalUrl')) {
    function tenantPortalUrl(string $slug, string $path = '/'): string
    {
        $slug = strtolower(trim($slug));
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            $path = '';
        }

        $base = tenantBaseDomain();
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        // Prefer request scheme when available.
        try {
            $req = service('request');
            if ($req && method_exists($req, 'getUri')) {
                $uriScheme = $req->getUri()->getScheme();
                if (is_string($uriScheme) && $uriScheme !== '') {
                    $scheme = $uriScheme;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if ($base === 'localhost' || $base === '127.0.0.1') {
            return $scheme . '://' . $slug . '.localhost' . $path;
        }

        return $scheme . '://' . $slug . '.' . $base . $path;
    }
}

if (!function_exists('currentTenant')) {
    /** @return object|null */
    function currentTenant()
    {
        return TenantContext::tenant();
    }
}

if (!function_exists('currentTenantId')) {
    function currentTenantId(): ?int
    {
        return TenantContext::id();
    }
}

if (!function_exists('currentTenantOwnerId')) {
    function currentTenantOwnerId(): ?int
    {
        return TenantContext::ownerUserId();
    }
}

if (!function_exists('isTenantRequest')) {
    function isTenantRequest(): bool
    {
        return TenantContext::isTenant();
    }
}

if (!function_exists('isPlatformRequest')) {
    function isPlatformRequest(): bool
    {
        return TenantContext::isPlatform();
    }
}

if (!function_exists('tenantBrandingUserId')) {
    function tenantBrandingUserId(): int
    {
        return TenantContext::brandingUserId();
    }
}

if (!function_exists('tenantLogoUrl')) {
    function tenantLogoUrl($tenant = null): string
    {
        $tenant = $tenant ?? currentTenant();
        if ($tenant && !empty($tenant->logo)) {
            return brandAssetUrl('img/tenants/' . (int) $tenant->id . '/' . ltrim($tenant->logo, '/'));
        }

        $ownerId = $tenant->owner_user_id ?? tenantBrandingUserId();

        return brandLogoUrlWithFallback(getBrandLogoUrl((int) $ownerId));
    }
}

if (!function_exists('resolveUserTenantId')) {
    /**
     * Resolve which tenant a user belongs to (tenant_id column or owner/tree).
     */
    function resolveUserTenantId($user): ?int
    {
        if (empty($user)) {
            return null;
        }

        if (is_array($user)) {
            $user = (object) $user;
        }

        if (!empty($user->tenant_id)) {
            return (int) $user->tenant_id;
        }

        $userModel   = model(User::class);
        $tenantModel = model(TenantModel::class);
        $role        = (string) ($user->role ?? '');
        $userId      = (int) ($user->id ?? 0);

        if ($role === 'super_admin') {
            return null; // platform operator
        }

        if ($role === 'admin' && $userId > 0) {
            $tenant = $tenantModel->findByOwner($userId);

            return $tenant ? (int) $tenant->id : null;
        }

        // Walk admin_id chain to sAdmin owner.
        $cursor = $user;
        $guard  = 0;
        while ($cursor && $guard < 8) {
            $guard++;
            $adminId = (int) ($cursor->admin_id ?? 0);
            if ($adminId <= 0) {
                break;
            }
            $parent = $userModel->find($adminId);
            if (!$parent) {
                break;
            }
            if (($parent->role ?? '') === 'admin') {
                if (!empty($parent->tenant_id)) {
                    return (int) $parent->tenant_id;
                }
                $tenant = $tenantModel->findByOwner((int) $parent->id);

                return $tenant ? (int) $tenant->id : null;
            }
            $cursor = $parent;
        }

        return null;
    }
}

if (!function_exists('ensureTenantStorage')) {
    function ensureTenantStorage(int $tenantId): string
    {
        $dir = FCPATH . 'assets/img/tenants/' . $tenantId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }
}

if (!function_exists('seedTenantOwnerSettings')) {
    /**
     * Seed branding settings for the tenant owner (uses existing settings system).
     */
    function seedTenantOwnerSettings(int $ownerUserId, array $data): void
    {
        if ($ownerUserId <= 0) {
            return;
        }

        helper('setting');
        $prefix = 'BaseController' . $ownerUserId;
        $map    = [
            'app_name'         => $data['app_name'] ?? $data['name'] ?? null,
            'app_title'        => $data['app_name'] ?? $data['name'] ?? null,
            'app_slogan'       => $data['app_slogan'] ?? null,
            'company_cell'     => $data['phone'] ?? null,
            'support_mobile'   => $data['phone'] ?? null,
            'webmaster_email'  => $data['email'] ?? null,
            'company_address'  => $data['address'] ?? null,
            'app_logo'         => $data['app_logo'] ?? null,
            'app_icon'         => $data['app_icon'] ?? null,
        ];

        foreach ($map as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            setting()->set($prefix . '.' . $key, $value);
        }

        if (function_exists('bumpSettingsCacheVersion')) {
            bumpSettingsCacheVersion();
        }
    }
}

if (!function_exists('seedDefaultTenantPermissions')) {
    /**
     * Default permission templates for a new sAdmin owner (mirrors registration).
     */
    function seedDefaultTenantPermissions(int $ownerUserId): void
    {
        if ($ownerUserId <= 0) {
            return;
        }

        $permissionModel = model('App\Models\Permission');
        $existing        = $permissionModel->where('user_id', $ownerUserId)->countAllResults();
        if ($existing > 0) {
            return;
        }

        $rows = [
            [
                'user_id'     => $ownerUserId,
                'user_type'   => 'user',
                'permissions' => json_encode([
                    'support_ticket'  => ['read', 'create', 'send_msg', 'update'],
                    'payment'         => ['read', 'payment', 'invoice'],
                    'subscription'    => ['read', 'renew'],
                    'profile_update'  => ['read', 'update'],
                    'password_change' => ['update'],
                ]),
            ],
            [
                'user_id'     => $ownerUserId,
                'user_type'   => 'employee',
                'permissions' => json_encode([
                    'area'            => ['read', 'create', 'delete', 'update'],
                    'package'         => ['read', 'create'],
                    'customer'        => ['read', 'create', 'delete', 'update', 'update_subscription', 'update_conn'],
                    'password_change' => ['update'],
                ]),
            ],
            [
                'user_id'     => $ownerUserId,
                'user_type'   => 'resellerAdmin',
                'permissions' => json_encode([
                    'area'             => ['read', 'create', 'delete', 'update'],
                    'package'          => ['read'],
                    'customer'         => ['read', 'create', 'delete', 'update', 'update_subscription'],
                    'employee'         => ['read', 'create', 'delete', 'update'],
                    'reseller'         => ['read', 'update', 'update_subscription', 'update_conn'],
                    'customer_payment' => ['read', 'create', 'delete', 'update', 'invoice'],
                    'employee_payment' => ['read', 'create', 'delete', 'update'],
                    'support_ticket'   => ['read', 'create', 'send_msg', 'delete', 'update'],
                    'payment'          => ['read', 'payment', 'invoice'],
                    'subscription'     => ['read', 'renew'],
                    'profile_update'   => ['read', 'update'],
                    'password_change'  => ['update'],
                ]),
            ],
        ];

        foreach ($rows as $row) {
            $permissionModel->insert($row);
        }
    }
}
