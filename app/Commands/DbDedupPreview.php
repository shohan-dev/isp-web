<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * db:dedup-preview — READ-ONLY. Show the duplicate
 * `payments(user_id, period, status)` groups that block the
 * `uniq_pay_user_period_status` UNIQUE index, with each group's rows, so a human
 * can decide which payment row to keep before deleting the rest.
 *
 *   php spark db:dedup-preview            # list every duplicate group + its rows
 *   php spark db:dedup-preview --limit=20 # cap the number of groups shown
 *
 * This command NEVER changes data — deciding which of several real payment rows
 * to keep is a money decision and must stay human. For each group it marks a
 * SUGGESTED keep (prefer a row with a non-empty gateway_trx, else the newest id)
 * purely as a hint; you write the actual DELETE. Once the duplicates are
 * resolved, `php spark db:optimize` adds the UNIQUE index automatically.
 *
 * See docs/production-optimization/02-DATABASE-FIX-PLAN.md §7 and OPERATOR-TODO.md.
 */
class DbDedupPreview extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:dedup-preview';
    protected $description = 'READ-ONLY: show duplicate payments(user_id,period,status) groups + rows so a human can dedup before the UNIQUE index.';
    protected $usage       = 'db:dedup-preview [--limit=N]';

    public function run(array $params)
    {
        $limit = max(1, (int) (CLI::getOption('limit') ?? 100));

        try {
            $db = \Config\Database::connect();
            $db->initialize();
        } catch (Throwable $e) {
            CLI::error('Could not connect to the database: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        if (! $db->tableExists('payments')) {
            CLI::error('payments table not found.');

            return EXIT_ERROR;
        }
        if (! $db->fieldExists('period', 'payments')) {
            CLI::error('payments.period column is absent — run `php spark db:optimize` first.');

            return EXIT_ERROR;
        }

        // Count duplicate groups (period-keyed, non-null period).
        $total = (int) ($db->query(
            'SELECT COUNT(*) AS c FROM ('
            . 'SELECT 1 FROM `payments` WHERE `period` IS NOT NULL '
            . 'GROUP BY `user_id`, `period`, `status` HAVING COUNT(*) > 1'
            . ') g'
        )->getRowArray()['c'] ?? 0);

        CLI::write('db:dedup-preview — duplicate payments(user_id, period, status) groups', 'yellow');
        CLI::newLine();

        if ($total === 0) {
            CLI::write('No duplicate groups. Run `php spark db:optimize` to add uniq_pay_user_period_status.', 'green');

            return EXIT_SUCCESS;
        }

        CLI::write("Found {$total} duplicate group(s)" . ($total > $limit ? " — showing the first {$limit}" : '') . '.', 'white');
        CLI::write('NOTE: read-only. Decide which row to keep, delete the rest, then re-run `php spark db:optimize`.', 'dark_gray');
        CLI::newLine();

        $hasGatewayTrx = $db->fieldExists('gateway_trx', 'payments');

        $groups = $db->query(
            'SELECT `user_id`, `period`, `status`, COUNT(*) AS c FROM `payments` '
            . 'WHERE `period` IS NOT NULL '
            . 'GROUP BY `user_id`, `period`, `status` HAVING COUNT(*) > 1 '
            . 'ORDER BY c DESC, `user_id` ASC LIMIT ' . (int) $limit
        )->getResultArray();

        $cols = 'id, amount, method_trx' . ($hasGatewayTrx ? ', gateway_trx' : '') . ', created_at';
        foreach ($groups as $i => $g) {
            $n = $i + 1;
            CLI::write("[{$n}] user_id={$g['user_id']}  period={$g['period']}  status={$g['status']}  ({$g['c']} rows)", 'cyan');

            $rows = $db->query(
                "SELECT {$cols} FROM `payments` WHERE `user_id` = ? AND `period` = ? AND `status` = ? ORDER BY `id` ASC",
                [$g['user_id'], $g['period'], $g['status']]
            )->getResultArray();

            $keepId = $this->suggestKeep($rows, $hasGatewayTrx);
            foreach ($rows as $r) {
                $mark = ((int) $r['id'] === $keepId) ? CLI::color('  ← suggested KEEP', 'green') : '';
                $gtx  = $hasGatewayTrx ? "  gateway_trx=" . ($r['gateway_trx'] ?? '∅') : '';
                CLI::write(sprintf('      id=%-8s amount=%-10s method_trx=%-18s%s  %s%s',
                    $r['id'], $r['amount'], $r['method_trx'] ?? '∅', $gtx, $r['created_at'] ?? '∅', $mark));
            }
            CLI::newLine();
        }

        CLI::write('After dedup, run `php spark db:optimize` — it adds uniq_pay_user_period_status once the data is clean.', 'white');

        return EXIT_SUCCESS;
    }

    /**
     * Suggest which row to keep: prefer a row with a non-empty gateway_trx (a real
     * confirmed gateway transaction), else the newest id. A HINT only.
     */
    private function suggestKeep(array $rows, bool $hasGatewayTrx): int
    {
        if ($hasGatewayTrx) {
            foreach ($rows as $r) {
                if (! empty($r['gateway_trx'])) {
                    return (int) $r['id'];
                }
            }
        }
        $maxId = 0;
        foreach ($rows as $r) {
            $maxId = max($maxId, (int) $r['id']);
        }

        return $maxId;
    }
}
