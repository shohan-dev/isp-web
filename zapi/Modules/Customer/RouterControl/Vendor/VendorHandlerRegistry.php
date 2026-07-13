<?php

namespace Zapi\Modules\Customer\RouterControl\Vendor;

use Zapi\Modules\Customer\RouterControl\Contracts\VendorHandlerInterface;

/**
 * Maps a control target's `vendor` brand to a VendorHandlerInterface.
 *
 * Brands without a dedicated class fall back to GenericCloudVendorHandler, which
 * reads per-brand cloud config from env (vendor.<brand>.apiUrl / .apiKey). To
 * add a real brand with custom auth/paths, implement VendorHandlerInterface and
 * register it in $handlers below.
 */
class VendorHandlerRegistry
{
    /** Brand (lowercase) => fully-qualified handler class with a (string $brand) ctor. */
    private static array $handlers = [
        // 'tplink' => \Zapi\Modules\Customer\RouterControl\Vendor\TpLinkCloudHandler::class,
        // 'tenda'  => \Zapi\Modules\Customer\RouterControl\Vendor\TendaCloudHandler::class,
    ];

    /** Brands we are willing to attempt via the generic cloud template. */
    private static array $genericBrands = ['tplink', 'tp-link', 'tenda', 'dlink', 'd-link', 'huawei'];

    public static function resolve(?string $vendor): ?VendorHandlerInterface
    {
        if (empty($vendor)) {
            return null;
        }
        $brand = strtolower(trim($vendor));

        if (isset(self::$handlers[$brand])) {
            $class = self::$handlers[$brand];
            return new $class($brand);
        }

        if (in_array($brand, self::$genericBrands, true)) {
            return new GenericCloudVendorHandler($brand);
        }

        // Unknown brand → still try the generic handler; it self-gates on config
        // (env), returning 'unsupported' when not wired, so the dispatcher falls
        // back to the WebView guided path.
        return new GenericCloudVendorHandler($brand);
    }
}
