<?php

namespace Zapi\Modules\Reseller\Customer\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Customer\Services\CustomerService;

class CustomerController extends BaseApiController
{
    protected CustomerService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new CustomerService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function create(...$args)
    {
        return $this->service->create(...$args);
    }

    public function syncPppoeIds(...$args)
    {
        return $this->service->syncPppoeIds(...$args);
    }

    public function importExcel(...$args)
    {
        return $this->service->importExcel(...$args);
    }

    public function bulkRecharge(...$args)
    {
        return $this->service->bulkRecharge(...$args);
    }

    public function transfer(...$args)
    {
        return $this->service->transfer(...$args);
    }

    public function bulkDelete(...$args)
    {
        return $this->service->bulkDelete(...$args);
    }

    public function pppoeStatus(...$args)
    {
        return $this->service->pppoeStatus(...$args);
    }

    public function updatePop(...$args)
    {
        return $this->service->updatePop(...$args);
    }

    public function bulkUpdatePop(...$args)
    {
        return $this->service->bulkUpdatePop(...$args);
    }

    public function updateRouter(...$args)
    {
        return $this->service->updateRouter(...$args);
    }

    public function bulkUpdateRouter(...$args)
    {
        return $this->service->bulkUpdateRouter(...$args);
    }

    public function exportExcel(...$args)
    {
        return $this->service->exportExcel(...$args);
    }

    public function auditLogs(...$args)
    {
        return $this->service->auditLogs(...$args);
    }

    public function macStatus(...$args)
    {
        return $this->service->macStatus(...$args);
    }

    public function macBind(...$args)
    {
        return $this->service->macBind(...$args);
    }

    public function macUnbind(...$args)
    {
        return $this->service->macUnbind(...$args);
    }

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

    public function index(...$args)
    {
        return $this->service->index(...$args);
    }

    public function details(...$args)
    {
        return $this->service->details(...$args);
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









