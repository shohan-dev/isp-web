<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * db:retention — prune rows older than N days from the high-growth log tables,
 * in safe bounded batches (Phase 4 — DB retention; see
 * docs/production-optimization/02-DATABASE-FIX-PLAN.md §7).
 *
 *   php spark db:retention                 # prune > 90 days, 5000/batch
 *   php spark db:retention --days=180      # keep 180 days
 *   php spark db:retention --dry-run       # report counts, delete nothing
 *   php spark db:retention --batch=2000    # smaller batches (gentler on a busy DB)
 *
 * Safe by construction:
 *   - Only tables on the hard-coded ALLOWLIST (table => date-column) can be
 *     pruned — never an arbitrary table, never a non-date predicate.
 *   - Deletes in `LIMIT $batch` chunks so each statement holds a short lock
 *     (no single multi-hundred-thousand-row DELETE that stalls writers).
 *   - `--dry-run` only COUNTs; it changes nothing.
 *   - Skips an absent table / absent date column.
 *
 * `user_data_usage` is the fastest-growing table (daily per-customer rows); 90
 * days of history is plenty for the dashboard trend. Schedule off-peak (e.g.
 * via `spark cron:run` or crontab) once validated with `--dry-run`.
 */
class DbRetention extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:retention';
    protected $description = 'Prune rows older than N days from high-growth log tables, in safe bounded batches.';
    protected $usage       = 'db:retention [--days=90] [--batch=5000] [--dry-run]';

    /** ALLOWLIST — the only tables this command may prune: table => date column. */
    private array $targets = [
        'user_data_usage' => 'date',
    ];

    public function run(array $params)
    {
        $days   = max(1, (int) (CLI::getOption('days') ?? 90));
        $batch  = max(100, (int) (CLI::getOption('batch') ?? 5000));
        $dry    = (bool) CLI::getOption('dry-run');
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));

        try {
            $db = \Config\Database::connect();
            $db->initialize();
        } catch (Throwable $e) {
            CLI::error('Could not connect to the database: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write("db:retention — pruning rows older than {$days} days (before {$cutoff})"
            . ($dry ? ' [DRY RUN — no changes]' : '') . ", batch {$batch}", 'yellow');
        CLI::newLine();

        $grand = 0;
        foreach ($this->targets as $table => $col) {
            if (! $db->tableExists($table)) {
                CLI::write("  · {$table} absent — skip", 'dark_gray');
                continue;
            }
            if (! $db->fieldExists($col, $table)) {
                CLI::write("  · {$table}.{$col} absent — skip", 'dark_gray');
                continue;
            }

            $count = (int) ($db->query(
                "SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$col}` < ?",
                [$cutoff]
            )->getRowArray()['c'] ?? 0);

            if ($count === 0) {
                CLI::write("  · {$table}: nothing older than {$cutoff}", 'dark_gray');
                continue;
            }

            if ($dry) {
                CLI::write("  + would prune {$count} row(s) from {$table} (older than {$cutoff})", 'cyan');
                $grand += $count;
                continue;
            }

            $deleted = 0;
            do {
                $db->query("DELETE FROM `{$table}` WHERE `{$col}` < ? LIMIT {$batch}", [$cutoff]);
                $n = $db->affectedRows();
                $deleted += $n;
                if ($n > 0) {
                    CLI::write("    … {$table}: {$deleted}/{$count}", 'dark_gray');
                }
            } while ($n > 0);

            CLI::write("  + pruned {$deleted} row(s) from {$table}", 'green');
            $grand += $deleted;
        }

        CLI::newLine();
        CLI::write(
            $dry ? "Would prune {$grand} row(s) total." : "Pruned {$grand} row(s) total.",
            $grand > 0 ? 'green' : 'white'
        );

        return EXIT_SUCCESS;
    }
}
