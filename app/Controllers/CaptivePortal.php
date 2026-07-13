<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class CaptivePortal extends BaseController
{
    /**
     * Shows the expired reminder page and tries to match the User by IP.
     */
    public function index()
    {
        $userIp = $this->request->getIPAddress();
        $routerId = $this->request->getGet('rid') ?? null;
        log_message('info', "[CaptivePortal] Request from IP: {$userIp}");

        helper('router');
        $routerModel = model('App\Models\Router');
        $routerDataModel = model('App\Models\UserRouterDataModel');
        $userModel = model('App\Models\User');
        $paymentModel = model('App\Models\Payment');

        $pppoeName = null;
        $foundUser = null;

        // Optimization: Prevent loop timeout
        set_time_limit(180);

        // 1. Detect if Localhost for easier testing
        $isLocalhost = ($userIp === '::1' || $userIp === '127.0.0.1');

        // 2. Try to find which PPPoE user has this internal IP (e.g. 10.10.x.x)
        $routersToCheck = [];
        if ($routerId) {
            $specificRouter = $routerModel->find($routerId);
            if ($specificRouter) {
                $routersToCheck[] = $specificRouter;
            }
        }

        // Only search all routers if we are NOT on localhost and no rid was provided
        if (empty($routersToCheck) && !$isLocalhost) {
            $routersToCheck = $routerModel->where('status', 'active')->findAll();
        }

        if (!$isLocalhost) {
            foreach ($routersToCheck as $router) {
                try {
                    $ridToCheck = is_array($router) ? $router['id'] : $router->id;
                    $client = routerClient($ridToCheck);
                    if (!$client)
                        continue;

                    // Query MikroTik for ONLY this specific IP
                    $query = (new \RouterOS\Query('/ppp/active/print'))->where('address', $userIp);
                    $session = $client->query($query)->read();

                    if (!empty($session[0]['name'])) {
                        $pppoeName = $session[0]['name'];
                        log_message('info', "[CaptivePortal] IP {$userIp} matches PPPoE User: {$pppoeName} on Router: {$ridToCheck}");
                        $routerId = $ridToCheck; // Successfully identified the router
                        break;
                    }
                }
                catch (\Exception $e) {
                    log_message('error', "[CaptivePortal] Connection error: " . $e->getMessage());
                }
            }
        }
        else {
            log_message('info', "[CaptivePortal] Localhost detected, skipping real router lookup.");
        }

        // 2. Map PPPoE name to Database User
        if ($pppoeName) {
            $routerData = $routerDataModel->where('pppoe_secret', $pppoeName)->first();
            if ($routerData) {
                $uid = is_array($routerData) ? $routerData['user_id'] : $routerData->user_id;
                $foundUser = $userModel->find($uid);
            }
        }

        // 3. Find pending payment ID
        $paymentId = 12345; // Default dummy
        if ($foundUser) {
            $userId = is_object($foundUser) ? $foundUser->id : $foundUser['id'];
            $pending = $paymentModel->where(['user_id' => $userId, 'status' => 'pending'])->orderBy('id', 'DESC')->first();
            if ($pending) {
                $paymentId = is_object($pending) ? $pending->id : $pending['id'];
            }
            log_message('info', "[CaptivePortal] Identified User ID: {$userId}, Payment ID: {$paymentId}");
        }
        else {
            log_message('warning', "[CaptivePortal] Could not find any PPPoE user for IP: {$userIp}");
        }

        // 4. Show the design
        return view('captive/expired_generic', [
            'payment_id' => $paymentId,
            'user' => $foundUser,
            'user_ip' => $userIp,
            'router_id' => $routerId
        ]);
    }

    /**
     * Backup of complex logic
     */
    public function index_complex_backup()
    {
    // ... skipped
    }
}
