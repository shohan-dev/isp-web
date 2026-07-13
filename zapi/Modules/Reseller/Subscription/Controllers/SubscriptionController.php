<?php

namespace Zapi\Modules\Reseller\Subscription\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Subscription\Services\SubscriptionService;

class SubscriptionController extends BaseApiController
{
    protected SubscriptionService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new SubscriptionService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    

    public function info(...$args)
    {
        return $this->service->info(...$args);
    }

    public function renew(...$args)
    {
        return $this->service->renew(...$args);
    }

    public function bulkRenew(...$args)
    {
        return $this->service->bulkRenew(...$args);
    }

}









