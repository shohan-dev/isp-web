<?php

namespace Zapi\Modules\Reseller\Dashboard\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Dashboard\Services\DashboardService;

class DashboardController extends BaseApiController
{
    protected DashboardService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new DashboardService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function dashboard(...$args)
    {
        return $this->service->dashboard(...$args);
    }

}









