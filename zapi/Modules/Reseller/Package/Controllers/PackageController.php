<?php

namespace Zapi\Modules\Reseller\Package\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Package\Services\PackageService;

class PackageController extends BaseApiController
{
    protected PackageService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new PackageService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

    public function delete(...$args)
    {
        return $this->service->delete(...$args);
    }

    public function details(...$args)
    {
        return $this->service->details(...$args);
    }

}









