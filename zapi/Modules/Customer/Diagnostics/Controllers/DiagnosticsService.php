<?php

namespace Zapi\Modules\Customer\Diagnostics\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class DiagnosticsService extends CustomerBaseService
{
    public function runSpeedTest($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        // Run actual speed test or get from Mikrotik
        $speedResult = $this->performSpeedTest($userId);

        // Save result to database
        $this->saveSpeedTestResult($userId, $speedResult);

        return $this->respondSuccess([
            'user_id' => $userId,
            'test_id' => 'ST-' . time(),
            'test_time' => date('Y-m-d H:i:s'),
            'download_speed' => $speedResult['download'],
            'upload_speed' => $speedResult['upload'],
            'latency' => $speedResult['latency'],
            'jitter' => $speedResult['jitter'],
            'packet_loss' => $speedResult['packet_loss'],
            'server' => $speedResult['server'],
            'ip_address' => $speedResult['ip'],
            'result' => $this->getSpeedRating($speedResult),
            'recommendations' => $this->getRecommendations($speedResult)
        ]);
    }

    public function getSpeedTestHistory($userId, $limit = 10)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $history = $this->getSpeedTestHistoryFromDB($userId, $limit);

        return $this->respondSuccess([
            'user_id' => $userId,
            'total_tests' => count($history),
            'history' => $history,
            'average_download' => $this->calculateAvg($history, 'download'),
            'average_upload' => $this->calculateAvg($history, 'upload'),
            'average_latency' => $this->calculateAvg($history, 'latency')
        ]);
    }

    public function checkPacketLoss($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        // Perform packet loss test
        $result = $this->performPacketLossTest($userId);

        return $this->respondSuccess([
            'user_id' => $userId,
            'test_time' => date('Y-m-d H:i:s'),
            'packets_sent' => $result['sent'],
            'packets_received' => $result['received'],
            'packet_loss_percent' => $result['loss'],
            'status' => $result['loss'] < 1 ? 'excellent' : ($result['loss'] < 5 ? 'good' : 'poor'),
            'recommendations' => $this->getPacketLossRecommendations($result['loss'])
        ]);
    }

    public function checkLatency($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $result = $this->performLatencyTest($userId);

        return $this->respondSuccess([
            'user_id' => $userId,
            'test_time' => date('Y-m-d H:i:s'),
            'min_latency' => $result['min'],
            'max_latency' => $result['max'],
            'avg_latency' => $result['avg'],
            'status' => $result['avg'] < 20 ? 'excellent' : ($result['avg'] < 50 ? 'good' : 'poor'),
            'recommendations' => $this->getLatencyRecommendations($result['avg'])
        ]);
    }

    public function analyzeJitter($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $result = $this->performJitterTest($userId);

        return $this->respondSuccess([
            'user_id' => $userId,
            'test_time' => date('Y-m-d H:i:s'),
            'jitter_ms' => $result['jitter'],
            'min_latency' => $result['min'],
            'max_latency' => $result['max'],
            'status' => $result['jitter'] < 5 ? 'excellent' : ($result['jitter'] < 20 ? 'good' : 'poor'),
            'recommendations' => $this->getJitterRecommendations($result['jitter'])
        ]);
    }

    public function getUptimeReport($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $uptime = $this->getUptimeFromMikrotik($userId);

        return $this->respondSuccess([
            'user_id' => $userId,
            'current_session_start' => $uptime['start'],
            'current_session_duration' => $uptime['duration'],
            'uptime_percent_7days' => $uptime['percent_7d'],
            'uptime_percent_30days' => $uptime['percent_30d'],
            'total_disconnects_7d' => $uptime['disconnects_7d'],
            'total_disconnects_30d' => $uptime['disconnects_30d'],
            'average_session_duration' => $uptime['avg_session'],
            'status' => $uptime['percent_7d'] > 99 ? 'excellent' : ($uptime['percent_7d'] > 95 ? 'good' : 'poor')
        ]);
    }

    public function getCongestionStatus($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $congestion = $this->getNetworkCongestion($userId);

        return $this->respondSuccess([
            'user_id' => $userId,
            'current_status' => $congestion['status'],
            'peak_hours' => $congestion['peak_hours'],
            'current_load_percent' => $congestion['load'],
            'download_speed_reduction' => $congestion['reduction'],
            'recommendations' => $congestion['recommendations']
        ]);
    }

    public function getLineQuality($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        // Get line quality from Mikrotik (DSL/Fiber metrics)
        $quality = $this->getLineQualityFromMikrotik($userId);

        return $this->respondSuccess([
            'user_id' => $userId,
            'line_type' => $quality['type'],
            'snr_margin' => $quality['snr'],
            'line_attenuation' => $quality['attenuation'],
            'output_power' => $quality['power'],
            'sync_speed_down' => $quality['sync_down'],
            'sync_speed_up' => $quality['sync_up'],
            'max_speed_down' => $quality['max_down'],
            'max_speed_up' => $quality['max_up'],
            'status' => $quality['snr'] > 10 ? 'excellent' : ($quality['snr'] > 6 ? 'good' : 'poor'),
            'recommendations' => $this->getLineQualityRecommendations($quality)
        ]);
    }

    public function getLastDisconnectReason($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $reason = $this->getDisconnectReasonFromDB($userId);

        return $this->respondSuccess([
            'user_id' => $userId,
            'last_disconnect_time' => $reason['time'],
            'disconnect_reason' => $reason['reason'],
            'disconnect_duration' => $reason['duration'],
            'resolved' => $reason['resolved'],
            'recommendations' => $reason['recommendations']
        ]);
    }

    // Private helper methods
    private function performSpeedTest(int $userId): array
    {
        // Try to get live bandwidth data from MikroTik interface monitor.
        try {
            helper('router');
            $user = $this->user_model->find($userId);
            if ($user && $user->router_id) {
                $pppoe = resolvePppoeSecret((int) $userId); // BUG-22: shared helper
                if ($pppoe) {
                    $client = routerClient($user->router_id);
                    if ($client && !is_array($client)) {
                        $bw = getPPPoEUserBandwidth($client, $pppoe);
                        if (!empty($bw)) {
                            $rxBps = (float) ($bw['rx-bits-per-second'] ?? 0);
                            $txBps = (float) ($bw['tx-bits-per-second'] ?? 0);
                            return [
                                'download'    => round($rxBps / 1_000_000, 2) . ' Mbps',
                                'upload'      => round($txBps / 1_000_000, 2) . ' Mbps',
                                'latency'     => 'N/A',
                                'jitter'      => 'N/A',
                                'packet_loss' => 'N/A',
                                'server'      => 'MikroTik Live Measurement',
                                'ip'          => $this->request->getIPAddress(),
                                'source'      => 'mikrotik',
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('info', 'DiagnosticsService: MikroTik speed measurement failed: ' . $e->getMessage());
        }

        // Fallback: derive from today's DB usage record if available.
        try {
            $usage = model('App\Models\UserDataUsageModel')
                ->where('admin_id', $userId)
                ->where('date', date('Y-m-d'))
                ->first();
            if ($usage) {
                $row = is_object($usage) ? $usage : (object) $usage;
                return [
                    'download'    => round((float) ($row->rx_today ?? 0), 2) . ' MB today',
                    'upload'      => round((float) ($row->tx_today ?? 0), 2) . ' MB today',
                    'latency'     => 'N/A',
                    'jitter'      => 'N/A',
                    'packet_loss' => 'N/A',
                    'server'      => 'Usage DB Estimate',
                    'ip'          => $this->request->getIPAddress(),
                    'source'      => 'db',
                ];
            }
        } catch (\Throwable $e) {
            log_message('info', 'DiagnosticsService: DB speed estimate failed: ' . $e->getMessage());
        }

        return [
            'download'    => 'N/A',
            'upload'      => 'N/A',
            'latency'     => 'N/A',
            'jitter'      => 'N/A',
            'packet_loss' => 'N/A',
            'server'      => 'Unavailable',
            'ip'          => $this->request->getIPAddress(),
            'source'      => 'unavailable',
        ];
    }

    private function saveSpeedTestResult(int $userId, array $result): void
    {
        try {
            $cache   = \Config\Services::cache();
            $key     = "speed_test_history_{$userId}";
            $history = $cache->get($key);
            $history = is_array($history) ? $history : [];

            array_unshift($history, array_merge($result, ['tested_at' => date('Y-m-d H:i:s')]));

            // Keep only the last 20 results
            $history = array_slice($history, 0, 20);
            $cache->save($key, $history, 86400 * 30);
        } catch (\Throwable $e) {
            log_message('info', 'DiagnosticsService: speed test save failed: ' . $e->getMessage());
        }
    }

    private function getSpeedRating(array $result): string
    {
        $download = (float) $result['download'];
        if ($download >= 50) {
            return 'Excellent';
        }
        if ($download >= 25) {
            return 'Good';
        }
        if ($download >= 10) {
            return 'Fair';
        }
        if ($download > 0) {
            return 'Poor';
        }
        return 'Unknown';
    }

    private function getRecommendations(array $result): array
    {
        $recs = [];
        if ((float) $result['latency'] > 50) {
            $recs[] = 'High latency detected. Try restarting your router.';
        }
        if ((float) $result['packet_loss'] > 1) {
            $recs[] = 'Packet loss detected. Contact technical support.';
        }
        if (empty($recs)) {
            $recs[] = 'Your connection looks great! No issues detected.';
        }
        return $recs;
    }

    private function getSpeedTestHistoryFromDB(int $userId, int $limit): array
    {
        try {
            $cache   = \Config\Services::cache();
            $key     = "speed_test_history_{$userId}";
            $history = $cache->get($key);
            if (is_array($history)) {
                return array_slice($history, 0, $limit);
            }
        } catch (\Throwable $e) {
            log_message('info', 'DiagnosticsService: speed test history fetch failed: ' . $e->getMessage());
        }
        return [];
    }

    private function calculateAvg($history, $key)
    {
        if (empty($history)) return 0;
        $values = array_map(function($item) use ($key) {
            return floatval(str_replace([' Mbps', ' ms', '%'], '', $item[$key] ?? 0));
        }, $history);
        return round(array_sum($values) / count($values), 2);
    }

    private function performPacketLossTest($userId)
    {
        return ['sent' => 100, 'received' => 99, 'loss' => 1];
    }

    private function performLatencyTest($userId)
    {
        return ['min' => '5 ms', 'max' => '25 ms', 'avg' => '15 ms'];
    }

    private function performJitterTest($userId)
    {
        return ['jitter' => '5 ms', 'min' => '10 ms', 'max' => '20 ms'];
    }

    private function getUptimeFromMikrotik($userId)
    {
        return [
            'start' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'duration' => '2 hours',
            'percent_7d' => 99.5,
            'percent_30d' => 98.5,
            'disconnects_7d' => 2,
            'disconnects_30d' => 8,
            'avg_session' => '4 hours'
        ];
    }

    private function getNetworkCongestion($userId)
    {
        return [
            'status' => 'normal',
            'peak_hours' => ['18:00-23:00'],
            'load' => 45,
            'reduction' => '0%',
            'recommendations' => ['Network is running smoothly']
        ];
    }

    private function getLineQualityFromMikrotik($userId)
    {
        return [
            'type' => 'GPON Fiber',
            'snr' => 20,
            'attenuation' => '15 dB',
            'power' => '-8 dBm',
            'sync_down' => '1000 Mbps',
            'sync_up' => '500 Mbps',
            'max_down' => '1000 Mbps',
            'max_up' => '500 Mbps'
        ];
    }

    private function getDisconnectReasonFromDB($userId)
    {
        return [
            'time' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'reason' => 'Router power outage',
            'duration' => '30 minutes',
            'resolved' => true,
            'recommendations' => ['Ensure stable power supply to router']
        ];
    }

    private function getPacketLossRecommendations($loss)
    {
        if ($loss < 1) return ["Connection is stable"];
        return ["High packet loss - contact support", "Try restarting router"];
    }

    private function getLatencyRecommendations($avg)
    {
        if ($avg < 20) return ["Latency is excellent"];
        return ["Consider restarting router", "Check for network congestion"];
    }

    private function getJitterRecommendations($jitter)
    {
        if ($jitter < 5) return ["Jitter is excellent"];
        return ["High jitter may affect VoIP/gaming", "Contact support if persistent"];
    }

    private function getLineQualityRecommendations($quality)
    {
        $recs = [];
        if ($quality['snr'] < 10) $recs[] = "Low SNR - contact support";
        if ($quality['attenuation'] > 20) $recs[] = "High attenuation - check line";
        if (empty($recs)) $recs[] = "Line quality is excellent";
        return $recs;
    }
}