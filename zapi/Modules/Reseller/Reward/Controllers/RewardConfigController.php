<?php

namespace Zapi\Modules\Reseller\Reward\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Reward\Services\RewardConfigPortalService;

class RewardConfigController extends BaseApiController
{
    protected RewardConfigPortalService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new RewardConfigPortalService();
        $this->service->initController($request, $response, $logger);
    }

    public function get($resellerId = null)
    {
        return $this->service->getResellerConfig($resellerId);
    }

    public function update($resellerId = null)
    {
        return $this->service->updateResellerConfig($resellerId);
    }

    public function getGlobal()
    {
        return $this->service->getGlobalConfig();
    }

    public function updateGlobal()
    {
        return $this->service->updateGlobalConfig();
    }
}
