<?php

namespace Zapi\Monitoring\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Zapi\Monitoring\Config\TrafficMonitorConfig;
use Zapi\Monitoring\Services\TrafficIgnoreService;
use Zapi\Monitoring\Support\TrafficRequestContext;

class TrafficStartFilter implements FilterInterface
{
    private TrafficIgnoreService $ignoreService;

    public function __construct()
    {
        $this->ignoreService = new TrafficIgnoreService();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!TrafficMonitorConfig::enabled() || $this->ignoreService->shouldSkip($request)) {
            return null;
        }

        TrafficRequestContext::markStart();
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
