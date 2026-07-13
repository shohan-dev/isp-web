<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * AddRemainingHotPathIndexes — completes the index plan from
 * docs/production-optimization/02-DATABASE-FIX-PLAN.md §3.2 that were not
 * included in AddHotPathIndexes (2026-06-17-000001).
 *
 * Safe / idempotent: skips missing tables, missing columns, and indexes that
 * already exist — safe to re-run on prod, staging, or a partial schema.
 */
class AddRemainingHotPathIndexes extends Migration
{
    /**
     * table => [ indexName => [columns...] ]
     */
    private array $indexes = [
        'users' => [
            // Cron expiry sweep filters all three together (CronJob.php:547-550).
            'idx_users_role_expire' => ['role', 'subscription_status', 'will_expire'],
        ],
        'payments' => [
            'idx_pay_created_at'  => ['created_at'],
            'idx_pay_method_trx'  => ['method_trx', 'status'],
        ],
        'reseller_packages' => [
            'idx_rpkg_user_id' => ['user_id'],
        ],
        'all_reseller_packages' => [
            'idx_arpkg_user_id' => ['user_id'],
        ],
        'tickets' => [
            'idx_tickets_status' => ['status'],
        ],
    ];

    public function up(): void
    {
        $db     = $this->db;
        $dbName = $db->getDatabase();

        foreach ($this->indexes as $table => $defs) {
            if (! $db->tableExists($table)) {
                continue;
            }

            foreach ($defs as $indexName => $cols) {
                if ($this->indexExists($dbName, $table, $indexName)) {
                    continue;
                }

                $missing = false;
                foreach ($cols as $c) {
                    if (! $db->fieldExists($c, $table)) {
                        $missing = true;
                        break;
                    }
                }
                if ($missing) {
                    continue;
                }

                $colList = implode(', ', array_map(static fn ($c) => '`' . $c . '`', $cols));
                $db->query("CREATE INDEX `{$indexName}` ON `{$table}` ({$colList})");
            }
        }
    }

    public function down(): void
    {
        $db     = $this->db;
        $dbName = $db->getDatabase();

        foreach ($this->indexes as $table => $defs) {
            if (! $db->tableExists($table)) {
                continue;
            }

            foreach (array_keys($defs) as $indexName) {
                if ($this->indexExists($dbName, $table, $indexName)) {
                    $db->query("DROP INDEX `{$indexName}` ON `{$table}`");
                }
            }
        }
    }

    private function indexExists(string $dbName, string $table, string $indexName): bool
    {
        $rows = $this->db->query(
            'SELECT 1 FROM information_schema.statistics '
            . 'WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$dbName, $table, $indexName]
        )->getResultArray();

        return ! empty($rows);
    }
}
