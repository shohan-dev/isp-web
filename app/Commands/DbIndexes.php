<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * db:indexes — idempotently ensure EVERY hot-path optimization index exists.
 *
 *   php spark db:indexes            # add any missing index, report what it did
 *   php spark db:indexes --dry-run  # report only, change nothing
 *
 * This is the single authoritative, re-runnable index installer for a NEW
 * server / fresh database (or to verify an existing one). It consolidates the
 * indexes from the three index migrations (AddHotPathIndexes,
 * AddRemainingHotPathIndexes, AddPaymentPeriodAndGatewayTrx) so you can ensure
 * the whole optimization index set in one command without relying on migration
 * history. See docs/production-optimization/02-DATABASE-FIX-PLAN.md §3.
 *
 * Safe by construction — for every index it:
 *   - skips a table that does not exist on this schema,
 *   - skips an index that already exists (checked via information_schema),
 *   - skips an index whose column(s) are not present (e.g. payments.period /
 *     gateway_trx before their migration has run — run `spark migrate` first to
 *     add those columns, then re-run this),
 *   - for UNIQUE indexes, refuses to add when duplicate values exist (it would
 *     fail) and reports the duplicate count so the data can be reconciled; for
 *     payments.gateway_trx it falls back to a NON-unique index so reads are
 *     still fast while the dup is resolved.
 * So it is safe to re-run any number of times, on any schema state.
 */
