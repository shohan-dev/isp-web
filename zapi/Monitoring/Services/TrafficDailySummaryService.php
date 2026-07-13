<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficDailySummaryService
{
    private TrafficPathResolver $pathResolver;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
    }

    public function update(array $record): void
    {
        $stamp = isset($record['created_at']) ? new \DateTimeImmutable((string) $record['created_at']) : new \DateTimeImmutable('now');
        $file = $this->pathResolver->indexDaySummaryFile($stamp);
        $this->ensureDir(dirname($file));

        $summary = $this->readJson($file);
        $summary['version'] = 1;
        $summary['date'] = $stamp->format('Y-m-d');
        $summary['updated_at'] = gmdate('c');
        $summary['totals'] = is_array($summary['totals'] ?? null) ? $summary['totals'] : [];
        $summary['top_endpoints'] = is_array($summary['top_endpoints'] ?? null) ? $summary['top_endpoints'] : [];
        $summary['device_summary'] = is_array($summary['device_summary'] ?? null) ? $summary['device_summary'] : [
            'source' => [],
            'device_type' => [],
            'device_os' => [],
            'device_browser' => [],
        ];

        $status = (int) ($record['status_code'] ?? 0);
        $duration = (int) ($record['duration_ms'] ?? 0);
        $isApi = (bool) ($record['is_api'] ?? false);
        $path = (string) ($record['path'] ?? '/');

        $summary['totals']['hits'] = ((int) ($summary['totals']['hits'] ?? 0)) + 1;
        $summary['totals']['api_hits'] = ((int) ($summary['totals']['api_hits'] ?? 0)) + ($isApi ? 1 : 0);
        $summary['totals']['web_hits'] = ((int) ($summary['totals']['web_hits'] ?? 0)) + ($isApi ? 0 : 1);
        $summary['totals']['errors'] = ((int) ($summary['totals']['errors'] ?? 0)) + ($status >= 400 ? 1 : 0);
        $summary['totals']['total_latency_ms'] = ((int) ($summary['totals']['total_latency_ms'] ?? 0)) + $duration;
        $summary['totals']['avg_latency_ms'] = (int) round(
            ((int) ($summary['totals']['total_latency_ms'] ?? 0)) / max(1, (int) ($summary['totals']['hits'] ?? 1))
        );

        $top = $summary['top_endpoints'][$path] ?? ['hits' => 0, 'errors' => 0, 'total_latency_ms' => 0];
        $top['hits']++;
        $top['errors'] += $status >= 400 ? 1 : 0;
        $top['total_latency_ms'] += $duration;
        $summary['top_endpoints'][$path] = $top;

        $source = (string) ($record['client_source'] ?? ($isApi ? 'app' : 'web'));
        $type = (string) ($record['device_type'] ?? 'unknown');
        $os = (string) ($record['device_os'] ?? 'unknown');
        $browser = (string) ($record['device_browser'] ?? 'unknown');
        $summary['device_summary']['source'][$source] = ((int) ($summary['device_summary']['source'][$source] ?? 0)) + 1;
        $summary['device_summary']['device_type'][$type] = ((int) ($summary['device_summary']['device_type'][$type] ?? 0)) + 1;
        $summary['device_summary']['device_os'][$os] = ((int) ($summary['device_summary']['device_os'][$os] ?? 0)) + 1;
        $summary['device_summary']['device_browser'][$browser] = ((int) ($summary['device_summary']['device_browser'][$browser] ?? 0)) + 1;

        $this->writeJson($file, $summary);
    }

    public function readRange(array $filters): array
    {
        $dir = $this->pathResolver->summaryBaseDir();
        if (!is_dir($dir)) {
            return [];
        }
        $files = @scandir($dir);
        if (!is_array($files)) {
            return [];
        }

        $rows = [];
        foreach ($files as $fileName) {
            if ($fileName === '.' || $fileName === '..' || substr($fileName, -5) !== '.json') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $fileName;
            $item = $this->readJson($path);
            $date = (string) ($item['date'] ?? '');
            if ($date === '') {
                continue;
            }
            if (!empty($filters['from']) && strcmp($date, (new \DateTimeImmutable((string) $filters['from']))->format('Y-m-d')) < 0) {
                continue;
            }
            if (!empty($filters['to']) && strcmp($date, (new \DateTimeImmutable((string) $filters['to']))->format('Y-m-d')) > 0) {
                continue;
            }
            $rows[] = $item;
        }

        usort($rows, static fn ($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
        return $rows;
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    private function readJson(string $file): array
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

    private function writeJson(string $file, array $payload): void
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return;
        }
        @file_put_contents($file, $json . PHP_EOL, LOCK_EX);
    }
}

