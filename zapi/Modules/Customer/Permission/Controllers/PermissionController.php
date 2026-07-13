<?php

namespace Zapi\Modules\Customer\Permission\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class PermissionController extends BaseCustomerPortalController
{
    protected PermissionService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new PermissionService();
        $this->service->initController($request, $response, $logger);
    }

    public function index()
    {
        return $this->service->index();
    }
}
