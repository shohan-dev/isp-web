<?php

namespace Zapi\Modules\Customer\Subscription\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;
use Zapi\Modules\Shared\Rewards\Models\RewardRenewalIntentModel;
use Zapi\Modules\Shared\Rewards\Services\RewardEngine;
use Zapi\Modules\Shared\Rewards\Services\RewardConfigService;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;

class SubscriptionService extends CustomerBaseService
{
    public function index()
    {
        $userId = $this->request->getGet('user_id');
        if (empty($userId)) {
            return $this->respondError('user_id query parameter is required', 400, 'REQUEST_FAILED');
        }
        if (!$this->actorCanAccessUser($userId)) {
            return $this->respondError('You do not have access to this account', 403, 'FORBIDDEN');
        }

        $userDetails = $this->user_model->where(['id' => $userId])->first();
        if (empty($userDetails)) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        $packages = $this->resolvePackagesForUser($userDetails);
        $pager = $this->getPaginationParams();
        $totalFound = count($packages);
        $pagedPackages = array_slice($packages, $pager['offset'], $pager['limit']);

        return $this->respondSuccess([
            'title' => 'My Subscription',
            'details' => $userDetails,
            'packages' => $pagedPackages,
            'package_id' => $userDetails->package_id,
            'pre_package' => $userDetails->pre_package,
            'pending_package_id' => $userDetails->pending_package_id ?? null,
            'trial_ends_at' => $userDetails->trial_ends_at ?? null,
            'subscription_status' => $userDetails->subscription_status,
            'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedPackages)),
        ]);
    }

