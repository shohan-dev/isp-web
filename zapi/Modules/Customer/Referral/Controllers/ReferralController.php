<?php

namespace Zapi\Modules\Customer\Referral\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class ReferralController extends BaseCustomerPortalController
{
    protected ReferralService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new ReferralService();
        $this->service->initController($request, $response, $logger);
    }

    public function overview()
    {
        return $this->service->overview();
    }

    public function history()
    {
        return $this->service->history();
    }
}
