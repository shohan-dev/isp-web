<?php

namespace Zapi\Modules\Customer\Usage\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class UsageService extends CustomerBaseService
{
    public function getUsage()
    {
        $userId = $this->request->getGet('user_id');
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        $usage = $this->getDataUsage((int) $userId);

        $pkg         = $this->package_model->find($user->package_id ?? 0);
        $packageName = $this->resolveModelField($pkg, 'package_name', 'Unknown');
        $bandwidth   = $this->resolveModelField($pkg, 'bandwidth', 'Unknown');

        return $this->respondSuccess([
            'user_id'          => (int) $userId,
            'user_name'        => $user->name ?? 'Unknown',
            'current_package'  => $packageName,
            'bandwidth'        => $bandwidth,
            'total_data_used'  => $usage['total_used'],
            'data_remaining'   => $usage['remaining'],
            'total_data_limit' => $usage['limit'],
            'usage_percentage' => $usage['percentage'],
            'daily_average'    => $usage['daily_avg'],
            'peak_usage'       => $usage['peak'],
            'offpeak_usage'    => $usage['offpeak'],
            'last_updated'     => $usage['last_updated'],
            'reset_date'       => date('Y-m-t'),
            'days_remaining'   => $usage['days_left'],
        ]);
    }

    public function getTraffic($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        $traffic = $this->getUserTraffic((int) $userId, $user);

        return $this->respondSuccess([
            'user_id'                  => (int) $userId,
            'user_name'                => $user->name ?? 'Unknown',
            'upload_total'             => $traffic['upload'],
            'download_total'           => $traffic['download'],
            'total_traffic'            => $traffic['total'],
            'current_session_upload'   => $traffic['session_upload'],
            'current_session_download' => $traffic['session_download'],
            'session_duration'         => $traffic['duration'],
            'average_speed_upload'     => $traffic['avg_upload'],
            'average_speed_download'   => $traffic['avg_download'],
        ]);
    }

    public function getPeakHours($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $peakData = $this->getPeakHoursData((int) $userId);

        return $this->respondSuccess([
            'user_id'           => (int) $userId,
            'peak_hours'        => $peakData['hours'],
            'average_peak_usage'=> $peakData['avg_peak'],
            'non_peak_usage'    => $peakData['non_peak'],
            'peak_percentage'   => $peakData['peak_percent'],
        ]);
    }

    public function getUsageHistory($userId, $days = 30)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $days    = max(1, min(365, (int) $days));
        $history = $this->getUsageHistoryFromDB((int) $userId, $days);

