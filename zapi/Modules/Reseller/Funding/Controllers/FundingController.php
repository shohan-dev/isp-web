<?php

namespace Zapi\Modules\Reseller\Funding\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Funding\Services\FundingService;

class FundingController extends BaseApiController
{
    protected FundingService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new FundingService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

    public function create(...$args)
    {
        return $this->service->create(...$args);
    }

    public function delete(...$args)
    {
        return $this->service->delete(...$args);
    }

}









