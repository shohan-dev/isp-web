<?php

/**
 * Subscription trial, pending package change, and quota helpers.
 */

use App\Models\AdminPackage;
use App\Services\BillingService;

if (!function_exists('paymentChangeType')) {
    /**
     * @param object|array|null $payment
     */
    function paymentChangeType($payment): ?string
    {
        if (empty($payment)) {
            return null;
        }
        $raw = is_object($payment) ? ($payment->custom_data ?? null) : ($payment['custom_data'] ?? null);
        if (empty($raw)) {
            return null;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) && !empty($decoded['change_type'])
            ? (string) $decoded['change_type']
            : null;
    }
}

if (!function_exists('paymentTargetPackageId')) {
    /**
     * @param object|array|null $payment
     */
    function paymentTargetPackageId($payment): ?int
    {
        if (empty($payment)) {
            return null;
        }
        $raw = is_object($payment) ? ($payment->custom_data ?? null) : ($payment['custom_data'] ?? null);
        if (empty($raw)) {
            return null;
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || empty($decoded['target_package_id'])) {
            return null;
        }

        return (int) $decoded['target_package_id'];
    }
}

if (!function_exists('hasPendingPackageChange')) {
    /**
     * @param object|array|null $user
     */
    function hasPendingPackageChange($user): bool
    {
        if (empty($user)) {
            return false;
        }
        $pending = is_object($user)
            ? ($user->pending_package_id ?? null)
            : ($user['pending_package_id'] ?? null);

        return !empty($pending) && (int) $pending > 0;
    }
}

if (!function_exists('userHasSuccessfulSubscriptionPayment')) {
    function userHasSuccessfulSubscriptionPayment(int $userId): bool
    {
        $paymentModel = model('App\Models\Payment');

        return (bool) $paymentModel
            ->where('user_id', $userId)
            ->where('status', 'successful')
            ->groupStart()
                ->like('invoice', 'INV-', 'after')
                ->orWhere('invoice IS NOT NULL', null, false)
            ->groupEnd()
            ->where('paid_at IS NOT NULL', null, false)
            ->countAllResults();
    }
}

if (!function_exists('isOnFreeTrial')) {
    /**
     * @param object|array|null $user
     */
    function isOnFreeTrial($user): bool
    {
        if (empty($user)) {
            return false;
        }

        $trialEnds = is_object($user)
            ? ($user->trial_ends_at ?? null)
            : ($user['trial_ends_at'] ?? null);

        if (empty($trialEnds)) {
            return false;
        }

        $userId = (int) (is_object($user) ? ($user->id ?? 0) : ($user['id'] ?? 0));
        if ($userId > 0 && userHasSuccessfulSubscriptionPayment($userId)) {
            return false;
        }

        return strtotime((string) $trialEnds) > time();
    }
}

if (!function_exists('trialDaysRemaining')) {
    /**
     * @param object|array|null $user
     */
    function trialDaysRemaining($user): int
    {
        if (!isOnFreeTrial($user)) {
            return 0;
        }
        $trialEnds = is_object($user)
            ? ($user->trial_ends_at ?? null)
            : ($user['trial_ends_at'] ?? null);

        return max(0, (int) ceil((strtotime((string) $trialEnds) - time()) / 86400));
    }
}

if (!function_exists('getTenantCustomerQuota')) {
    function getTenantCustomerQuota(?int $userId = null): array
    {
        $userId = $userId ?? (int) session()->get('user_id');
        if ($userId <= 0) {
            return [
                'used' => 0,
                'limit' => 0,
                'is_unlimited' => true,
                'percent' => 0,
                'package_id' => null,
                'pending_package_id' => null,
            ];
        }

        $userModel = model('App\Models\User');
        $user = $userModel->find($userId);
        if (!$user) {
            return [
                'used' => 0,
                'limit' => 0,
                'is_unlimited' => true,
                'percent' => 0,
                'package_id' => null,
                'pending_package_id' => null,
            ];
        }

        $countData = getAllCostomerForUser($userId);
        $used = (int) ($countData['count'] ?? 0);
        $package = $countData['package'] ?? null;
        $limit = 0;
        if ($package) {
            $limit = (int) (is_object($package) ? ($package->duration ?? 0) : ($package['duration'] ?? 0));
        }
        $isUnlimited = $limit <= 0;
        $percent = (!$isUnlimited && $limit > 0)
            ? min(100, round(($used / $limit) * 100, 1))
            : 0;

        return [
            'used' => $used,
            'limit' => $limit,
            'is_unlimited' => $isUnlimited,
            'percent' => $percent,
            'package_id' => $user->package_id ?? null,
            'pending_package_id' => $user->pending_package_id ?? null,
            'is_trial' => isOnFreeTrial($user),
            'trial_ends_at' => $user->trial_ends_at ?? null,
            'trial_days_remaining' => trialDaysRemaining($user),
        ];
    }
}

