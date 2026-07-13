<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

//login check filter
use App\Filters\LoginCheck;

//auth check filter
use App\Filters\AuthCheck;

// multi-tenant host resolve
use App\Filters\TenantResolveFilter;
use App\Filters\MaintenanceFilter;
use App\Filters\RoleGuard;

//permission check
use App\Filters\PermissionCheck;
use App\Filters\SubscriptionGuard;

//cron shared-secret guard
use App\Filters\CronAuth;

//login brute-force throttle (flag-gated)
use App\Filters\ThrottleFilter;
// Phase O: structured per-request timing log
use App\Filters\RequestTimingFilter;
use Zapi\Core\Filters\ApiResponseEnvelopeFilter;
use Zapi\Core\Filters\JwtAuthFilter;
use Zapi\Core\Filters\RoleAuthFilter;
use Zapi\Core\Filters\PermissionAuthFilter;
use Zapi\Monitoring\Filters\TrafficEndFilter;
use Zapi\Monitoring\Filters\TrafficStartFilter;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     */
    public array $aliases = [
        'csrf' => CSRF::class,
        'toolbar' => DebugToolbar::class,
        'honeypot' => Honeypot::class,
        'invalidchars' => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,

        'logincheck' => LoginCheck::class,
        'authcheck' => AuthCheck::class,
        'tenantresolve' => TenantResolveFilter::class,
        'permissioncheck' => PermissionCheck::class,
        'subscriptionguard' => SubscriptionGuard::class,
        'cronauth' => CronAuth::class,
        'throttle' => ThrottleFilter::class,
        'zapijwt' => JwtAuthFilter::class,
        'zapirole' => RoleAuthFilter::class,
        'zapipermission' => PermissionAuthFilter::class,
        'zapienvelope' => ApiResponseEnvelopeFilter::class,
        'trafficstart' => TrafficStartFilter::class,
        'trafficend' => TrafficEndFilter::class,
        'timing' => RequestTimingFilter::class,
        'maintenance' => MaintenanceFilter::class,
        'role' => RoleGuard::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     */
    public array $globals = [
        'before' => [
            'maintenance',
            'tenantresolve',
            // 'honeypot',
            'csrf' => [
                'except' => [
                    'payment/gateway/*/query-payment/*',
                    'payment/gateway/*/callback',
                    // your login API route
                    'api/*',        // if you have more APIs
                    'ai-chat',
                ],
            ],
            // 'subscriptionguard' => [
            //     'except' => [
            //         '/',
            //         'auth/*',
            //         'api/*',
            //         'cron/*',
            //         'payment/*',
            //         'logout',
            //     ],
            // ],
            // 'invalidchars',
            'trafficstart' => [
                'except' => [
                    'api/docs',
                    'api/docs/*',
                    'assets/*',
                    'favicon.ico',
                    'healthz',
                ],
            ],
        ],
        'after' => [
            //'toolbar',
            // 'honeypot',
            // 'secureheaders',
            'trafficend' => [
                'except' => [
                    'api/docs',
                    'api/docs/*',
                    'assets/*',
                    'favicon.ico',
                    'healthz',
                ],
            ],
            // Phase O (O5): structured JSON timing log to 'timing' log channel.
            // Skip high-frequency noise paths to keep log volume sane.
            'timing' => [
                'except' => [
                    'assets/*',
                    'favicon.ico',
                    'healthz',
                    'metrics',
                ],
            ],
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don’t expect could bypass the filter.
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     */
    public array $filters = [
        // Phase 0.6b: the Zapi-served Swagger UI and the traffic-monitor *read*
        // endpoints ship with no auth filter — anyone could read the full API
        // schema and live traffic metrics. Gate them behind a logged-in panel
        // session. The 3 monitor *cron* endpoints (flush-queue / maintain-queue /
        // retention-cleanup) keep their own shared-secret guard and are
        // intentionally NOT listed here — cron callers have no session.
        'authcheck' => [
            'before' => [
                'api/docs',
                'api/docs/*',
                'api/monitor/traffic',
                'api/monitor/overview',
                'api/monitor/top-endpoints',
                'api/monitor/timeline',
                'api/monitor/recent',
                'api/monitor/snapshot',
            ],
        ],
        // Phase 2 (T6): login brute-force throttle — INERT until flag('login_throttle')
        // is set (see App\Filters\ThrottleFilter). Tune as throttle:attempts,seconds.
        'throttle' => [
            'before' => [
                'auth/login/validate',
            ],
        ],
    ];
}
