<?php

namespace Zapi\Modules\Customer\RouterControl\Vendor;

use Zapi\Modules\Customer\RouterControl\Contracts\VendorHandlerInterface;

/**
 * Reference vendor handler that talks to a brand CLOUD API over HTTPS.
 *
 * This is the reusable template every real brand handler follows: read the
 * brand's cloud base URL + API key from config, send the device id (here, the
 * target MAC) plus parameters, and map the response to an ActionResult. It is
 * intentionally generic — each real brand (TP-Link, Tenda, D-Link cloud, …)
 * differs in auth, paths, and payloads, so subclass/override the request
 * mapping per brand.
 *
 * Config (per brand, in .env): vendor.<brand>.apiUrl, vendor.<brand>.apiKey
 * When unset, isConfigured() is false and the dispatcher uses the WebView
 * fallback. NOTE: home routers on a LAN IP are NOT reachable from the backend —
 * only a cloud API (or ISP-managed device) can be driven server-side.
 */
class GenericCloudVendorHandler implements VendorHandlerInterface
{
    private string $brand;
    private string $apiUrl;
    private string $apiKey;

    public function __construct(string $brand)
    {
        $this->brand  = strtolower($brand);
        $this->apiUrl = rtrim((string) env("vendor.{$this->brand}.apiUrl", ''), '/');
        $this->apiKey = (string) env("vendor.{$this->brand}.apiKey", '');
    }

    public function brand(): string
    {
        return $this->brand;
    }

    public function isConfigured(object $target): bool
    {
        return $this->apiUrl !== '' && $this->apiKey !== '' && $this->deviceRef($target) !== null;
    }

    private function deviceRef(object $target): ?string
    {
        // Cloud APIs typically key a device by MAC or serial. Reuse the
        // registry's mac (or acs_device_id) as the cloud device reference.
        foreach (['mac', 'acs_device_id'] as $field) {
            $v = $target->{$field} ?? null;
            if (!empty($v)) {
                return (string) $v;
            }
        }
        return null;
    }

    public function reboot(object $target, array $ctx): array
    {
        if (!$this->isConfigured($target)) {
            return ['status' => 'unsupported'];
        }
        $r = $this->call('reboot', ['device' => $this->deviceRef($target)]);
        if (!$r['ok']) {
            return ['status' => 'unsupported'];
        }
        return [
            'status'         => 'success',
            'transport_used' => 'vendor_api:' . $this->brand,
            'message'        => 'আপনার রাউটার রিস্টার্ট করা হয়েছে।',
            'steps'          => [],
        ];
    }

    public function changeWifi(object $target, array $wifi, array $ctx): array
    {
        if (!$this->isConfigured($target)) {
            return ['status' => 'unsupported'];
        }
        $r = $this->call('wifi', [
            'device'   => $this->deviceRef($target),
            'ssid'     => $wifi['ssid'] ?? '',
            'password' => $wifi['password'] ?? '',
            'band'     => $wifi['band'] ?? 'both',
        ]);
        if (!$r['ok']) {
            return ['status' => 'unsupported'];
        }
        return [
            'status'         => 'success',
            'transport_used' => 'vendor_api:' . $this->brand,
            'message'        => 'আপনার ওয়াইফাই নাম ও পাসওয়ার্ড পরিবর্তন করা হয়েছে।',
            'steps'          => [],
        ];
    }

    public function listDevices(object $target, array $ctx): array
    {
        if (!$this->isConfigured($target)) {
            return ['status' => 'unsupported'];
        }
        $r = $this->call('devices', ['device' => $this->deviceRef($target)], 'GET');
        if (!$r['ok']) {
            return ['status' => 'unsupported'];
        }

        $raw = (is_array($r['body']) && isset($r['body']['devices']) && is_array($r['body']['devices']))
            ? $r['body']['devices']
            : [];
        $devices = [];
        foreach ($raw as $d) {
            if (!is_array($d)) {
                continue;
            }
            $devices[] = [
                'mac'      => isset($d['mac']) ? strtoupper((string) $d['mac']) : null,
                'ip'       => $d['ip'] ?? null,
                'hostname' => $d['name'] ?? ($d['hostname'] ?? null),
                'online'   => (bool) ($d['online'] ?? false),
            ];
        }

        return [
            'status'                 => 'success',
            'transport_used'         => 'vendor_api:' . $this->brand,
            'connection'             => ['online' => true, 'ip' => null, 'mac' => null, 'uptime' => null],
            'devices'                => $devices,
            'total_devices'          => count($devices),
            'home_devices_supported' => true,
            'web_admin_url'          => $target->web_admin_url ?? 'http://192.168.0.1',
            'message'                => empty($devices)
                ? 'এই মুহূর্তে কোনো যুক্ত ডিভাইস পাওয়া যায়নি।'
                : 'আপনার নেটওয়ার্কে যুক্ত ডিভাইসগুলো নিচে দেখানো হলো।',
        ];
    }

    /**
     * Generic cloud call. Real brands override path/auth/payload shape.
     * @return array{ok:bool, http:int, body:mixed}
     */
    private function call(string $path, array $payload, string $method = 'POST'): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'http' => 0, 'body' => null];
        }

        $url = $this->apiUrl . '/' . ltrim($path, '/');
        $ch  = curl_init();
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ];
        if ($method === 'GET') {
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($payload);
        } else {
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
        }
        curl_setopt_array($ch, $opts);

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            log_message('error', "vendor_api({$this->brand}) {$method} {$url} failed: {$err}");
            return ['ok' => false, 'http' => 0, 'body' => null];
        }

        return [
            'ok'   => $code >= 200 && $code < 300,
            'http' => $code,
            'body' => json_decode((string) $resp, true) ?? $resp,
        ];
    }
}