        return $this->respondSuccess([
            'user_id'       => (int) $userId,
            'period_days'   => $days,
            'total_days'    => count($history),
            'daily_usage'   => $history,
            'total_used_mb' => (float) array_sum(array_column($history, 'used_mb')),
            'average_daily_mb' => $this->calculateAverage($history, 'used_mb'),
        ]);
    }

    // ── private helpers ────────────────────────────────────────────────────────

    private function getDataUsage(int $userId): array
    {
        $buffer     = new \App\Services\UsageBufferService();
        $today      = date('Y-m-d');
        $yearMonth  = date('Y-m');
        $monthStart = date('Y-m-01');

        // 1. Check monthly aggregate cache (warmed by a previous call this month).
        $monthly = $buffer->getMonthlyCache($userId, $yearMonth);

        if ($monthly === null) {
            // 2. Cache miss: aggregate from DB.
            $usageModel = model('App\Models\UserDataUsageModel');
            $row        = $usageModel
                ->selectSum('rx_today')
                ->selectSum('tx_today')
                ->where('admin_id', $userId)
                ->where('date >=', $monthStart)
                ->where('date <=', $today)
                ->first();

            $rxDB = (float) (is_object($row) ? ($row->rx_today ?? 0) : ($row['rx_today'] ?? 0));
            $txDB = (float) (is_object($row) ? ($row->tx_today ?? 0) : ($row['tx_today'] ?? 0));

            // 3. Add the most recent buffered row (may not be flushed to DB yet).
            $bufferedRow = $buffer->getBufferedRow($today, $userId);
            if ($bufferedRow !== null) {
                $rxDB = max($rxDB, (float) ($bufferedRow['rx_today'] ?? 0));
                $txDB = max($txDB, (float) ($bufferedRow['tx_today'] ?? 0));
            }

            $monthly = ['rx' => $rxDB, 'tx' => $txDB];
            $buffer->saveMonthlyCache($userId, $yearMonth, $monthly);
        }

        $rxMonthly = (float) $monthly['rx'];
        $txMonthly = (float) $monthly['tx'];
        $totalUsed = $rxMonthly + $txMonthly;
        $dayOfMonth = (int) date('j');
        $dailyAvg   = $dayOfMonth > 0 ? round($totalUsed / $dayOfMonth, 2) : 0;

        // Today's row: Redis buffer → cache → DB (via UsageBufferService).
        $todayRow   = $buffer->getForUser($userId, $today);
        $lastUpdate = $todayRow ? ($todayRow['date'] ?? $today) : $today;

        return [
            'total_used'   => round($totalUsed, 2) . ' MB',
            'remaining'    => 'Unlimited',
            'limit'        => 'Unlimited',
            'percentage'   => 0,
            'daily_avg'    => $dailyAvg . ' MB',
            'peak'         => round($rxMonthly, 2) . ' MB',
            'offpeak'      => round($txMonthly, 2) . ' MB',
            'last_updated' => $lastUpdate,
            'days_left'    => (int) date('t') - $dayOfMonth,
        ];
    }

    private function getUserTraffic(int $userId, object $user): array
    {
        $usageModel = model('App\Models\UserDataUsageModel');

        // Cumulative totals from DB
        $totals = $usageModel
            ->selectSum('rx_today')
            ->selectSum('tx_today')
            ->where('admin_id', $userId)
            ->first();

        $rxTotal = (float) ($totals->rx_today ?? 0);
        $txTotal = (float) ($totals->tx_today ?? 0);

        // Try live session from MikroTik
        $sessionRx = $sessionTx = 0.0;
        $duration  = 'N/A';
        $avgDown   = 'N/A';
        $avgUp     = 'N/A';

        try {
            helper('router');
            $routerId = $user->router_id ?? null;
            if ($routerId) {
                $rdModel = model('App\Models\UserRouterDataModel');
                $rd      = $rdModel->where('user_id', $userId)->first();
                $pppoe   = $rd ? (is_object($rd) ? ($rd->pppoe_secret ?? null) : ($rd['pppoe_secret'] ?? null)) : null;

                if ($pppoe) {
                    $client = routerClient($routerId);
                    if ($client && !is_array($client)) {
                        $activeData = getactive_user($client);
                        $actives    = $activeData['data']['activeusers'] ?? [];
                        foreach ($actives as $sess) {
                            if (($sess['name'] ?? '') === $pppoe) {
                                $sessionRx = round(((float) ($sess['bytes-in'] ?? 0)) / 1048576, 2);
                                $sessionTx = round(((float) ($sess['bytes-out'] ?? 0)) / 1048576, 2);
                                $duration  = $sess['uptime'] ?? 'N/A';
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('info', 'UsageService: MikroTik session fetch failed: ' . $e->getMessage());
        }

        return [
            'upload'          => round($txTotal, 2) . ' MB',
            'download'        => round($rxTotal, 2) . ' MB',
            'total'           => round($rxTotal + $txTotal, 2) . ' MB',
            'session_upload'  => $sessionTx . ' MB',
            'session_download'=> $sessionRx . ' MB',
            'duration'        => $duration,
            'avg_upload'      => $avgUp,
            'avg_download'    => $avgDown,
        ];
    }

    private function getPeakHoursData(int $userId): array
    {
        $usageModel  = model('App\Models\UserDataUsageModel');
        $last7Days   = date('Y-m-d', strtotime('-7 days'));

        $rows = $usageModel
            ->where('admin_id', $userId)
            ->where('date >=', $last7Days)
            ->orderBy('date', 'ASC')
            ->findAll();

        $totalRx    = 0.0;
        $totalTx    = 0.0;

        foreach ($rows as $r) {
            $row     = is_object($r) ? $r : (object) $r;
            $totalRx += (float) ($row->rx_today ?? 0);
            $totalTx += (float) ($row->tx_today ?? 0);
        }

        $total    = $totalRx + $totalTx;
        $avgPerDay = count($rows) > 0 ? round($total / count($rows), 2) : 0;

        return [
            'hours'        => ['18:00-23:00'],
            'avg_peak'     => $avgPerDay . ' MB',
            'non_peak'     => round($total * 0.4, 2) . ' MB',
            'peak_percent' => $total > 0 ? 60 : 0,
        ];
    }

    private function getUsageHistoryFromDB(int $userId, int $days): array
    {
        $buffer     = new \App\Services\UsageBufferService();
        $usageModel = model('App\Models\UserDataUsageModel');
        $since      = date('Y-m-d', strtotime("-{$days} days"));

        // DB fetch (one query for all days).
        $rows = $usageModel
            ->where('admin_id', $userId)
            ->where('date >=', $since)
            ->orderBy('date', 'ASC')
            ->findAll();

        $indexed = [];
        foreach ($rows as $r) {
            $arr  = is_object($r) ? (array) $r : $r;
            $d    = (string) ($arr['date'] ?? '');
            if ($d !== '') {
                $indexed[$d] = $arr;
            }
        }

        $history = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));

            // Prefer the Redis buffer row for today (most up-to-date, not yet flushed).
            $row = ($i === 0) ? $buffer->getForUser($userId, $date) : ($indexed[$date] ?? null);
            if ($row === null) {
                $row = $indexed[$date] ?? null;
            }

            $rx = $row ? (float) ($row['rx_today'] ?? $row['rx_mb'] ?? 0) : 0;
            $tx = $row ? (float) ($row['tx_today'] ?? $row['tx_mb'] ?? 0) : 0;

            $history[] = [
                'date'        => $date,
                'used_mb'     => round($rx + $tx, 2),
                'download_mb' => round($rx, 2),
                'upload_mb'   => round($tx, 2),
            ];
        }

        return $history;
    }

    private function calculateAverage(array $history, string $key): float
    {
        if (empty($history)) {
            return 0.0;
        }
        return round(array_sum(array_column($history, $key)) / count($history), 2);
    }

    private function resolveModelField($model, string $field, $default)
    {
        if (!$model) {
            return $default;
        }
        return is_object($model) ? ($model->{$field} ?? $default) : ($model[$field] ?? $default);
    }
}
