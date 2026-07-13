<?php

/**
 * JWT access-token revocation (Phase 2 — closes the "no token revocation" defect).
 *
 * A stateless JWT is otherwise valid until it expires — there is no server-side
 * logout. revokeUserTokens() records a per-user "every token issued before NOW
 * is dead" timestamp in the cache layer (file now, Redis on cutover → shared
 * across nodes). Zapi\Core\Filters\JwtAuthFilter rejects any access token whose
 * `iat` predates that timestamp, so security events (password change/reset,
 * account disable) immediately invalidate outstanding access tokens.
 *
 * FAILS SAFE: cache errors are swallowed and an unset entry means "not revoked",
 * so this can never reject a legitimate token on its own.
 *
 * SCOPE: covers BOTH access and refresh tokens. The refresh endpoint
 * (AuthController::refreshToken) also rejects a refresh token whose `iat`
 * predates the revoke stamp, so a password change/reset kills every session —
 * which is why the TTL below must outlive the refresh-token lifetime.
 */

if (! function_exists('revokeUserTokens')) {
    /** Invalidate all tokens issued before now for $userId. */
    function revokeUserTokens($userId, ?int $ttl = null): bool
    {
        $userId = (string) $userId;
        if ($userId === '' || $userId === '0') {
            return false;
        }
        // The stamp must outlive the longest-lived token, or a revoked refresh
        // token could mint new access tokens after the stamp expires (refresh
        // TTL defaults to 30 days).
        $ttl = $ttl ?? max(604800, (int) env('zapi.jwtRefreshTtl', 2592000));
        try {
            return (bool) cache()->save('jwt_revoke_after_' . $userId, time(), $ttl);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('tokensRevokedAfter')) {
    /** Unix ts before which this user's access tokens are revoked, or null if none. */
    function tokensRevokedAfter($userId): ?int
    {
        $userId = (string) $userId;
        if ($userId === '') {
            return null;
        }
        try {
            $value = cache('jwt_revoke_after_' . $userId);
        } catch (\Throwable $e) {
            return null; // fail open
        }

        return $value === null ? null : (int) $value;
    }
}
