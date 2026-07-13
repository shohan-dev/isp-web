<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * db:optimize — the ONE command that brings any database (local, staging, or
 * production) to the full Phase-4 optimized baseline, idempotently.
 *
 *   php spark db:optimize            # check, then apply everything missing
 *   php spark db:optimize --dry-run  # CHECK ONLY: report what's missing, change nothing
 *
 * Why this exists: getting a fresh / production DB fully optimized used to need
 * TWO steps — `spark migrate` (to add the payments.period / gateway_trx columns)
 * then `spark db:indexes` (to add the hot-path indexes). A DB managed OUTSIDE
 * CI4's migration history — a restored dump, a hand-managed production schema, a
 * box where migrations were never run — would never get the columns from
 * `db:indexes` alone (it just skips an absent column). This command does BOTH in
 * one pass: it CHECKS what is already present, then APPLIES only what is missing,
 * so "run this and the DB is optimized" is literally one command on any server.
 *
 * It is the single entry point for the deploy checklist's database step.
 *
 * Everything it does is ADDITIVE, NON-DESTRUCTIVE, idempotent and production-safe:
 *   1. Schema — `payments.period DATE` (+ backfill from `created_at`) and
 *      `payments.gateway_trx VARCHAR(60)`. Same column types/nullability as the
 *      AddPaymentPeriodAndGatewayTrx migration; every step is guarded so a re-run
 *      is a no-op, and the cosmetic `AFTER` anchor falls back to append-at-end if
 *      a divergent schema is missing it (never fatals on a 1054).
 *   2. Indexes — every hot-path + UNIQUE index, by delegating to `db:indexes`
 *      (which check-then-adds each one, refuses a UNIQUE while duplicates exist,
 *      and falls back to a non-unique gateway_trx index until the dup is fixed).
 *
 * It does NOT prune data — retention (`db:retention`) is a separate, explicitly
 * invoked, destructive command and is intentionally kept out of "make it optimal".
 *
 * Safe to run any number of times, on any schema state, on a populated DB.
 * See docs/production-optimization/02-DATABASE-FIX-PLAN.md.
 */
class DbOptimize extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:optimize';
    protected $description = 'Bring any DB to the full optimized baseline (schema + all indexes) in one idempotent, check-first pass.';
    protected $usage       = 'db:optimize [--dry-run]';

    private int $applied = 0;
    private int $present = 0;

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

        CLI::write('db:optimize — bringing `' . $dbName . '` to the full optimized baseline'
            . ($dry ? '  (DRY RUN — no changes)' : ''), 'yellow');
        CLI::newLine();

        try {
            // ---- 1. Schema (payments.period + gateway_trx) --------------------
            CLI::write('1) Schema (payments.period + gateway_trx)', 'white');
            $this->ensurePaymentsSchema($db, $dry);
            CLI::newLine();

            // ---- 2. Indexes ---------------------------------------------------
            CLI::write('2) Hot-path + UNIQUE indexes', 'white');
            // db:indexes reads the SAME global --dry-run option (CLI::$options is
            // process-static), so it honours dry-run without us threading it through.
            $this->call('db:indexes');
            CLI::newLine();
        } catch (Throwable $e) {
            // Every step is additive + idempotent, so a half-finished run is never
            // dangerous — just re-run after fixing the cause. Report it gracefully
            // instead of dumping a stack trace.
            CLI::newLine();
            CLI::error('Stopped on an unexpected error: ' . $e->getMessage());
            CLI::write('Every step is additive and idempotent — fix the cause and re-run `php spark db:optimize` to finish.', 'yellow');

            return EXIT_ERROR;
        }

        // ---- verdict ----------------------------------------------------------
        if ($dry) {
            CLI::write('Check complete. Re-run WITHOUT --dry-run to apply the changes above.', 'green');
        } else {
            $schema = $this->applied > 0
                ? "{$this->applied} schema change(s) applied, {$this->present} already present"
                : 'schema already optimal';
            CLI::write("Done — `{$dbName}` is at the full optimized baseline ({$schema}; index results above).", 'green');
        }

        return EXIT_SUCCESS;
    }

    /**
     * Ensure payments.period (+ backfill) and payments.gateway_trx exist.
     * Column additions only — the indexes/UNIQUEs ON these columns are handled by
     * the db:indexes pass that follows. Mirrors AddPaymentPeriodAndGatewayTrx::up().
     */
    private function ensurePaymentsSchema($db, bool $dry): void
    {
        if (! $db->tableExists('payments')) {
            CLI::write('  · table payments absent — skipping schema (run `spark migrate` for a fresh app schema first)', 'dark_gray');

            return;
        }

        // period DATE, after `month`.
        if ($db->fieldExists('period', 'payments')) {
            CLI::write('  · payments.period present', 'dark_gray');
            $this->present++;
        } elseif ($dry) {
            CLI::write('  + would add payments.period DATE (+ backfill from created_at)', 'cyan');
            $this->applied++;
        } else {
            // `AFTER month` is cosmetic; if a divergent schema lacks the anchor,
            // append at the end rather than fatal on a 1054 Unknown column.
            $after = $db->fieldExists('month', 'payments') ? ' AFTER `month`' : '';
            $db->query('ALTER TABLE `payments` ADD `period` DATE NULL' . $after);
            CLI::write('  + added payments.period DATE', 'green');
            $this->applied++;
        }

        // Backfill period from created_at wherever it is still NULL (idempotent —
        // only fills gaps; an already-backfilled row is untouched).
        if ($db->fieldExists('period', 'payments')) {
            $pending = (int) ($db->query(
                'SELECT COUNT(*) AS c FROM `payments` WHERE `period` IS NULL AND `created_at` IS NOT NULL'
            )->getRowArray()['c'] ?? 0);

            if ($pending > 0 && $dry) {
                CLI::write("  + would backfill payments.period on {$pending} row(s)", 'cyan');
                $this->applied++;
            } elseif ($pending > 0) {
                $db->query(
                    "UPDATE `payments` SET `period` = DATE_FORMAT(`created_at`, '%Y-%m-01') "
                    . 'WHERE `period` IS NULL AND `created_at` IS NOT NULL'
                );
                CLI::write("  + backfilled payments.period on {$pending} row(s)", 'green');
                $this->applied++;
            }
        }

        // gateway_trx VARCHAR(60), after `method_trx`.
        if ($db->fieldExists('gateway_trx', 'payments')) {
            CLI::write('  · payments.gateway_trx present', 'dark_gray');
            $this->present++;
        } elseif ($dry) {
            CLI::write('  + would add payments.gateway_trx VARCHAR(60)', 'cyan');
            $this->applied++;
        } else {
            $after = $db->fieldExists('method_trx', 'payments') ? ' AFTER `method_trx`' : '';
            $db->query('ALTER TABLE `payments` ADD `gateway_trx` VARCHAR(60) NULL' . $after);
            CLI::write('  + added payments.gateway_trx VARCHAR(60)', 'green');
            $this->applied++;
        }
    }
}
