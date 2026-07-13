<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficRecentStoreService
{
    private const MAX_RECENT_LINES = 5000;

    private TrafficPathResolver $pathResolver;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
    }

    public function append(array $record): void
    {
        $file = $this->pathResolver->indexRecentFile();
        $this->ensureDir(dirname($file));

        $json = json_encode($record, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }

        @file_put_contents($file, $json . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->trimRecent($file);
    }

    public function latest(int $limit = 100, array $filters = []): array
    {
        $file = $this->pathResolver->indexRecentFile();
        if (!is_file($file)) {
            return [];
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $rows = [];
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $decoded = json_decode((string) $lines[$i], true);
            if (!is_array($decoded)) {
                continue;
            }
            if (!$this->matchesFilter($decoded, $filters)) {
                continue;
            }
            $rows[] = $decoded;
            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    private function trimRecent(string $file): void
    {
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || count($lines) <= self::MAX_RECENT_LINES) {
            return;
        }

        $lines = array_slice($lines, -1 * self::MAX_RECENT_LINES);
        $tmp = $file . '.tmp';
        $payload = implode(PHP_EOL, $lines) . PHP_EOL;
        if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
            return;
        }
        @rename($tmp, $file);
    }

    private function matchesFilter(array $row, array $filters): bool
    {
        if (isset($filters['kind']) && in_array((string) $filters['kind'], ['api', 'web'], true)) {
            $kind = (string) ($row['client_source'] ?? ((bool) ($row['is_api'] ?? false) ? 'app' : 'web'));
            if ($filters['kind'] === 'api' && $kind !== 'app' && !(bool) ($row['is_api'] ?? false)) {
                return false;
            }
            if ($filters['kind'] === 'web' && $kind !== 'web' && (bool) ($row['is_api'] ?? false)) {
                return false;
            }
        }

        $createdAt = (string) ($row['created_at'] ?? '');
        if (!empty($filters['from']) && $createdAt !== '' && strcmp($createdAt, (string) $filters['from']) < 0) {
            return false;
        }
        if (!empty($filters['to']) && $createdAt !== '' && strcmp($createdAt, (string) $filters['to']) > 0) {
            return false;
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null) {
            if ((int) ($row['user_id'] ?? -1) !== (int) $filters['user_id']) {
                return false;
            }
        }
        if (!empty($filters['path_contains']) && stripos((string) ($row['path'] ?? ''), (string) $filters['path_contains']) === false) {
            return false;
        }
        if (!empty($filters['method']) && strtoupper((string) ($row['method'] ?? '')) !== strtoupper((string) $filters['method'])) {
            return false;
        }
        if (!empty($filters['client_source']) && strtolower((string) ($row['client_source'] ?? '')) !== strtolower((string) $filters['client_source'])) {
            return false;
        }
        if (!empty($filters['status_min']) && (int) ($row['status_code'] ?? 0) < (int) $filters['status_min']) {
            return false;
        }
        if (!empty($filters['status_max']) && (int) ($row['status_code'] ?? 0) > (int) $filters['status_max']) {
            return false;
        }

        return true;
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}