if (!function_exists('getAllCostomerForUser')) {
    /**
     * Customer count + package limit for a specific tenant user id (no session).
     */
    function getAllCostomerForUser(int $tenantUserId): array
    {
        $userModel = model('App\Models\User');
        $details = $userModel->find($tenantUserId);
        if (!$details) {
            return ['count' => 0, 'package' => null];
        }

        $userId = $tenantUserId;
        $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', 'resellerAdmin')->findAll();
        $totalCustomerCount = 0;
        foreach ($userIds as $reseller) {
            $totalCustomerCount += $userModel->where('admin_id', $reseller->id)->countAllResults();
        }

        $count = $userModel->builder()
            ->where('role', 'user')
            ->where('admin_id', $userId)
            ->countAllResults();
        $count += $totalCustomerCount;

        $adminPackage = model('App\Models\AdminPackage');
        $package = $adminPackage->select('duration, package_name, id')
            ->where('id', $details->package_id)
            ->first();

        return ['count' => $count, 'package' => $package];
    }
}

if (!function_exists('calculatePackageChangePrice')) {
    function calculatePackageChangePrice(int $userId, int $targetPackageId, $currentPackageId = null, $willExpire = null): float
    {
        $user = getUserById($userId);
        if (!$user) {
            return 0.0;
        }

        $currentPackageId = $currentPackageId ?? $user->package_id;
        $willExpire = $willExpire ?? $user->will_expire;

        if ((string) $currentPackageId === (string) $targetPackageId) {
            $pkg = getUserPackage($userId, $targetPackageId);
            $monthly = (float) (is_object($pkg) ? ($pkg->price ?? 0) : ($pkg['price'] ?? 0));

            return round($monthly, 2);
        }

        $newPkg = getUserPackage($userId, $targetPackageId);
        $newMonthly = (float) (is_object($newPkg) ? ($newPkg->price ?? 0) : ($newPkg['price'] ?? 0));

        $willTs = strtotime((string) $willExpire);
        $days = ($willTs && $willTs > time())
            ? (int) ceil(($willTs - time()) / 86400)
            : 30;

        return round((new BillingService())->quote($newMonthly, max(1, $days)), 2);
    }
}

