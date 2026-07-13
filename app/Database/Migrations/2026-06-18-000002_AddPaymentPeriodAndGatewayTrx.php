<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * AddPaymentPeriodAndGatewayTrx — Phase 4 (MT-7 / D4).
 *
 * Fixes the year-less billing shape and gives the gateway path a real
 * idempotency key. All steps are ADDITIVE and NON-DESTRUCTIVE, idempotent, and
 * guarded so this is safe to re-run on a populated DB:
 *
 *   1. payments.period DATE — the billing month as a real date (first of month),
 *      backfilled from created_at. Replaces filtering on the year-less `month`
 *      string (prior-year June no longer collides with this June).
 *   2. idx_pay_user_period_status — supports the per-user/period/status reads.
 *   3. payments.gateway_trx VARCHAR(60) NULL + UNIQUE — a dedicated successful-
 *      gateway transaction key (NOT a bare UNIQUE(method_trx)). NULLs are allowed
 *      to repeat in a MySQL UNIQUE index, so this is safe to add empty; the app
 *      populates it only on the successful-gateway path going forward.
 *   4. UNIQUE(user_id, period, status) — the period idempotency guard, but added
 *      ONLY IF the existing data has no duplicates. Real payment rows must never
 *      be auto-deleted, so if duplicate (user_id, period, status) groups exist
 *      the unique index is SKIPPED and logged; dedup is a separate, human-
 *      reviewed step (MT-7) after which this migration re-run adds the index.
 */
class AddPaymentPeriodAndGatewayTrx extends Migration
{
    public function up(): void
    {
        $db     = $this->db;
        $dbName = $db->getDatabase();

        if (! $db->tableExists('payments')) {
            return;
        }

        // 1. period DATE (nullable), after `month`.
        if (! $db->fieldExists('period', 'payments')) {
            $db->query('ALTER TABLE `payments` ADD `period` DATE NULL AFTER `month`');
        }

        // 2. Backfill period from created_at (first day of that month) where unset.
        $db->query(
            "UPDATE `payments` SET `period` = DATE_FORMAT(`created_at`, '%Y-%m-01') "
            . 'WHERE `period` IS NULL AND `created_at` IS NOT NULL'
        );

        // 3. Supporting index for per-user/period/status reads.
        if (! $this->indexExists($dbName, 'payments', 'idx_pay_user_period_status')) {
            $db->query('CREATE INDEX `idx_pay_user_period_status` ON `payments` (`user_id`, `period`, `status`)');
        }

        // 4. Dedicated gateway transaction key + UNIQUE (NULLs may repeat).
        if (! $db->fieldExists('gateway_trx', 'payments')) {
            $db->query('ALTER TABLE `payments` ADD `gateway_trx` VARCHAR(60) NULL AFTER `method_trx`');
        }
        if (! $this->indexExists($dbName, 'payments', 'uniq_pay_gateway_trx')) {
            $dupes = (int) ($db->query(
                'SELECT COUNT(*) AS c FROM ('
                . 'SELECT 1 FROM `payments` WHERE `gateway_trx` IS NOT NULL AND `gateway_trx` != \'\' '
                . 'GROUP BY `gateway_trx` HAVING COUNT(*) > 1'
                . ') d'
            )->getRow()->c ?? 0);

            if ($dupes === 0) {
                $db->query('CREATE UNIQUE INDEX `uniq_pay_gateway_trx` ON `payments` (`gateway_trx`)');
            } else {
                if (! $this->indexExists($dbName, 'payments', 'idx_pay_gateway_trx')) {
                    $db->query('CREATE INDEX `idx_pay_gateway_trx` ON `payments` (`gateway_trx`)');
                }
                log_message(
                    'warning',
                    "[migration] UNIQUE(gateway_trx) on payments SKIPPED: {$dupes} duplicate "
                    . 'non-empty value(s) exist. Added non-unique idx_pay_gateway_trx instead.'
                );
            }
        }

        // 5. Period idempotency guard — only if the data is already clean.
        if (! $this->indexExists($dbName, 'payments', 'uniq_pay_user_period_status')) {
            $dupes = (int) ($db->query(
                'SELECT COUNT(*) AS c FROM ('
                . 'SELECT 1 FROM `payments` WHERE `period` IS NOT NULL '
                . 'GROUP BY `user_id`, `period`, `status` HAVING COUNT(*) > 1'
                . ') d'
            )->getRow()->c ?? 0);

            if ($dupes === 0) {
                $db->query('CREATE UNIQUE INDEX `uniq_pay_user_period_status` ON `payments` (`user_id`, `period`, `status`)');
            } else {
                log_message(
                    'warning',
                    "[migration] UNIQUE(user_id, period, status) on payments SKIPPED: {$dupes} duplicate "
                    . 'group(s) exist. Dedup (MT-7) is a human-reviewed step; re-run this migration after dedup.'
                );
            }
        }
    }

    public function down(): void
    {
        $db     = $this->db;
        $dbName = $db->getDatabase();

        if (! $db->tableExists('payments')) {
            return;
        }

        foreach (['uniq_pay_user_period_status', 'uniq_pay_gateway_trx', 'idx_pay_gateway_trx', 'idx_pay_user_period_status'] as $idx) {
            if ($this->indexExists($dbName, 'payments', $idx)) {
                $db->query("DROP INDEX `{$idx}` ON `payments`");
            }
        }
        if ($db->fieldExists('gateway_trx', 'payments')) {
            $db->query('ALTER TABLE `payments` DROP COLUMN `gateway_trx`');
        }
        if ($db->fieldExists('period', 'payments')) {
            $db->query('ALTER TABLE `payments` DROP COLUMN `period`');
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
