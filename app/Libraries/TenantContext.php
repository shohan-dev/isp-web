<?php

namespace App\Libraries;

/**
 * Request-scoped tenant context (shared-app multi-tenancy).
 *
 * Platform hosts (apex) have no tenant. Subdomains resolve to a tenants row.
 */
class TenantContext
{
    public const MODE_PLATFORM = 'platform';
    public const MODE_TENANT   = 'tenant';
    public const MODE_UNKNOWN  = 'unknown';

    /** @var object|null */
    private static $tenant = null;

    /** @var string */
    private static $mode = self::MODE_PLATFORM;

    /** @var string */
    private static $slug = '';

    /** @var string */
    private static $host = '';

    /** @var bool */
    private static $resolved = false;

    public static function reset(): void
    {
        self::$tenant   = null;
        self::$mode     = self::MODE_PLATFORM;
        self::$slug     = '';
        self::$host     = '';
        self::$resolved = false;
    }

    public static function markResolved(string $mode, string $host = '', string $slug = '', $tenant = null): void
    {
        self::$mode     = $mode;
        self::$host     = $host;
        self::$slug     = $slug;
        self::$tenant   = $tenant;
        self::$resolved = true;
    }

    public static function isResolved(): bool
    {
        return self::$resolved;
    }

    public static function mode(): string
    {
        return self::$mode;
    }

    public static function isPlatform(): bool
    {
        return self::$mode === self::MODE_PLATFORM;
    }

    public static function isTenant(): bool
    {
        return self::$mode === self::MODE_TENANT && self::$tenant !== null;
    }

    public static function isUnknown(): bool
    {
        return self::$mode === self::MODE_UNKNOWN;
    }

    public static function isSuspended(): bool
    {
        if (!self::isTenant()) {
            return false;
        }

        return strtolower((string) (self::$tenant->status ?? '')) === 'suspended';
    }

    public static function isActive(): bool
    {
        if (!self::isTenant()) {
            return false;
        }

        return strtolower((string) (self::$tenant->status ?? '')) === 'active';
    }

    /** @return object|null */
    public static function tenant()
    {
        return self::$tenant;
    }

    public static function id(): ?int
    {
        if (!self::$tenant) {
            return null;
        }

        $id = self::$tenant->id ?? null;

        return $id !== null ? (int) $id : null;
    }

    public static function ownerUserId(): ?int
    {
        if (!self::$tenant) {
            return null;
        }

        $id = self::$tenant->owner_user_id ?? null;

        return $id !== null && (int) $id > 0 ? (int) $id : null;
    }

    public static function slug(): string
    {
        return self::$slug;
    }

    public static function host(): string
    {
        return self::$host;
    }

    /**
     * Branding owner for getSetting() on public pages.
     * Platform falls back to legacy super-admin settings user (id 2).
     */
    public static function brandingUserId(): int
    {
        if (self::isTenant() && self::ownerUserId()) {
            return self::ownerUserId();
        }

        return 2;
    }
}
