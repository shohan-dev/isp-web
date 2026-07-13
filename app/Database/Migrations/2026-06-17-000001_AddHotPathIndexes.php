<?php

namespace App\Database\Migrations;

use App\Traits\DbSchemaHelper;
use CodeIgniter\Database\Migration;

/**
 * AddHotPathIndexes
 *
 * Adds the composite/secondary indexes that the dashboards, DataTables and
 * cron sweeps actually filter and join on. Verified against the live queries
 * (see docs/production-optimization/02-DATABASE-FIX-PLAN.md §3).
 *
 * Safe / idempotent by design:
 *   - skips a table that does not exist
 *   - skips an index that already exists (checked via information_schema)
 *   - skips an index whose column(s) are not present on the table
 * so it can be re-run, and applied to environments at different schema states,
 * without error.
 *
 * NOTE: `payments.month` is a year-less month-name string; this index matches
 * the current query shape but is superseded by the `payments.period DATE`
 * column work in Phase 4 (02-DATABASE-FIX-PLAN.md §7.1).
 */
class AddHotPathIndexes extends Migration
{
    use DbSchemaHelper; // BUG-20: shared indexExists() — removes the duplicate
    /**
     * table => [ indexName => [columns...] ]
     */
    private array $indexes = [
        'users' => [
            'idx_users_admin_role'               => ['admin_id', 'role'],
            'idx_users_role_will_expire'         => ['role', 'will_expire'],
            'idx_users_role_subscription_status' => ['role', 'subscription_status'],
            'idx_users_area_id'                  => ['area_id'],
            'idx_users_router_id'                => ['router_id'],
            'idx_users_package_id'               => ['package_id'],
        ],
        'payments' => [
            'idx_pay_user_month_status' => ['user_id', 'month', 'status'],
            'idx_pay_admin_id'          => ['admin_id'],
            'idx_pay_paidby'            => ['paidby'],
        ],
        'user_data_usage' => [
            'idx_udu_admin_date' => ['admin_id', 'date'],
            'idx_udu_date'       => ['date'],
        ],
        'user_router_data' => [
            'idx_urd_user_id' => ['user_id'],
        ],
        'reseller_transaction' => [
            'idx_rtxn_admin_id' => ['admin_id'],
        ],
        'reseller_funding' => [
            'idx_rfund_admin_id' => ['admin_id'],
        ],
        'connection_details' => [
            'idx_conn_user_id' => ['user_id'],
        ],
        'areas' => [
            'idx_areas_user_id' => ['user_id'],
        ],
        'packages' => [
            'idx_packages_user_id' => ['user_id'],
        ],
    ];

    public function up(): void
    {
        $db = $this->db;

        foreach ($this->indexes as $table => $defs) {
            if (! $db->tableExists($table)) {
                continue;
            }

            foreach ($defs as $indexName => $cols) {
                if ($this->indexExists($table, $indexName)) { // BUG-20: trait call
                    continue;
                }

                // Skip if any target column is missing on this schema.
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
        $db = $this->db;

        foreach ($this->indexes as $table => $defs) {
            if (! $db->tableExists($table)) {
                continue;
            }

            foreach (array_keys($defs) as $indexName) {
                if ($this->indexExists($table, $indexName)) { // BUG-20: trait call
                    $db->query("DROP INDEX `{$indexName}` ON `{$table}`");
                }
            }
        }
    }
}
