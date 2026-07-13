<?php

namespace Zapi\Modules\Customer\Diagnostics\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class DiagnosticsController extends BaseCustomerPortalController
{
    protected $service;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new DiagnosticsService();
        $this->service->initController($request, $response, $logger);
    }

    public function speedtest()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->runSpeedTest($userId);
    }

    public function speedtestHistory()
    {
        $userId = $this->request->getGet('user_id');
        $limit = $this->request->getGet('limit') ?? 10;
        return $this->service->getSpeedTestHistory($userId, $limit);
    }

    public function packetLoss()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->checkPacketLoss($userId);
    }

    public function latencyTest()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->checkLatency($userId);
    }

    public function jitterAnalysis()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->analyzeJitter($userId);
    }

    public function uptimeReport()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getUptimeReport($userId);
    }

    public function networkCongestion()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getCongestionStatus($userId);
    }

    public function lineQuality()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getLineQuality($userId);
    }

    public function lastDisconnect()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getLastDisconnectReason($userId);
    }
}