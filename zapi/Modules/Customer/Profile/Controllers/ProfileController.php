<?php

namespace Zapi\Modules\Customer\Profile\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class ProfileController extends BaseCustomerPortalController
{
    protected ProfileService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new ProfileService();
        $this->service->initController($request, $response, $logger);
    }

    public function update()
    {
        return $this->service->update();
    }
}
