<?php

namespace Zapi\Modules\Customer\RouterControl\Adapters;

use Zapi\Modules\Customer\RouterControl\Contracts\RouterControllerInterface;

/**
 * ISP MikroTik (PPPoE) adapter. Reboot = drop ONLY this subscriber's session
 * (never /system/reboot). Connected-devices = the subscriber's connection (the
 * home-LAN devices are behind the customer's NAT and not visible here).
 * Change-WiFi is NOT supported for a customer-owned home AP → the dispatcher
 * falls through to the web-UI guided fallback.
 */
class MikrotikAdapter implements RouterControllerInterface
{
    public function capabilityType(): string
    {
        return 'mikrotik_pppoe';
    }

    public function supports(string $action): bool
    {
        return in_array($action, ['reboot', 'list_devices'], true);
    }

    public function reboot(object $target, array $ctx): array
    {
        helper('router');
        $routerId = $target->router_id ?? null;
        $pppoe    = $target->pppoe_username ?? null;
        if (!$routerId || !$pppoe) {
            return ['status' => 'unsupported', 'transport_used' => 'mikrotik_unavailable'];
        }

        $r = rebootSubscriberSession($routerId, $pppoe);
        if (in_array($r['status'], ['success', 'offline'], true)) {
            return [
                'status'         => 'success',
                'transport_used' => 'mikrotik_pppoe_session_reset',
                'message'        => 'আপনার সংযোগ রিসেট করা হয়েছে। কয়েক সেকেন্ডের মধ্যে রাউটার স্বয়ংক্রিয়ভাবে আবার যুক্ত হবে।',
                'steps'          => [
                    'সংযোগ রিসেট করা হয়েছে',
                    '১০–৩০ সেকেন্ড অপেক্ষা করুন',
                    'ইন্টারনেট স্বয়ংক্রিয়ভাবে ফিরে আসবে',
                ],
                'important'      => 'রিস্টার্টের সময় রাউটারের পাওয়ার বন্ধ করবেন না।',
            ];
        }

        // Unreachable / failed → let the dispatcher fall back to guided manual.
        return ['status' => 'unsupported', 'transport_used' => 'mikrotik_unreachable'];
    }

    public function changeWifi(object $target, array $wifi, array $ctx): array
    {
        // The ISP MikroTik cannot change a customer-owned home router's WiFi.
        return ['status' => 'unsupported'];
    }

    public function listDevices(object $target, array $ctx): array
    {
        helper('router');
        $routerId = $target->router_id ?? null;
        $pppoe    = $target->pppoe_username ?? null;
        $session  = ($routerId && $pppoe) ? getSubscriberActiveSession($routerId, $pppoe) : null;

        return [
            'status'                 => 'success',
            'transport_used'         => 'mikrotik_pppoe',
            'connection'             => [
                'online' => $session !== null,
                'ip'     => $session['address'] ?? null,
                'mac'    => $session['caller-id'] ?? null,
                'uptime' => $session['uptime'] ?? null,
            ],
            'devices'                => [],
            'home_devices_supported' => false,
            'web_admin_url'          => $target->web_admin_url ?? 'http://192.168.0.1',
            'message'                => $session !== null
                ? 'আপনার সংযোগ সচল আছে। বাসার ওয়াইফাইয়ে যুক্ত ডিভাইস দেখতে রাউটারের অ্যাডমিন পেজ খুলুন।'
                : 'এই মুহূর্তে সংযোগ সক্রিয় দেখা যাচ্ছে না। ডিভাইস দেখতে রাউটারের অ্যাডমিন পেজ খুলুন।',
        ];
    }
}
