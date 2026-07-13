<?php

namespace Zapi\Modules\Reseller\Router\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Router\Services\RouterService;

class RouterController extends BaseApiController
{
    protected RouterService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new RouterService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function list(...$args)
    {
        return $this->service->list(...$args);
    }

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

}









