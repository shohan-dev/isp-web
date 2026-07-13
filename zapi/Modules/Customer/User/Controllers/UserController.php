<?php

namespace Zapi\Modules\Customer\User\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class UserController extends BaseCustomerPortalController
{
    protected UserService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new UserService();
        $this->service->initController($request, $response, $logger);
    }

    public function index($id = null)
    {
        return $this->service->index($id);
    }

    public function packages()
    {
        return $this->service->packages();
    }

    public function pingUserApi()
    {
        return $this->service->pingUserApi();
    }

    public function fetch()
    {
        return $this->service->fetch();
    }
}
