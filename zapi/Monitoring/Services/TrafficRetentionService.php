<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Config\TrafficMonitorConfig;
use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficRetentionService
{
    private TrafficPathResolver $pathResolver;
    private TrafficSpoolFlushService $spoolFlushService;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
        $this->spoolFlushService = new TrafficSpoolFlushService($this->pathResolver);
    }

    /**
     * Prune old spool, raw, compact daily summaries, and index artifacts.
     *
     * @return array<string, int>
     */
    public function cleanup(?int $detailDays = null): array
    {
        $this->spoolFlushService->dispose(48);
        $rawDays = $detailDays ?? TrafficMonitorConfig::rawRetentionDays();
        $summaryDays = TrafficMonitorConfig::summaryRetentionDays();

        $stats = [
            'spool_files_deleted' => $this->pruneTreeByMtimeAndExtension($this->pathResolver->spoolBaseDir(), $rawDays, '.jsonl'),
            'raw_files_deleted' => $this->pruneTreeByMtimeAndExtension($this->pathResolver->rawBaseDir(), $rawDays, '.jsonl'),
            'compact_daily_files_deleted' => $this->pruneTreeByMtimeAndExtension($this->pathResolver->compactDailyDir(), $summaryDays, '.json'),
        ];

        $indexBase = $this->pathResolver->indexBaseDir();
        $aggDeleted = 0;
        foreach (['agg_minute', 'agg_endpoint', 'agg_device'] as $sub) {
            $aggDeleted += $this->pruneTreeByMtimeAndExtension($indexBase . DIRECTORY_SEPARATOR . $sub, $rawDays, '.json');
        }
        $stats['index_agg_files_deleted'] = $aggDeleted;
        $stats['index_summary_day_files_deleted'] = $this->pruneTreeByMtimeAndExtension($this->pathResolver->summaryBaseDir(), $summaryDays, '.json');
        $stats['index_budget_files_deleted'] = $this->pruneTreeByMtimeAndExtension($indexBase . DIRECTORY_SEPARATOR . 'budget', $rawDays, '.json');

        return $stats;
    }

    /**
     * Reserved for future re-index from raw JSONL; no-op so callers do not fatal.
     */
    public function rebuildHour(\DateTimeImmutable $hour): void
    {
    }

    private function pruneTreeByMtimeAndExtension(string $root, int $days, string $suffix): int
    {
        if ($suffix === '' || !is_dir($root)) {
            return 0;
        }
        $suffixLen = strlen($suffix);
        $cutoff = (new \DateTimeImmutable('now'))->modify('-' . max(1, $days) . ' days');
        $deleted = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isFile()) {
                if (substr($item->getFilename(), -$suffixLen) !== $suffix) {
                    continue;
                }
                if ((new \DateTimeImmutable('@' . $item->getMTime())) < $cutoff && @unlink($path)) {
                    $deleted++;
                }
            } elseif ($item->isDir()) {
                $files = @scandir($path);
                if (is_array($files) && count($files) <= 2) {
                    @rmdir($path);
                }
            }
        }

        return $deleted;
    }
}
