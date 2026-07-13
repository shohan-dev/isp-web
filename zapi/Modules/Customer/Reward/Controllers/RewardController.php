<?php

namespace Zapi\Modules\Customer\Reward\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class RewardController extends BaseCustomerPortalController
{
    protected RewardService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new RewardService();
        $this->service->initController($request, $response, $logger);
    }

    public function wallet()
    {
        return $this->service->wallet();
    }

    public function transactions()
    {
        return $this->service->transactions();
    }

    public function redeemPreview()
    {
        return $this->service->redeemPreview();
    }
}
