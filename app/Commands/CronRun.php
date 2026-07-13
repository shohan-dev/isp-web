<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\CronLock;
use App\Controllers\CronJob;

/**
 * cron:run — run a cron action under a singleton lock (Phase 3.6 / T4).
 *
 *   php spark cron:run daily-payments
 *   php spark cron:run sync-credentials --ttl 3600
 *
 * Wraps the existing CronJob actions (all of which are CLI-safe — they never
 * touch $this->request and pass explicit admin ids to getSetting) in a
 * App\Services\CronLock lease. If a previous run of the same action is still
 * going, this one logs and exits cleanly instead of running concurrently, so
 * billing/provisioning crons cannot overlap.
 *
 * Point the system crontab at these instead of curling the secret-gated
 * /cron/* GET routes (which stay as a transition net).
 */
class CronRun extends BaseCommand
{
    protected $group       = 'Cron';
    protected $name        = 'cron:run';
    protected $description = 'Run a cron action under a singleton lock (prevents overlapping runs / double-billing).';
    protected $usage       = 'cron:run <action> [--ttl seconds]';
    protected $arguments   = ['action' => 'Cron action slug (see the list below).'];
    protected $options     = ['--ttl' => 'Lock lease seconds (default 1800).'];

    /** action slug => CronJob method. */
    private array $actions = [
        'manage-user'          => 'index',
        'customer-data-usages' => 'customer_data_usages',
        'usage-flush'          => 'flushUsage',       // hourly: flush Redis buffer → DB
        'backup-and-notify'    => 'backupDatabaseAndSendEmail',
        'send-notification'    => 'sendNotification',
        'users-activity'       => 'usersactivity',
        'cleanup-logs'         => 'deleteWriteAbleLogs',
        'daily-payments'       => 'daily_payment_generate',
        'payg-billing'         => 'paygBilling',      // daily: PAYG tenant wallet charges
        'enable-users'         => 'updateUser_activity',
        'sync-credentials'     => 'sync_all_credentials',
        'purge-trash'          => 'purgeTrash',
    ];

    /**
     * Phase-F3: per-action default TTL overrides.
     * daily-payments can take hours at 20k — 1800s would expire mid-run and
     * allow a second run to start, causing double-billing.
     */
    private array $actionTtls = [
        'daily-payments'  => 14400, // 4 hours
        'sync-credentials'=> 7200,  // 2 hours
    ];

    public function run(array $params)
    {
        $action = $params[0] ?? CLI::getOption('action');

        if (! $action || ! isset($this->actions[$action])) {
            CLI::error('Unknown or missing cron action.');
            CLI::write('Available actions: ' . implode(', ', array_keys($this->actions)));
            return EXIT_ERROR;
        }

        $method      = $this->actions[$action];
        $defaultTtl  = $this->actionTtls[$action] ?? 1800;
        $ttl         = (int) (CLI::getOption('ttl') ?: $defaultTtl);
        $lockName = 'cron:' . $action;
        $owner    = gethostname() . ':' . getmypid();

        $lock = new CronLock();
        if (! $lock->acquire($lockName, $ttl, $owner)) {
            CLI::write("[{$lockName}] already running elsewhere — skipping this run.", 'yellow');
            return EXIT_SUCCESS; // a concurrent run holding the lock is expected, not an error
        }

        CLI::write("[{$lockName}] lock acquired by {$owner} — running {$method}() ...", 'green');
        $startedAt = microtime(true);

        try {
            (new CronJob())->{$method}();
            $secs = round(microtime(true) - $startedAt, 1);
            CLI::write("[{$lockName}] done in {$secs}s.", 'green');
            return EXIT_SUCCESS;
        } catch (\Throwable $e) {
            log_message('error', "cron:run {$action} failed: " . $e->getMessage());
            CLI::error("[{$lockName}] failed: " . $e->getMessage());
            return EXIT_ERROR;
        } finally {
            $lock->release($lockName, $owner);
        }
    }
}
