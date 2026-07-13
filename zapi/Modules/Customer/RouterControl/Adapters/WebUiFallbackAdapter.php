<?php

namespace Zapi\Modules\Customer\RouterControl\Adapters;

use Zapi\Modules\Customer\RouterControl\Contracts\RouterControllerInterface;

/**
 * Universal fallback. The backend cannot reach a customer's home router on its
 * private LAN IP (RFC1918), so this adapter returns a `pending` result with
 * guided Bangla steps and a web_admin_url the app opens in its in-app WebView
 * (while the phone is on the router's WiFi). Guarantees every device — even a
 * fully unmanaged one — has a working in-app path.
 */
class WebUiFallbackAdapter implements RouterControllerInterface
{
    public function capabilityType(): string
    {
        return 'web_only';
    }

    public function supports(string $action): bool
    {
        return true;
    }

    private function url(object $target): string
    {
        return $target->web_admin_url ?? 'http://192.168.0.1';
    }

    public function reboot(object $target, array $ctx): array
    {
        return [
            'status'         => 'pending',
            'transport_used' => 'manual',
            'message'        => 'অনুগ্রহ করে নিচের ধাপ অনুসরণ করে রাউটারটি রিস্টার্ট করুন, অথবা রাউটার অ্যাডমিন পেজ খুলুন।',
            'steps'          => [
                'রাউটারের পাওয়ার ক্যাবল খুলে ৩০ সেকেন্ড অপেক্ষা করুন।',
                'আবার পাওয়ার ক্যাবল লাগান।',
                '২-৩ মিনিট অপেক্ষা করুন — ইন্টারনেট ফিরে আসবে।',
            ],
            'web_admin_url'  => $this->url($target),
        ];
    }

    public function changeWifi(object $target, array $wifi, array $ctx): array
    {
        $ssid = $wifi['ssid'] ?? '';
        return [
            'status'         => 'pending',
            'transport_used' => 'manual',
            'message'        => 'ওয়াইফাইয়ের নাম/পাসওয়ার্ড পরিবর্তন করতে রাউটার অ্যাডমিন পেজ খুলুন এবং নিচের ধাপ অনুসরণ করুন।',
            'steps'          => [
                'রাউটার অ্যাডমিন পেজ খুলুন (সাধারণত http://192.168.0.1)',
                'Wireless / WiFi সেটিংসে যান',
                "নতুন নাম (SSID) দিন: {$ssid}",
                'নতুন পাসওয়ার্ড দিন এবং Save / Apply চাপুন',
            ],
            'web_admin_url'  => $this->url($target),
        ];
    }

    public function listDevices(object $target, array $ctx): array
    {
        return [
            'status'                 => 'pending',
            'transport_used'         => 'manual',
            'connection'             => ['online' => null, 'ip' => null, 'mac' => null, 'uptime' => null],
            'devices'                => [],
            'home_devices_supported' => false,
            'message'                => 'যুক্ত ডিভাইসগুলো দেখতে রাউটার অ্যাডমিন পেজ খুলুন।',
            'steps'                  => [
                'রাউটার অ্যাডমিন পেজ খুলুন (সাধারণত http://192.168.0.1)',
                'Connected Devices / DHCP Clients অংশটি দেখুন',
            ],
            'web_admin_url'          => $this->url($target),
        ];
    }
}
