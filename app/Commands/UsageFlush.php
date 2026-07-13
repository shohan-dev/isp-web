<?php

namespace App\Commands;

use App\Services\UsageBufferService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * usage:flush — flush Redis-buffered MikroTik data to the database.
 *
 * Run this hourly so DB always holds the latest snapshot even if the
 * day-end flush cron is the primary persistence trigger:
 *
 *   php spark usage:flush
 *   php spark usage:flush --date=2026-06-23   # flush a specific date
 *   php spark usage:flush --days=2            # flush today + yesterday
 *
 * Schedule via crontab (runs in addition to customer-data-usages):
 *   0 * * * * php /path/to/isp-core/spark usage:flush >> /var/log/isp_usage_flush.log 2>&1
 *
 * Or as a singleton (preferred — prevents overlapping flushes):
 *   0 * * * * php /path/to/isp-core/spark cron:run usage-flush
 */
class UsageFlush extends BaseCommand
{
    protected $group       = 'Usage';
    protected $name        = 'usage:flush';
    protected $description = 'Flush Redis-buffered MikroTik usage data to the database.';
    protected $usage       = 'usage:flush [--date=YYYY-MM-DD] [--days=1]';
    protected $options     = [
        '--date' => 'Specific date to flush (default: today, format YYYY-MM-DD).',
        '--days' => 'Number of past days to flush, counting back from today (default: 1 = today only).',
    ];

    public function run(array $params)
    {
        $days    = max(1, (int) (CLI::getOption('days') ?? 1));
        $dateOpt = (string) (CLI::getOption('date') ?? '');

        // Build the list of dates to flush.
        $dates = [];
        if ($dateOpt !== '') {
            $ts = strtotime($dateOpt);
            if ($ts === false) {
                CLI::error("Invalid --date value: {$dateOpt}");
                return EXIT_ERROR;
            }
            $dates[] = date('Y-m-d', $ts);
        } else {
            for ($i = 0; $i < $days; $i++) {
                $dates[] = date('Y-m-d', strtotime("-{$i} days"));
            }
        }

        $buffer   = new UsageBufferService();
        $exitCode = EXIT_SUCCESS;

        foreach ($dates as $date) {
            CLI::write("Flushing usage buffer for {$date} ...", 'cyan');

            try {
                $result = $buffer->flushToDb($date);
            } catch (Throwable $e) {
                CLI::error("  flush failed for {$date}: " . $e->getMessage());
                log_message('error', "usage:flush failed for date={$date}: " . $e->getMessage());
                $exitCode = EXIT_ERROR;
                continue;
            }

            $color = $result['errors'] > 0 ? 'red' : ($result['flushed'] > 0 ? 'green' : 'dark_gray');
            CLI::write(sprintf(
                '  date=%s  flushed=%d  skipped=%d  errors=%d',
                $date,
                $result['flushed'],
                $result['skipped'],
                $result['errors']
            ), $color);

            log_message('info', sprintf(
                'usage:flush date=%s flushed=%d skipped=%d errors=%d',
                $date,
                $result['flushed'],
                $result['skipped'],
                $result['errors']
            ));
        }

        return $exitCode;
    }
}
