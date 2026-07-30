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

        // 1. Detect if Localhost for easier testing
        $isLocalhost = ($userIp === '::1' || $userIp === '127.0.0.1');

        // Never call routerClient() on first paint — even the designed MikroTik
        // flow (?rid=) used to block up to ~15s here and exhaust PHP-FPM under
        // a flapping router. Resolve after paint via resolve() for both paths.
        $resolveNeeded = false;
        if ($routerId) {
            $specificRouter = $routerModel->find($routerId);
            if ($specificRouter) {
                $resolveNeeded = true;
            } elseif (!$isLocalhost) {
                // Unknown rid — fall back to sweep-all after paint.
                $routerId = null;
                $resolveNeeded = true;
            }
        } elseif (!$isLocalhost) {
            $resolveNeeded = true;
        } else {
            log_message('info', "[CaptivePortal] Localhost detected, skipping real router lookup.");
        }

        // First paint: dummy payment id only; resolve() patches Pay Now after paint.
        [$foundUser, $paymentId] = $this->resolveUserAndPayment(null);

        return view('captive/expired_generic', [
            'payment_id' => $paymentId,
            'user' => $foundUser,
            'user_ip' => $userIp,
            'router_id' => $routerId,
            'resolve_needed' => $resolveNeeded,
        ]);
    }

    /**
     * Post-paint AJAX: resolve PPPoE → payment id without blocking first paint.
     * Accepts optional ?rid= to check one router; otherwise sweeps active routers.
     */
    public function resolve()
    {
        // Public page — no session writes; release lock if PHP started one.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $userIp = $this->request->getIPAddress();
        $isLocalhost = ($userIp === '::1' || $userIp === '127.0.0.1');
        $routerId = $this->request->getGet('rid') ?? null;

        helper('router');
        $routerModel = model('App\Models\Router');

        $pppoeName = null;
        $matchedRouterId = null;

        if (!$isLocalhost) {
            if ($routerId) {
                $specificRouter = $routerModel->find($routerId);
                if ($specificRouter) {
                    $pppoeName = $this->matchPppoeOnRouter($routerId, $userIp);
                    if ($pppoeName !== null) {
                        $matchedRouterId = $routerId;
                    }
                }
            } else {
                // Optimization: prevent this (bounded, background-from-the-user's-
                // perspective) loop from hard timing out mid-sweep.
                set_time_limit(180);
                $routersToCheck = $routerModel->where('status', 'active')->findAll();
                foreach ($routersToCheck as $router) {
                    $ridToCheck = is_array($router) ? $router['id'] : $router->id;
                    $pppoeName = $this->matchPppoeOnRouter($ridToCheck, $userIp);
                    if ($pppoeName !== null) {
                        $matchedRouterId = $ridToCheck;
                        break;
                    }
                }
            }
        }

        [$foundUser, $paymentId] = $this->resolveUserAndPayment($pppoeName);

        return $this->response->setJSON([
            'ok' => true,
            'payment_id' => $paymentId,
            'router_id' => $matchedRouterId,
            'user_found' => $foundUser !== null,
        ]);
    }

    /**
     * One connect + one filtered /ppp/active/print for a single router.
     * Extracted so index() (rid given) and resolve() (rid missing, swept
     * one router at a time) share the exact same lookup.
     */
    private function matchPppoeOnRouter($routerId, string $userIp): ?string
    {
        try {
            $client = routerClient($routerId);
            if (!$client) {
                return null;
            }

            $query = (new \RouterOS\Query('/ppp/active/print'))->where('address', $userIp);
            $session = $client->query($query)->read();

            if (!empty($session[0]['name'])) {
                log_message('info', "[CaptivePortal] IP {$userIp} matches PPPoE User: {$session[0]['name']} on Router: {$routerId}");
                return $session[0]['name'];
            }
        } catch (\Exception $e) {
            log_message('error', "[CaptivePortal] Connection error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Map a resolved PPPoE name to its DB user + latest pending payment.
     * @return array{0: object|array|null, 1: int}
     */
    private function resolveUserAndPayment(?string $pppoeName): array
    {
        $foundUser = null;
        $paymentId = 12345; // Default dummy

        if ($pppoeName) {
            $routerDataModel = model('App\Models\UserRouterDataModel');
            $userModel = model('App\Models\User');
            $routerData = $routerDataModel->where('pppoe_secret', $pppoeName)->first();
            if ($routerData) {
                $uid = is_array($routerData) ? $routerData['user_id'] : $routerData->user_id;
                $foundUser = $userModel->find($uid);
            }
        }

        if ($foundUser) {
            $paymentModel = model('App\Models\Payment');
            $userId = is_object($foundUser) ? $foundUser->id : $foundUser['id'];
            $pending = $paymentModel->where(['user_id' => $userId, 'status' => 'pending'])->orderBy('id', 'DESC')->first();
            if ($pending) {
                $paymentId = is_object($pending) ? $pending->id : $pending['id'];
            }
            log_message('info', "[CaptivePortal] Identified User ID: {$userId}, Payment ID: {$paymentId}");
        } elseif ($pppoeName !== null) {
            log_message('warning', "[CaptivePortal] Could not map PPPoE user '{$pppoeName}' to a customer.");
        }

        return [$foundUser, $paymentId];
    }

    /**
     * Backup of complex logic
     */
    public function index_complex_backup()
    {
    // ... skipped
    }
}
