<?php

namespace Zapi\Modules\Customer\Security\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class SecurityController extends BaseCustomerPortalController
{
    protected $service;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new SecurityService();
        $this->service->initController($request, $response, $logger);
    }

    public function reportFraud()
    {
        $userId = $this->request->getGet('user_id');
        // BUG-04 fix: verify the caller is authorised to act on this user_id.
        if (! $this->service->actorCanAccessUser($userId)) {
            return $this->service->respondError('Forbidden', 403, 'FORBIDDEN');
        }
        $description = $this->request->getPost('description');
        $type = $this->request->getPost('type');
        return $this->service->reportFraud($userId, $description, $type);
    }

    public function checkSuspiciousLogin()
    {
        $userId = $this->request->getGet('user_id');
        if (! $this->service->actorCanAccessUser($userId)) {
            return $this->service->respondError('Forbidden', 403, 'FORBIDDEN');
        }
        return $this->service->checkSuspiciousLogins($userId);
    }

    public function ipWhitelist()
    {
        $userId = $this->request->getGet('user_id');
        if (! $this->service->actorCanAccessUser($userId)) {
            return $this->service->respondError('Forbidden', 403, 'FORBIDDEN');
        }
        $ipAddress = $this->request->getPost('ip_address');
        $action = $this->request->getPost('action'); // add or remove
        return $this->service->manageIPWhitelist($userId, $ipAddress, $action);
    }

    public function deviceTrust()
    {
        $userId = $this->request->getGet('user_id');
        if (! $this->service->actorCanAccessUser($userId)) {
            return $this->service->respondError('Forbidden', 403, 'FORBIDDEN');
        }
        $macAddress = $this->request->getPost('mac_address');
        $trusted = $this->request->getPost('trusted');
        return $this->service->toggleDeviceTrust($userId, $macAddress, $trusted);
    }
}