<?php

namespace Zapi\Modules\Customer\Device\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class DeviceController extends BaseCustomerPortalController
{
    protected $service;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new DeviceService();
        $this->service->initController($request, $response, $logger);
    }

    public function getDevices()
    {
        $userId = $this->request->getGet('user_id');
        return $this->service->getConnectedDevices($userId);
    }

    public function blockDevice()
    {
        $userId = $this->request->getGet('user_id');
        $macAddress = $this->request->getPost('mac_address');
        return $this->service->blockDevice($userId, $macAddress);
    }

    public function unblockDevice()
    {
        $userId = $this->request->getGet('user_id');
        $macAddress = $this->request->getPost('mac_address');
        return $this->service->unblockDevice($userId, $macAddress);
    }

    public function limitBandwidth()
    {
        $userId = $this->request->getGet('user_id');
        $macAddress = $this->request->getPost('mac_address');
        $limit = $this->request->getPost('limit');
        return $this->service->limitBandwidth($userId, $macAddress, $limit);
    }

    public function deviceInfo()
    {
        $userId = $this->request->getGet('user_id');
        $macAddress = $this->request->getGet('mac_address');
        return $this->service->getDeviceInfo($userId, $macAddress);
    }
}