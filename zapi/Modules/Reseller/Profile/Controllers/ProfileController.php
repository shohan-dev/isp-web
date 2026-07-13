<?php

namespace Zapi\Modules\Reseller\Profile\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Profile\Services\ProfileService;

class ProfileController extends BaseApiController
{
    protected ProfileService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new ProfileService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

    public function update(...$args)
    {
        return $this->service->update(...$args);
    }

    public function updateOrganization(...$args)
    {
        return $this->service->updateOrganization(...$args);
    }

    public function changePassword(...$args)
    {
        return $this->service->changePassword(...$args);
    }

}









