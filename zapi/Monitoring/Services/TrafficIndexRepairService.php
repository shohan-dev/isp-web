<?php

namespace Zapi\Monitoring\Services;

class TrafficIndexRepairService
{
    private TrafficRetentionService $retentionService;

    public function __construct(?TrafficRetentionService $retentionService = null)
    {
        $this->retentionService = $retentionService ?? new TrafficRetentionService();
    }

    public function rebuildRecentHours(int $hours = 1): void
    {
        $hours = max(1, $hours);
        for ($i = 0; $i < $hours; $i++) {
            $point = (new \DateTimeImmutable('now'))->modify('-' . $i . ' hour');
            $hour = $point->setTime((int) $point->format('H'), 0, 0);
            $this->retentionService->rebuildHour($hour);
        }
    }
}

