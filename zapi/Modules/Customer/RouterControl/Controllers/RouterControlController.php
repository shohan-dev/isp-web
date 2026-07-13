<?php

namespace Zapi\Modules\Customer\RouterControl\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

/**
 * Unified router-control endpoints for the customer 3-action app:
 * reboot, change WiFi, view connected devices — plus target listing.
 * Thin controller; all logic lives in RouterControlService.
 */
class RouterControlController extends BaseCustomerPortalController
{
    protected $service;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new RouterControlService();
        $this->service->initController($request, $response, $logger);
    }

    public function targets()
    {
        return $this->service->targets();
    }

    public function reboot()
    {
        return $this->service->reboot();
    }

    public function changeWifi()
    {
        return $this->service->changeWifi();
    }

    public function devices()
    {
        return $this->service->devices();
    }

    public function onboardTr069()
    {
        return $this->service->onboardTr069();
    }
}
