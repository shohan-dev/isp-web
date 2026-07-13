<?php

namespace Zapi\Modules\Reseller\Area\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Area\Services\ServiceAreaService;

class ServiceAreaController extends BaseApiController
{
    protected ServiceAreaService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new ServiceAreaService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function index(...$args)
    {
        return $this->service->index(...$args);
    }

    public function subindex(...$args)
    {
        return $this->service->subindex(...$args);
    }

    public function create(...$args)
    {
        return $this->service->create(...$args);
    }

    public function subcreate(...$args)
    {
        return $this->service->subcreate(...$args);
    }

    public function edit(...$args)
    {
        return $this->service->edit(...$args);
    }

    public function editsub(...$args)
    {
        return $this->service->editsub(...$args);
    }

    public function update(...$args)
    {
        return $this->service->update(...$args);
    }

    public function updatesub(...$args)
    {
        return $this->service->updatesub(...$args);
    }

    public function delete(...$args)
    {
        return $this->service->delete(...$args);
    }

    public function deletesub(...$args)
    {
        return $this->service->deletesub(...$args);
    }

}









