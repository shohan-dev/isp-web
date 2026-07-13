<?php

namespace Zapi\Modules\Reseller\CustomerPayment\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\CustomerPayment\Services\CustomerPaymentService;

class CustomerPaymentController extends BaseApiController
{
    protected CustomerPaymentService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new CustomerPaymentService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

    public function userPayments(...$args)
    {
        return $this->service->userPayments(...$args);
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









