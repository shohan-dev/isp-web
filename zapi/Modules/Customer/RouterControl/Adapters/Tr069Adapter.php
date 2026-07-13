<?php

namespace Zapi\Modules\Customer\RouterControl\Adapters;

use Zapi\Modules\Customer\RouterControl\Contracts\RouterControllerInterface;
use Zapi\Modules\Customer\RouterControl\Support\GenieAcsClient;
use Zapi\Modules\Customer\RouterControl\Support\CpeParamResolver;

/**
 * TR-069 / CWMP home-CPE adapter via GenieACS (Phase 5).
 *
 *   reboot       → Reboot RPC ({"name":"reboot"})
 *   change_wifi  → setParameterValues on per-model SSID + Security.KeyPassphrase
 *                  (paths resolved per device model by CpeParamResolver)
 *   list_devices → refreshObject + read Hosts.Host.* from the device document
 *
 * Active only when GenieACS NBI is configured (env genieacs.nbiUrl). When the
 * ACS or device is unreachable, methods return 'unsupported' so the dispatcher
 * falls back to the in-app WebView guided path. A queued task (HTTP 202) is
 * returned as 'pending' — a real outcome that applies on the next inform.
 *
 * Specs cross-checked (high confidence); validate against the deployed GenieACS
 * + real devices before production. See docs/router-control-system-design.md §5.2.
 */
class Tr069Adapter implements RouterControllerInterface
{
    private GenieAcsClient $client;
    private CpeParamResolver $resolver;

    public function __construct()
    {
        $this->client   = new GenieAcsClient();
        $this->resolver = new CpeParamResolver($this->client);
    }

    public function capabilityType(): string
    {
        return 'tr069';
    }

    public function supports(string $action): bool
    {
        return $this->client->isConfigured();
    }

    private function deviceId(object $target): ?string
    {
        $id = $target->acs_device_id ?? null;
        return !empty($id) ? (string) $id : null;
    }

    public function reboot(object $target, array $ctx): array
    {
        $id = $this->deviceId($target);
        if ($id === null) {
            return ['status' => 'unsupported'];
        }

        $r = $this->client->reboot($id);
        if (!$r['ok']) {
            return ['status' => 'unsupported']; // ACS/device unreachable → WebView fallback
        }

        return [
            'status'         => $r['queued'] ? 'pending' : 'success',
            'transport_used' => 'tr069',
            'message'        => $r['queued']
                ? 'রিস্টার্ট অনুরোধ পাঠানো হয়েছে। রাউটারটি অনলাইনে এলে কিছুক্ষণের মধ্যে রিস্টার্ট হবে।'
                : 'আপনার রাউটার রিস্টার্ট করা হয়েছে। কয়েক সেকেন্ডের মধ্যে আবার যুক্ত হবে।',
            'steps'          => [],
        ];
    }

    public function changeWifi(object $target, array $wifi, array $ctx): array
    {
        $id  = $this->deviceId($target);
        $map = $id !== null ? $this->resolver->resolve($id) : null;
        if ($id === null || $map === null) {
            return ['status' => 'unsupported'];
        }

        $band     = $wifi['band'] ?? 'both';
        $ssid     = (string) ($wifi['ssid'] ?? '');
        $password = (string) ($wifi['password'] ?? '');

        $values = [];
        if ($band === 'both' || $band === '2g') {
            if (!empty($map->ssid_2g_path)) {
                $values[] = [$map->ssid_2g_path, $ssid, 'xsd:string'];
            }
            if (!empty($map->pass_2g_path)) {
                $values[] = [$map->pass_2g_path, $password, 'xsd:string'];
            }
        }
        if ($band === 'both' || $band === '5g') {
            if (!empty($map->ssid_5g_path)) {
                $values[] = [$map->ssid_5g_path, $ssid, 'xsd:string'];
            }
            if (!empty($map->pass_5g_path)) {
                $values[] = [$map->pass_5g_path, $password, 'xsd:string'];
            }
        }

        if (empty($values)) {
            return ['status' => 'unsupported'];
        }

        $r = $this->client->setParameterValues($id, $values);
        if (!$r['ok']) {
            return ['status' => 'unsupported'];
        }

        return [
            'status'         => $r['queued'] ? 'pending' : 'success',
            'transport_used' => 'tr069',
            'message'        => $r['queued']
                ? 'ওয়াইফাই পরিবর্তনের অনুরোধ পাঠানো হয়েছে। রাউটারটি অনলাইনে এলে প্রয়োগ হবে।'
                : 'আপনার ওয়াইফাই নাম ও পাসওয়ার্ড পরিবর্তন করা হয়েছে।',
            'steps'          => [],
        ];
    }

    public function listDevices(object $target, array $ctx): array
    {
        $id  = $this->deviceId($target);
        $map = $id !== null ? $this->resolver->resolve($id) : null;
        if ($id === null || $map === null || empty($map->hosts_table_path)) {
            return ['status' => 'unsupported'];
        }

        $object = rtrim($map->hosts_table_path, '.');

        // Ask the CPE to refresh its Hosts table, then read it back from the doc.
        $this->client->refreshObject($id, $object);
        $doc = $this->client->queryDevice($id, [$object]);
        if ($doc === null) {
            return ['status' => 'unsupported'];
        }

        $devices = $this->parseHosts($doc, $map->hosts_table_path, $map->host_mac_field ?: 'MACAddress');

        return [
            'status'                 => 'success',
            'transport_used'         => 'tr069',
            'connection'             => ['online' => true, 'ip' => null, 'mac' => null, 'uptime' => null],
            'devices'                => $devices,
            'total_devices'          => count($devices),
            'home_devices_supported' => true,
            'web_admin_url'          => $target->web_admin_url ?? 'http://192.168.0.1',
            'message'                => empty($devices)
                ? 'এই মুহূর্তে কোনো যুক্ত ডিভাইস পাওয়া যায়নি। রাউটারটি অনলাইনে থাকলে কিছুক্ষণ পর আবার চেষ্টা করুন।'
                : 'আপনার নেটওয়ার্কে যুক্ত ডিভাইসগুলো নিচে দেখানো হলো।',
        ];
    }

    /** Walk the Hosts.Host table inside a GenieACS device document. */
    private function parseHosts(array $doc, string $hostsPath, string $macField): array
    {
        $segments = array_values(array_filter(explode('.', $hostsPath), fn ($s) => $s !== ''));
        $node     = $doc;
        foreach ($segments as $seg) {
            if (is_array($node) && array_key_exists($seg, $node)) {
                $node = $node[$seg];
            } else {
                return [];
            }
        }
        if (!is_array($node)) {
            return [];
        }

        $devices = [];
        foreach ($node as $key => $host) {
            if (!ctype_digit((string) $key) || !is_array($host)) {
                continue; // skip metadata keys, keep numeric instances
            }
            $mac    = $this->client->value($host[$macField] ?? null);
            $ip     = $this->client->value($host['IPAddress'] ?? null);
            $name   = $this->client->value($host['HostName'] ?? null);
            $active = $this->client->value($host['Active'] ?? null);
            if ($mac === null && $ip === null) {
                continue;
            }
            $devices[] = [
                'mac'      => $mac !== null ? strtoupper((string) $mac) : null,
                'ip'       => $ip,
                'hostname' => $name,
                'online'   => in_array(strtolower((string) $active), ['true', '1'], true),
            ];
        }
        return $devices;
    }
}
