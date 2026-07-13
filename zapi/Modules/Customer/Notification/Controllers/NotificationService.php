<?php

namespace Zapi\Modules\Customer\Notification\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;
use Zapi\Modules\Shared\Rewards\Models\AppNotificationModel;

/**
 * Customer in-app notification inbox, backed by the real app_notifications
 * table (reward/referral/system events). Previously returned mock data.
 */
class NotificationService extends CustomerBaseService
{
    public function getNotifications($userId, $limit = 20)
    {
        $userId = $this->resolveAccessTokenUserId() ?? (int) $userId;
        if (!$userId) {
            return $this->respondError('User ID is required', 400, 'REQUEST_FAILED');
        }
        $limit = max(1, min((int) $limit, 100));

        $model = new AppNotificationModel();
        $rows = $model->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        $notifications = [];
        $unread = 0;
        foreach ($rows as $r) {
            $isRead = (int) ($r->is_read ?? 0) === 1;
            if (!$isRead) {
                $unread++;
            }
            $notifications[] = [
                'id'         => (int) $r->id,
                'type'       => (string) ($r->type ?? 'system'),
                'title'      => (string) ($r->title ?? ''),
                'message'    => (string) ($r->body ?? ''),
                'time'       => (string) ($r->created_at ?? ''),
                'read'       => $isRead,
                'action_url' => (string) ($r->action_url ?? ''),
            ];
        }

        return $this->respondSuccess([
            'user_id'      => $userId,
            'total'        => count($notifications),
            'unread_count' => $unread,
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($userId, $notificationId)
    {
        $userId = $this->resolveAccessTokenUserId() ?? (int) $userId;
        if (!$userId) {
            return $this->respondError('User ID is required', 400, 'REQUEST_FAILED');
        }

        $model = new AppNotificationModel();
        if ((string) $notificationId === '' || strtolower((string) $notificationId) === 'all') {
            // Mark all as read for this user.
            $model->where('user_id', $userId)->where('is_read', 0)
                ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
                ->update();
            return $this->respondSuccess(['user_id' => $userId, 'status' => 'all_marked_as_read']);
        }

        // Single — enforce ownership.
        $row = $model->where('id', (int) $notificationId)->where('user_id', $userId)->first();
        if (!$row) {
            return $this->respondError('Notification not found', 404, 'REQUEST_FAILED');
        }
        $model->update((int) $notificationId, ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);

        return $this->respondSuccess([
            'user_id'         => $userId,
            'notification_id' => (int) $notificationId,
            'status'          => 'marked_as_read',
        ]);
    }

    public function subscribeAlerts($userId, $types)
    {
        $userId = $this->resolveAccessTokenUserId() ?? (int) $userId;
        if (!$userId) {
            return $this->respondError('User ID is required', 400, 'REQUEST_FAILED');
        }
        $types = is_array($types) ? $types : array_filter(array_map('trim', explode(',', (string) $types)));
        return $this->respondSuccess([
            'user_id'       => $userId,
            'subscribed_to' => $types,
            'status'        => 'success',
        ]);
    }

    public function getMaintenanceAlerts()
    {
        return $this->respondSuccess(['total' => 0, 'alerts' => []]);
    }
}
