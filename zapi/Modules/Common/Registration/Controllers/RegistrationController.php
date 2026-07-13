<?php

namespace Zapi\Modules\Common\Registration\Controllers;

use Zapi\Core\Base\BaseApiController;

class RegistrationController extends BaseApiController
{
    protected RegistrationService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new RegistrationService();
        $this->service->initController($request, $response, $logger);
    }

    /** POST /api/common/register  (public) */
    public function register()
    {
        return $this->service->register();
    }

    /** GET /api/common/referral/validate/{code}  (public) */
    public function validateCode($code = null)
    {
        return $this->service->validateCode($code);
    }
}
