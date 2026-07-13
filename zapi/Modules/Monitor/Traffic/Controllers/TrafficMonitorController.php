<?php

namespace Zapi\Modules\Monitor\Traffic\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Zapi\Monitoring\Config\TrafficMonitorConfig;
use Zapi\Monitoring\Services\TrafficAggregationService;
use Zapi\Monitoring\Services\TrafficRetentionService;
use Zapi\Monitoring\Services\TrafficSpoolFlushService;

class TrafficMonitorController extends Controller
{
    private TrafficAggregationService $aggregationService;
    private TrafficSpoolFlushService $spoolFlushService;

    public function __construct()
    {
        $this->aggregationService = new TrafficAggregationService();
        $this->spoolFlushService = new TrafficSpoolFlushService();
    }

    public function traffic()
    {
        $filters = $this->extractFilters();
        $data = $this->aggregationService->build($filters);

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setBody($this->renderHtml($data));
    }

    public function overview()
    {
        $data = $this->aggregationService->build($this->extractFilters());
        return $this->response->setJSON($data['overview']);
    }

    public function topEndpoints()
    {
        $data = $this->aggregationService->build($this->extractFilters());
        return $this->response->setJSON($this->paginateList($data['top_endpoints'] ?? []));
    }

    public function timeline()
    {
        $data = $this->aggregationService->build($this->extractFilters());
        return $this->response->setJSON($this->paginateList($data['timeline'] ?? []));
    }

    public function recent()
    {
        $data = $this->aggregationService->build($this->extractFilters());
        return $this->response->setJSON($this->paginateList($data['recent'] ?? []));
    }

    public function snapshot()
    {
        $data = $this->aggregationService->build($this->extractFilters());
        return $this->response->setJSON($data);
    }

    public function flushQueue()
    {
        if (($deny = $this->guardCron()) !== null) {
            return $deny;
        }
        $maxFiles = max(1, (int) ($this->request->getGet('max_files') ?? 120));
        $result = $this->spoolFlushService->flush($maxFiles);
        return $this->response->setJSON([
            'ok' => true,
            'mode' => 'never_down_file_spool',
            'result' => $result,
        ]);
    }

    public function maintainQueue()
    {
        if (($deny = $this->guardCron()) !== null) {
            return $deny;
        }
        $maxAgeHours = max(1, (int) ($this->request->getGet('max_spool_age_hours') ?? 48));
        $result = $this->spoolFlushService->dispose($maxAgeHours);
        return $this->response->setJSON([
            'ok' => true,
            'mode' => 'never_down_file_spool',
            'result' => $result,
        ]);
    }

    public function retentionCleanup()
    {
        if (($deny = $this->guardCron()) !== null) {
            return $deny;
        }
        $stats = (new TrafficRetentionService())->cleanup();

        return $this->response->setJSON([
            'ok' => true,
            'mode' => 'retention',
            'result' => $stats,
        ]);
    }

    private function guardCron(): ?ResponseInterface
    {
        $secret = TrafficMonitorConfig::cronSecret();
        $provided = (string) ($this->request->getGet('key') ?? $this->request->getHeaderLine('X-Monitor-Cron-Key'));
        // Fail CLOSED: if the secret is unset, these spool-mutating / retention-delete
        // routes stay locked (matches CronAuth posture). Set zapi.monitor.cronSecret to open.
        if ($secret === '' || $provided === '' || !hash_equals($secret, $provided)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'error' => 'invalid_or_missing_cron_key',
            ]);
        }

        return null;
    }

    private function extractFilters(): array
    {
        $query = [];
        parse_str((string) $this->request->getUri()->getQuery(), $query);

        $kind = strtolower((string) ($query['kind'] ?? ''));
        $kind = in_array($kind, ['api', 'web'], true) ? $kind : null;

        return [
            'kind' => $kind,
            'from' => (string) ($query['from'] ?? ''),
            'to' => (string) ($query['to'] ?? ''),
            'user_id' => isset($query['user_id']) && is_numeric($query['user_id']) ? (int) $query['user_id'] : null,
            'path_contains' => trim((string) ($query['path_contains'] ?? '')),
            'method' => trim((string) ($query['method'] ?? '')),
            'status_min' => isset($query['status_min']) && is_numeric($query['status_min']) ? (int) $query['status_min'] : null,
            'status_max' => isset($query['status_max']) && is_numeric($query['status_max']) ? (int) $query['status_max'] : null,
            'client_source' => trim((string) ($query['client_source'] ?? '')),
        ];
    }

    private function paginateList(array $rows): array
    {
        $query = [];
        parse_str((string) $this->request->getUri()->getQuery(), $query);
        $page = max(1, (int) ($query['page'] ?? 1));
        $limitQuery = (int) ($query['limit'] ?? 0);
        $perPageQuery = (int) ($query['per_page'] ?? 0);
        $limit = $limitQuery > 0 ? $limitQuery : $perPageQuery;
        $limit = $limit > 0 ? min($limit, 100) : 10;
        $total = count($rows);
        $offset = ($page - 1) * $limit;
        $paged = array_slice($rows, $offset, $limit);
        $totalPages = max(1, (int) ceil(($total > 0 ? $total : 1) / $limit));
        $page = min($page, $totalPages);

        return [
            'data' => $paged,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
                'hasNext' => $page < $totalPages,
                'hasPrev' => $page > 1,
            ],
        ];
    }

    private function renderHtml(array $data): string
    {
        $overview = $data['overview'] ?? [];
        $topEndpoints = $data['top_endpoints'] ?? [];
        $timeline = $data['timeline'] ?? [];
        $recent = $data['recent'] ?? [];
        $deviceSummary = $data['device_summary'] ?? [];
        $comparison = $data['comparison'] ?? [];
        $queueStats = $data['queue_stats'] ?? [];
        $activeFilters = $data['filters'] ?? [];
        $generatedAt = (string) ($data['generated_at'] ?? gmdate('c'));

        ob_start();
        include ROOTPATH . 'zapi/Monitoring/Views/monitor/traffic_dashboard.php';
        return (string) ob_get_clean();
    }
}

