<?php

namespace Zapi\Modules\Reseller\Sms\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Sms\Services\SmsService;

class SmsController extends BaseApiController
{
    protected SmsService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new SmsService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function recipients(...$args)
    {
        return $this->service->recipients(...$args);
    }

    public function send(...$args)
    {
        return $this->service->send(...$args);
    }

    public function fetch(...$args)
    {
        return $this->service->fetch(...$args);
    }

    public function delete(...$args)
    {
        return $this->service->delete(...$args);
    }

}









