<?php

namespace Zapi\Modules\Customer\RouterControl\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;
use Zapi\Modules\Customer\RouterControl\Contracts\RouterControllerInterface;
use Zapi\Modules\Customer\RouterControl\Adapters\MikrotikAdapter;
use Zapi\Modules\Customer\RouterControl\Adapters\Tr069Adapter;
use Zapi\Modules\Customer\RouterControl\Adapters\VendorApiAdapter;
use Zapi\Modules\Customer\RouterControl\Adapters\WebUiFallbackAdapter;
use Zapi\Modules\Customer\RouterControl\Support\GenieAcsClient;
use Zapi\Modules\Customer\RouterControl\Support\CpeParamResolver;

/**
 * Unified router-control dispatcher.
 *
 * Resolves the caller's control target, picks the adapter by capability_type,
 * and runs reboot / change-wifi / list-devices. If the chosen adapter can't do
 * it (unsupported / pending), it falls through to the web-UI guided fallback so
 * the customer always gets a usable response. Ownership, cooldowns and audit
 * logging come from CustomerBaseService.
 */
class RouterControlService extends CustomerBaseService
{
    private const DEFAULT_WEB_ADMIN_URL = 'http://192.168.0.1';
    private const REBOOT_COOLDOWN       = 300;
    private const WIFI_COOLDOWN         = 120;

    /* ----- endpoints ----- */

    public function targets()
    {
        $userId = $this->effectiveUserId();
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }
        if (!$this->canPerformAction($userId, 'view_targets')) {
            return $this->respondError('You are not allowed to perform this action', 403);
        }

        $rows    = model('App\Models\ControlTargetModel')->where('user_id', $userId)->findAll();
        $targets = [];
        if (empty($rows)) {
            $targets[] = $this->targetView($this->deriveDefaultTarget($userId));
        } else {
            foreach ($rows as $r) {
                $targets[] = $this->targetView($this->normalizeTarget($r));
            }
        }

