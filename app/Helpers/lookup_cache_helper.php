<?php

/**
 * Redis-backed cache for infrequently changing lookup lists (areas, packages)
 * used in customer filter dropdowns. Bust via bumpLookupCacheVersion() on CRUD.
 */

if (!function_exists('lookupCacheVersion')) {
    function lookupCacheVersion(int $ownerId): int
    {
        // Phase-C4: guard so a Redis blip doesn't crash the entire lookup helper.
        try {
            $ver = \Config\Services::cache()->get('lookup_ver_' . $ownerId);
            return $ver !== null ? (int) $ver : 0;
        } catch (\Throwable $e) {
            log_message('warning', 'lookupCacheVersion read failed: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('bumpLookupCacheVersion')) {
    function bumpLookupCacheVersion(int $ownerId): void
    {
        try {
            $cache = \Config\Services::cache();
            $ver   = lookupCacheVersion($ownerId) + 1;
            $cache->save('lookup_ver_' . $ownerId, $ver, 86400 * 7);
        } catch (\Throwable $e) {
            log_message('warning', 'bumpLookupCacheVersion write failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('resolveLookupOwnerId')) {
    /**
     * Admin owner id for area lists (matches Customer::index employee resolution).
     */
    function resolveLookupOwnerId(?int $userId = null, ?string $role = null): int
    {
        $userId = $userId ?? (int) session()->get('user_id');
        $role = $role ?? (string) session()->get('user_role');

        if ($role !== 'employee') {
            return $userId;
        }

        $userModel = model('App\Models\User');
        $details = $userModel->find($userId);
        if ($details === null) {
            return $userId;
        }

        $preCreatedBy = (string) ($details->created_by ?? '');
        if ($preCreatedBy === 'admin') {
            return (int) $details->admin_id;
        }

        $adminId = (int) $details->admin_id;
        $admin = $userModel->find($adminId);

        return (int) ($admin->admin_id ?? $adminId);
    }
}

if (!function_exists('resolvePackageFilterScope')) {
    /**
     * @return array{reseller: bool, ownerId: int}
     */
    function resolvePackageFilterScope(?int $userId = null, ?string $role = null): array
    {
        $userId = $userId ?? (int) session()->get('user_id');
        $role = $role ?? (string) session()->get('user_role');
        $userModel = model('App\Models\User');

        if ($role === 'admin') {
            return ['reseller' => false, 'ownerId' => $userId];
        }

        if ($role === 'resellerAdmin') {
            $details = $userModel->find($userId);

            return ['reseller' => true, 'ownerId' => (int) ($details->admin_id ?? $userId)];
        }

        if ($role === 'employee') {
            $details = $userModel->find($userId);
            if ($details !== null && ($details->created_by ?? '') === 'resellerAdmin') {
                return ['reseller' => true, 'ownerId' => (int) $details->admin_id];
            }

            return ['reseller' => false, 'ownerId' => resolveLookupOwnerId($userId, $role)];
        }

        return ['reseller' => false, 'ownerId' => $userId];
    }
}

if (!function_exists('getCachedAreaOptionsForFilter')) {
    /**
     * @return array<int, object>
     */
    function getCachedAreaOptionsForFilter(?int $userId = null, ?string $role = null): array
    {
        $userId = $userId ?? (int) session()->get('user_id');
        $role = $role ?? (string) session()->get('user_role');
        $ownerId = resolveLookupOwnerId($userId, $role);
        $ver = lookupCacheVersion($ownerId);
        $cacheKey = "lookup_areas_{$ownerId}_{$role}_{$userId}_v{$ver}";

        $cache = \Config\Services::cache();
        try {
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Area lookup cache read failed: ' . $e->getMessage());
        }

        $areaModel = model('App\Models\Area');
        $builder = $areaModel->where('user_id', $ownerId);

        if ($role === 'employee') {
            $user = model('App\Models\User')->find($userId);
            if ($user !== null && ! empty($user->area_id)) {
                $ids = array_filter(array_map('trim', explode(',', (string) $user->area_id)));
                if ($ids !== []) {
                    $builder->whereIn('id', $ids);
                }
            }
        }

        $rows = $builder->orderBy('area_name', 'ASC')->asObject()->findAll();
        try {
            $cache->save($cacheKey, $rows, 600);
        } catch (\Throwable $e) {
            log_message('warning', 'Area lookup cache write failed: ' . $e->getMessage());
        }

        return $rows;
    }
}

if (!function_exists('getCachedPackageOptionsForFilter')) {
    /**
     * @return array<int, object>
     */
    function getCachedPackageOptionsForFilter(?int $userId = null, ?string $role = null): array
    {
        $userId = $userId ?? (int) session()->get('user_id');
        $role = $role ?? (string) session()->get('user_role');
        $scope = resolvePackageFilterScope($userId, $role);
        $ownerId = $scope['ownerId'];
        $ver = lookupCacheVersion($ownerId);
        $kind = $scope['reseller'] ? 'reseller' : 'super_admin';
        $cacheKey = "lookup_packages_{$kind}_{$ownerId}_{$role}_{$userId}_v{$ver}";

        $cache = \Config\Services::cache();
        try {
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Package lookup cache read failed: ' . $e->getMessage());
        }

        if ($scope['reseller']) {
            $packageModel = model('App\Models\ResellerPackages');
            $rows = $packageModel
                ->where('user_id', $ownerId)
                ->orderBy('package_name', 'ASC')
                ->asObject()
                ->findAll();
        } else {
            $packageModel = model('App\Models\Package');
            $rows = $packageModel
                ->where('user_id', $ownerId)
                ->orderBy('package_name', 'ASC')
                ->asObject()
                ->findAll();
        }

        try {
            $cache->save($cacheKey, $rows, 600);
        } catch (\Throwable $e) {
            log_message('warning', 'Package lookup cache write failed: ' . $e->getMessage());
        }

        return $rows;
    }
}

if (!function_exists('lookupOwnerIdForCacheBust')) {
    /**
     * Owner id to invalidate when the current user mutates areas/packages.
     */
    function lookupOwnerIdForCacheBust(): int
    {
        $userId = (int) session()->get('user_id');
        $role = (string) session()->get('user_role');

        if ($role === 'employee' || $role === 'resellerAdmin') {
            $details = model('App\Models\User')->find($userId);

            return (int) ($details->admin_id ?? $userId);
        }

        return $userId;
    }
}
