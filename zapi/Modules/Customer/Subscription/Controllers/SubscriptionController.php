<?php

namespace Zapi\Modules\Customer\Subscription\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class SubscriptionController extends BaseCustomerPortalController
{
    protected SubscriptionService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new SubscriptionService();
        $this->service->initController($request, $response, $logger);
    }

    public function index()
    {
        return $this->service->index();
    }

    public function renew()
    {
        return $this->service->renew();
    }

    public function activatePackage()
    {
        return $this->service->activatePackage();
    }

    public function quota()
    {
        return $this->service->quota();
    }

    public function update()
    {
        return $this->service->update();
    }
}
