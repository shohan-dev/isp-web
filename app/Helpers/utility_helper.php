<?php

/**
 * Utility Helper File
 */

if (!function_exists('brandAssetUrl')) {
    /**
     * Public URL for an assets/ file with cache-bust when present on disk.
     */
    function brandAssetUrl(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $url = rtrim(base_url(), '/') . '/assets/' . $relativePath;
        $file = FCPATH . 'assets/' . $relativePath;

        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }

        return $url;
    }
}

if (!function_exists('getBrandFaviconUrl')) {
    /**
     * Default platform wordmark fallback at assets/img/icon/logo.png.
     */
    function getBrandFaviconUrl(): string
    {
        return brandAssetUrl('img/icon/logo.png');
    }
}

if (!function_exists('brandIconFileExists')) {
    function brandIconFileExists(): bool
    {
        return is_file(FCPATH . 'assets/img/icon/logo.png');
    }
}

if (!function_exists('brandLogoUrlWithFallback')) {
    /**
     * Use requested logo when the file exists; otherwise assets/img/icon/logo.png.
     */
    function brandLogoUrlWithFallback(?string $url = null): string
    {
        $fallback = getBrandFaviconUrl();

        if ($url === null || $url === '') {
            return $fallback;
        }

        if (isPlatformDefaultLogo($url)) {
            return $fallback;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if ($path !== '' && preg_match('#/assets/(.+)$#', $path, $matches)) {
            $file = FCPATH . 'assets/' . $matches[1];
            if (is_file($file)) {
                return $url;
            }
        }

        return $fallback;
    }
}

if (!function_exists('safePlatformBrandTitle')) {
    function safePlatformBrandTitle(): string
    {
        try {
            return getPlatformBrandTitle();
        } catch (\Throwable $e) {
            return 'ISP Pay BD';
        }
    }
}

if (!function_exists('safeAuthBrandLogo')) {
    /**
     * Auth logo resolution with DB-safe fallback to assets/img/icon/logo.png.
     */
    function safeAuthBrandLogo(
        string $context,
        $tenant = null,
        ?int $brandUserId = null,
        ?string $brandTitle = null,
        ?string $appName = null
    ): array {
        $fallbackUrl = getBrandFaviconUrl();
        $fallbackAlt = 'ISP Pay BD';
        $brandUserId = $brandUserId ?? 2;

        $tenantActive = false;
        try {
            $tenantActive = function_exists('isTenantRequest') && isTenantRequest();
        } catch (\Throwable $e) {
            $tenantActive = false;
        }

        try {
            if ($context === 'auth-register') {
                $platformId = platformBrandingUserId();
                $logoUrl = brandLogoUrlWithFallback(getBrandLogoUrl($platformId));
                $logoAlt = safePlatformBrandTitle();
                $logoFull = brandLogoIsFull($logoUrl, $platformId);
                $tagline = $logoFull ? '' : resolveBrandTagline($platformId);

                return compact('logoUrl', 'logoAlt', 'logoFull', 'tagline');
            }

            if ($context === 'auth-login' && !$tenantActive) {
                $logoUrl = brandLogoUrlWithFallback(getBrandLogoUrl($brandUserId));
                $logoAlt = $brandTitle ?? $appName ?? resolveBrandTitle($tenant, $brandUserId);
                $logoFull = brandLogoIsFull($logoUrl, $brandUserId);
                $tagline = $logoFull ? '' : resolveBrandTagline($brandUserId);

                return compact('logoUrl', 'logoAlt', 'logoFull', 'tagline');
            }

            if ($context === 'auth-login') {
                $logoUrl = brandLogoUrlWithFallback(resolveAuthLoginLogoUrl($tenant, $brandUserId));
                $logoAlt = $brandTitle ?? $appName ?? resolveBrandTitle($tenant, $brandUserId);
                $logoFull = brandLogoIsFull($logoUrl, $brandUserId);
                $tagline = $logoFull ? '' : resolveBrandTagline($brandUserId);

                return compact('logoUrl', 'logoAlt', 'logoFull', 'tagline');
            }

            $logoUrl = brandLogoUrlWithFallback(resolveBrandLogoUrl($tenant, $brandUserId));
            $logoAlt = $brandTitle ?? $appName ?? resolveBrandTitle($tenant, $brandUserId);

            return [
                'logoUrl' => $logoUrl,
                'logoAlt' => $logoAlt,
                'logoFull' => brandLogoIsFull($logoUrl, $brandUserId),
                'tagline' => '',
            ];
        } catch (\Throwable $e) {
            log_message('error', 'safeAuthBrandLogo: ' . $e->getMessage());
        }

        return [
            'logoUrl' => $fallbackUrl,
            'logoAlt' => $fallbackAlt,
            'logoFull' => true,
            'tagline' => '',
        ];
    }
}

if (!function_exists('safeAuthGateBranding')) {
    /**
     * Gate page branding with DB-safe defaults for auth screens.
     */
    function safeAuthGateBranding($tenant = null, int $brandUserId = 2, bool $isTenantPortal = false): array
    {
        $branding = [
            'appName' => 'ISP Pay BD',
            'appSlogan' => 'A Complete Billing ISP Management Software.',
            'logoUrl' => getBrandFaviconUrl(),
            'brandTitle' => 'ISP Pay BD',
            'primaryColor' => null,
        ];

        try {
            if ($isTenantPortal && $tenant && !empty($tenant->name)) {
                $branding['brandTitle'] = (string) $tenant->name;
            } else {
                $branding['brandTitle'] = resolveBrandTitle($tenant, $brandUserId);
            }

            $branding['appName'] = (string) getSetting('app_name', $branding['appName'], $brandUserId);
            $branding['appSlogan'] = (string) getSetting('app_slogan', $branding['appSlogan'], $brandUserId);
            $branding['logoUrl'] = brandLogoUrlWithFallback(resolveBrandLogoUrl($tenant, $brandUserId));

            if ($tenant && !empty($tenant->primary_color)) {
                $branding['primaryColor'] = $tenant->primary_color;
            }
        } catch (\Throwable $e) {
            log_message('error', 'safeAuthGateBranding: ' . $e->getMessage());
        }

        return $branding;
    }
}

if (!function_exists('isPlatformDefaultLogo')) {
    function isPlatformDefaultLogo(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return (bool) preg_match('#/assets/img/icon/logo\.png$#i', $path);
    }
}

if (!function_exists('getBrandLogoUrl')) {
    /**
     * Resolved brand logo for nav/auth surfaces: app_logo → app_icon → default logo.png.
     */
    function getBrandLogoUrl(?int $brandUserId = null): string
    {
        $userId = $brandUserId ?? (function_exists('tenantBrandingUserId') ? (int) tenantBrandingUserId() : 2);

        $logo = getSetting('app_logo', '', $userId);
        if ($logo !== '') {
            return brandAssetUrl('img/logo/' . ltrim($logo, '/'));
        }

        $icon = getSetting('app_icon', '', $userId);
        if ($icon !== '') {
            return brandAssetUrl('img/logo/' . ltrim($icon, '/'));
        }

        return getBrandFaviconUrl();
    }
}

if (!function_exists('resolveBrandLogoUrl')) {
    /**
     * Tenant-aware logo URL for public surfaces (landing, auth, forgot).
     */
    function resolveBrandLogoUrl($tenant = null, ?int $brandUserId = null): string
    {
        $tenant = $tenant ?? (function_exists('currentTenant') ? currentTenant() : null);

        if (function_exists('isTenantRequest') && isTenantRequest() && function_exists('tenantLogoUrl')) {
            return tenantLogoUrl($tenant);
        }

        return getBrandLogoUrl($brandUserId);
    }
}

if (!function_exists('brandLogoIsFull')) {
    /**
     * True when the asset is a horizontal/full wordmark (hide duplicate text labels).
     */
    function brandLogoIsFull(?string $url = null, ?int $brandUserId = null): bool
    {
        $url = $url ?? resolveBrandLogoUrl(null, $brandUserId);

        if (isPlatformDefaultLogo($url)) {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        if (preg_match('#/assets/img/tenants/#', $path)) {
            return true;
        }

        $userId = $brandUserId ?? (function_exists('tenantBrandingUserId') ? (int) tenantBrandingUserId() : 2);
        $appLogo = getSetting('app_logo', '', $userId);
        if ($appLogo !== '' && str_contains($url, ltrim($appLogo, '/'))) {
            return true;
        }

        return false;
    }
}

if (!function_exists('resolveBrandTitle')) {
    /**
     * Landing + public surfaces call this first (e.g. dashboard/home.php:7) —
     * mirrors resolvePublicBrandLogoUrl()'s defensive try/catch so a settings
     * lookup failure degrades to a safe default instead of 500ing the page.
     */
    function resolveBrandTitle($tenant = null, ?int $brandUserId = null): string
    {
        try {
            $tenant = $tenant ?? (function_exists('currentTenant') ? currentTenant() : null);

            if ($tenant && !empty($tenant->name) && function_exists('isTenantRequest') && isTenantRequest()) {
                return (string) $tenant->name;
            }

            $userId = $brandUserId ?? (function_exists('tenantBrandingUserId') ? (int) tenantBrandingUserId() : 2);

            return (string) getSetting('app_name', 'ISP Pay BD', $userId);
        } catch (\Throwable $e) {
            return 'ISP Pay BD';
        }
    }
}

if (!function_exists('resolveBrandTagline')) {
    function resolveBrandTagline(?int $brandUserId = null): string
    {
        $userId = $brandUserId ?? (function_exists('tenantBrandingUserId') ? (int) tenantBrandingUserId() : 2);

        return (string) getSetting('app_slogan', 'ISP Management', $userId);
    }
}

if (!function_exists('platformBrandingUserId')) {
    function platformBrandingUserId(): int
    {
        return 2;
    }
}

if (!function_exists('getPlatformBrandLogoUrl')) {
    /**
     * Platform application logo from software settings (super-admin).
     */
    function getPlatformBrandLogoUrl(): string
    {
        return brandLogoUrlWithFallback(getBrandLogoUrl(platformBrandingUserId()));
    }
}

if (!function_exists('resolveAuthLoginLogoUrl')) {
    /**
     * Login logo: tenant branding on tenant portals, else software settings logo.
     */
    function resolveAuthLoginLogoUrl($tenant = null, ?int $brandUserId = null): string
    {
        $tenant = $tenant ?? (function_exists('currentTenant') ? currentTenant() : null);

        if (function_exists('isTenantRequest') && isTenantRequest() && function_exists('tenantLogoUrl')) {
            return tenantLogoUrl($tenant);
        }

        $userId = $brandUserId ?? (function_exists('tenantBrandingUserId') ? (int) tenantBrandingUserId() : platformBrandingUserId());

        return getBrandLogoUrl($userId);
    }
}

if (!function_exists('resolvePublicBrandLogoUrl')) {
    /**
     * Landing + public surfaces: tenant logo or software-settings application logo.
     */
    function resolvePublicBrandLogoUrl($tenant = null, ?int $brandUserId = null): string
    {
        try {
            return brandLogoUrlWithFallback(resolveBrandLogoUrl($tenant, $brandUserId));
        } catch (\Throwable $e) {
            return getBrandFaviconUrl();
        }
    }
}

if (!function_exists('getPlatformBrandTitle')) {
    function getPlatformBrandTitle(): string
    {
        return (string) getSetting('app_name', 'ISP Pay BD', platformBrandingUserId());
    }
}

if (!function_exists('getBrandIconUrl')) {
    /**
     * Application icon for favicon tags: app_icon → app_logo → default logo.png.
     */
    function getBrandIconUrl(?int $brandUserId = null): string
    {
        $userId = $brandUserId ?? (function_exists('tenantBrandingUserId') ? (int) tenantBrandingUserId() : platformBrandingUserId());

        $icon = getSetting('app_icon', '', $userId);
        if ($icon !== '') {
            return brandAssetUrl('img/logo/' . ltrim($icon, '/'));
        }

        return brandLogoUrlWithFallback(getBrandLogoUrl($userId));
    }
}

if (!function_exists('renderBrandFaviconTags')) {
    function renderBrandFaviconTags(): string
    {
        try {
            $url = esc(brandLogoUrlWithFallback(getBrandIconUrl()), 'attr');
        } catch (\Throwable $e) {
            $url = esc(getBrandFaviconUrl(), 'attr');
        }

        return '<link rel="icon" type="image/png" sizes="512x512" href="' . $url . '">' . PHP_EOL
            . '    <link rel="apple-touch-icon" sizes="512x512" href="' . $url . '">';
    }
}

/**
 * Get setting
 */

if (!function_exists('cron_log')) {
    function cron_log(string $message, string $level = 'info'): void
    {
        $dir = WRITEPATH . 'cron';

        // Create directory if not exists
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $dir . '/cron-' . date('Y-m-d') . '.log';
        $time = date('Y-m-d H:i:s');

        $log = "[{$time}] [{$level}] {$message}" . PHP_EOL;

        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('getSettingPrefixForUser')) {
    function getSettingPrefixForUser($userId)
    {
        static $prefixCache = [];
        if (isset($prefixCache[$userId])) {
            return $prefixCache[$userId];
        }

        $prefix = 'BaseController2'; // Default fallback

        // Safely check session to avoid crashes in non-web environments or early calls
        $currentLoggedInId = null;
        try {
            $session = \Config\Services::session();
            if ($session) {
                $currentLoggedInId = $session->get('user_id');
            }
        } catch (\Exception $e) {
            // Silently ignore session errors in non-web/CLI contexts
        }

        // If no userId is explicitly passed, use the one from session
        if (empty($userId)) {
            $userId = $currentLoggedInId;
        }

        $currentUser = $currentLoggedInId ? getUserById($currentLoggedInId) : null;

        if (!empty($userId)) {
            $user = getUserById($userId);
            if ($user) {
                $role = $user->role ?? '';
                $createdBy = $user->created_by ?? '';
                $adminId = $user->admin_id ?? 0;
                $subscriptionStatus = $user->subscription_status ?? 'active';

                // Resolve the top-level sAdmin for this user
                $parentSAdmin = null;
                if ($role === 'admin') {
                    $parentSAdmin = $user;
                } else {
                    $curr = $user;
                    while ($curr && $curr->role !== 'admin' && !empty($curr->admin_id)) {
                        $curr = getUserById($curr->admin_id);
                        if ($curr && $curr->role === 'admin') {
                            $parentSAdmin = $curr;
                            break;
                        }
                    }
                    if (!$parentSAdmin) {
                        $parentSAdmin = getUserById(2);
                    }
                }

                $isParentSAdminExpired = ($parentSAdmin && ($parentSAdmin->subscription_status ?? 'active') !== 'active');

                if ($role === 'admin') {
                    if ($currentUser && $currentUser->role === 'super_admin') {
                        $prefix = 'BaseController2';
                    } else {
                        $prefix = 'BaseController' . $user->id;
                    }
                } elseif ($role === 'super_admin') {
                    $prefix = 'BaseController' . ($parentSAdmin->id ?? 2);
                } elseif ($role === 'resellerAdmin' || $createdBy === 'resellerAdmin') {
                    if ($isParentSAdminExpired) {
                        $prefix = 'SKIP_SMS';
                    } else {
                        $reseller = ($role === 'resellerAdmin') ? $user : getUserById($adminId);

                        $hasSmsPermission = function ($res) {
                            if (!$res || !function_exists('userHasPermission'))
                                return false;
                            return userHasPermission('sms_message', 'read', $res->role, $res->id, $res->admin_id);
                        };

                        if ($reseller && $hasSmsPermission($reseller)) {
                            $prefix = 'BaseController' . ($parentSAdmin->id ?? 2);
                        }
                    }
                } else {
                    $prefix = 'BaseController' . ($parentSAdmin->id ?? 2);
                }
            }
        } else {
            $prefix = 'BaseController2';
        }

        $prefixCache[$userId] = $prefix;
        return $prefix;
    }
}

if (!function_exists('getSetting')) {
    function getSetting($key, $defaultValue = '', $id = null)
    {
        helper('setting');
        $userId = $id ?? session()->get('user_id');

        // Per-request memoization: getSetting is called ~125x per page render and
        // is deterministic within a request for a given (key, default, resolved
        // user, cli-context). Settings rarely change mid-request; a stale read is
        // cosmetic and self-heals next request. (C8)
        //
        // BUG-14: `static` persists across FPM worker requests. Bust the cache
        // when REQUEST_TIME_FLOAT changes (i.e., a new HTTP request arrived) so
        // admin setting changes are visible without restarting workers.
        static $__settingCache  = [];
        static $__requestStamp  = null;
        $__currentStamp = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
        if ($__requestStamp !== $__currentStamp) {
            $__settingCache = [];
            $__requestStamp = $__currentStamp;
        }
        $__dv = is_scalar($defaultValue) ? (string) $defaultValue : json_encode($defaultValue);
        $__sk = $key . '|' . $__dv . '|' . (string) $userId . '|' . (is_cli() ? '1' : '0');
        if (array_key_exists($__sk, $__settingCache)) {
            return $__settingCache[$__sk];
        }

        $__compute = function () use ($key, $defaultValue, $id, $userId) {



        $prefix = getSettingPrefixForUser($userId);

        // Safely determine the user's role and status for isolation logic
        $userContext = !empty($userId) ? getUserById($userId) : null;
        $userRole = $userContext->role ?? '';
        $isExpiredSAdmin = ($userRole === 'admin' && ($userContext->subscription_status ?? 'active') !== 'active');

        $brandingKeys = ['app_logo', 'app_icon', 'app_name', 'app_slogan', 'app_title', 'company_cell', 'support_mobile', 'company_phone', 'webmaster_email', 'company_address'];
        $isBrandingKey = in_array($key, $brandingKeys);

        // Gateway keys should NEVER fall back across admins
        $gatewayKeys = [
            'default_sms_gateway',
            'bulksmsbd_api_key',
            'bulksmsbd_sender_id',
            'greenwebsms_token',
            'smsq_api_key',
            'smsq_client_id',
            'smsq_sender_id',
            'telnet_api_key',
            'telnet_sender_id',
            'bulksmsdhaka_api_key',
            'awajdigital_api_key',
            'enable_bkashpg',
            'bkashpg_app_key',
            'bkashpg_app_secret',
            'bkashpg_username',
            'bkashpg_password',
            'enable_nagadpg',
            'nagadpg_merchant_account',
            'nagadpg_merchant_id',
            'enable_sslcommerz',
            'sslcommerz_store_id',
            'sslcommerz_store_passwd',
            'enable_eps',
            'eps_merchant_id',
            'eps_store_id',
            'eps_username',
            'eps_password',
            'eps_hash_key',
            'enable_shurjopay',
            'shurjopay_username',
            'shurjopay_password',
            'shurjopay_prefix',
            'enable_paystation',
            'paystation_merchant_id',
            'paystation_password'
        ];
        $isGatewayKey = in_array($key, $gatewayKeys);

        // Only use Super Admin gateways for expired sAdmins during System Cron Jobs (Notifications)
        // This ensures they receive expiration alerts but cannot use your credits for their own business.
        $isSystemNotification = (is_cli() && (strpos($key, 'sms_') !== false || strpos($key, 'email_') !== false || strpos($key, 'smtp_') !== false || $key === 'default_sms_gateway' || $key === 'app_name'));

        if ($isExpiredSAdmin && $isSystemNotification) {
            $prefix = 'BaseController2';
        }

        // Handle SKIP_SMS special case for branding
        if ($prefix === 'SKIP_SMS' && $isBrandingKey) {
            $prefix = 'BaseController2';
        }

        // For branding keys, ensure we check reseller context if applicable
        if ($isBrandingKey && !empty($userId)) {
            $user = getUserById($userId);
            if ($user) {
                $role = $user->role ?? '';
                $createdBy = $user->created_by ?? '';
                if ($role === 'resellerAdmin' || $createdBy === 'resellerAdmin') {
                    $resId = ($role === 'resellerAdmin') ? $user->id : ($user->admin_id ?? 2);
                    $prefix = 'BaseController' . $resId;
                }
            }
        }

        try {
            // 1. Try Primary Prefix (Helper then DB)
            $result = setting()->get($prefix . '.' . $key);
            if (empty($result)) {
                $db = \Config\Database::connect();
                $row = $db->table('settings')->where('class', $prefix)->where('key', $key)->get()->getRow();
                if ($row && !empty($row->value)) {
                    $result = $row->value;
                }
            }

            // 1.5 Sanity check for images (Primary)
            if ($isBrandingKey && ($key === 'app_logo' || $key === 'app_icon') && !empty($result)) {
                $path = FCPATH . 'assets/img/logo/' . $result;
                if (!file_exists($path)) {
                    $result = null;
                }
            }

            // 2. Fallback for Branding Keys
            if ($isBrandingKey && empty($result)) {
                // Determine fallbacks based on user role to ensure isolation
                // sAdmins should NOT fall back to BaseController2 branding by default
                $isOtherSAdmin = ($userRole === 'admin' && $prefix !== 'BaseController2');

                if ($isOtherSAdmin) {
                    $fallbacks = ['BaseController']; // Skip BaseController2 for active sAdmins
                } else {
                    $fallbacks = ($prefix === 'BaseController2') ? ['BaseController'] : ['BaseController2', 'BaseController'];
                }

                foreach ($fallbacks as $fb) {
                    $result = setting()->get($fb . '.' . $key);
                    if (empty($result)) {
                        $db = \Config\Database::connect();
                        $row = $db->table('settings')->where('class', $fb)->where('key', $key)->get()->getRow();
                        if ($row && !empty($row->value)) {
                            $result = $row->value;
                        }
                    }

                    // Sanity check for images (Fallback)
                    if (($key === 'app_logo' || $key === 'app_icon') && !empty($result)) {
                        $path = FCPATH . 'assets/img/logo/' . $result;
                        if (!file_exists($path)) {
                            $result = null;
                        }
                    }

                    if (!empty($result))
                        break;
                }
            }

            // // 3. Ultimate Fallback for Branding if still empty
            // if ($isBrandingKey && empty($result)) {
            //     if ($key === 'app_logo') $result = '1777877481_18eaaea0afac1e79eb52.png'; // Known working logo
            //     if ($key === 'app_icon') $result = '1763481019_3c872c5ae03665e6e721.png'; // Known working icon
            //     if ($key === 'app_name') $result = 'ISP PAY BD';
            //     if ($key === 'app_slogan') $result = 'Best ISP Billing Solution';
            //     if ($key === 'app_title') $result = 'ISP PAY BD';
            // }

            if (!empty($result)) {
                return (string) $result;
            }
        } catch (\Throwable $e) {
            // Fail safe to the default on ANY error (DB down, missing table, etc.)
            // so a settings lookup can never take down a page.
            log_message('error', "getSetting error [{$key}] for prefix [{$prefix}] user [{$userId}]: " . $e->getMessage());
        }

            return (string) $defaultValue;
        };

        // L2 cross-request cache (Phase 2 / C4). Sensitive keys (gateway/payment/
        // SMS/SMTP credentials, logo files) ALWAYS read fresh so a rotation takes
        // effect immediately. Everything else is cached, version-busted by
        // setSetting(), with a 60s TTL safety net. Every cache call is wrapped:
        // on any fault we fall through to a fresh read, so the worst case is "no
        // caching", never a wrong/stale sensitive value.
        if (isSensitiveSettingKey($key)) {
            return $__settingCache[$__sk] = $__compute();
        }

        $__l2 = 'set2_' . settingsCacheVersion() . '_' . md5($__sk);
        try {
            $__hit = cache($__l2);
            if ($__hit !== null) {
                return $__settingCache[$__sk] = $__hit;
            }
        } catch (\Throwable $e) {
            // fall through to a fresh read
        }

        $__val = $__compute();
        try {
            cache()->save($__l2, $__val, 60);
        } catch (\Throwable $e) {
            // caching is best-effort
        }

        return $__settingCache[$__sk] = $__val;
    }
}

if (!function_exists('secure_random_string')) {
    function secure_random_string(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $index = random_int(0, $charactersLength - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }
}

if (!function_exists('row_value')) {
    /**
     * Safe accessor for a model ->first()/->getRow() result that may be null.
     *
     * Returns $default when the row is null/false or the key is absent, so a
     * missing DB row never fatals on a property/array dereference. (PHP 8 throws
     * \Error on null->prop, which `catch (\Exception)` does NOT catch — the root
     * cause of the null-deref crashes swept on branch `optimize`.) Works for both
     * object rows ($returnType='object') and array rows.
     *
     * Usage: $name = row_value($userModel->find($id), 'name', 'Unknown');
     */
    function row_value($row, string $key, $default = null)
    {
        if ($row === null || $row === false) {
            return $default;
        }
        if (is_array($row)) {
            return $row[$key] ?? $default;
        }
        if (is_object($row)) {
            return $row->$key ?? $default;
        }
        return $default;
    }
}

/**
 * Whether a setting key must always be read fresh (never L2-cached): gateway /
 * payment / SMS / SMTP credentials, and the logo/icon keys (filesystem-dependent).
 */
if (!function_exists('isSensitiveSettingKey')) {
    function isSensitiveSettingKey($key): bool
    {
        if (in_array($key, ['app_logo', 'app_icon'], true)) {
            return true;
        }
        $k = strtolower((string) $key);
        foreach (['sms', 'smtp', 'email', 'bkash', 'nagad', 'ssl', 'gateway', 'api_key', 'secret', 'token', 'password', 'merchant', 'sender_id', 'client_id', 'store_id'] as $needle) {
            if (strpos($k, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

/** Monotonic version stamp mixed into L2 setting cache keys; bumped on any write. */
if (!function_exists('settingsCacheVersion')) {
    function settingsCacheVersion(): int
    {
        try {
            $v = cache('settings_cache_version');

            return $v === null ? 0 : (int) $v;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

/** Invalidate every L2-cached setting by advancing the version stamp. */
if (!function_exists('bumpSettingsCacheVersion')) {
    function bumpSettingsCacheVersion(): void
    {
        try {
            cache()->save('settings_cache_version', settingsCacheVersion() + 1, 2592000);
        } catch (\Throwable $e) {
            // best-effort; the 60s TTL on cached settings bounds any miss
        }
    }
}

/**
 * Set setting
 */
if (!function_exists('setSetting')) {
    function setSetting($keys, $value = null)
    {
        helper('setting');
        $userId = session()->get('user_id');
        if (empty($userId))
            return false;

        $prefix = getSettingPrefixForUser($userId);
        $brandingKeys = ['app_logo', 'app_icon', 'app_name', 'company_cell', 'support_mobile', 'company_phone', 'webmaster_email', 'company_address'];

        if (is_array($keys)) {
            foreach ($keys as $key => $value) {
                $targetPrefix = $prefix;
                // If it's a branding key, resellers should save to their own prefix
                if (in_array($key, $brandingKeys)) {
                    $targetPrefix = 'BaseController' . $userId;
                }
                setting()->set($targetPrefix . '.' . $key, $value);
            }
            bumpSettingsCacheVersion(); // invalidate L2 reads (Phase 2 / C4)
            return true;
        }

        $targetPrefix = $prefix;
        if (in_array($keys, $brandingKeys)) {
            $targetPrefix = 'BaseController' . $userId;
        }
        setting()->set($targetPrefix . '.' . $keys, $value);
        bumpSettingsCacheVersion(); // invalidate L2 reads (Phase 2 / C4)
        return true;
    }
}

/**
 * Get session
 */
if (!function_exists('getSession')) {
    function getSession($key)
    {

        return session()->get($key);
    }
}

// if( !function_exists('getSession') )
// {
//     function getSession($key) {

//         return session()->get($key);
//     }
// }
/**
 * Set session
 */
if (!function_exists('setSession')) {
    function setSession($key, $value = null)
    {

        return is_array($key) ? session()->set($key) : session()->set($key, $value);
    }
}

/**
 * Get input method
 */
if (!function_exists('getInputMethod')) {
    function getInputMethod($upper = false)
    {

        $request = \Config\Services::request();

        return $request->getMethod($upper);
    }
}


/**
 * Get user's post format input
 */
if (!function_exists('getPostInput')) {
    function getPostInput($key = null)
    {

        $request = \Config\Services::request();

        return !empty($key) ? $request->getPost($key) : $request->getPost();
    }
}

/**
 * Get user's get format input
 */
if (!function_exists('getGetInput')) {
    function getGetInput($key = null)
    {

        $request = \Config\Services::request();

        return !empty($key) ? $request->getGet($key) : $request->getGet();
    }
}

/**
 * Get user's post format input
 */
if (!function_exists('getFileInput')) {

    function getFileInput($key)
    {

        $request = \Config\Services::request();

        return $request->getFile($key);
    }
}

/**
 * Get user's raw format input
 */
if (!function_exists('getRawInput')) {
    function getRawInput($key = null)
    {

        $request = \Config\Services::request();

        return !empty($key) ? $request->getRawInputVar($key) : $request->getRawInputVar();
    }
}


/**
 * Request response
 */
if (!function_exists('requestResponse')) {
    function requestResponse($status, $response, $code = null)
    {

        $response = [
            'status' => $status,
            'response' => $response
        ];

        service('response')->setStatusCode($code != null ? $code : 200)->setJson($response)->send();
    }
}

/**
 * Send email
 */
if (!function_exists('sendMail')) {
    function sendMail($to, $subject, $content, $from = null)
    {

        //load email library
        $email = \Config\Services::email();

        //set email protocol
        $config['protocol'] = 'smtp';

        //set user agent
        $config['userAgent'] = getSetting('app_name');

        //set smtp host
        $config['SMTPHost'] = getSetting('smtp_host');

        //set smtp username
        $config['SMTPUser'] = getSetting('smtp_user');

        //set smtp password
        $config['SMTPPass'] = getSetting('smtp_password');

        //set smtp port
        $config['SMTPPort'] = getSetting('smtp_port');

        //set smtp crypto
        $config['SMTPCrypto'] = getSetting('smtp_crypto');

        //initialize the email class
        $email->initialize($config);

        $from = $from == null ? getSetting('webmaster_email') : $from;

        //set email from
        $email->setFrom($from, getSetting('app_name'));

        //set email to
        $email->setTo($to);

        //set email subject
        $email->setSubject($subject);

        //set email body
        $email->setMessage($content);

        //set mail type
        $email->setMailType('html');

        //set newline
        $email->setNewLine("\r\n");

        //send email
        return $email->send();
    }
}


/**
 * Show 404 page not found page
 */
if (!function_exists('show_404')) {
    function show_404()
    {

        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
}

/**
 * Whether the logged-in user may open the Redis cache inspector (ops UI).
 */
if (! function_exists('isRedisInspectorViewer')) {
    function isRedisInspectorViewer(): bool
    {
        $userId = getSession('user_id');
        if (empty($userId)) {
            return false;
        }

        $user = getUserById($userId);
        if ($user === null) {
            return false;
        }

        $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');

        return strcasecmp(trim((string) $email), 'info@isppaybd.com') === 0;
    }
}
