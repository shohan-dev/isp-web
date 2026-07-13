<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficQueryService
{
    private TrafficPathResolver $pathResolver;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
    }

    public function readAll(array $filters = []): array
    {
        $basePath = $this->pathResolver->rawBaseDir();
        if (!is_dir($basePath)) {
            return [];
        }

        $files = $this->collectFiles($basePath);
        $rows = [];

        foreach ($files as $file) {
            foreach ($this->readJsonLines($file) as $row) {
                if ($this->matchesFilter($row, $filters)) {
                    $rows[] = $row;
                }
            }
        }

        usort($rows, static fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return $rows;
    }

    private function collectFiles(string $basePath): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if (strpos($file->getPathname(), DIRECTORY_SEPARATOR . '_index' . DIRECTORY_SEPARATOR) !== false) {
                continue;
            }
            if (substr($file->getFilename(), -6) === '.jsonl') {
                $files[] = $file->getPathname();
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

    private function matchesFilter(array $row, array $filters): bool
    {
        if (isset($filters['kind']) && in_array((string) $filters['kind'], ['api', 'web'], true)) {
            $isApi = (bool) ($row['is_api'] ?? false);
            if ($filters['kind'] === 'api' && !$isApi) {
                return false;
            }
            if ($filters['kind'] === 'web' && $isApi) {
                return false;
            }
        }

        if (!empty($filters['from']) && !empty($row['created_at']) && strcmp((string) $row['created_at'], (string) $filters['from']) < 0) {
            return false;
        }
        if (!empty($filters['to']) && !empty($row['created_at']) && strcmp((string) $row['created_at'], (string) $filters['to']) > 0) {
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
}

