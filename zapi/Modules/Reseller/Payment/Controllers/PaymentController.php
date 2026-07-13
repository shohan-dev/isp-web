<?php

namespace Zapi\Modules\Reseller\Payment\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Payment\Services\PaymentService;

class PaymentController extends BaseApiController
{
    protected PaymentService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new PaymentService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

}









