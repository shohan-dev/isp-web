<?php

namespace App\Filters;

use App\Services\CronLock;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CronLockFilter — prevent overlapping HTTP /cron/* runs.
 *
 * `php spark cron:run` already uses CronLock. The legacy secret-gated GET
 * routes bypassed it; this filter closes that gap without retiring the URLs
 * (ops may still curl them during transition).
 */
class CronLockFilter implements FilterInterface
{
    /** URI segment after /cron/ → CronRun action slug (same names as CronRun). */
    private const URI_TO_LOCK = [
        'manage-user'                => 'manage-user',
        'customer_data_usages'       => 'customer-data-usages',
        'backupDatabaseAndSendEmail' => 'backup-and-notify',
        'send-notification'          => 'send-notification',
        'usersactivity'              => 'users-activity',
        'deleteWriteAbleLogs'        => 'cleanup-logs',
        'daily_payment_generate'     => 'daily-payments',
        'payg_billing'               => 'payg-billing',
        'updateUser_activity'        => 'enable-users',
        'sync-credentials'           => 'sync-credentials',
        'usage-flush'                => 'usage-flush',
        'purge-trash'                => 'purge-trash',
    ];

    private const ACTION_TTLS = [
        'daily-payments'    => 14400,
        'sync-credentials'  => 7200,
        'backup-and-notify' => 3600,
    ];

    /** @var array{name:string,owner:string}|null */
    private static ?array $held = null;

    public function before(RequestInterface $request, $arguments = null)
    {
        self::$held = null;

        if (is_cli()) {
            return;
        }

        $path = trim($request->getUri()->getPath(), '/');
        if (! preg_match('#(?:^|/)cron/([^/]+)#', $path, $m)) {
            return;
        }

        $segment = $m[1];
        $action  = self::URI_TO_LOCK[$segment] ?? null;
        if ($action === null) {
            return;
        }

        $ttl      = self::ACTION_TTLS[$action] ?? 1800;
        $lockName = 'cron:' . $action;
        $owner    = gethostname() . ':' . getmypid();

        $lock = new CronLock();
        if (! $lock->acquire($lockName, $ttl, $owner)) {
            log_message('warning', "[CronLockFilter] {$lockName} already held — rejecting HTTP overlap");
            return service('response')
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => 'skipped',
                    'message' => "Cron action '{$action}' already running",
                ]);
        }

        self::$held = ['name' => $lockName, 'owner' => $owner];
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (self::$held === null) {
            return;
        }

        (new CronLock())->release(self::$held['name'], self::$held['owner']);
        self::$held = null;
    }
}