if (!function_exists('requestPackageChange')) {
    /**
     * Set pending_package_id and create/update a pending invoice.
     *
     * @return array{ok:bool,message?:string,payment_id?:int,payment_url?:string,amount?:float,invoice?:string}
     */
    function requestPackageChange(int $userId, int $targetPackageId, ?int $duration = 30): array
    {
        helper(['user', 'wallet']);
        $userModel = model('App\Models\User');
        $paymentModel = model('App\Models\Payment');
        $user = $userModel->find($userId);

        if (!$user) {
            return ['ok' => false, 'message' => 'User not found.'];
        }

        if ((string) ($user->package_id ?? '') === (string) $targetPackageId
            && !hasPendingPackageChange($user)
        ) {
            return ['ok' => false, 'message' => 'You are already on this package.'];
        }

        $existingPending = (int) ($user->pending_package_id ?? 0);
        if ($existingPending > 0 && (string) $existingPending !== (string) $targetPackageId) {
            $pendingPay = $paymentModel->where('user_id', $userId)
                ->where('status', 'pending')
                ->orderBy('id', 'DESC')
                ->first();
            if ($pendingPay) {
                return [
                    'ok' => false,
                    'message' => 'Complete payment for your pending package change before selecting another.',
                    'payment_id' => (int) $pendingPay->id,
                    'payment_url' => route_to('route.payment.pay', $pendingPay->id),
                ];
            }
        }

        $targetPackage = (new AdminPackage())->find($targetPackageId);
        if (empty($targetPackage)) {
            return ['ok' => false, 'message' => 'Package not found.'];
        }

        $price = calculatePackageChangePrice($userId, $targetPackageId);
        $currentMonth = date('F');
        $customData = [
            'duration' => $duration ?? 30,
            'target_package_id' => $targetPackageId,
            'change_type' => 'package_change',
        ];

        $existingPayment = $paymentModel->where([
            'user_id' => $userId,
            'month' => $currentMonth,
        ])->where('status !=', 'successful')->first();

        if ($existingPayment && paymentChangeType($existingPayment) === 'package_change'
            && paymentTargetPackageId($existingPayment) === $targetPackageId
        ) {
            $paymentId = (int) $existingPayment->id;
            $invoice = $existingPayment->invoice ?? '';
        } else {
            $paymentData = [
                'user_id' => $userId,
                // A top-level ISP admin has no parent admin_id, so scope their own
                // SaaS subscription invoice to themselves. Customers keep their
                // parent's id. payments.admin_id is NOT NULL — never insert null.
                'admin_id' => $user->admin_id ?: $userId,
                'paidby' => $userId,
                'user_type' => $user->role === 'admin' ? 'admin' : 'user',
                'invoice' => 'INV-' . random_int(100000, 999999),
                'amount' => $price,
                'month' => $currentMonth,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'custom_data' => json_encode($customData),
            ];

            if ($existingPayment) {
                $paymentModel->update($existingPayment->id, $paymentData);
                $paymentId = (int) $existingPayment->id;
                $invoice = $paymentData['invoice'];
            } else {
                $paymentModel->insert($paymentData);
                $paymentId = (int) $paymentModel->getInsertID();
                $invoice = $paymentData['invoice'];
            }
        }

        $userModel->update($userId, [
            'pending_package_id' => $targetPackageId,
        ]);

        $pkgName = is_object($targetPackage)
            ? ($targetPackage->package_name ?? 'New package')
            : ($targetPackage['package_name'] ?? 'New package');

        return [
            'ok' => true,
            'payment_id' => $paymentId,
            'payment_url' => route_to('route.payment.pay', $paymentId),
            'amount' => $price,
            'invoice' => $invoice,
            'current_package_id' => $user->package_id,
            'pending_package_id' => $targetPackageId,
            'package_name' => $pkgName,
            'message' => 'Your current package stays active. Invoice ' . $invoice
                . ' created for ' . $pkgName . '. Pay now or anytime from My Payment.',
        ];
    }
}

if (!function_exists('applyPackageChangeOnPayment')) {
    /**
     * Apply pending package swap when a package-change invoice is paid.
     *
     * @return array<string,mixed> User fields to merge into gateway/callback update
     */
    function applyPackageChangeOnPayment(int $userId, $payment): array
    {
        $userModel = model('App\Models\User');
        $user = $userModel->find($userId);
        if (!$user) {
            return [];
        }

        $targetId = paymentTargetPackageId($payment);
        if (!$targetId && hasPendingPackageChange($user)) {
            $targetId = (int) $user->pending_package_id;
        }

        if (!$targetId || paymentChangeType($payment) !== 'package_change') {
            if (!hasPendingPackageChange($user)) {
                return [];
            }
            if (!$targetId) {
                return [];
            }
        }

        if ((string) ($user->package_id ?? '') === (string) $targetId
            && empty($user->pending_package_id)
        ) {
            return [];
        }

        $update = [
            'package_id' => $targetId,
            'pre_package' => $user->package_id,
            'pending_package_id' => null,
            'subscription_status' => 'active',
            'conn_status' => 'conn',
            'trial_ends_at' => null,
        ];

        $userModel->update($userId, $update);

        return $update;
    }
}

