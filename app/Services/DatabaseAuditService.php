<?php

namespace App\Services;

use App\Traits\DbSchemaHelper;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * DatabaseAuditService — read-only schema + benchmark audit for optimization status.
 *
 * Used by `php spark db:audit`. Never mutates data.
 */
class DatabaseAuditService
{
    use DbSchemaHelper; // BUG-20: shared indexExists()

    private BaseConnection $db;

    private string $dbName;

    /** @var array<string, array<string, list<string>>> */
    private array $plannedIndexes = [
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
            'idx_pay_user_period_status' => ['user_id', 'period', 'status'],
            'idx_pay_admin_id'           => ['admin_id'],
            'idx_pay_paidby'             => ['paidby'],
            'idx_pay_created_at'         => ['created_at'],
            'idx_pay_method_trx'         => ['method_trx', 'status'],
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

    /** @var list<string> */
    private array $plannedTables = ['jobs', 'cron_locks'];

    /** @var list<string> */
    private array $plannedMigrations = [
        '2026-06-17-000001',
        '2026-06-17-000002',
        '2026-06-18-000001',
        '2026-06-18-000002',
        '2026-06-18-000003',
    ];

    public function __construct(?BaseConnection $db = null)
    {
        // Phase I1/E6: prefer the 'read' replica group for this read-only audit
        // service so it does not compete with writes on the primary. Falls back
        // to primary automatically via the failover config if no replica is set.
        $this->db     = $db ?? (Database::connect('read') ?? Database::connect());
        $this->dbName = $this->db->getDatabase();
    }

    /**
     * @return array<string, mixed>
     */
    public function run(int $benchmarkIterations = 3): array
    {
        $started = microtime(true);

        $schema     = $this->auditSchema();
        $dataHealth = $this->auditDataHealth();
        $benchmarks = $this->runBenchmarks($benchmarkIterations);
        $load       = $this->estimateLoadCapacity($benchmarks, $dataHealth['table_sizes'] ?? []);
        $scores     = $this->computeScores($schema, $dataHealth, $benchmarks);
        $overall    = $this->overallLevel($scores['total']);

        return [
            'generated_at'    => date('c'),
            'database'        => $this->dbName,
            'duration_sec'    => round(microtime(true) - $started, 2),
            'optimization'    => [
                'score'           => $scores['total'],
                'level'           => $overall['level'],
                'label'           => $overall['label'],
                'categories'      => $scores['categories'],
            ],
            'load_capacity'   => $load,
            'schema'          => $schema,
            'data_health'     => $dataHealth,
            'benchmarks'      => $benchmarks,
            'recommendations' => $this->recommendations($schema, $dataHealth, $benchmarks, $scores),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditSchema(): array
    {
        $indexes = [];
        $missing = [];
        $present = 0;
        $total   = 0;

        foreach ($this->plannedIndexes as $table => $defs) {
            if (! $this->db->tableExists($table)) {
                foreach ($defs as $name => $_cols) {
                    $total++;
                    $missing[] = ['table' => $table, 'index' => $name, 'reason' => 'table_missing'];
                }
                continue;
            }

            foreach ($defs as $name => $cols) {
                $total++;
                $exists = $this->indexExists($table, $name);
                if ($exists) {
                    $present++;
                    $indexes[] = ['table' => $table, 'index' => $name, 'status' => 'present'];
                } else {
                    $missing[] = ['table' => $table, 'index' => $name, 'columns' => $cols];
                }
            }
        }

        // gateway_trx unique OR fallback non-unique counts as partial credit
        if ($this->db->tableExists('payments')) {
            $hasGatewayIdx = $this->indexExists('payments', 'uniq_pay_gateway_trx')
                || $this->indexExists('payments', 'idx_pay_gateway_trx');
            if ($hasGatewayIdx) {
                $present++;
            }
            $total++;
            if (! $hasGatewayIdx) {
                $missing[] = ['table' => 'payments', 'index' => 'uniq_pay_gateway_trx|idx_pay_gateway_trx'];
            }
        }

        $tables = [];
        foreach ($this->plannedTables as $t) {
            $tables[$t] = $this->db->tableExists($t);
        }

        $columns = [];
        if ($this->db->tableExists('payments')) {
            $columns['payments.period']      = $this->db->fieldExists('period', 'payments');
            $columns['payments.gateway_trx'] = $this->db->fieldExists('gateway_trx', 'payments');
        }

        $migrations = $this->auditMigrations();

        $tablesPresent = count(array_filter($tables));
        $colsPresent   = count(array_filter($columns));
        $colsTotal     = count($columns);

        $indexPct = $total > 0 ? round(($present / $total) * 100, 1) : 0;

        return [
            'indexes' => [
                'present' => $present,
                'total'   => $total,
                'percent' => $indexPct,
                'missing' => $missing,
            ],
            'tables' => [
                'planned' => $this->plannedTables,
                'present' => $tables,
                'percent' => count($this->plannedTables) > 0
                    ? round(($tablesPresent / count($this->plannedTables)) * 100, 1) : 0,
            ],
            'columns' => [
                'planned' => array_keys($columns),
                'present' => $columns,
                'percent' => $colsTotal > 0 ? round(($colsPresent / $colsTotal) * 100, 1) : 0,
            ],
            'migrations' => $migrations,
            'payment_unique_constraint' => $this->indexExists('payments', 'uniq_pay_user_period_status'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMigrations(): array
    {
        if (! $this->db->tableExists('migrations')) {
            return ['applied' => [], 'missing' => $this->plannedMigrations, 'percent' => 0];
        }

        $rows = $this->db->table('migrations')
            ->select('version')
            ->whereIn('version', $this->plannedMigrations)
            ->get()
            ->getResultArray();

        $applied = array_column($rows, 'version');
        $missing = array_values(array_diff($this->plannedMigrations, $applied));

        return [
            'applied' => $applied,
            'missing' => $missing,
            'percent' => round((count($applied) / count($this->plannedMigrations)) * 100, 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditDataHealth(): array
    {
        $sizes = [];

        foreach (['users', 'payments', 'user_data_usage', 'jobs', 'tickets'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $row = $this->db->query(
                'SELECT table_rows AS approx_rows, '
                . 'ROUND(data_length/1024/1024, 2) AS data_mb, '
                . 'ROUND(index_length/1024/1024, 2) AS index_mb '
                . 'FROM information_schema.tables '
                . 'WHERE table_schema = ? AND table_name = ?',
                [$this->dbName, $table]
            )->getRow();

            if ($row) {
                $sizes[$table] = [
                    'approx_rows' => (int) $row->approx_rows,
                    'data_mb'     => (float) $row->data_mb,
                    'index_mb'    => (float) $row->index_mb,
                ];
            }
        }

        $paymentDupes = 0;
        if ($this->db->tableExists('payments') && $this->db->fieldExists('period', 'payments')) {
            $paymentDupes = (int) ($this->db->query(
                'SELECT COUNT(*) AS c FROM ('
                . 'SELECT 1 FROM payments WHERE period IS NOT NULL '
                . 'GROUP BY user_id, period, status HAVING COUNT(*) > 1'
                . ') d'
            )->getRow()->c ?? 0);
        }

        return [
            'table_sizes'              => $sizes,
            'payment_duplicate_groups' => $paymentDupes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runBenchmarks(int $iterations): array
    {
        $adminId = $this->sampleAdminId();
        $tests   = [];

        if ($adminId === null) {
            return ['error' => 'No admin_id found in users table — cannot run tenant benchmarks.'];
        }

        $currentMonth    = date('F');
        $lastMonth       = date('F', strtotime('-1 month'));
        $sevenDaysAgo    = date('Y-m-d', strtotime('-6 days'));

        $tests['tenant_customer_count'] = $this->bench(
            'Tenant customer count',
            'customer_list',
            $iterations,
            fn () => $this->db->query(
                'SELECT COUNT(*) AS c FROM users WHERE admin_id = ? AND role = ?',
                [$adminId, 'user']
            )->getRow()
        );

        $tests['customer_grid_page'] = $this->bench(
            'Customer grid page (25 rows, joins)',
            'customer_list',
            $iterations,
            fn () => $this->db->query(
                'SELECT users.id, users.name, areas.area_name, routers.name AS router_name, '
                . 'COALESCE(p_admin.package_name, p_reseller.package_name) AS package_name '
                . 'FROM users '
                . 'LEFT JOIN user_router_data ON user_router_data.user_id = users.id '
                . 'LEFT JOIN areas ON areas.id = users.area_id '
                . 'LEFT JOIN routers ON routers.id = users.router_id '
                . 'LEFT JOIN packages p_admin ON p_admin.id = users.package_id '
                . 'LEFT JOIN reseller_packages p_reseller ON p_reseller.id = users.package_id '
                . 'WHERE users.admin_id = ? AND users.role = ? '
                . 'ORDER BY users.id DESC LIMIT 25',
                [$adminId, 'user']
            )->getResult()
        );

        $tests['payment_status_subquery'] = $this->bench(
            'Payment status subquery (per-user pattern)',
            'payments',
            $iterations,
            fn () => $this->db->query(
                'SELECT u.id, '
                . '(SELECT status FROM payments WHERE user_id = u.id AND month = ? ORDER BY id DESC LIMIT 1) AS cur, '
                . '(SELECT status FROM payments WHERE user_id = u.id AND month = ? ORDER BY id DESC LIMIT 1) AS prev '
                . 'FROM users u WHERE u.admin_id = ? AND u.role = ? LIMIT 25',
                [$currentMonth, $lastMonth, $adminId, 'user']
            )->getResult()
        );

        $tests['dashboard_payment_sum'] = $this->bench(
            'Dashboard payment SUM (month)',
            'dashboard',
            $iterations,
            fn () => $this->db->query(
                'SELECT COALESCE(SUM(p.amount), 0) AS total FROM payments p '
                . 'INNER JOIN users u ON u.id = p.user_id '
                . 'WHERE (u.admin_id = ? OR p.paidby = ?) AND p.month = ? AND p.status = ?',
                [$adminId, $adminId, $currentMonth, 'successful']
            )->getRow()
        );

        $tests['usage_trend_grouped'] = $this->bench(
            'Bandwidth trend (7-day GROUP BY — optimized shape)',
            'dashboard',
            $iterations,
            fn () => $this->db->query(
                'SELECT udu.date AS d, SUM(udu.rx_today + udu.tx_today) AS total '
                . 'FROM user_data_usage udu '
                . 'INNER JOIN users u ON u.id = udu.admin_id '
                . 'WHERE udu.date >= ? AND u.admin_id = ? '
                . 'GROUP BY udu.date',
                [$sevenDaysAgo, $adminId]
            )->getResult()
        );

        $tests['usage_trend_loop'] = $this->bench(
            'Bandwidth trend (7× single-day — old shape)',
            'dashboard',
            $iterations,
            function () use ($adminId) {
                for ($i = 6; $i >= 0; $i--) {
                    $d = date('Y-m-d', strtotime("-{$i} days"));
                    $this->db->query(
                        'SELECT COALESCE(SUM(udu.rx_today + udu.tx_today), 0) AS total '
                        . 'FROM user_data_usage udu '
                        . 'INNER JOIN users u ON u.id = udu.admin_id '
                        . 'WHERE udu.date = ? AND u.admin_id = ?',
                        [$d, $adminId]
                    )->getRow();
                }
            }
        );

        $tests['cron_expiry_sweep'] = $this->bench(
            'Cron expiry sweep (role + will_expire)',
            'cron',
            $iterations,
            fn () => $this->db->query(
                'SELECT COUNT(*) AS c FROM users '
                . 'WHERE role = ? AND subscription_status = ? AND will_expire < ?',
                ['user', 'active', date('Y-m-d H:i:s')]
            )->getRow()
        );

        return [
            'admin_id'   => $adminId,
            'iterations' => $iterations,
            'tests'      => $tests,
        ];
    }

    /**
     * @param array<string, mixed> $benchmarks
     * @param array<string, mixed> $tableSizes
     * @return array<string, mixed>
     */
    private function estimateLoadCapacity(array $benchmarks, array $tableSizes): array
    {
        if (isset($benchmarks['error'])) {
            return ['error' => $benchmarks['error']];
        }

        $gridMs = $benchmarks['tests']['customer_grid_page']['avg_ms'] ?? 500;
        $dashMs = ($benchmarks['tests']['dashboard_payment_sum']['avg_ms'] ?? 50)
            + ($benchmarks['tests']['usage_trend_grouped']['avg_ms'] ?? 30);

        // Tier-A reference: 90 FPM workers (deploy/README.md)
        $fpmWorkers = 90;

        $gridConcurrent  = $gridMs > 0 ? (int) floor($fpmWorkers * (1000 / max($gridMs, 1))) : 0;
        $steadyRps       = $gridMs > 0 ? round(min(500, $fpmWorkers / ($gridMs / 1000)), 1) : 0;

        $userRows = $tableSizes['users']['approx_rows'] ?? 0;

        // Heuristic registered-user capacity bands from optimization docs
        $registeredCapacity = match (true) {
            $gridMs < 80  && $steadyRps >= 80  => '15,000–20,000 registered users (single VPS, typical DAU)',
            $gridMs < 150 && $steadyRps >= 40  => '8,000–15,000 registered users',
            $gridMs < 300 && $steadyRps >= 20  => '3,000–8,000 registered users',
            default                             => 'Under 3,000 registered users — optimize further',
        };

        return [
            'assumptions' => [
                'php_fpm_workers' => $fpmWorkers,
                'note'            => 'Estimates assume tuned VPS (Tier-A). Concurrent users ≠ registered users.',
            ],
            'by_category' => [
                'customer_list' => [
                    'avg_ms'              => $gridMs,
                    'rating'              => $this->msRating($gridMs, [80, 150, 300]),
                    'max_sustained_rps'   => $steadyRps,
                    'parallel_grid_loads' => min($fpmWorkers, max(1, (int) round(1000 / max($gridMs, 1)))),
                ],
                'dashboard' => [
                    'avg_ms'    => round($dashMs, 2),
                    'rating'    => $this->msRating($dashMs, [50, 120, 250]),
                    'polls_per_min_sustainable' => $dashMs > 0 ? (int) floor(($fpmWorkers * 60000) / ($dashMs * 20)) : 0,
                ],
            ],
            'registered_user_estimate' => $registeredCapacity,
            'current_user_rows'        => $userRows,
        ];
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $dataHealth
     * @param array<string, mixed> $benchmarks
     * @return array{categories: array<string, array<string, mixed>>, total: float}
     */
    private function computeScores(array $schema, array $dataHealth, array $benchmarks): array
    {
        $indexScore = (float) ($schema['indexes']['percent'] ?? 0);
        $tableScore = (float) ($schema['tables']['percent'] ?? 0);
        $colScore   = (float) ($schema['columns']['percent'] ?? 0);
        $migScore   = (float) ($schema['migrations']['percent'] ?? 0);

        $schemaScore = ($indexScore * 0.55) + ($tableScore * 0.15) + ($colScore * 0.15) + ($migScore * 0.15);

        $dataScore = 100.0;
        if (($dataHealth['payment_duplicate_groups'] ?? 0) > 0) {
            $dataScore -= min(40, (int) $dataHealth['payment_duplicate_groups'] / 3);
        }

        $queryScore = 50.0;
        if (! isset($benchmarks['error'])) {
            $ratings = [];
            foreach ($benchmarks['tests'] as $test) {
                $ratings[] = match ($test['rating'] ?? 'poor') {
                    'excellent' => 100,
                    'good'      => 80,
                    'fair'      => 55,
                    'slow'      => 30,
                    default     => 10,
                };
            }
            $queryScore = count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 50;
        }

        $infraScore = $tableScore;

        $total = round(
            ($schemaScore * 0.35) + ($queryScore * 0.40) + ($dataScore * 0.15) + ($infraScore * 0.10),
            1
        );

        return [
            'total'      => $total,
            'categories' => [
                'schema_indexes' => [
                    'score'   => round($schemaScore, 1),
                    'weight'  => '35%',
                    'detail'  => "Indexes {$schema['indexes']['present']}/{$schema['indexes']['total']} ({$indexScore}%)",
                ],
                'query_performance' => [
                    'score'  => round($queryScore, 1),
                    'weight' => '40%',
                    'detail' => isset($benchmarks['error']) ? $benchmarks['error'] : 'Hot-path SQL benchmarks',
                ],
                'data_health' => [
                    'score'  => round($dataScore, 1),
                    'weight' => '15%',
                    'detail' => 'Payment duplicate groups: ' . ($dataHealth['payment_duplicate_groups'] ?? 0),
                ],
                'infrastructure' => [
                    'score'  => round($infraScore, 1),
                    'weight' => '10%',
                    'detail' => 'Queue/cron tables + migrations applied',
                ],
            ],
        ];
    }

    /**
     * @return array{level: string, label: string}
     */
    private function overallLevel(float $score): array
    {
        return match (true) {
            $score >= 90 => ['level' => 'A', 'label' => 'Highly optimized — production-ready schema'],
            $score >= 75 => ['level' => 'B', 'label' => 'Well optimized — minor gaps remain'],
            $score >= 60 => ['level' => 'C', 'label' => 'Partially optimized — indexes help, queries need work'],
            $score >= 40 => ['level' => 'D', 'label' => 'Under-optimized — noticeable performance risk'],
            default      => ['level' => 'F', 'label' => 'Not optimized — migrations/indexes missing or very slow queries'],
        };
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $dataHealth
     * @param array<string, mixed> $benchmarks
     * @param array{categories: array<string, mixed>, total: float} $scores
     * @return list<string>
     */
    private function recommendations(array $schema, array $dataHealth, array $benchmarks, array $scores): array
    {
        $recs = [];

        foreach ($schema['indexes']['missing'] ?? [] as $m) {
            $recs[] = "Add missing index {$m['index']} on table {$m['table']}";
            if (count($recs) >= 5) {
                break;
            }
        }

        if (! ($schema['payment_unique_constraint'] ?? false) && ($dataHealth['payment_duplicate_groups'] ?? 0) > 0) {
            $recs[] = "Dedup {$dataHealth['payment_duplicate_groups']} payment duplicate group(s), then re-run migrate for UNIQUE(user_id, period, status)";
        }

        if (! isset($benchmarks['error'])) {
            $loop = $benchmarks['tests']['usage_trend_loop']['avg_ms'] ?? 0;
            $grp  = $benchmarks['tests']['usage_trend_grouped']['avg_ms'] ?? 1;
            if ($loop > max(50, $grp * 4)) {
                $recs[] = 'Dashboard bandwidth trend still uses 7× single-day queries in app code — switch to GROUP BY (8 queries → 1)';
            }

            if (($benchmarks['tests']['customer_grid_page']['avg_ms'] ?? 0) > 150) {
                $recs[] = 'Customer grid still slow — batch N+1 callbacks and replace correlated payment subqueries with JOINs on payments.period';
            }

            if (($benchmarks['tests']['payment_status_subquery']['avg_ms'] ?? 0) > 100) {
                $recs[] = 'Payment status subqueries per row are expensive — migrate queries from payments.month to payments.period';
            }
        }

        if (($scores['categories']['infrastructure']['score'] ?? 0) < 100) {
            $recs[] = 'Run `php spark migrate` to create jobs + cron_locks tables';
        }

        return array_values(array_unique($recs));
    }

    /**
     * @param callable(): mixed $fn
     * @return array<string, mixed>
     */
    private function bench(string $label, string $category, int $iterations, callable $fn): array
    {
        $times = [];
        for ($i = 0; $i < max(1, $iterations); $i++) {
            $t0 = microtime(true);
            $fn();
            $times[] = (microtime(true) - $t0) * 1000;
        }

        $avg = round(array_sum($times) / count($times), 2);
        $max = round(max($times), 2);

        $thresholds = match ($category) {
            'customer_list' => [80, 150, 300],
            'dashboard'     => [50, 120, 250],
            'payments'      => [60, 120, 250],
            'cron'          => [40, 100, 200],
            default         => [50, 150, 300],
        };

        return [
            'label'    => $label,
            'category' => $category,
            'avg_ms'   => $avg,
            'max_ms'   => $max,
            'rating'   => $this->msRating($avg, $thresholds),
            'samples'  => array_map(static fn ($t) => round($t, 2), $times),
        ];
    }

    /**
     * @param list<float|int> $thresholds [excellent, good, fair]
     */
    private function msRating(float $ms, array $thresholds): string
    {
        return match (true) {
            $ms <= $thresholds[0] => 'excellent',
            $ms <= $thresholds[1] => 'good',
            $ms <= $thresholds[2] => 'fair',
            default               => 'slow',
        };
    }

    private function sampleAdminId(): ?int
    {
        if (! $this->db->tableExists('users')) {
            return null;
        }

        $row = $this->db->query(
            "SELECT admin_id FROM users WHERE role IN ('admin','resellerAdmin','super_admin') AND admin_id IS NOT NULL LIMIT 1"
        )->getRow();

        if ($row && ! empty($row->admin_id)) {
            return (int) $row->admin_id;
        }

        $row = $this->db->query(
            'SELECT id FROM users WHERE role IN (\'admin\',\'resellerAdmin\',\'super_admin\') ORDER BY id ASC LIMIT 1'
        )->getRow();

        return $row ? (int) $row->id : null;
    }

}