    public function renew()
    {
        // JSON bodies (mobile app) are not visible to getPost(); use getInputValue like other zapi actions.
        $packageId = $this->getInputValue('package_id');
        $userId = $this->getInputValue('customer') ?? $this->getInputValue('user_id');
        $userRole = $this->getInputValue('role');

        if (!$packageId) {
            return $this->respondError('Package ID is required', 400, 'REQUEST_FAILED');
        }
        if (!$userId) {
            return $this->respondError('Customer ID is required', 400, 'REQUEST_FAILED');
        }
        if (!$this->actorCanAccessUser($userId)) {
            return $this->respondError('You do not have access to this account', 403, 'FORBIDDEN');
        }

        $userDetails = $this->user_model->where(['id' => $userId])->first();
        if (!$userDetails) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        if (is_object($userDetails)) {
            $currentPackageId = $userDetails->package_id;
            $adminId = $userDetails->admin_id;
            $role = $userRole ?? $userDetails->created_by;
            $willExpire = $userDetails->will_expire;
        } else {
            $currentPackageId = $userDetails['package_id'];
            $adminId = $userDetails['admin_id'];
            $role = $userRole ?? $userDetails['created_by'];
            $willExpire = $userDetails['will_expire'];
        }

        $currentMonth = date('F');
        $existingPayment = $this->payment_model->where([
            'user_id' => $userId,
            'paidby' => $userId,
            'month' => $currentMonth,
        ])->first();

        if ($existingPayment) {
            $payStatus = strtolower((string) (is_object($existingPayment) ? ($existingPayment->status ?? '') : ($existingPayment['status'] ?? '')));
            $samePackage = (string) $currentPackageId === (string) $packageId;
            if ($payStatus === 'successful' && $samePackage) {
                return $this->respondError('This billing period is already paid for your current package.', 400, 'REQUEST_FAILED');
            }
        }

        $price = $this->calculateSubscriptionPrice($userId, $packageId, $currentPackageId, $role, $willExpire, $adminId);
        if ($price === false) {
            return $this->respondError('Unable to calculate subscription price', 500, 'REQUEST_FAILED');
        }

        $isPackageChange = (string) $currentPackageId !== (string) $packageId;
        if ($isPackageChange) {
            $existingPending = is_object($userDetails)
                ? ($userDetails->pending_package_id ?? null)
                : ($userDetails['pending_package_id'] ?? null);
            if (!empty($existingPending) && (string) $existingPending !== (string) $packageId) {
                $blocking = $this->payment_model->where('user_id', $userId)
                    ->where('status', 'pending')
                    ->orderBy('id', 'DESC')
                    ->first();
                if ($blocking) {
                    return $this->respondError(
                        'Complete payment for your pending package change before selecting another.',
                        400,
                        'REQUEST_FAILED'
                    );
                }
            }
            $this->user_model->update($userId, ['pending_package_id' => (int) $packageId]);
        }

        $customData = [
            'duration' => 30,
        ];
        if ($isPackageChange) {
            $customData['target_package_id'] = (int) $packageId;
            $customData['change_type'] = 'package_change';
        }

        $paymentData = [
            'user_id' => $userId,
            'admin_id' => $adminId,
            'paidby' => $userId,
            'user_type' => 'user',
            'invoice' => 'INV-' . random_int(100000, 999999),
            'amount' => $price,
            'month' => $currentMonth,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'custom_data' => json_encode($customData),
        ];

        if ($existingPayment) {
            $result = $this->payment_model->update($existingPayment->id, $paymentData);
            $paymentId = $existingPayment->id;
        } else {
            $result = $this->payment_model->insert($paymentData);
            $paymentId = $this->payment_model->getInsertID();
        }

        if (!$result) {
            return $this->respondError('Failed to create payment record', 500, 'REQUEST_FAILED');
        }

        // ---- Reward integration ---------------------------------------
        // Capture renewal intent (for the reconciler's early-renewal / upgrade
        // rewards) and apply any reward-point redemption as a checkout discount.
        $ownerId = (int) $adminId;
        $pointsApplied = 0;
        $rewardDiscount = 0.0;
        $payable = (float) $price;
        $this->captureRenewalIntent($userId, (int) $paymentId, $currentPackageId, $packageId, $willExpire, (float) $price);

        $redeemPoints = (int) ($this->getInputValue('redeem_points') ?? 0);
        if ($redeemPoints > 0) {
            $redeem = $this->applyRewardRedemption($userId, $ownerId, $redeemPoints, (int) $paymentId, (float) $price);
            if ($redeem['points'] > 0) {
                $pointsApplied = $redeem['points'];
                $rewardDiscount = $redeem['discount'];
                $payable = $redeem['payable'];
                $this->payment_model->update($paymentId, ['amount' => $payable]);
            }
        }

        $renewMsg = $isPackageChange
            ? 'Your current package stays active until payment completes. Invoice created — pay now or anytime from My Payment.'
            : 'Payment invoice is generated. You can make payment anytime from `My Payment` option. Or if you want to pay now then click on `Pay` button below';

        return $this->respondSuccess([
            'msg' => $renewMsg,
            'payment_url' => route_to('route.payment.pay', $paymentId),
            'pay_now_url' => base_url('api/customer/make-payment/' . $paymentId),
            'payment_id' => (string) $paymentId,
            'can_pay_now' => true,
            'points_applied' => $pointsApplied,
            'reward_discount' => round($rewardDiscount, 2),
            'payable_after_points' => round($payable, 2),
        ]);
    }

