<?php

/**
 * Kill-switch flags (Phase 2 / Phase 6 §7) — operator toggles that take effect
 * WITHOUT a deploy, backed by the cache layer (file now; Redis once
 * `cache.handler` is switched in .env — flags then propagate across all nodes).
 *
 * flag() FAILS SAFE: on any cache/Redis error it returns the supplied default,
 * so a flag-store outage can never crash a request. Honor points pass the
 * default that means "full functionality", so an unset/broken store = normal
 * behavior:
 *
 *   if (flag('degrade_mode'))                 { ...shed load... }
 *   if (flag('live_router_widgets', true))    { ...do live router I/O... }
 *
 * Toggle from an admin action or `php spark` tinker:
 *   setFlag('degrade_mode', true);     // on until cleared
 *   clearFlag('degrade_mode');
 */

if (! function_exists('flag')) {
    function flag(string $name, bool $default = false): bool
    {
        try {
            $val = cache('flag_' . $name);
        } catch (\Throwable $e) {
            return $default; // flag store down -> fail safe to the caller's default
        }

        return $val === null ? $default : (bool) $val;
    }
}

if (! function_exists('setFlag')) {
    /** Persist a flag. ttl 0 keeps the framework default; pass seconds to auto-expire. */
    function setFlag(string $name, bool $value, int $ttl = 31536000): bool
    {
        try {
            return (bool) cache()->save('flag_' . $name, $value ? 1 : 0, $ttl);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('clearFlag')) {
    function clearFlag(string $name): void
    {
        try {
            cache()->delete('flag_' . $name);
        } catch (\Throwable $e) {
            // no-op: clearing a flag that isn't set (or a down store) is harmless
        }
    }
}

if (! function_exists('isMaintenanceMode')) {
    function isMaintenanceMode(): bool
    {
        return flag(
            'maintenance_mode',
            filter_var(env('MAINTENANCE_MODE', 'false'), FILTER_VALIDATE_BOOLEAN)
        );
    }
}

if (! function_exists('isTenantingEnabled')) {
    /**
     * Master switch for subdomain tenant portals. false (default) = every
     * request is treated as the platform app — no tenant resolution runs.
     */
    function isTenantingEnabled(): bool
    {
        return flag(
            'tenant_enabled',
            filter_var(env('TENANT_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)
        );
    }
}

if (! function_exists('maintenanceAllowIps')) {
    /**
     * @return list<string>
     */
    function maintenanceAllowIps(): array
    {
        $raw = (string) env('MAINTENANCE_ALLOW_IPS', '');
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