if (!function_exists('buildSubscriptionRenewUserData')) {
    /**
     * Build user-row updates after a successful subscription payment.
     *
     * @return array<string,mixed>
     */
    function buildSubscriptionRenewUserData(int $userId, $payment, ?int $duration = null): array
    {
        helper(['user', 'wallet']);

        applyPackageChangeOnPayment($userId, $payment);

        $data = [
            'subscription_status' => 'active',
            'last_renewed' => date('Y-m-d H:i:s'),
            'conn_status' => 'conn',
        ];

        if (!renewAlreadyApplied($payment)) {
            $decoded = [];
            $raw = is_object($payment) ? ($payment->custom_data ?? null) : ($payment['custom_data'] ?? null);
            if (!empty($raw)) {
                $decoded = json_decode((string) $raw, true) ?: [];
            }
            $dur = $duration ?? (isset($decoded['duration']) ? (int) $decoded['duration'] : null);
            $data['will_expire'] = calcUserSubsRenewDate($userId, $dur);
            if (is_object($payment) && !empty($payment->id)) {
                markRenewApplied($payment->id);
            }
        }

        $user = getUserById($userId);
        if ($user && userHasSuccessfulSubscriptionPayment($userId)) {
            $data['trial_ends_at'] = null;
        }

        return $data;
    }
}

if (!function_exists('isSAdminSubscriptionExpired')) {
    /**
     * @param object|array|null $user
     */
    function isSAdminSubscriptionExpired($user): bool
    {
        if (empty($user)) {
            return false;
        }
        $role = is_object($user) ? ($user->role ?? '') : ($user['role'] ?? '');
        if ($role !== 'admin') {
            return false;
        }

        if (hasPendingPackageChange($user)) {
            return false;
        }

        $status = is_object($user)
            ? ($user->subscription_status ?? '')
            : ($user['subscription_status'] ?? '');

        if ($status !== 'inactive') {
            return false;
        }

        $willExpire = is_object($user)
            ? ($user->will_expire ?? null)
            : ($user['will_expire'] ?? null);

        return empty($willExpire) || strtotime((string) $willExpire) <= time();
    }
}

if (!function_exists('assertTenantCanAddCustomer')) {
    /**
     * @return string|null Error message when tenant cannot add more customers.
     */
    function assertTenantCanAddCustomer(int $sAdminId): ?string
    {
        $sAdmin = model('App\Models\User')->find($sAdminId);
        if ($sAdmin === null) {
            return 'ISP admin account not found.';
        }

        helper('subscription');
        if ($sAdmin->status === 'inactive') {
            return 'The ISP admin account is not active. Cannot add customer.';
        }
        if (isSAdminSubscriptionExpired($sAdmin)) {
            return 'The ISP admin subscription has expired. Cannot add customer.';
        }
        if ($sAdmin->conn_status != 'conn' && !hasPendingPackageChange($sAdmin)) {
            return 'The ISP admin account is not connected. Cannot add customer.';
        }

        $quota = getTenantCustomerQuota($sAdminId);
        if (!$quota['is_unlimited'] && $quota['limit'] > 0 && $quota['used'] >= $quota['limit']) {
            return 'You are at your limit! Update your package to create a new customer.';
        }

        return null;
    }
}

if (!function_exists('paymentPurposeLabel')) {
  /**
   * Human-readable invoice purpose for My Payment UI.
   *
   * @param object|array|null $payment
   */
    function paymentPurposeLabel($payment): string
    {
        if (paymentPurpose($payment) === 'wallet_topup') {
            return 'Wallet top-up';
        }

        $changeType = paymentChangeType($payment);
        if ($changeType === 'package_change') {
            $targetId = paymentTargetPackageId($payment);
            if ($targetId) {
                $pkg = model('App\Models\AdminPackage')->find($targetId);
                $name = $pkg ? (is_object($pkg) ? ($pkg->package_name ?? '') : ($pkg['package_name'] ?? '')) : '';

                return 'Package change' . ($name !== '' ? ' → ' . $name : '');
            }

            return 'Package change';
        }

        return 'Subscription renewal';
    }
}
