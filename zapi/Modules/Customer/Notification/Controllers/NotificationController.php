<?php

namespace Zapi\Modules\Customer\Notification\Controllers;

use Zapi\Modules\Customer\Core\Controllers\BaseCustomerPortalController;

class NotificationController extends BaseCustomerPortalController
{
    protected $service;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new NotificationService();
        $this->service->initController($request, $response, $logger);
    }

    public function getNotifications()
    {
        $userId = $this->request->getGet('user_id');
        $limit = $this->request->getGet('limit') ?? 20;
        return $this->service->getNotifications($userId, $limit);
    }

    public function markAsRead()
    {
        $userId = $this->request->getGet('user_id');
        $notificationId = $this->request->getPost('notification_id');
        return $this->service->markAsRead($userId, $notificationId);
    }

    public function subscribeAlerts()
    {
        $userId = $this->request->getGet('user_id');
        $types = $this->request->getPost('types');
        return $this->service->subscribeAlerts($userId, $types);
    }

    public function getMaintenanceAlerts()
    {
        return $this->service->getMaintenanceAlerts();
    }
}