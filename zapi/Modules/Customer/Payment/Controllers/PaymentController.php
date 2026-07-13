<?php

namespace Zapi\Modules\Customer\Payment\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class PaymentController extends BaseCustomerPortalController
{
    protected PaymentService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new PaymentService();
        $this->service->initController($request, $response, $logger);
    }

    public function makePayment($id)
    {
        return $this->service->makePayment($id);
    }

    public function makeResellerPayment($id)
    {
        return $this->service->makeResellerPayment($id);
    }

    public function makePaymentJson($id)
    {
        return $this->respondSuccess([
            'paymentId' => (int) $id,
            'message' => 'Use /api/customer/make-payment/{id} for HTML view during compatibility window.',
        ]);
    }

    public function makeResellerPaymentJson($id)
    {
        return $this->respondSuccess([
            'paymentId' => (int) $id,
            'message' => 'Use /api/customer/make-reseller-payment/{id} for HTML view during compatibility window.',
        ]);
    }
}
