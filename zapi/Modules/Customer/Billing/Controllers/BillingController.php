<?php

namespace Zapi\Modules\Customer\Billing\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class BillingController extends BaseCustomerPortalController
{
    protected $service;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new BillingService();
        $this->service->initController($request, $response, $logger);
    }

    public function paymentFetch()
    {
        return $this->service->getPaymentInfo();
    }

    public function invoicePrint()
    {
        $invoiceId = $this->request->getGet('invoice_id');
        return $this->service->getInvoice($invoiceId);
    }

    public function makePayment()
    {
        $userId = $this->request->getGet('id') ?? $this->request->getGet('user_id');
        return $this->service->initiatePayment($userId);
    }

    public function paymentHistory()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getPaymentHistory($userId);
    }

    public function paymentDue()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getDueReminders($userId);
    }

    public function autoSuspend()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getAutoSuspendStatus($userId);
    }

    public function autopayToggle()
    {
        $userId = $this->request->getGet('user_id');
        $enabled = $this->request->getPost('enabled');
        return $this->service->toggleAutopay($userId, $enabled);
    }
}