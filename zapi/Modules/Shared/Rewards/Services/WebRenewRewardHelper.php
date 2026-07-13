<?php

namespace Zapi\Modules\Shared\Rewards\Services;

use Zapi\Modules\Shared\Rewards\Support\RewardSources;

/**
 * Shared reward redemption helpers for the legacy web subscription renew flow.
 * Keeps reward logic in zapi while app/Controllers/Subscription.php calls in.
 */
class WebRenewRewardHelper
{
    /**
     * @return array{
     *   available_points:int,
     *   max_usable_points:int,
     *   points_applied:int,
     *   discount_bdt:float,
     *   final_payable:float,
     *   point_value_bdt:float,
     *   max_redeem_percent:int,
     *   enabled:bool
     * }
     */
    public static function preview(int $userId, int $packageId, int $requestedPoints = 0): array
    {
        $empty = [
            'available_points'  => 0,
            'max_usable_points' => 0,
            'points_applied'    => 0,
            'discount_bdt'      => 0.0,
            'final_payable'     => 0.0,
            'point_value_bdt'   => 1.0,
            'max_redeem_percent'=> 100,
            'enabled'           => false,
        ];

        if ($userId <= 0 || $packageId <= 0) {
            return $empty;
        }

        try {
            $user = model('App\Models\User')->find($userId);
            if (!$user) {
                return $empty;
            }
            $ownerId = (int) (is_object($user) ? ($user->admin_id ?? 0) : ($user['admin_id'] ?? 0));
            $config = new RewardConfigService();
            if (!$config->isEnabled($ownerId, RewardSources::KEY_REDEMPTION_ENABLED)) {
                return $empty;
            }

            $engine = new RewardEngine();
            $available = $engine->availableBalance($userId);
            $price = self::resolvePackagePrice($userId, $packageId);
            $pointValue = max(0.0001, $config->getFloat($ownerId, RewardSources::KEY_POINT_VALUE_BDT));
            $maxPercent = $config->getInt($ownerId, RewardSources::KEY_MAX_REDEEM_PERCENT);

            $priceInPoints = (int) floor($price / $pointValue);
            $percentCap = (int) floor(($price * $maxPercent / 100) / $pointValue);
            $maxUsable = max(0, min($available, $priceInPoints, $percentCap));
            $use = $requestedPoints > 0 ? min($requestedPoints, $maxUsable) : $maxUsable;
            $discount = $use * $pointValue;

            return [
                'available_points'   => $available,
                'max_usable_points'  => $maxUsable,
                'points_applied'     => $use,
                'discount_bdt'       => round($discount, 2),
                'final_payable'      => round(max(0, $price - $discount), 2),
                'package_price'      => round($price, 2),
                'point_value_bdt'    => $pointValue,
                'max_redeem_percent' => $maxPercent,
                'enabled'            => $available > 0 && $maxUsable > 0,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'WebRenewRewardHelper::preview ' . $e->getMessage());
            return $empty;
        }
    }

    /**
     * @return array{points:int, discount:float, payable:float}
     */
    public static function apply(int $userId, int $paymentId, float $price, int $redeemPoints): array
    {
        $result = ['points' => 0, 'discount' => 0.0, 'payable' => $price];
        if ($userId <= 0 || $paymentId <= 0 || $redeemPoints <= 0) {
            return $result;
        }

        try {
            $user = model('App\Models\User')->find($userId);
            if (!$user) {
                return $result;
            }
            $ownerId = (int) (is_object($user) ? ($user->admin_id ?? 0) : ($user['admin_id'] ?? 0));
            $config = new RewardConfigService();
            if (!$config->isEnabled($ownerId, RewardSources::KEY_REDEMPTION_ENABLED)) {
                return $result;
            }

            $engine = new RewardEngine();
            $available = $engine->availableBalance($userId);
            $pointValue = max(0.0001, $config->getFloat($ownerId, RewardSources::KEY_POINT_VALUE_BDT));
            $maxPercent = $config->getInt($ownerId, RewardSources::KEY_MAX_REDEEM_PERCENT);

            $priceInPoints = (int) floor($price / $pointValue);
            $percentCap = (int) floor(($price * $maxPercent / 100) / $pointValue);
            $usable = max(0, min($redeemPoints, $available, $priceInPoints, $percentCap));
            if ($usable <= 0) {
                return $result;
            }

            $hold = $engine->redeemHold($userId, $ownerId, $usable, $paymentId);
            if (!($hold['ok'] ?? false)) {
                return $result;
            }

            $discount = $usable * $pointValue;
            return [
                'points'   => $usable,
                'discount' => $discount,
                'payable'  => max(0, $price - $discount),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'WebRenewRewardHelper::apply ' . $e->getMessage());
            return $result;
        }
    }

    private static function resolvePackagePrice(int $userId, int $packageId): float
    {
        if (function_exists('getUserPackage')) {
            try {
                $pkg = getUserPackage($userId, $packageId);
                if ($pkg) {
                    $price = is_object($pkg) ? ($pkg->price ?? null) : ($pkg['price'] ?? null);
                    if ($price !== null) {
                        return (float) $price;
                    }
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }
        try {
            $pkg = model('App\Models\Package')->find($packageId);
            if ($pkg) {
                return (float) (is_object($pkg) ? ($pkg->price ?? 0) : ($pkg['price'] ?? 0));
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 0.0;
    }
}
