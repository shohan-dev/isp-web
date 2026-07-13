<?php

namespace Zapi\Monitoring\Services;

class TrafficSnapshotService
{
    private TrafficCompactReadService $compactReadService;
    private TrafficSpoolFlushService $spoolFlushService;
    private int $autoFlushMaxFiles;

    public function __construct(
        ?TrafficCompactReadService $compactReadService = null,
        ?TrafficSpoolFlushService $spoolFlushService = null
    ) {
        $this->compactReadService = $compactReadService ?? new TrafficCompactReadService();
        $this->spoolFlushService = $spoolFlushService ?? new TrafficSpoolFlushService();
        $configuredLimit = (int) env('zapi.monitor.autoFlushMaxFiles', 120);
        $this->autoFlushMaxFiles = max(1, min(500, $configuredLimit));
    }

    public function build(array $filters = []): array
    {
        // Ensure monitor dashboard reads fresh data even when cron flush is not running.
        try {
            $this->spoolFlushService->flush($this->autoFlushMaxFiles);
        } catch (\Throwable $e) {
            // Fail-open: dashboard must still render from existing compact data.
        }

        return $this->compactReadService->buildSnapshot($filters);
    }
}

