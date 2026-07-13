<?php

namespace Zapi\Modules\Customer\RouterControl\Support;

/**
 * Resolves the TR-069 parameter-path map for a CPE. Looks up a persisted map by
 * OUI + ProductClass; if absent, reads DeviceInfo via the NBI, detects the data
 * model root (TR-098 InternetGatewayDevice vs TR-181 Device), seeds BBF-standard
 * default paths, persists them, and returns the map.
 *
 * IMPORTANT: the default 2.4/5 GHz indices are vendor-typical, NOT universal
 * (e.g. Huawei HG8245 maps WLANConfiguration 1=2.4GHz, 5=5GHz). The persisted
 * row should be validated/overridden per real model. Set the human passphrase
 * (KeyPassphrase), never PreSharedKey (auto-derived).
 */
class CpeParamResolver
{
    public function __construct(private GenieAcsClient $client)
    {
    }

    /**
     * @return object|null  cpe_param_maps row (object) with resolved paths
     */
    public function resolve(string $acsDeviceId)
    {
        [$oui, $productClass] = $this->splitId($acsDeviceId);
        $model = model('App\Models\CpeParamMapModel');

        if ($oui !== null) {
            $row = $model->where(['oui' => $oui, 'product_class' => $productClass])->first();
            if ($row) {
                return $row;
            }
        }

        $info = $this->client->getDeviceInfo($acsDeviceId);
        if ($info === null) {
            return null;
        }

        $defaults = $this->defaultsFor($info['data_model']);
        $now      = date('Y-m-d H:i:s');
        $data     = array_merge($defaults, [
            'oui'           => $oui ?? '',
            'product_class' => $info['product_class'] ?? ($productClass ?? ''),
            'manufacturer'  => $info['manufacturer'] ?? '',
            'data_model'    => $info['data_model'],
            'notes'         => 'auto-seeded from BBF defaults; verify indices against the real device',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $id = $model->insert($data, true);
        return $id ? $model->find($id) : (object) $data;
    }

    /** BBF-standard default parameter paths per data model. */
    public function defaultsFor(string $dataModel): array
    {
        if ($dataModel === 'tr181') {
            return [
                'ssid_2g_path'     => 'Device.WiFi.SSID.1.SSID',
                'ssid_5g_path'     => 'Device.WiFi.SSID.2.SSID',
                'pass_2g_path'     => 'Device.WiFi.AccessPoint.1.Security.KeyPassphrase',
                'pass_5g_path'     => 'Device.WiFi.AccessPoint.2.Security.KeyPassphrase',
                'hosts_table_path' => 'Device.Hosts.Host.',
                'host_mac_field'   => 'PhysAddress',
            ];
        }

        // TR-098 (InternetGatewayDevice). Huawei-typical band->index mapping.
        return [
            'ssid_2g_path'     => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
            'ssid_5g_path'     => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.5.SSID',
            'pass_2g_path'     => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase',
            'pass_5g_path'     => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.5.PreSharedKey.1.KeyPassphrase',
            'hosts_table_path' => 'InternetGatewayDevice.LANDevice.1.Hosts.Host.',
            'host_mac_field'   => 'MACAddress',
        ];
    }

    /** GenieACS _id = OUI-ProductClass-Serial. Returns [oui, productClass]. */
    private function splitId(string $deviceId): array
    {
        $parts = explode('-', $deviceId);
        if (count($parts) < 2) {
            return [null, null];
        }
        return [$parts[0], $parts[1]];
    }
}