        return $this->respondSuccess(['user_id' => $userId, 'targets' => $targets]);
    }

    public function reboot()
    {
        $userId = $this->effectiveUserId();
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }
        if (!$this->getUser($userId)) {
            return $this->respondError('User not found', 404);
        }
        if (!$this->canPerformAction($userId, 'router_reboot')) {
            return $this->respondError('You are not allowed to perform this action', 403);
        }
        if (!$this->withinCooldown('router_reboot', $userId, self::REBOOT_COOLDOWN)) {
            return $this->respondSuccess($this->cooldownResult($userId, 'router_reboot'));
        }

        $target = $this->resolveTarget($userId, $this->getInputValue('target_id'));
        $res    = $this->run($target, 'reboot', fn($a) => $a->reboot($target, $this->ctx($userId)));

        $this->logAction($userId, 'router_reboot', 'Reboot via ' . ($res['transport_used'] ?? 'unknown'), ['router' => $target->router_id ?? null]);
        return $this->respondSuccess(array_merge(['user_id' => $userId, 'action' => 'router_reboot'], $res));
    }

    public function changeWifi()
    {
        $userId = $this->effectiveUserId();
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }
        if (!$this->getUser($userId)) {
            return $this->respondError('User not found', 404);
        }
        if (!$this->canPerformAction($userId, 'change_wifi')) {
            return $this->respondError('You are not allowed to perform this action', 403);
        }

        $ssid     = trim((string) $this->getInputValue('ssid'));
        $password = (string) $this->getInputValue('password');
        $band     = (string) ($this->getInputValue('band') ?: 'both');

        if ($ssid === '' || mb_strlen($ssid) > 32) {
            return $this->respondError('SSID must be between 1 and 32 characters', 400);
        }
        if (strlen($password) < 8) {
            return $this->respondError('WiFi password must be at least 8 characters', 400);
        }
        if (!$this->withinCooldown('wifi_change', $userId, self::WIFI_COOLDOWN)) {
            return $this->respondSuccess($this->cooldownResult($userId, 'wifi_change'));
        }

        $target = $this->resolveTarget($userId, $this->getInputValue('target_id'));
        $wifi   = ['ssid' => $ssid, 'password' => $password, 'band' => $band];
        $res    = $this->run($target, 'change_wifi', fn($a) => $a->changeWifi($target, $wifi, $this->ctx($userId)));

        // Never log the password.
        $this->logAction($userId, 'wifi_change', 'WiFi change (ssid=' . $ssid . ', band=' . $band . ') via ' . ($res['transport_used'] ?? 'unknown'), ['router' => $target->router_id ?? null]);
        return $this->respondSuccess(array_merge(['user_id' => $userId, 'action' => 'wifi_change'], $res));
    }

    public function devices()
    {
        $userId = $this->effectiveUserId();
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }
        if (!$this->getUser($userId)) {
            return $this->respondError('User not found', 404);
        }
        if (!$this->canPerformAction($userId, 'view_devices')) {
            return $this->respondError('You are not allowed to perform this action', 403);
        }

        $target = $this->resolveTarget($userId, $this->getInputValue('target_id'));
        $res    = $this->run($target, 'list_devices', fn($a) => $a->listDevices($target, $this->ctx($userId)));

        $this->logAction($userId, 'view_devices', 'Devices via ' . ($res['transport_used'] ?? 'unknown'), ['router' => $target->router_id ?? null]);
        return $this->respondSuccess(array_merge(['user_id' => $userId, 'action' => 'list_devices'], $res));
    }

    /**
     * Onboard a TR-069 home CPE: link an ACS device id to this customer, read
     * its DeviceInfo, seed/resolve its per-model parameter map, and create (or
     * update) a `tr069` control target. Requires GenieACS to be configured.
     */
    public function onboardTr069()
    {
        $userId = $this->effectiveUserId();
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }
        if (!$this->getUser($userId)) {
            return $this->respondError('User not found', 404);
        }
        if (!$this->canPerformAction($userId, 'onboard_tr069')) {
            return $this->respondError('You are not allowed to perform this action', 403);
        }

        $acsId = trim((string) $this->getInputValue('acs_device_id'));
        if ($acsId === '') {
            return $this->respondError('acs_device_id is required', 400);
        }

        $client = new GenieAcsClient();
        if (!$client->isConfigured()) {
            return $this->respondError('TR-069 management is not configured', 503);
        }

        $info = $client->getDeviceInfo($acsId);
        if ($info === null) {
            return $this->respondError('Device not found on the ACS (or it is offline)', 404);
        }

        // Build/persist the per-model parameter map (best-effort).
        (new CpeParamResolver($client))->resolve($acsId);

        $model    = model('App\Models\ControlTargetModel');
        $existing = $model->where(['user_id' => $userId, 'acs_device_id' => $acsId])->first();
        $now      = date('Y-m-d H:i:s');

        $row = [
            'user_id'         => $userId,
            'capability_type' => 'tr069',
            'label'           => $this->getInputValue('label') ?: ($info['model'] ?: 'আমার রাউটার'),
            'acs_device_id'   => $acsId,
            'vendor'          => $info['manufacturer'],
            'model'           => $info['model'],
            'data_model'      => $info['data_model'],
            'lan_gateway_ip'  => $this->getInputValue('lan_gateway_ip') ?: null,
            'online_status'   => 'online',
            'last_seen_at'    => $now,
            'updated_at'      => $now,
        ];

        if ($existing) {
            $model->update($existing->id, $row);
            $id = (int) $existing->id;
        } else {
            $row['created_at'] = $now;
            $id = (int) $model->insert($row, true);
        }

        $this->logAction($userId, 'onboard_tr069', 'Linked TR-069 device ' . $acsId);
        $target = $this->normalizeTarget($model->find($id));
        return $this->respondSuccess(['user_id' => $userId, 'target' => $this->targetView($target)]);
    }

    /* ----- dispatch ----- */

    /**
     * Run $action on the target's adapter; fall through to the web-UI guided
     * fallback only when the adapter cannot handle it ('unsupported'). A
     * 'pending' result (e.g. a TR-069 task queued for the next inform) is a
     * legitimate terminal outcome and is returned as-is.
     */
    private function run(object $target, string $action, callable $call): array
    {
        $adapter = $this->adapterFor($target->capability_type ?? 'web_only');
        if ($adapter && $adapter->supports($action)) {
            $res = $call($adapter);
            if (($res['status'] ?? '') !== 'unsupported') {
                return $res; // definitive (success / pending-queued / failed)
            }
        }

        // Fallback adapter exposes the same method signatures.
        $fallback = new WebUiFallbackAdapter();
        return $call($fallback);
    }

    private function adapterFor(string $capability): ?RouterControllerInterface
    {
        switch ($capability) {
            case 'mikrotik_pppoe':
                return new MikrotikAdapter();
            case 'tr069':
                return new Tr069Adapter();
            case 'vendor_api':
                return new VendorApiAdapter();
            case 'web_only':
                return new WebUiFallbackAdapter();
            default:
                return null;
        }
    }

    /* ----- target resolution ----- */

    private function resolveTarget(int $userId, $targetId): object
    {
        $model = model('App\Models\ControlTargetModel');

        if (!empty($targetId)) {
            $row = $model->where(['id' => (int) $targetId, 'user_id' => $userId])->first();
            if ($row) {
                return $this->normalizeTarget($row);
            }
        }

        $rows = $model->where('user_id', $userId)->findAll();
        if (!empty($rows)) {
            return $this->normalizeTarget($rows[0]);
        }

        return $this->deriveDefaultTarget($userId);
    }

    private function deriveDefaultTarget(int $userId): object
    {
        helper('router');
        $user     = $this->getUser($userId);
        $routerId = $user->router_id ?? null;
        $pppoe    = getSubscriberPppoeName($userId);

        if ($routerId && $pppoe) {
            return (object) [
                'id'              => null,
                'user_id'         => $userId,
                'capability_type' => 'mikrotik_pppoe',
                'label'           => 'আমার সংযোগ',
                'router_id'       => $routerId,
                'pppoe_username'  => $pppoe,
                'web_admin_url'   => self::DEFAULT_WEB_ADMIN_URL,
            ];
        }

        return (object) [
            'id'              => null,
            'user_id'         => $userId,
            'capability_type' => 'web_only',
            'label'           => 'আমার রাউটার',
            'router_id'       => null,
            'pppoe_username'  => null,
            'web_admin_url'   => self::DEFAULT_WEB_ADMIN_URL,
        ];
    }

    private function normalizeTarget($row): object
    {
        $t = (object) $row;
        if (empty($t->web_admin_url)) {
            $t->web_admin_url = !empty($t->lan_gateway_ip) ? $t->lan_gateway_ip : self::DEFAULT_WEB_ADMIN_URL;
        }
        return $t;
    }

    private function targetView(object $t): array
    {
        $adapter = $this->adapterFor($t->capability_type ?? 'web_only');
        $actions = [];
        foreach (['reboot', 'change_wifi', 'list_devices'] as $a) {
            $actions[$a] = ($adapter && $adapter->supports($a)) ? 'auto' : 'manual';
        }

        return [
            'id'              => $t->id ?? null,
            'label'           => $t->label ?? 'আমার রাউটার',
            'capability_type' => $t->capability_type ?? 'web_only',
            'web_admin_url'   => $t->web_admin_url ?? self::DEFAULT_WEB_ADMIN_URL,
            'actions'         => $actions,
        ];
    }

    /* ----- helpers ----- */

    private function effectiveUserId(): ?int
    {
        $uid = $this->getInputValue('user_id');
        if ($uid !== null && $uid !== '') {
            return (int) $uid;
        }
        return $this->resolveAccessTokenUserId();
    }

    private function ctx(int $userId): array
    {
        return ['user_id' => $userId, 'actor' => $this->resolveAccessTokenUserId()];
    }

    private function cooldownResult(int $userId, string $action): array
    {
        return [
            'user_id'        => $userId,
            'action'         => $action,
            'status'         => 'pending',
            'transport_used' => 'cooldown',
            'message'        => 'কিছুক্ষণ আগেই অনুরোধ পাঠানো হয়েছে। অনুগ্রহ করে কয়েক মিনিট পর আবার চেষ্টা করুন।',
            'steps'          => [],
        ];
    }
}
