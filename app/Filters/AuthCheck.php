<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthCheck implements FilterInterface
{
    /**
     * Auth check filter
     * @action: user, admin & employee auth check
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('utility');

        // Payment-gateway session bridge (e.g. SSLCommerz return trip loses the
        // session cookie). Re-establish the session ONLY from a server-issued,
        // HMAC-signed, time-limited token — never from an attacker-suppliable
        // literal. value_a = "{exp}:{hmac}" over "{user_id}|{role}|{pid}|{exp}".
        // Fails closed when security.sessionBridgeKey is unset.
        $bridgeToken = (string) getPostInput('value_a');
        if ($bridgeToken !== '' && strpos($bridgeToken, ':') !== false) {
            [$exp, $sig] = explode(':', $bridgeToken, 2);
            $bridgeSecret = (string) env('security.sessionBridgeKey', '');
            $uid  = (string) getPostInput('value_b');
            $role = (string) getPostInput('value_c');
            $pid  = (string) getPostInput('value_d');

            if ($bridgeSecret !== '' && ctype_digit((string) $exp) && (int) $exp >= time()) {
                $expected = hash_hmac('sha256', $uid . '|' . $role . '|' . $pid . '|' . $exp, $bridgeSecret);
                if (hash_equals($expected, (string) $sig)) {
                    setSession([
                        'user_id'   => $uid,
                        'user_role' => $role,
                        'pid'       => $pid,
                    ]);
                }
            }
        }

        $usermodel = model('App\Models\User');

        $data = $usermodel
                ->where([
                    'id' =>  getSession('user_id'),
                    'role' =>  getSession('user_role'),
                ])
                ->first();

        if (empty($data)) {
            if (session()->has('user_id')) {
                session()->remove(['user_id', 'user_role', 'admin_id', 'status', 'tenant_id']);
                session()->setFlashdata('error', 'Your session has expired.');
            }
            return redirect()->route('route.auth.login');
        }

        // Tenant portal: session must belong to the current host tenant.
        helper('tenant');
        if (function_exists('isTenantRequest') && isTenantRequest()) {
            if (($data->role ?? '') === 'super_admin') {
                session()->remove(['user_id', 'user_role', 'admin_id', 'status', 'tenant_id']);
                session()->setFlashdata('error', 'Platform admin must use the main domain.');
                return redirect()->route('route.auth.login');
            }
            $userTenantId = function_exists('resolveUserTenantId') ? resolveUserTenantId($data) : null;
            $portalTenantId = currentTenantId();
            if (empty($portalTenantId) || empty($userTenantId) || (int) $portalTenantId !== (int) $userTenantId) {
                session()->remove(['user_id', 'user_role', 'admin_id', 'status', 'tenant_id']);
                session()->setFlashdata('error', 'This account does not belong to this portal.');
                return redirect()->route('route.auth.login');
            }
            if (function_exists('currentTenant') && currentTenant() && strtolower((string) (currentTenant()->status ?? '')) === 'suspended') {
                session()->remove(['user_id', 'user_role', 'admin_id', 'status', 'tenant_id']);
                session()->setFlashdata('error', 'This portal is suspended.');
                return redirect()->route('route.auth.login');
            }
            if (empty(getSession('tenant_id'))) {
                setSession(['tenant_id' => (int) $userTenantId]);
            }
        }

        // Determine if they are an expired user (allowed only to pay/renew)
        $isUserExpired = ($data->role === 'user' && ($data->status === 'inactive' || $data->subscription_status === 'inactive') && !empty($data->will_expire) && strtotime($data->will_expire) <= time());
        helper('subscription');
        $isSAdminExpired = isSAdminSubscriptionExpired($data);
        $isResellerExpired = ($data->role === 'resellerAdmin' && $data->status === 'inactive');

        // If inactive but not expired, they are manually deactivated (except resellers, who see exhome)
        if ($data->status === 'inactive' && !$isUserExpired && !$isSAdminExpired && !$isResellerExpired) {
            if (session()->has('user_id')) {
                session()->remove(['user_id', 'user_role', 'admin_id', 'status', 'tenant_id']);
                session()->setFlashdata('error', 'Your account is currently disabled.');
            }
            return redirect()->route('route.auth.login');
        }

        // Enforce subscription guard check for expired users
        if ($isSAdminExpired || $isUserExpired || $isResellerExpired) {
            $path = trim($request->getUri()->getPath(), '/');

            // Allowed paths for expired users to view/perform renewal
            $allowedPaths = [
                'exhome',
                'logout',
                'auth',
                'payment',
                'wallet',
                'admins/packages',
            ];

            $allowedPrefixes = [
                'auth/',
                'payment/',
                'wallet/',
                'make-payment/',
                'subscription/',
                'Resellersubscription/',
                'admins/subscription/',
                'admins/packages/',
                'admins/getPackage/',
                'admins/updatePackage/',
                'admins/update/',
            ];

            $isAllowed = false;
            foreach ($allowedPaths as $allowed) {
                if ($path === $allowed) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                foreach ($allowedPrefixes as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        $isAllowed = true;
                        break;
                    }
                }
            }

            // Also check for admins/{id} (self recharge route)
            if (!$isAllowed && preg_match('#^admins/\d+$#', $path)) {
                $isAllowed = true;
            }

            if (!$isAllowed) {
                // Set the session status to inactive to match login check
                setSession(['status' => 'inactive']);
                return redirect()->route('route.exhome');
            }
        } else {
            // Ensure session status matches their active database status
            if (getSession('status') === 'inactive') {
                session()->remove('status');
            }
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
