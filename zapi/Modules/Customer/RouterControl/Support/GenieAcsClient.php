<?php

namespace Zapi\Modules\Customer\RouterControl\Support;

/**
 * Thin PHP client for the GenieACS Northbound Interface (NBI, default :7557).
 *
 * The NBI has NO built-in auth and should bind to loopback — this backend must
 * be the only thing that can reach it. Configure the base URL via env
 * `genieacs.nbiUrl` (e.g. http://127.0.0.1:7557). When unset, isConfigured()
 * returns false and the Tr069Adapter degrades to the web-UI guided fallback.
 *
 * Task results for getParameterValues/refreshObject are asynchronous: GenieACS
 * writes them into the device document, which is then read via queryDevice().
 * See docs/router-control-system-design.md §5.2 (specs cross-checked, high
 * confidence) — validate against your deployed GenieACS version before relying
 * on exact field names.
 */
class GenieAcsClient
{
    private string $base;
    private int $timeoutMs;

    public function __construct(?string $base = null, ?int $timeoutMs = null)
    {
        $this->base      = rtrim($base ?? (string) env('genieacs.nbiUrl', ''), '/');
        $this->timeoutMs = $timeoutMs ?? (int) env('genieacs.taskTimeoutMs', 3000);
    }

    public function isConfigured(): bool
    {
        return $this->base !== '';
    }

    /**
     * Build a URL-path-safe device id from its parts. GenieACS device _id is
     * OUI-ProductClass-SerialNumber; '-' is the field separator, so dashes/%
     * INSIDE a field are escaped (official examples double-encode dashes).
     */
    public static function encodeDeviceId(string $oui, string $productClass, string $serial): string
    {
        $esc = static fn (string $f): string => str_replace(['%', '-'], ['%2525', '%252D'], $f);
        return $esc($oui) . '-' . $esc($productClass) . '-' . $esc($serial);
    }

    /* ----- task RPCs ----- */

    /**
     * Enqueue a task. With $connectionRequest the ACS tries to run it now.
     * @return array{ok:bool, http:int, queued:bool, body:mixed}
     *   http 200 = executed now, 202 = queued for next inform (both are fine).
     */
    public function enqueueTask(string $deviceId, array $task, bool $connectionRequest = true): array
    {
        $qs  = 'timeout=' . $this->timeoutMs . ($connectionRequest ? '&connection_request' : '');
        $url = $this->base . '/devices/' . $this->pathId($deviceId) . '/tasks?' . $qs;
        $r   = $this->http('POST', $url, json_encode($task));
        $r['queued'] = ($r['http'] === 202);
        return $r;
    }

    public function reboot(string $deviceId): array
    {
        return $this->enqueueTask($deviceId, ['name' => 'reboot']);
    }

    public function factoryReset(string $deviceId): array
    {
        return $this->enqueueTask($deviceId, ['name' => 'factoryReset']);
    }

    /** @param array<int, array{0:string,1:mixed,2?:string}> $values [path, value, xsdType?] */
    public function setParameterValues(string $deviceId, array $values): array
    {
        return $this->enqueueTask($deviceId, ['name' => 'setParameterValues', 'parameterValues' => $values]);
    }

    /** @param string[] $names */
    public function getParameterValues(string $deviceId, array $names): array
    {
        return $this->enqueueTask($deviceId, ['name' => 'getParameterValues', 'parameterNames' => array_values($names)]);
    }

    public function refreshObject(string $deviceId, string $objectName): array
    {
        return $this->enqueueTask($deviceId, ['name' => 'refreshObject', 'objectName' => $objectName]);
    }

    /* ----- reads ----- */

    /**
     * Fetch a device document (optionally projected). Returns the first match
     * or null. Each parameter is an object like {_value, _type, _timestamp}.
     *
     * @param string[] $projection dot-separated parameter paths
     */
    public function queryDevice(string $deviceId, array $projection = []): ?array
    {
        $url = $this->base . '/devices/?query=' . rawurlencode(json_encode(['_id' => $deviceId]));
        if (!empty($projection)) {
            $url .= '&projection=' . rawurlencode(implode(',', $projection));
        }
        $r = $this->http('GET', $url);
        if (!$r['ok'] || !is_array($r['body'])) {
            return null;
        }
        return $r['body'][0] ?? null;
    }

    /**
     * Read DeviceInfo and detect the data model root.
     * @return array{data_model:string, manufacturer:?string, product_class:?string, model:?string}|null
     */
    public function getDeviceInfo(string $deviceId): ?array
    {
        $doc = $this->queryDevice($deviceId, [
            'InternetGatewayDevice.DeviceInfo.Manufacturer',
            'InternetGatewayDevice.DeviceInfo.ProductClass',
            'InternetGatewayDevice.DeviceInfo.ModelName',
            'Device.DeviceInfo.Manufacturer',
            'Device.DeviceInfo.ProductClass',
            'Device.DeviceInfo.ModelName',
        ]);
        if ($doc === null) {
            return null;
        }

        $isTr181 = isset($doc['Device']);
        $root    = $isTr181 ? 'Device' : 'InternetGatewayDevice';
        $info    = $doc[$root]['DeviceInfo'] ?? [];

        return [
            'data_model'    => $isTr181 ? 'tr181' : 'tr098',
            'manufacturer'  => $this->value($info['Manufacturer'] ?? null),
            'product_class' => $this->value($info['ProductClass'] ?? null),
            'model'         => $this->value($info['ModelName'] ?? null),
        ];
    }

    /** Extract a GenieACS parameter scalar ({_value:...}) or pass through. */
    public function value($node)
    {
        if (is_array($node) && array_key_exists('_value', $node)) {
            return $node['_value'];
        }
        return is_array($node) ? null : $node;
    }

    /* ----- internals ----- */

    private function pathId(string $deviceId): string
    {
        // Preserve '-' field separators; encode other unsafe chars. Embedded
        // dashes inside a field should already be %252D via encodeDeviceId().
        return rawurlencode($deviceId);
    }

    /**
     * @return array{ok:bool, http:int, body:mixed, error?:string}
     */
    private function http(string $method, string $url, ?string $body = null): array
    {
        if (!function_exists('curl_init')) {
            log_message('error', 'GenieACS NBI: cURL extension unavailable');
            return ['ok' => false, 'http' => 0, 'body' => null, 'error' => 'no_curl'];
        }

        $ch = curl_init($url);
        $opts = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT_MS     => $this->timeoutMs + 7000,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            log_message('error', "GenieACS NBI {$method} {$url} failed: {$err}");
            return ['ok' => false, 'http' => 0, 'body' => null, 'error' => $err];
        }

        $decoded = json_decode((string) $resp, true);
        return [
            'ok'   => $code >= 200 && $code < 300,
            'http' => $code,
            'body' => $decoded ?? $resp,
        ];
    }
}
