<?php

namespace Zapi\Monitoring\Services;

class TrafficAggregationService
{
    private TrafficSnapshotService $snapshotService;

    public function __construct(?TrafficSnapshotService $snapshotService = null)
    {
        $this->snapshotService = $snapshotService ?? new TrafficSnapshotService();
    }

    public function build(array $filters = []): array
    {
        return $this->snapshotService->build($filters);
    }
}

