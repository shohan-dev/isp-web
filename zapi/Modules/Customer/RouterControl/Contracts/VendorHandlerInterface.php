<?php

namespace Zapi\Modules\Customer\RouterControl\Contracts;

/**
 * Per-brand home-CPE handler used by VendorApiAdapter.
 *
 * IMPORTANT constraint: the ISP backend cannot reach a customer's home router on
 * its private LAN IP (RFC1918). A vendor handler therefore must talk to the
 * vendor's CLOUD API (e.g. TP-Link/Tenda/D-Link cloud) or an ISP-managed device
 * with a reachable address — never a LAN scrape. When a brand has no cloud
 * contract configured, isConfigured() returns false and the dispatcher falls
 * back to the in-app WebView guided path.
 *
 * Methods return the same ActionResult arrays as RouterControllerInterface:
 *   status: success | pending | failed | unsupported  (+ message, steps, ...).
 */
interface VendorHandlerInterface
{
    public function brand(): string;

    /** Whether this brand is wired up (cloud base URL + credentials + device ref). */
    public function isConfigured(object $target): bool;

    public function reboot(object $target, array $ctx): array;

    /** @param array{ssid:string,password:string,band:string} $wifi */
    public function changeWifi(object $target, array $wifi, array $ctx): array;

    public function listDevices(object $target, array $ctx): array;
}