    /**
     * Snapshot renewal context so the (post-hoc) reward reconciler can award
     * early-renewal / upgrade points after the gateway overwrites will_expire.
     */
    private function captureRenewalIntent($userId, int $paymentId, $currentPackageId, $newPackageId, $willExpire, float $price): void
    {
        try {
            $oldTs = strtotime((string) $willExpire);
            $daysBefore = ($oldTs && $oldTs > time()) ? (int) floor(($oldTs - time()) / 86400) : 0;

            $isUpgrade = 0;
            if (function_exists('getUserPackage') && $currentPackageId && (string) $currentPackageId !== (string) $newPackageId) {
                $newPkg = getUserPackage($userId, $newPackageId);
                $oldPkg = getUserPackage($userId, $currentPackageId);
                $newP = (float) (is_object($newPkg) ? ($newPkg->price ?? 0) : ($newPkg['price'] ?? 0));
                $oldP = (float) (is_object($oldPkg) ? ($oldPkg->price ?? 0) : ($oldPkg['price'] ?? 0));
                $isUpgrade = $newP > $oldP ? 1 : 0;
            }

            (new RewardRenewalIntentModel())->captureOnce([
                'user_id'            => (int) $userId,
                'payment_id'         => $paymentId,
                'old_package_id'     => $currentPackageId ?: null,
                'new_package_id'     => $newPackageId ?: null,
                'old_will_expire'    => $willExpire ?: null,
                'days_before_expiry' => $daysBefore,
                'is_upgrade'         => $isUpgrade,
                'new_package_price'  => (int) round($price),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'captureRenewalIntent failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate + place a reward-point redemption hold against the pending
     * payment. Returns the points actually applied, the BDT discount, and the
     * resulting payable amount.
     *
     * @return array{points:int, discount:float, payable:float}
     */
    private function applyRewardRedemption($userId, int $ownerId, int $redeemPoints, int $paymentId, float $price): array
    {
        $result = ['points' => 0, 'discount' => 0.0, 'payable' => $price];
        try {
            $config = new RewardConfigService();
            if (!$config->isEnabled($ownerId, RewardSources::KEY_REDEMPTION_ENABLED)) {
                return $result;
            }
            $engine = new RewardEngine();
            $available = $engine->availableBalance((int) $userId);
            $pointValue = max(0.0001, $config->getFloat($ownerId, RewardSources::KEY_POINT_VALUE_BDT));
            $maxPercent = $config->getInt($ownerId, RewardSources::KEY_MAX_REDEEM_PERCENT);

            $priceInPoints = (int) floor($price / $pointValue);
            $percentCap    = (int) floor(($price * $maxPercent / 100) / $pointValue);
            $usable = max(0, min($redeemPoints, $available, $priceInPoints, $percentCap));
            if ($usable <= 0) {
                return $result;
            }

            $hold = $engine->redeemHold((int) $userId, $ownerId, $usable, $paymentId);
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
            log_message('error', 'applyRewardRedemption failed: ' . $e->getMessage());
            return $result;
        }
    }

    public function activatePackage()
    {
        helper('subscription');
        $userId = $this->getInputValue('user_id');
        $packageId = $this->getInputValue('package_id');
        if (empty($userId) || empty($packageId)) {
            return $this->respondError('user_id and package_id are required', 400, 'REQUEST_FAILED');
        }
        if (!$this->actorCanAccessUser($userId)) {
            return $this->respondError('You do not have access to this account', 403, 'FORBIDDEN');
        }

        $details = $this->user_model->where(['id' => $userId])->first();
        if (empty($details)) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        if ((string) ($details->package_id ?? '') === (string) $packageId
            && !hasPendingPackageChange($details)
        ) {
            return $this->respondError('You are already on this package.', 400, 'REQUEST_FAILED');
        }

        $result = requestPackageChange((int) $userId, (int) $packageId);
        if (!$result['ok']) {
            return $this->respondError($result['message'] ?? 'Could not request package change.', 400, 'REQUEST_FAILED');
        }

        return $this->respondSuccess([
            'message' => $result['message'] ?? 'New package is pending. Complete payment to activate your subscription.',
            'data' => [
                'package_id' => (string) ($result['current_package_id'] ?? $details->package_id),
                'pending_package_id' => (string) ($result['pending_package_id'] ?? $packageId),
                'payment_id' => (string) ($result['payment_id'] ?? ''),
                'payment_url' => $result['payment_url'] ?? '',
                'amount' => $result['amount'] ?? 0,
                'invoice' => $result['invoice'] ?? '',
                'subscription_status' => $details->subscription_status,
            ],
        ]);
    }

    public function quota()
    {
        helper('subscription');
        $userId = $this->request->getGet('user_id');
        if (empty($userId)) {
            return $this->respondError('user_id query parameter is required', 400, 'REQUEST_FAILED');
        }
        if (!$this->actorCanAccessUser($userId)) {
            return $this->respondError('You do not have access to this account', 403, 'FORBIDDEN');
        }

        $user = $this->user_model->find($userId);
        if (!$user) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        $quota = getTenantCustomerQuota((int) $userId);

        return $this->respondSuccess([
            'data' => $quota,
        ]);
    }

    public function update()
    {
        $userId = $this->request->getPost('user_id');
        if (empty($userId)) {
            return $this->respondError('user_id is required', 400, 'REQUEST_FAILED');
        }
        if (!$this->actorCanAccessUser($userId)) {
            return $this->respondError('You do not have access to this account', 403, 'FORBIDDEN');
        }
        if (!$this->user_model->where(['id' => $userId])->first()) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        $willExpireStr = (string) getPostInput('will_expire');
        $willTs = strtotime($willExpireStr);
        $isActive = $willTs !== false && $willTs > time();
        $data = [
            'package_id' => getPostInput('package_id'),
            'last_renewed' => getPostInput('last_renewed'),
            'will_expire' => $willExpireStr,
            'subscription_status' => $isActive ? 'active' : 'inactive',
            'conn_status' => $isActive ? 'conn' : 'disconn',
        ];
        if (!$this->user_model->where(['id' => $userId])->set($data)->update()) {
            return $this->respondError('Something went wrong. Please try again.', 500, 'REQUEST_FAILED');
        }

        return $this->respondSuccess(['message' => 'Subscription updated successfully']);
    }

    private function calculateSubscriptionPrice($userId, $newPackageId, $currentPackageId, $role, $willExpire, $adminId = null)
    {
        $price = 0;
        $difference = 0;
        if ($currentPackageId != $newPackageId) {
            $now = time();
            $willExpireTime = strtotime($willExpire);
            if (is_numeric($willExpireTime) && $willExpireTime > $now) {
                $difference = floor(($willExpireTime - $now) / (60 * 60 * 24));
            }
        }

        if ($role === 'resellerAdmin') {
            $price = $this->getResellerPrice($newPackageId, $adminId, $currentPackageId, $difference);
        } else {
            $price = $this->getRegularUserPrice($userId, $newPackageId, $currentPackageId, $difference);
        }

        return $price;
    }

    private function getResellerPrice($packageId, $adminId, $currentPackageId, $difference)
    {
        $adminDetails = $this->user_model->where(['id' => $adminId])->first();
        if (!$adminDetails) {
            return false;
        }
        $fund = is_object($adminDetails) ? $adminDetails->fund : $adminDetails['fund'];
        $price = ResellerPackagePrice($packageId);
        if ($fund < $price) {
            throw new \Exception("Your Reseller doesn't have enough fund. Please contact with him.");
        }
        // Phase 5 unification (2026-06-21): a package change is priced by the ONE
        // canonical rule BillingService::quote() = (newMonthly/30)*$difference, where
        // $difference is the now->expiry window (SubscriptionService.php:309) — the SAME
        // window+rule the reseller-admin and web upgrade paths use, so this gateway-bound
        // self-serve charge reconciles with them. Was the retired
        // max(newFull - (oldFull/30)*difference, 0) (450 for 300->600/15d) -> now 300.
        // 2dp so the value persisted to payments.amount (varchar, forwarded to bKash) is clean.
        if ($currentPackageId != $packageId && $difference > 0) {
            $price = (new \App\Services\BillingService())->quote((float) $price, (int) $difference);
        }
        return round((float) $price, 2);
    }

    private function getRegularUserPrice($userId, $newPackageId, $currentPackageId, $difference)
    {
        $newPackage = getUserPackage($userId, $newPackageId);
        $price = is_object($newPackage) ? $newPackage->price : $newPackage['price'];
        // Phase 5 unification: same canonical quote() rule + 2dp as getResellerPrice.
        if ($currentPackageId != $newPackageId && $difference > 0) {
            $price = (new \App\Services\BillingService())->quote((float) $price, (int) $difference);
        }
        return round((float) $price, 2);
    }
}

