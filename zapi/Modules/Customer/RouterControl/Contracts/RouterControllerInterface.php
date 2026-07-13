<?php

namespace Zapi\Modules\Customer\RouterControl\Contracts;

/**
 * Unified router-control contract. One adapter per capability_type
 * (mikrotik_pppoe / tr069 / vendor_api / web_only). The RouterControlService
 * selects an adapter by the target's capability and calls these methods.
 *
 * Every method returns an "ActionResult" array with at least:
 *   - status: 'success' | 'pending' | 'failed' | 'unsupported'
 *   - message: Bangla, user-facing
 * and optionally: transport_used, steps[], web_admin_url, connection, devices[].
 *
 * 'unsupported' or 'pending' signals the dispatcher to fall through to the
 * web-UI guided fallback adapter.
 */
interface RouterControllerInterface
{
    public function capabilityType(): string;

    /** Whether this adapter can actually perform $action ('reboot'|'change_wifi'|'list_devices'). */
    public function supports(string $action): bool;

    public function reboot(object $target, array $ctx): array;

    /** @param array{ssid:string,password:string,band:string} $wifi */
    public function changeWifi(object $target, array $wifi, array $ctx): array;

    public function listDevices(object $target, array $ctx): array;
}
