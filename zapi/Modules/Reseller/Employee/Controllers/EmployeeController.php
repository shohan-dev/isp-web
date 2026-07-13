<?php

namespace Zapi\Modules\Reseller\Employee\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Employee\Services\EmployeeService;

class EmployeeController extends BaseApiController
{
    protected EmployeeService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new EmployeeService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

    public function details(...$args)
    {
        return $this->service->details(...$args);
    }

    public function create(...$args)
    {
        return $this->service->create(...$args);
    }

    public function update(...$args)
    {
        return $this->service->update(...$args);
    }

    public function delete(...$args)
    {
        return $this->service->delete(...$args);
    }

}









