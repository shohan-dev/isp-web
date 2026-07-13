<?php

namespace Zapi\Modules\Customer\Reward\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;
use Zapi\Modules\Shared\Rewards\Models\RewardTransactionModel;
use Zapi\Modules\Shared\Rewards\Services\RewardEngine;
use Zapi\Modules\Shared\Rewards\Services\RewardConfigService;
use Zapi\Modules\Shared\Rewards\Support\RewardMessages;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;

class RewardService extends CustomerBaseService
{
    /**
     * GET /api/customer/reward/wallet
     */
    public function wallet()
    {
        $userId = $this->actorUserId();
        if (!$userId) {
            return $this->respondError('Authentication required', 401, 'UNAUTHORIZED');
        }

        $user = $this->user_model->find($userId);
        $ownerId = $user ? (int) ($user->admin_id ?? 0) : 0;

        $engine = new RewardEngine();
        $w = $engine->getWallet($userId, $ownerId);
        $config = new RewardConfigService();

        $balance = (int) ($w->balance ?? 0);
        $held    = (int) ($w->held ?? 0);
        $available = max(0, $balance - $held);

        return $this->respondSuccess([
            'balance'         => $available,
            'held'            => $held,
            'lifetime_earned' => (int) ($w->lifetime_earned ?? 0),
            'lifetime_used'   => (int) ($w->lifetime_spent ?? 0),
            'expiring_points' => $engine->expiringSoonPoints($userId, 30),
            'point_value_bdt' => $config->getFloat($ownerId, RewardSources::KEY_POINT_VALUE_BDT),
        ]);
    }

    /**
     * GET /api/customer/reward/transactions
     */
    public function transactions()
    {
        $userId = $this->actorUserId();
        if (!$userId) {
            return $this->respondError('Authentication required', 401, 'UNAUTHORIZED');
        }

        $ledger = new RewardTransactionModel();
        $pager = $this->getPaginationParams();

        $total = $ledger->where('user_id', $userId)->countAllResults(false);
        $rows = $ledger->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll($pager['limit'], $pager['offset']);

        $items = [];
        foreach ($rows as $r) {
            $points = (int) $r->points;
            $items[] = [
                'id'            => (int) $r->id,
                'date'          => (string) ($r->created_at ?? ''),
                'source'        => (string) ($r->source ?? ''),
                'points'        => abs($points),
                'direction'     => $points >= 0 ? 'credit' : 'debit',
                'balance_after' => (int) ($r->balance_after ?? 0),
                'description'   => (string) ($r->note ?? RewardMessages::reasonForSource((string) $r->source)),
            ];
        }

        return $this->respondSuccess([
            'items'      => $items,
            'pagination' => $this->buildPaginationMeta($total, $pager['page'], $pager['limit'], count($items)),
        ]);
    }

    /**
     * GET /api/customer/reward/redeem-preview?package_id=&points=
     * Computes the redemption caps for a package without committing anything.
     */
    public function redeemPreview()
    {
        $userId = $this->actorUserId();
        if (!$userId) {
            return $this->respondError('Authentication required', 401, 'UNAUTHORIZED');
        }
        $packageId = (int) $this->getInputValue('package_id');
        $requested = (int) $this->getInputValue('points');
        if ($packageId <= 0) {
            return $this->respondError('package_id is required', 400, 'REQUEST_FAILED');
        }

        $user = $this->user_model->find($userId);
        $ownerId = $user ? (int) ($user->admin_id ?? 0) : 0;

        $engine = new RewardEngine();
        $config = new RewardConfigService();

        $available = $engine->availableBalance($userId);
        $price = $this->resolvePackagePrice($userId, $packageId);
        $pointValue = max(0.0001, $config->getFloat($ownerId, RewardSources::KEY_POINT_VALUE_BDT));
        $maxPercent = $config->getInt($ownerId, RewardSources::KEY_MAX_REDEEM_PERCENT);

        // Caps: balance, package amount (in points), and % of price.
        $priceInPoints = (int) floor($price / $pointValue);
        $percentCap = (int) floor(($price * $maxPercent / 100) / $pointValue);
        $maxUsable = max(0, min($available, $priceInPoints, $percentCap));

        $use = $requested > 0 ? min($requested, $maxUsable) : $maxUsable;
        $discount = $use * $pointValue;
        $finalPayable = max(0, $price - $discount);

        return $this->respondSuccess([
            'package_id'        => $packageId,
            'package_price'     => round($price, 2),
            'available_points'  => $available,
            'max_usable_points' => $maxUsable,
            'points_applied'    => $use,
            'discount_bdt'      => round($discount, 2),
            'final_payable'     => round($finalPayable, 2),
            'point_value_bdt'   => $pointValue,
            'max_redeem_percent' => $maxPercent,
        ]);
    }

    // ---- helpers ------------------------------------------------------

    private function actorUserId(): ?int
    {
        $actor = $this->resolveAccessTokenUserId();
        if ($actor !== null) {
            return $actor;
        }
        $param = (int) $this->getInputValue('user_id');
        return $param > 0 ? $param : null;
    }

    private function resolvePackagePrice(int $userId, int $packageId): float
    {
        // Reuse the legacy helper used by the subscription flow when available.
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
            $pkg = $this->package_model->find($packageId);
            if ($pkg) {
                return (float) (is_object($pkg) ? ($pkg->price ?? 0) : ($pkg['price'] ?? 0));
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 0.0;
    }
}
