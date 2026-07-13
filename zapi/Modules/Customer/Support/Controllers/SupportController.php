<?php

namespace Zapi\Modules\Customer\Support\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class SupportController extends BaseCustomerPortalController
{
    protected SupportService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new SupportService();
        $this->service->initController($request, $response, $logger);
    }

    public function fetch()
    {
        return $this->service->fetch();
    }

    public function details()
    {
        return $this->service->details();
    }

    public function sendMessage()
    {
        return $this->service->sendMessage();
    }

    public function createTicket()
    {
        return $this->service->createTicket();
    }

    public function contact()
    {
        return $this->service->contact();
    }
}
