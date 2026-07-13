<?php

namespace Zapi\Modules\Reseller\Referral\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Referral\Services\ReferralService;

class ReferralController extends BaseApiController
{
    protected ReferralService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new ReferralService();
        $this->service->initController($request, $response, $logger);
    }

    public function list($resellerId = null)
    {
        return $this->service->list($resellerId);
    }

    public function details($resellerId = null, $referralId = null)
    {
        return $this->service->details($resellerId, $referralId);
    }

    public function approve($resellerId = null, $referralId = null)
    {
        return $this->service->approve($resellerId, $referralId);
    }

    public function reject($resellerId = null, $referralId = null)
    {
        return $this->service->reject($resellerId, $referralId);
    }
}
