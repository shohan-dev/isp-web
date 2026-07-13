<?php

namespace Zapi\Modules\Customer\Usage\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class UsageController extends BaseCustomerPortalController
{
    protected $service;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new UsageService();
        $this->service->initController($request, $response, $logger);
    }

    public function index()
    {
        return $this->service->getUsage();
    }

    public function traffic()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getTraffic($userId);
    }

    public function peakHours()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getPeakHours($userId);
    }

    public function history()
    {
        $userId = $this->request->getGet('user_id');
        $days = $this->request->getGet('days') ?? 30;
        return $this->service->getUsageHistory($userId, $days);
    }
}