<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficAggregateStoreService
{
    private TrafficPathResolver $pathResolver;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
    }

    public function update(array $record): void
    {
        $stamp = isset($record['created_at']) ? new \DateTimeImmutable((string) $record['created_at']) : new \DateTimeImmutable('now');
        $minuteKey = $stamp->format('Y-m-d H:i');

        $this->updateMinuteAgg($record, $stamp, $minuteKey);
        $this->updateEndpointAgg($record, $stamp, $minuteKey);
        $this->updateDeviceAgg($record, $stamp, $minuteKey);
        $this->touchMeta($stamp);
    }

    public function readMinuteRows(array $filters = []): array
    {
        $rows = [];
        foreach ($this->listJsonFiles(dirname($this->pathResolver->indexMinuteAggFile(new \DateTimeImmutable('now')))) as $file) {
            $decoded = $this->readJsonFile($file);
            foreach (($decoded['minutes'] ?? []) as $period => $item) {
                if (!is_array($item) || !$this->matchesPeriod($period, $filters)) {
                    continue;
                }
                $item['period'] = $period;
                $rows[] = $item;
            }
        }
        usort($rows, static fn ($a, $b) => strcmp((string) ($a['period'] ?? ''), (string) ($b['period'] ?? '')));
        return $rows;
    }

    public function readEndpointRows(array $filters = []): array
    {
        $stats = [];
        foreach ($this->listJsonFiles(dirname($this->pathResolver->indexEndpointAggFile(new \DateTimeImmutable('now')))) as $file) {
            $decoded = $this->readJsonFile($file);
            foreach (($decoded['minutes'] ?? []) as $period => $pathMap) {
                if (!is_array($pathMap) || !$this->matchesPeriod($period, $filters)) {
                    continue;
                }
                foreach ($pathMap as $path => $item) {
                    if (!empty($filters['path_contains']) && stripos((string) $path, (string) $filters['path_contains']) === false) {
                        continue;
                    }
                    if (!isset($stats[$path])) {
                        $stats[$path] = ['path' => $path, 'hits' => 0, 'errors' => 0, 'total_latency_ms' => 0];
                    }
                    $stats[$path]['hits'] += (int) ($item['hits'] ?? 0);
                    $stats[$path]['errors'] += (int) ($item['errors'] ?? 0);
                    $stats[$path]['total_latency_ms'] += (int) ($item['total_latency_ms'] ?? 0);
                }
            }
        }

        foreach ($stats as &$row) {
            $row['avg_latency_ms'] = $row['hits'] > 0 ? (int) round($row['total_latency_ms'] / $row['hits']) : 0;
            unset($row['total_latency_ms']);
        }
        unset($row);

        usort($stats, static fn ($a, $b) => $b['hits'] <=> $a['hits']);
        return $stats;
    }

    public function readDeviceRows(array $filters = []): array
    {
        $totals = [
            'source' => [],
            'device_type' => [],
            'device_os' => [],
            'device_browser' => [],
        ];
        foreach ($this->listJsonFiles(dirname($this->pathResolver->indexDeviceAggFile(new \DateTimeImmutable('now')))) as $file) {
            $decoded = $this->readJsonFile($file);
            foreach (($decoded['minutes'] ?? []) as $period => $group) {
                if (!is_array($group) || !$this->matchesPeriod($period, $filters)) {
                    continue;
                }
                foreach ($totals as $bucket => $values) {
                    foreach (($group[$bucket] ?? []) as $key => $count) {
                        $totals[$bucket][$key] = ($totals[$bucket][$key] ?? 0) + (int) $count;
                    }
                }
            }
        }
        return $totals;
    }

    private function updateMinuteAgg(array $record, \DateTimeImmutable $stamp, string $minuteKey): void
    {
        $file = $this->pathResolver->indexMinuteAggFile($stamp);
        $this->ensureDir(dirname($file));
        $decoded = $this->readJsonFile($file);
        $decoded['minutes'] = is_array($decoded['minutes'] ?? null) ? $decoded['minutes'] : [];

        $minute = is_array($decoded['minutes'][$minuteKey] ?? null) ? $decoded['minutes'][$minuteKey] : [
            'hits' => 0,
            'api_hits' => 0,
            'web_hits' => 0,
            'errors' => 0,
            'errors_4xx' => 0,
            'errors_5xx' => 0,
            'total_latency_ms' => 0,
            'max_latency_ms' => 0,
        ];

        $status = (int) ($record['status_code'] ?? 0);
        $duration = (int) ($record['duration_ms'] ?? 0);
        $minute['hits']++;
        $minute['api_hits'] += (bool) ($record['is_api'] ?? false) ? 1 : 0;
        $minute['web_hits'] += (bool) ($record['is_web'] ?? true) ? 1 : 0;
        $minute['errors'] += $status >= 400 ? 1 : 0;
        $minute['errors_4xx'] += ($status >= 400 && $status < 500) ? 1 : 0;
        $minute['errors_5xx'] += $status >= 500 ? 1 : 0;
        $minute['total_latency_ms'] += $duration;
        $minute['max_latency_ms'] = max((int) ($minute['max_latency_ms'] ?? 0), $duration);
        $minute['avg_latency_ms'] = $minute['hits'] > 0 ? (int) round($minute['total_latency_ms'] / $minute['hits']) : 0;

        $decoded['version'] = 1;
        $decoded['minutes'][$minuteKey] = $minute;
        $this->writeJsonFile($file, $decoded);
    }

    private function updateEndpointAgg(array $record, \DateTimeImmutable $stamp, string $minuteKey): void
    {
        $file = $this->pathResolver->indexEndpointAggFile($stamp);
        $this->ensureDir(dirname($file));
        $decoded = $this->readJsonFile($file);
        $decoded['minutes'] = is_array($decoded['minutes'] ?? null) ? $decoded['minutes'] : [];

        $path = (string) ($record['path'] ?? '/');
        $status = (int) ($record['status_code'] ?? 0);
        $duration = (int) ($record['duration_ms'] ?? 0);

        $existing = $decoded['minutes'][$minuteKey][$path] ?? ['hits' => 0, 'errors' => 0, 'total_latency_ms' => 0];
        $existing['hits']++;
        $existing['errors'] += $status >= 400 ? 1 : 0;
        $existing['total_latency_ms'] += $duration;
        $decoded['minutes'][$minuteKey][$path] = $existing;
        $decoded['version'] = 1;
        $this->writeJsonFile($file, $decoded);
    }

    private function updateDeviceAgg(array $record, \DateTimeImmutable $stamp, string $minuteKey): void
    {
        $file = $this->pathResolver->indexDeviceAggFile($stamp);
        $this->ensureDir(dirname($file));
        $decoded = $this->readJsonFile($file);
        $decoded['minutes'] = is_array($decoded['minutes'] ?? null) ? $decoded['minutes'] : [];
        $bucket = is_array($decoded['minutes'][$minuteKey] ?? null) ? $decoded['minutes'][$minuteKey] : [];

        $source = (string) ($record['client_source'] ?? ((bool) ($record['is_api'] ?? false) ? 'app' : 'web'));
        $type = (string) ($record['device_type'] ?? 'unknown');
        $os = (string) ($record['device_os'] ?? 'unknown');
        $browser = (string) ($record['device_browser'] ?? 'unknown');

        $bucket['source'][$source] = ((int) ($bucket['source'][$source] ?? 0)) + 1;
        $bucket['device_type'][$type] = ((int) ($bucket['device_type'][$type] ?? 0)) + 1;
        $bucket['device_os'][$os] = ((int) ($bucket['device_os'][$os] ?? 0)) + 1;
        $bucket['device_browser'][$browser] = ((int) ($bucket['device_browser'][$browser] ?? 0)) + 1;
        $decoded['version'] = 1;
        $decoded['minutes'][$minuteKey] = $bucket;
        $this->writeJsonFile($file, $decoded);
    }

    private function touchMeta(\DateTimeImmutable $stamp): void
    {
        $file = $this->pathResolver->indexMetaFile();
        $this->ensureDir(dirname($file));
        $decoded = $this->readJsonFile($file);
        $decoded['version'] = 1;
        $decoded['updated_at'] = gmdate('c');
        $decoded['last_minute'] = $stamp->format('Y-m-d H:i');
        $this->writeJsonFile($file, $decoded);
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

    private function writeJsonFile(string $file, array $payload): void
    {
        $tmp = $file . '.tmp';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return;
        }
        if (@file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) {
            return;
        }
        @rename($tmp, $file);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    private function listJsonFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $items = @scandir($dir);
        if (!is_array($items)) {
            return [];
        }
        $files = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_file($path) && substr($item, -5) === '.json') {
                $files[] = $path;
            }
        }
        sort($files);
        return $files;
    }

    private function matchesPeriod(string $period, array $filters): bool
    {
        if (!empty($filters['from']) && strcmp($period, (new \DateTimeImmutable((string) $filters['from']))->format('Y-m-d H:i')) < 0) {
            return false;
        }
        if (!empty($filters['to']) && strcmp($period, (new \DateTimeImmutable((string) $filters['to']))->format('Y-m-d H:i')) > 0) {
            return false;
        }
        return true;
    }
}

