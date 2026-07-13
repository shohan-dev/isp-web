<?php

namespace App\Commands;

use App\Services\DatabaseAuditService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * db:audit — read-only database optimization audit + load estimate.
 *
 *   php spark db:audit
 *   php spark db:audit --json
 *   php spark db:audit --iterations=5
 *   php spark db:audit --json --output=writable/db-audit.json
 */
class DbAudit extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:audit';
    protected $description = 'Audit DB schema/indexes, benchmark hot queries, score optimization level, estimate load capacity.';
    protected $usage       = 'db:audit [--json] [--iterations=3] [--output=path]';

    public function run(array $params)
    {
        $asJson      = (bool) CLI::getOption('json');
        $iterations  = max(1, min(10, (int) (CLI::getOption('iterations') ?? 3)));
        $outputPath  = CLI::getOption('output');

        try {
            $report = (new DatabaseAuditService())->run($iterations);
        } catch (Throwable $e) {
            CLI::error('Database audit failed: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        if ($outputPath) {
            $dir = dirname($outputPath);
            if ($dir !== '.' && $dir !== '' && ! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            CLI::write("Report written to {$outputPath}", 'green');
        }

        if ($asJson) {
            CLI::write(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return EXIT_SUCCESS;
        }

        $this->renderReport($report);

        return EXIT_SUCCESS;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderReport(array $report): void
    {
        $opt = $report['optimization'];

        CLI::newLine();
        CLI::write('╔══════════════════════════════════════════════════════════════╗', 'cyan');
        CLI::write('║           ISP-CORE DATABASE OPTIMIZATION AUDIT               ║', 'cyan');
        CLI::write('╚══════════════════════════════════════════════════════════════╝', 'cyan');
        CLI::newLine();

        CLI::write('Database: ' . ($report['database'] ?? '?'), 'white');
        CLI::write('Generated: ' . ($report['generated_at'] ?? ''), 'dark_gray');
        CLI::write('Duration:  ' . ($report['duration_sec'] ?? '?') . 's', 'dark_gray');
        CLI::newLine();

        $scoreColor = match ($opt['level'] ?? 'F') {
            'A'     => 'green',
            'B'     => 'green',
            'C'     => 'yellow',
            'D'     => 'light_red',
            default => 'red',
        };

        CLI::write('── OPTIMIZATION SCORE ──────────────────────────────────────', 'cyan');
        CLI::write(
            sprintf('  Overall: %s / 100  [Grade %s]  %s', $opt['score'], $opt['level'], $opt['label']),
            $scoreColor
        );
        CLI::newLine();

        foreach ($opt['categories'] as $name => $cat) {
            $bar = $this->bar((float) $cat['score']);
            CLI::write(sprintf('  %-22s %s %5.1f%%  (%s)', ucwords(str_replace('_', ' ', $name)) . ':', $bar, $cat['score'], $cat['detail']), 'white');
        }
        CLI::newLine();

        $schema = $report['schema'] ?? [];
        CLI::write('── SCHEMA (indexes & migrations) ───────────────────────────', 'cyan');
        CLI::write(sprintf(
            '  Indexes:    %d / %d present (%.1f%%)',
            $schema['indexes']['present'] ?? 0,
            $schema['indexes']['total'] ?? 0,
            $schema['indexes']['percent'] ?? 0
        ), 'white');
        CLI::write(sprintf(
            '  Tables:     jobs=%s  cron_locks=%s',
            ($schema['tables']['present']['jobs'] ?? false) ? 'YES' : 'NO',
            ($schema['tables']['present']['cron_locks'] ?? false) ? 'YES' : 'NO'
        ), 'white');
        CLI::write(sprintf(
            '  Columns:    period=%s  gateway_trx=%s  UNIQUE(user,period,status)=%s',
            ($schema['columns']['present']['payments.period'] ?? false) ? 'YES' : 'NO',
            ($schema['columns']['present']['payments.gateway_trx'] ?? false) ? 'YES' : 'NO',
            ($schema['payment_unique_constraint'] ?? false) ? 'YES' : 'NO'
        ), 'white');
        CLI::write(sprintf(
            '  Migrations: %d / %d optimization migrations applied (%.1f%%)',
            count($schema['migrations']['applied'] ?? []),
            count($schema['migrations']['applied'] ?? []) + count($schema['migrations']['missing'] ?? []),
            $schema['migrations']['percent'] ?? 0
        ), 'white');

        $missing = $schema['indexes']['missing'] ?? [];
        if ($missing !== []) {
            CLI::newLine();
            CLI::write('  Missing indexes (' . count($missing) . '):', 'yellow');
            foreach (array_slice($missing, 0, 8) as $m) {
                CLI::write('    - ' . $m['table'] . '.' . $m['index'], 'yellow');
            }
            if (count($missing) > 8) {
                CLI::write('    ... and ' . (count($missing) - 8) . ' more', 'dark_gray');
            }
        }
        CLI::newLine();

        $health = $report['data_health'] ?? [];
        CLI::write('── DATA HEALTH ─────────────────────────────────────────────', 'cyan');
        foreach ($health['table_sizes'] ?? [] as $table => $info) {
            CLI::write(sprintf(
                '  %-20s ~%s rows  data=%sMB  indexes=%sMB',
                $table,
                number_format($info['approx_rows']),
                $info['data_mb'],
                $info['index_mb']
            ), 'white');
        }
        if (($health['payment_duplicate_groups'] ?? 0) > 0) {
            CLI::write('  Payment duplicate groups: ' . $health['payment_duplicate_groups'] . ' (blocks UNIQUE constraint)', 'yellow');
        }
        CLI::newLine();

        $bench = $report['benchmarks'] ?? [];
        if (isset($bench['error'])) {
            CLI::write('── BENCHMARKS ──────────────────────────────────────────────', 'cyan');
            CLI::write('  ' . $bench['error'], 'yellow');
        } else {
            CLI::write('── QUERY BENCHMARKS (tenant admin_id=' . ($bench['admin_id'] ?? '?') . ') ──', 'cyan');
            $byCategory = [];
            foreach ($bench['tests'] ?? [] as $test) {
                $byCategory[$test['category']][] = $test;
            }
            foreach ($byCategory as $category => $tests) {
                CLI::write('  [' . strtoupper($category) . ']', 'dark_gray');
                foreach ($tests as $test) {
                    $color = match ($test['rating']) {
                        'excellent' => 'green',
                        'good'      => 'green',
                        'fair'      => 'yellow',
                        default     => 'light_red',
                    };
                    CLI::write(sprintf(
                        '    %-42s %7.2f ms avg  [%s]',
                        $test['label'],
                        $test['avg_ms'],
                        strtoupper($test['rating'])
                    ), $color);
                }
            }
        }
        CLI::newLine();

        $load = $report['load_capacity'] ?? [];
        if (! isset($load['error'])) {
            CLI::write('── ESTIMATED LOAD CAPACITY (Tier-A: 90 FPM workers) ────────', 'cyan');
            $cl = $load['by_category']['customer_list'] ?? [];
            $db = $load['by_category']['dashboard'] ?? [];
            CLI::write(sprintf('  Customer list:  ~%s req/s sustained  |  rating: %s', $cl['max_sustained_rps'] ?? '?', strtoupper($cl['rating'] ?? '?')), 'white');
            CLI::write(sprintf('  Dashboard AJAX: ~%s ms combined     |  rating: %s', $db['avg_ms'] ?? '?', strtoupper($db['rating'] ?? '?')), 'white');
            CLI::write('  Registered users (heuristic): ' . ($load['registered_user_estimate'] ?? '?'), 'white');
            CLI::write('  Current user rows in DB:       ' . number_format($load['current_user_rows'] ?? 0), 'white');
            CLI::newLine();
        }

        $recs = $report['recommendations'] ?? [];
        if ($recs !== []) {
            CLI::write('── RECOMMENDATIONS ─────────────────────────────────────────', 'cyan');
            foreach ($recs as $i => $rec) {
                CLI::write('  ' . ($i + 1) . '. ' . $rec, 'yellow');
            }
            CLI::newLine();
        }

        CLI::write('Tip: php spark db:audit --json --output=writable/db-audit.json', 'dark_gray');
        CLI::newLine();
    }

    private function bar(float $score): string
    {
        $filled = (int) round($score / 10);
        $empty  = 10 - $filled;

        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }
}
