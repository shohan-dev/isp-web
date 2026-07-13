<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Config\TrafficMonitorConfig;
use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficBudgetGuardService
{
    private TrafficPathResolver $pathResolver;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
    }

    public function canWrite(array $record, int $lineBytes): bool
    {
        $stamp = isset($record['created_at']) ? new \DateTimeImmutable((string) $record['created_at']) : new \DateTimeImmutable('now');
        $budgetFile = $this->pathResolver->indexBudgetMetaFile($stamp);
        $this->ensureDir(dirname($budgetFile));

        $payload = $this->readJson($budgetFile);
        $current = (int) ($payload['raw_bytes'] ?? 0);
        $max = TrafficMonitorConfig::dailyMaxBytes();
        $next = $current + max(1, $lineBytes);

        // Keep non-noisy/error records; throttle noisy successes first.
        $isError = (int) ($record['status_code'] ?? 0) >= 400;
        $isNoisy = (bool) ($record['is_noise'] ?? false);
        if (!$isError && $isNoisy && $next > $max) {
            return false;
        }

        if ($next > $max) {
            return false;
        }

        $payload['version'] = 1;
        $payload['date'] = $stamp->format('Y-m-d');
        $payload['raw_bytes'] = $next;
        $payload['max_bytes'] = $max;
        $payload['updated_at'] = gmdate('c');
        $this->writeJson($budgetFile, $payload);
        return true;
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

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}

