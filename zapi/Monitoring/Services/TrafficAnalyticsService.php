<?php

namespace Zapi\Monitoring\Services;

class TrafficAnalyticsService
{
    public function overview(array $rows): array
    {
        $total = count($rows);
        $apiHits = 0;
        $webHits = 0;
        $errors = 0;
        $durations = [];

        foreach ($rows as $row) {
            $isApi = (bool) ($row['is_api'] ?? false);
            $status = (int) ($row['status_code'] ?? 0);
            $duration = (int) ($row['duration_ms'] ?? 0);
            $durations[] = $duration;
            $isApi ? $apiHits++ : $webHits++;
            if ($status >= 400) {
                $errors++;
            }
        }

        sort($durations);
        $avg = $total > 0 ? (int) round(array_sum($durations) / $total) : 0;
        $p95Index = $total > 0 ? (int) floor(($total - 1) * 0.95) : 0;
        $p95 = $total > 0 ? (int) ($durations[$p95Index] ?? 0) : 0;
        $errorRate = $total > 0 ? round(($errors * 100) / $total, 2) : 0.0;

        return [
            'total_hits' => $total,
            'api_hits' => $apiHits,
            'web_hits' => $webHits,
            'errors' => $errors,
            'error_rate_percent' => $errorRate,
            'avg_latency_ms' => $avg,
            'p95_latency_ms' => $p95,
        ];
    }

    public function topEndpoints(array $rows, int $limit = 10): array
    {
        $stats = [];
        foreach ($rows as $row) {
            $path = (string) ($row['path'] ?? '/');
            if (!isset($stats[$path])) {
                $stats[$path] = [
                    'path' => $path,
                    'hits' => 0,
                    'errors' => 0,
                    'total_latency_ms' => 0,
                    'avg_latency_ms' => 0,
                ];
            }

            $stats[$path]['hits']++;
            $stats[$path]['total_latency_ms'] += (int) ($row['duration_ms'] ?? 0);
            if ((int) ($row['status_code'] ?? 0) >= 400) {
                $stats[$path]['errors']++;
            }
        }

        foreach ($stats as &$item) {
            $item['avg_latency_ms'] = $item['hits'] > 0
                ? (int) round($item['total_latency_ms'] / $item['hits'])
                : 0;
            unset($item['total_latency_ms']);
        }
        unset($item);

        usort($stats, static fn ($a, $b) => $b['hits'] <=> $a['hits']);
        return array_slice($stats, 0, $limit);
    }

    public function timeline(array $rows): array
    {
        $monthly = [];
        $daily = [];
        $hourly = [];
        $minute = [];

        foreach ($rows as $row) {
            $dt = !empty($row['created_at']) ? new \DateTimeImmutable((string) $row['created_at']) : new \DateTimeImmutable('now');
            $monthKey = $dt->format('Y-m');
            $dayKey = $dt->format('Y-m-d');
            $hourKey = $dt->format('Y-m-d H:00');
            $minuteKey = $dt->format('Y-m-d H:i');

            $monthly[$monthKey] = ($monthly[$monthKey] ?? 0) + 1;
            $daily[$dayKey] = ($daily[$dayKey] ?? 0) + 1;
            $hourly[$hourKey] = ($hourly[$hourKey] ?? 0) + 1;
            $minute[$minuteKey] = ($minute[$minuteKey] ?? 0) + 1;
        }

        ksort($monthly);
        ksort($daily);
        ksort($hourly);
        ksort($minute);

        return [
            'monthly' => $this->mapTimeline($monthly),
            'daily' => $this->mapTimeline($daily),
            'hourly' => $this->mapTimeline($hourly),
            'minute' => $this->mapTimeline($minute),
        ];
    }

    private function mapTimeline(array $bucket): array
    {
        $rows = [];
        foreach ($bucket as $period => $hits) {
            $rows[] = [
                'period' => $period,
                'hits' => $hits,
            ];
        }

        return $rows;
    }
}