class DbIndexes extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:indexes';
    protected $description = 'Idempotently ensure all hot-path optimization indexes exist (check-then-add). Safe on a fresh or existing DB.';
    protected $usage       = 'db:indexes [--dry-run]';

    /** Regular (non-unique) indexes: table => [ indexName => [columns...] ]. */
    private array $indexes = [
        'users' => [
            'idx_users_admin_role'               => ['admin_id', 'role'],
            'idx_users_role_will_expire'         => ['role', 'will_expire'],
            'idx_users_role_subscription_status' => ['role', 'subscription_status'],
            'idx_users_role_expire'              => ['role', 'subscription_status', 'will_expire'],
            'idx_users_area_id'                  => ['area_id'],
            'idx_users_router_id'                => ['router_id'],
            'idx_users_package_id'               => ['package_id'],
        ],
        'payments' => [
            'idx_pay_user_month_status'  => ['user_id', 'month', 'status'],
            'idx_pay_admin_id'           => ['admin_id'],
            'idx_pay_paidby'             => ['paidby'],
            'idx_pay_created_at'         => ['created_at'],
            'idx_pay_method_trx'         => ['method_trx', 'status'],
            'idx_pay_user_period_status' => ['user_id', 'period', 'status'],
        ],
        'user_data_usage' => [
            'idx_udu_admin_date' => ['admin_id', 'date'],
            'idx_udu_date'       => ['date'],
        ],
        'user_router_data'      => ['idx_urd_user_id' => ['user_id']],
        'reseller_transaction'  => ['idx_rtxn_admin_id' => ['admin_id']],
        'reseller_funding'      => ['idx_rfund_admin_id' => ['admin_id']],
        'connection_details'    => ['idx_conn_user_id' => ['user_id']],
        'areas'                 => ['idx_areas_user_id' => ['user_id']],
        'packages'              => ['idx_packages_user_id' => ['user_id']],
        'reseller_packages'     => ['idx_rpkg_user_id' => ['user_id']],
        'all_reseller_packages' => ['idx_arpkg_user_id' => ['user_id']],
        'tickets'               => ['idx_tickets_status' => ['status']],
        'jobs'                  => ['idx_jobs_queue_status_avail' => ['queue', 'status', 'available_at']],
    ];

    /**
     * UNIQUE indexes: table => [ indexName => [columns...] ]. Added only when no
     * duplicate value exists; gateway_trx falls back to a non-unique index.
     */
    private array $uniqueIndexes = [
        'payments' => [
            'uniq_pay_gateway_trx'        => ['gateway_trx'],
            'uniq_pay_user_period_status' => ['user_id', 'period', 'status'],
        ],
    ];

    private int $added = 0;
    private int $present = 0;
    private int $skipped = 0;

    public function run(array $params)
    {
        $dry = (bool) CLI::getOption('dry-run');

        try {
            $db = \Config\Database::connect();
            $db->initialize();
            $dbName = $db->getDatabase();
        } catch (Throwable $e) {
            CLI::error('Could not connect to the database: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('db:indexes — ensuring hot-path indexes on `' . $dbName . '`'
            . ($dry ? ' (DRY RUN — no changes)' : ''), 'yellow');
        CLI::newLine();

        foreach ($this->indexes as $table => $defs) {
            if (! $db->tableExists($table)) {
                CLI::write('  · table ' . $table . ' absent — skipping ' . count($defs) . ' index(es)', 'dark_gray');
                $this->skipped += count($defs);
                continue;
            }
            foreach ($defs as $indexName => $cols) {
                $this->ensureIndex($db, $dbName, $table, $indexName, $cols, false, $dry);
            }
        }

        // UNIQUE indexes — only when the data has no duplicates.
        foreach ($this->uniqueIndexes as $table => $defs) {
            if (! $db->tableExists($table)) {
                continue;
            }
            foreach ($defs as $indexName => $cols) {
                $this->ensureUniqueIndex($db, $dbName, $table, $indexName, $cols, $dry);
            }
        }

        CLI::newLine();
        CLI::write(sprintf('Done: %d added, %d already present, %d skipped.', $this->added, $this->present, $this->skipped),
            $this->added > 0 ? 'green' : 'white');

        return EXIT_SUCCESS;
    }

    private function ensureIndex($db, string $dbName, string $table, string $indexName, array $cols, bool $unique, bool $dry): void
    {
        if ($this->indexExists($db, $dbName, $table, $indexName)) {
            $this->present++;
            return;
        }
        foreach ($cols as $c) {
            if (! $db->fieldExists($c, $table)) {
                CLI::write("  · {$table}.{$indexName} — column `{$c}` absent (run `spark migrate` to add it), skipping", 'dark_gray');
                $this->skipped++;
                return;
            }
        }

        $colList = implode(', ', array_map(static fn ($c) => '`' . $c . '`', $cols));
        $kw      = $unique ? 'UNIQUE INDEX' : 'INDEX';
        if ($dry) {
            CLI::write("  + would add {$kw} {$indexName} ON {$table} ({$colList})", 'cyan');
            $this->added++;
            return;
        }
        $db->query("CREATE {$kw} `{$indexName}` ON `{$table}` ({$colList})");
        CLI::write("  + added {$kw} {$indexName} ON {$table} ({$colList})", 'green');
        $this->added++;
    }

    private function ensureUniqueIndex($db, string $dbName, string $table, string $indexName, array $cols, bool $dry): void
    {
        if ($this->indexExists($db, $dbName, $table, $indexName)) {
            $this->present++;
            return;
        }
        foreach ($cols as $c) {
            if (! $db->fieldExists($c, $table)) {
                CLI::write("  · {$table}.{$indexName} — column `{$c}` absent, skipping", 'dark_gray');
                $this->skipped++;
                return;
            }
        }

        $dupes = $this->duplicateGroups($db, $table, $cols);
        if ($dupes > 0) {
            // gateway_trx: keep reads fast with a non-unique fallback; the UNIQUE
            // can be added later once the duplicates are reconciled.
            if ($cols === ['gateway_trx']) {
                CLI::write("  ! {$table}.{$indexName} — {$dupes} duplicate value(s); adding NON-unique idx_pay_gateway_trx instead", 'yellow');
                $this->ensureIndex($db, $dbName, $table, 'idx_pay_gateway_trx', $cols, false, $dry);
                return;
            }
            CLI::write("  ! {$table}.{$indexName} — {$dupes} duplicate group(s); UNIQUE NOT added (reconcile the data, then re-run)", 'yellow');
            $this->skipped++;
            return;
        }

        $this->ensureIndex($db, $dbName, $table, $indexName, $cols, true, $dry);
    }

    /** Count groups of $cols that have a non-null duplicate value. */
    private function duplicateGroups($db, string $table, array $cols): int
    {
        $colList  = implode(', ', array_map(static fn ($c) => '`' . $c . '`', $cols));
        $notNull  = implode(' AND ', array_map(static fn ($c) => "`{$c}` IS NOT NULL AND `{$c}` <> ''", $cols));
        $sql      = "SELECT COUNT(*) AS c FROM (SELECT 1 FROM `{$table}` WHERE {$notNull} "
            . "GROUP BY {$colList} HAVING COUNT(*) > 1) g";
        try {
            $row = $db->query($sql)->getRowArray();
            return (int) ($row['c'] ?? 0);
        } catch (Throwable $e) {
            // If the probe fails, be conservative and treat as "has dupes" (don't add UNIQUE).
            return 1;
        }
    }

    private function indexExists($db, string $dbName, string $table, string $indexName): bool
    {
        $rows = $db->query(
            'SELECT 1 FROM information_schema.statistics '
            . 'WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$dbName, $table, $indexName]
        )->getResultArray();

        return ! empty($rows);
    }
}
