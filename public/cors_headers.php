<?php

/**
 * CORS policy — same-origin lock (Phase 0.12 / S10).
 *
 * Pure decision function so the security control is unit-testable (see
 * tests/unit/CorsHeadersTest.php). index.php runs this before the framework
 * boots, so it cannot use CI4 helpers/config — keep it dependency-free.
 *
 * Returns the origin to allow, or null to emit NO CORS headers. We allow ONLY
 * the server's own scheme+host: a cross-origin site therefore gets no
 * Access-Control-Allow-Origin and the browser blocks it from reading API
 * responses. Same-origin browser requests don't need CORS headers anyway, and
 * native mobile clients send no Origin and ignore CORS — so this breaks neither.
 */
if (! function_exists('cors_allowed_origin')) {
    function cors_allowed_origin(array $server): ?string
    {
        $origin = $server['HTTP_ORIGIN'] ?? '';
        if ($origin === '') {
            return null; // non-browser / same-origin GET with no Origin — nothing to allow
        }

        $https  = (! empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off');
        $scheme = $https ? 'https' : 'http';
        $self   = $scheme . '://' . ($server['HTTP_HOST'] ?? '');

        // Origins compare case-insensitively on scheme+host.
        return strcasecmp($origin, $self) === 0 ? $origin : null;
    }
}
