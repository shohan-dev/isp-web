<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use Daycry\CronJob\Scheduler;

class CronJob extends \Daycry\CronJob\Config\CronJob
{
    /**
     * Set true if you want save logs
     */
    public bool $logPerformance = false;

    /*
    |--------------------------------------------------------------------------
    | Log Saving Method
    |--------------------------------------------------------------------------
    |
    | Set to specify the REST API requires to be logged in
    |
    | 'file'   Save in files
    | 'database'  Save in database
    |
    */
    public string $logSavingMethod = 'file';

    /**
     * Directory
     */
    public string $filePath = WRITEPATH . 'cronJob/';

    /**
     * File Name in folder jobs structure
     */
    public string $fileName = 'jobs';

    /**
     * --------------------------------------------------------------------------
     * Maximum performance logs
     * --------------------------------------------------------------------------
     *
     * The maximum number of logs that should be saved per Job.
     * Lower numbers reduced the amount of database required to
     * store the logs.
     *
     * If you write 0 it is unlimited
     */
    public int $maxLogsPerJob = 3;

    /*
    |--------------------------------------------------------------------------
    | Database Group
    |--------------------------------------------------------------------------
    |
    | Connect to a database group for logging, etc.
    |
    */
    public ?string $databaseGroup = null;

    /*
    |--------------------------------------------------------------------------
    | Cronjob Table Name
    |--------------------------------------------------------------------------
    |
    | The table name in your database that stores cronjobs
    |
    */
    public string $tableName = 'cronjob';

    /*
    |--------------------------------------------------------------------------
    | Cronjob Notification
    |--------------------------------------------------------------------------
    |
    | Notification of each task
    |
    */
    public bool $notification = false;
    public string $from = 'your@example.com';
    public string $fromName = 'CronJob';
    public string $to = 'your@example.com';
    public string $toName = 'User';

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Notification of each task
    |
    */
    public array $views = [
        'login'                       => '\Daycry\CronJob\Views\login',
        'dashboard'                   => '\Daycry\CronJob\Views\dashboard',
        'layout'                      => '\Daycry\CronJob\Views\layout',
        'logs'                        => '\Daycry\CronJob\Views\logs'
    ];

    /*
    |--------------------------------------------------------------------------
    | Dashboard login
    |--------------------------------------------------------------------------
    */
    public bool $enableDashboard = false;
    public string $username = 'super_admin';
    public string $password = 'super_admin';

    /*
    |--------------------------------------------------------------------------
    | Cronjobs
    |--------------------------------------------------------------------------
    |
    | Register any tasks within this method for the application.
    | Called by the TaskRunner.
    |
    | @param Scheduler $schedule
    */
    // public function init(Scheduler $schedule)
    // {
    //     log_message('debug', 'Cron job page "manage-user" was accessed.');


    //    // $schedule->url(base_url('cron/manage-user'))->everyMinute(5);

    //     // /**
    //     //  * Manage users
    //     //  */
    //     // $schedule->url(base_url('cron/usersactivity'))->everyMinute(4);

    //     /**
    //      * Send expiry notification
    //      */
    //     // $schedule->url(base_url('cron/send-notification'))->everyMinute(2);

    //     $schedule->url(base_url('cron/send-notification'))->cron('8 14 * * *');

    // }

    /**
     * Page-load-performance audit, Axis 4: `php spark db:indexes` /
     * `db:retention` (app/Commands/DbIndexes.php, DbRetention.php) existed but
     * had no active schedule entry, so a post-restore index drift on
     * user_data_usage (300k+ rows) could sit unnoticed indefinitely and
     * user_data_usage grows +1 row/customer/day forever with nothing pruning
     * it. Neither command is destructive to run repeatedly (both are
     * documented as idempotent/safe to re-run), so schedule them as a
     * defensive, self-healing sweep rather than relying on someone
     * remembering to run them by hand after a restore/import.
     *
     * `spark cron:run` (this package's own scheduler tick — see the `command`
     * type task above, run via the system crontab, e.g. `* * * * *`) is what
     * actually fires these; this method only registers what to run and when.
     */
    public function init(Scheduler $schedule)
    {
        // Low-traffic hour; idempotent even if it overlaps a request.
        $schedule->command('db:retention')->daily('3:00am')->named('db-retention-daily');

        // Weekly safety net for index drift (e.g. after a raw DB restore/import
        // that skips migrations) — `db:indexes` no-ops if nothing is missing.
        $schedule->command('db:indexes')->sundays('3:15am')->named('db-indexes-weekly');
    }
}


