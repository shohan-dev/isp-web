<?php

/*
 |--------------------------------------------------------------------------
 | ERROR DISPLAY
 |--------------------------------------------------------------------------
 | In development, we want to show as many errors as possible to help
 | make sure they don't make it to production. And save us hours of
 | painful debugging.
 */
error_reporting(-1);
ini_set('display_errors', '1');

/*
 |--------------------------------------------------------------------------
 | DEBUG BACKTRACES
 |--------------------------------------------------------------------------
 | If true, this constant will tell the error screens to display debug
 | backtraces along with the other error information. If you would
 | prefer to not see this, set this value to false.
 */
defined('SHOW_DEBUG_BACKTRACE') || define('SHOW_DEBUG_BACKTRACE', true);

/*
 |--------------------------------------------------------------------------
 | DEBUG MODE
 |--------------------------------------------------------------------------
 | Debug mode is an experimental flag that can allow changes throughout
 | the system. This will control whether Kint is loaded, and a few other
 | items. It can always be used within your own application too.
 */
defined('CI_DEBUG') || define('CI_DEBUG', true);

/*
 |--------------------------------------------------------------------------
 | PUBLIC-HOST GUARD
 |--------------------------------------------------------------------------
 | This file is loaded ONLY when ENVIRONMENT === 'development'. In this app,
 | development mode also means AuthController::validateLogin() skips
 | password_verify() entirely — any known email logs in with any password.
 | That is a local-testing shortcut on a laptop and a full account takeover on
 | a server, and the only thing separating the two is one line in .env.
 |
 | So: refuse to serve development mode to anything that looks like the public
 | internet. A wrong .env then fails loudly on the first request instead of
 | silently unlocking every account.
 |
 | Allowed:  CLI (spark, cron), loopback, and private/LAN addresses reached
 |           over a local-looking host — so phone-on-the-same-wifi testing works.
 | Refused:  a public client IP, OR a public domain in the Host header. The host
 |           check is the important half: on a real deployment behind nginx or
 |           Cloudflare, REMOTE_ADDR is often a private address, so an IP check
 |           alone would happily serve development mode to the whole internet.
 */
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    $clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    // NO_PRIV_RANGE|NO_RES_RANGE makes filter_var() return false for loopback,
    // private and reserved addresses — i.e. false here means "not public".
    $clientIsPublic = $clientIp !== ''
        && filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = (string) preg_replace('/:\d+$/', '', $host);   // strip :8080
    $host = trim($host, '[]');                             // [::1] -> ::1

    $hostIsLocal = $host === ''
        || $host === 'localhost'
        || str_ends_with($host, '.localhost')
        || str_ends_with($host, '.test')
        || str_ends_with($host, '.local')
        || (filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false);

    if ($clientIsPublic || ! $hostIsLocal) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');

        exit(
            "Refusing to serve.\n\n"
            . "CI_ENVIRONMENT=development is set on a host that is not local, and development\n"
            . "mode disables the login password check on this application.\n\n"
            . "Set CI_ENVIRONMENT=production in this host's .env file.\n"
        );
    }
}
