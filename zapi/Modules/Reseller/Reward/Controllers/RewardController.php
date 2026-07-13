<?php

namespace Zapi\Modules\Reseller\Reward\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Reward\Services\RewardService;

class RewardController extends BaseApiController
{
    protected RewardService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new RewardService();
        $this->service->initController($request, $response, $logger);
    }

    public function report($resellerId = null)
    {
        return $this->service->report($resellerId);
    }

    public function wallets($resellerId = null)
    {
        return $this->service->wallets($resellerId);
    }
}
