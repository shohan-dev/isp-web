<?php

namespace App\Controllers;

use App\Libraries\RedisInspector as RedisInspectorService;

class RedisInspector extends BaseController
{
    public function index()
    {
        if (! $this->canView()) {
            show_404();
        }

        $options = $this->resolveOptions();
        $service = new RedisInspectorService();
        $report  = $service->inspect($options);

        return view('system/redis_inspector', [
            'title'   => 'Redis Cache Inspector',
            'report'  => $report,
            'options' => $options,
        ]);
    }

    public function refresh()
    {
        if (! $this->canView()) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error']);
        }

        $options = $this->resolveOptions();
        $service = new RedisInspectorService();
        $report  = $service->inspect($options);

        return $this->response->setJSON([
            'status' => 'success',
            'report' => $report,
        ]);
    }

    /**
     * @return array{
     *   pattern: string,
     *   search: string,
     *   category: string,
     *   page: int,
     *   per_page: int,
     *   sort: string
     * }
     */
    private function resolveOptions(): array
    {
        $pattern = trim((string) $this->request->getGet('pattern'));
        if ($pattern === '') {
            $pattern = '*';
        }

        $perPage = (int) $this->request->getGet('per_page');
        if (! in_array($perPage, RedisInspectorService::PER_PAGE_OPTIONS, true)) {
            $perPage = 25;
        }

        $sort = (string) $this->request->getGet('sort');
        if (! in_array($sort, RedisInspectorService::SORT_OPTIONS, true)) {
            $sort = 'key_desc';
        }

        return [
            'pattern'  => $pattern,
            'search'   => trim((string) $this->request->getGet('search')),
            'category' => trim((string) $this->request->getGet('category')),
            'page'     => max(1, (int) $this->request->getGet('page')),
            'per_page' => $perPage,
            'sort'     => $sort,
        ];
    }

    private function canView(): bool
    {
        return isRedisInspectorViewer();
    }
}
