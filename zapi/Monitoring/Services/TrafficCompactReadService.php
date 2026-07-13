<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficCompactReadService
{
    private TrafficPathResolver $pathResolver;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
    }

    public function buildSnapshot(array $filters = []): array
    {
        $days = $this->readDays($filters);
        $overview = $this->buildOverview($days);
        $topEndpoints = $this->buildTopEndpoints($days, 15, $filters);
        $timeline = $this->buildTimeline($days);
        $comparison = $this->buildComparison($days);
        $meta = $this->readMeta();

        return [
            'generated_at' => gmdate('c'),
            'filters' => $filters,
            'overview' => $overview,
            'top_endpoints' => $topEndpoints,
            'timeline' => $timeline,
            'recent' => [],
            'device_summary' => [],
            'daily_summary' => $days,
            'comparison' => $comparison,
            'queue_stats' => $meta['queue'] ?? ['pending_files' => 0, 'pending_bytes' => 0],
        ];
    }

    private function readDays(array $filters): array
    {
        $dir = $this->pathResolver->compactDailyDir();
        if (!is_dir($dir)) {
            return [];
        }
        $files = @scandir($dir);
        if (!is_array($files)) {
            return [];
        }
        $rows = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || substr($file, -5) !== '.json') {
                continue;
            }
            $payload = $this->readJsonFile($dir . DIRECTORY_SEPARATOR . $file);
            $date = (string) ($payload['date'] ?? '');
            if ($date === '') {
                continue;
            }
            if (!empty($filters['from']) && strcmp($date, (new \DateTimeImmutable((string) $filters['from']))->format('Y-m-d')) < 0) {
                continue;
            }
            if (!empty($filters['to']) && strcmp($date, (new \DateTimeImmutable((string) $filters['to']))->format('Y-m-d')) > 0) {
                continue;
            }
            $rows[] = $payload;
        }
        usort($rows, static fn ($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
        return $rows;
    }

    private function buildOverview(array $days): array
    {
        $totals = [
            'total_hits' => 0,
            'api_hits' => 0,
            'web_hits' => 0,
            'errors' => 0,
            'avg_latency_ms' => 0,
            'p95_latency_ms' => 0,
        ];
        $latencySum = 0;
        foreach ($days as $day) {
            $dayTotals = $day['totals'] ?? [];
            $totals['total_hits'] += (int) ($dayTotals['hits'] ?? 0);
            $totals['api_hits'] += (int) ($dayTotals['api_hits'] ?? 0);
            $totals['web_hits'] += (int) ($dayTotals['web_hits'] ?? 0);
            $totals['errors'] += (int) ($dayTotals['errors'] ?? 0);
            $totals['p95_latency_ms'] = max($totals['p95_latency_ms'], (int) ($dayTotals['max_latency_ms'] ?? 0));
            $latencySum += (int) ($dayTotals['total_latency_ms'] ?? 0);
        }
        $totals['avg_latency_ms'] = $totals['total_hits'] > 0 ? (int) round($latencySum / $totals['total_hits']) : 0;
        $totals['error_rate_percent'] = $totals['total_hits'] > 0 ? round(($totals['errors'] * 100) / $totals['total_hits'], 2) : 0.0;
        return $totals;
    }

    private function buildTopEndpoints(array $days, int $limit, array $filters): array
    {
        $bucket = [];
        foreach ($days as $day) {
            foreach (($day['endpoints'] ?? []) as $path => $row) {
                if (!empty($filters['path_contains']) && stripos((string) $path, (string) $filters['path_contains']) === false) {
                    continue;
                }
                if (!isset($bucket[$path])) {
                    $bucket[$path] = ['path' => $path, 'hits' => 0, 'errors' => 0, 'max_latency_ms' => 0];
                }
                $bucket[$path]['hits'] += (int) ($row['hits'] ?? 0);
                $bucket[$path]['errors'] += (int) ($row['errors'] ?? 0);
                $bucket[$path]['max_latency_ms'] = max((int) $bucket[$path]['max_latency_ms'], (int) ($row['max_latency_ms'] ?? 0));
            }
        }
        usort($bucket, static fn ($a, $b) => $b['hits'] <=> $a['hits']);
        return array_slice($bucket, 0, max(1, $limit));
    }

    private function buildTimeline(array $days): array
    {
        $daily = [];
        $hourly = [];
        $monthly = [];
        foreach ($days as $day) {
            $date = (string) ($day['date'] ?? '');
            if ($date === '') {
                continue;
            }
            $daily[$date] = (int) (($day['totals']['hits'] ?? 0));
            $month = substr($date, 0, 7);
            $monthly[$month] = ($monthly[$month] ?? 0) + $daily[$date];
            foreach (($day['hours'] ?? []) as $h => $info) {
                $k = $date . ' ' . str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
                $hourly[$k] = (int) ($info['hits'] ?? 0);
            }
        }
        ksort($daily);
        ksort($hourly);
        ksort($monthly);
        return [
            'monthly' => $this->mapPeriod($monthly),
            'daily' => $this->mapPeriod($daily),
            'hourly' => $this->mapPeriod($hourly),
            'minute' => [],
        ];
    }

    private function buildComparison(array $days): array
    {
        if (count($days) < 2) {
            return [];
        }
        $today = (int) (($days[0]['totals']['hits'] ?? 0));
        $yesterday = (int) (($days[1]['totals']['hits'] ?? 0));
        $delta = $today - $yesterday;
        return [
            'today_date' => (string) ($days[0]['date'] ?? ''),
            'yesterday_date' => (string) ($days[1]['date'] ?? ''),
            'today_hits' => $today,
            'yesterday_hits' => $yesterday,
            'delta_hits' => $delta,
            'delta_percent' => $yesterday > 0 ? round(($delta * 100) / $yesterday, 2) : 0.0,
        ];
    }

    private function mapPeriod(array $bucket): array
    {
        $rows = [];
        foreach ($bucket as $period => $hits) {
            $rows[] = ['period' => $period, 'hits' => $hits];
        }
        return $rows;
    }

    private function readMeta(): array
    {
        return $this->readJsonFile($this->pathResolver->compactMetaFile());
    }

    private function readJsonFile(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

