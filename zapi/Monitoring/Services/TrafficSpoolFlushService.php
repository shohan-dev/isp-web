<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficSpoolFlushService
{
    private TrafficPathResolver $pathResolver;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
    }

    public function flush(int $maxFiles = 120): array
    {
        $files = array_slice($this->listSpoolFiles(), 0, max(1, $maxFiles));
        if ($files === []) {
            $meta = $this->updateMeta(0, 0, 0);
            return ['processed_files' => 0, 'processed_events' => 0, 'pending_files' => (int) ($meta['queue']['pending_files'] ?? 0)];
        }

        $dayBuckets = [];
        $processedEvents = 0;
        $processedFiles = 0;

        foreach ($files as $file) {
            $events = $this->readJsonLines($file);
            foreach ($events as $event) {
                $day = $this->resolveDay($event);
                if (!isset($dayBuckets[$day])) {
                    $dayBuckets[$day] = [];
                }
                $dayBuckets[$day][] = $event;
                $processedEvents++;
            }

            @unlink($file);
            $processedFiles++;
        }

        foreach ($dayBuckets as $day => $events) {
            $this->applyDayEvents($day, $events);
        }

        $pendingFiles = count($this->listSpoolFiles());
        $pendingBytes = $this->directorySize($this->pathResolver->spoolBaseDir());
        $this->updateMeta($processedFiles, $processedEvents, $pendingFiles, $pendingBytes);

        return [
            'processed_files' => $processedFiles,
            'processed_events' => $processedEvents,
            'pending_files' => $pendingFiles,
            'pending_bytes' => $pendingBytes,
        ];
    }

    public function dispose(int $maxSpoolAgeHours = 48): array
    {
        $deleted = 0;
        $cutoff = (new \DateTimeImmutable('now'))->modify('-' . max(1, $maxSpoolAgeHours) . ' hours');
        foreach ($this->listSpoolFiles() as $file) {
            $mtime = @filemtime($file);
            if ($mtime === false) {
                continue;
            }
            if ((new \DateTimeImmutable('@' . $mtime)) < $cutoff) {
                @unlink($file);
                $deleted++;
            }
        }
        return ['deleted_spool_files' => $deleted];
    }

    private function applyDayEvents(string $day, array $events): void
    {
        $stamp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $day . ' 00:00:00') ?: new \DateTimeImmutable('now');
        $file = $this->pathResolver->compactDailyFile($stamp);
        $this->ensureDir(dirname($file));
        $daily = $this->readJsonFile($file);

        $daily['version'] = 1;
        $daily['date'] = $day;
        $daily['updated_at'] = gmdate('c');
        $daily['totals'] = is_array($daily['totals'] ?? null) ? $daily['totals'] : [
            'hits' => 0,
            'api_hits' => 0,
            'web_hits' => 0,
            'errors' => 0,
            'max_latency_ms' => 0,
            'total_latency_ms' => 0,
            'avg_latency_ms' => 0,
        ];
        $daily['hours'] = is_array($daily['hours'] ?? null) ? $daily['hours'] : [];
        $daily['endpoints'] = is_array($daily['endpoints'] ?? null) ? $daily['endpoints'] : [];

        foreach ($events as $event) {
            $status = (int) ($event['status_code'] ?? 0);
            $duration = (int) ($event['duration_ms'] ?? 0);
            $isApi = (bool) ($event['is_api'] ?? false);
            $path = (string) ($event['path'] ?? '/');
            $hour = $this->resolveHour($event);

            $daily['totals']['hits']++;
            $daily['totals']['api_hits'] += $isApi ? 1 : 0;
            $daily['totals']['web_hits'] += $isApi ? 0 : 1;
            $daily['totals']['errors'] += $status >= 400 ? 1 : 0;
            $daily['totals']['max_latency_ms'] = max((int) $daily['totals']['max_latency_ms'], $duration);
            $daily['totals']['total_latency_ms'] += $duration;

            if (!isset($daily['hours'][$hour])) {
                $daily['hours'][$hour] = ['hits' => 0, 'errors' => 0, 'max_latency_ms' => 0];
            }
            $daily['hours'][$hour]['hits']++;
            $daily['hours'][$hour]['errors'] += $status >= 400 ? 1 : 0;
            $daily['hours'][$hour]['max_latency_ms'] = max((int) $daily['hours'][$hour]['max_latency_ms'], $duration);

            if (!isset($daily['endpoints'][$path])) {
                $daily['endpoints'][$path] = ['hits' => 0, 'errors' => 0, 'max_latency_ms' => 0];
            }
            $daily['endpoints'][$path]['hits']++;
            $daily['endpoints'][$path]['errors'] += $status >= 400 ? 1 : 0;
            $daily['endpoints'][$path]['max_latency_ms'] = max((int) $daily['endpoints'][$path]['max_latency_ms'], $duration);
        }

        // Keep compact store small and permanent: retain hottest endpoints only.
        if (count($daily['endpoints']) > 1000) {
            uasort($daily['endpoints'], static fn ($a, $b) => ((int) ($b['hits'] ?? 0)) <=> ((int) ($a['hits'] ?? 0)));
            $daily['endpoints'] = array_slice($daily['endpoints'], 0, 1000, true);
        }

        $hits = max(1, (int) ($daily['totals']['hits'] ?? 1));
        $daily['totals']['avg_latency_ms'] = (int) round(((int) ($daily['totals']['total_latency_ms'] ?? 0)) / $hits);
        $this->writeJsonFile($file, $daily);
    }

    private function updateMeta(int $processedFiles, int $processedEvents, int $pendingFiles, int $pendingBytes = 0): array
    {
        $file = $this->pathResolver->compactMetaFile();
        $this->ensureDir(dirname($file));
        $meta = $this->readJsonFile($file);
        $meta['version'] = 1;
        $meta['updated_at'] = gmdate('c');
        $meta['last_flush'] = [
            'processed_files' => $processedFiles,
            'processed_events' => $processedEvents,
            'at' => gmdate('c'),
        ];
        $meta['queue'] = [
            'pending_files' => $pendingFiles,
            'pending_bytes' => $pendingBytes,
        ];
        $this->writeJsonFile($file, $meta);
        return $meta;
    }

    private function resolveDay(array $event): string
    {
        try {
            $dt = new \DateTimeImmutable((string) ($event['created_at'] ?? 'now'));
        } catch (\Throwable $e) {
            $dt = new \DateTimeImmutable('now');
        }
        return $dt->format('Y-m-d');
    }

    private function resolveHour(array $event): string
    {
        try {
            $dt = new \DateTimeImmutable((string) ($event['created_at'] ?? 'now'));
        } catch (\Throwable $e) {
            $dt = new \DateTimeImmutable('now');
        }
        return $dt->format('H');
    }

    private function listSpoolFiles(): array
    {
        $base = $this->pathResolver->spoolBaseDir();
        if (!is_dir($base)) {
            return [];
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );
        $files = [];
        foreach ($iterator as $item) {
            if ($item->isFile() && substr($item->getFilename(), -6) === '.jsonl') {
                $files[] = $item->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    private function readJsonLines(string $file): array
    {
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }
        $rows = [];
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }
        fclose($handle);
        return $rows;
    }

    private function directorySize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $size += (int) $item->getSize();
            }
        }
        return $size;
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
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return;
        }
        @file_put_contents($file, $json . PHP_EOL, LOCK_EX);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}

