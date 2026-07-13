<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SubscriptionGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId = $session->get('user_id');
        $role = $session->get('user_role');

        // Only enforce for logged-in PPPoE end users.
        if (empty($userId) || $role !== 'user') {
            return;
        }

        $path = trim($request->getUri()->getPath(), '/');

        // Allowed paths for expired users.
        $allowedPrefixes = [
            'auth',
            'api',
            'cron',
            'payment',
            'logout',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return;
            }
        }

        $userModel = model('App\Models\User');
        $paymentModel = model('App\Models\Payment');

        $user = $userModel->select('id, status, subscription_status, will_expire')
            ->where('id', $userId)
            ->first();

        if (empty($user) || $user->status !== 'active') {
            return;
        }

        $now = time();
        $willExpireTs = !empty($user->will_expire) ? strtotime($user->will_expire) : null;
        $isExpiredByDate = is_int($willExpireTs) && $willExpireTs <= $now;
        $isExpiredByStatus = ($user->subscription_status === 'inactive');

        if (!$isExpiredByDate && !$isExpiredByStatus) {
            return;
        }

        // Prefer direct gateway redirect if a pending invoice exists.
        $pendingPayment = $paymentModel->select('id')
            ->where([
            'user_id' => $userId,
            'paidby' => $userId,
            'status' => 'pending',
            'user_type' => 'user',
        ])
            ->orderBy('id', 'DESC')
            ->first();

        if (!empty($pendingPayment->id)) {
            return redirect()->to(route_to('route.payment.pay', $pendingPayment->id));
        }

        return redirect()->to(route_to('route.payment'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    //
    }
}
