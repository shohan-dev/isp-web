<?php

namespace Zapi\Modules\Reseller\Transaction\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Transaction\Services\TransactionService;

class TransactionController extends BaseApiController
{
    protected TransactionService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new TransactionService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
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









