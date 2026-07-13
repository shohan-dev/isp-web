<?php

namespace Zapi\Modules\Customer\RouterControl\Adapters;

use Zapi\Modules\Customer\RouterControl\Contracts\RouterControllerInterface;
use Zapi\Modules\Customer\RouterControl\Vendor\VendorHandlerRegistry;

/**
 * Vendor HTTP (cloud) API adapter for home CPE (Phase 6).
 *
 * Dispatches to a per-brand handler resolved from the target's `vendor` field
 * (see VendorHandlerRegistry). Each handler talks to the vendor's CLOUD API —
 * the backend cannot reach a home router's LAN IP. When no configured handler
 * exists for the brand, methods return 'unsupported' and the dispatcher falls
 * back to the in-app WebView guided path. See docs/router-control-system-design.md §5.3.
 */
class VendorApiAdapter implements RouterControllerInterface
{
    public function capabilityType(): string
    {
        return 'vendor_api';
    }

    public function supports(string $action): bool
    {
        // Capability exists; per-target/brand availability is decided in the
        // methods (handlers self-gate on config) so unconfigured brands fall
        // back to the WebView via the dispatcher's 'unsupported' handling.
        return true;
    }

    public function reboot(object $target, array $ctx): array
    {
        $handler = VendorHandlerRegistry::resolve($target->vendor ?? null);
        if ($handler === null || !$handler->isConfigured($target)) {
            return ['status' => 'unsupported'];
        }
        return $handler->reboot($target, $ctx);
    }

    public function changeWifi(object $target, array $wifi, array $ctx): array
    {
        $handler = VendorHandlerRegistry::resolve($target->vendor ?? null);
        if ($handler === null || !$handler->isConfigured($target)) {
            return ['status' => 'unsupported'];
        }
        return $handler->changeWifi($target, $wifi, $ctx);
    }

    public function listDevices(object $target, array $ctx): array
    {
        $handler = VendorHandlerRegistry::resolve($target->vendor ?? null);
        if ($handler === null || !$handler->isConfigured($target)) {
            return ['status' => 'unsupported'];
        }
        return $handler->listDevices($target, $ctx);
    }
}
