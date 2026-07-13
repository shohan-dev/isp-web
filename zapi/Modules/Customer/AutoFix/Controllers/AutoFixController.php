<?php

namespace Zapi\Modules\Customer\AutoFix\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class AutoFixController extends BaseCustomerPortalController
{
    protected $service;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new AutoFixService();
        $this->service->initController($request, $response, $logger);
    }

    public function rebootRouter()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->rebootRouter($userId);
    }

    public function reconnectPPPoE()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->reconnectPPPoE($userId);
    }

    public function flushDNS()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->flushDNS($userId);
    }

    public function resetSession()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->resetSession($userId);
    }

    public function applyConfigUpdate()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->applyConfigUpdate($userId);
    }

    public function quickFix()
    {
        $userId = $this->request->getGet('user_id');
        $issueType = $this->request->getGet('issue');
        return $this->service->performQuickFix($userId, $issueType);
    }
}