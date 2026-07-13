<?php

namespace Zapi\Modules\Common\CaptivePortal\Controllers;

use App\Controllers\BaseController;

class CaptivePortalController extends BaseController
{
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
        set_time_limit(180);

        $isLocalhost = ($userIp === '::1' || $userIp === '127.0.0.1');
        $routersToCheck = [];
        if ($routerId) {
            $specificRouter = $routerModel->find($routerId);
            if ($specificRouter) {
                $routersToCheck[] = $specificRouter;
            }
        }
        if (empty($routersToCheck) && !$isLocalhost) {
            $routersToCheck = $routerModel->where('status', 'active')->findAll();
        }

        if (!$isLocalhost) {
            foreach ($routersToCheck as $router) {
                try {
                    $ridToCheck = is_array($router) ? $router['id'] : $router->id;
                    $client = routerClient($ridToCheck);
                    if (!$client) {
                        continue;
                    }
                    $query = (new \RouterOS\Query('/ppp/active/print'))->where('address', $userIp);
                    $session = $client->query($query)->read();
                    if (!empty($session[0]['name'])) {
                        $pppoeName = $session[0]['name'];
                        $routerId = $ridToCheck;
                        break;
                    }
                } catch (\Exception $e) {
                    log_message('error', "[CaptivePortal] Connection error: " . $e->getMessage());
                }
            }
        }

        if ($pppoeName) {
            $routerData = $routerDataModel->where('pppoe_secret', $pppoeName)->first();
            if ($routerData) {
                $uid = is_array($routerData) ? $routerData['user_id'] : $routerData->user_id;
                $foundUser = $userModel->find($uid);
            }
        }

        $paymentId = 12345;
        if ($foundUser) {
            $userId = is_object($foundUser) ? $foundUser->id : $foundUser['id'];
            $pending = $paymentModel->where(['user_id' => $userId, 'status' => 'pending'])->orderBy('id', 'DESC')->first();
            if ($pending) {
                $paymentId = is_object($pending) ? $pending->id : $pending['id'];
            }
        }

        return view('captive/expired_generic', [
            'payment_id' => $paymentId,
            'user' => $foundUser,
            'user_ip' => $userIp,
            'router_id' => $routerId,
        ]);
    }
}
