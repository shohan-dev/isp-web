<?php

namespace App\Services;

use App\Models\AdminPackage;
use App\Models\TenantWallet;

/**
 * PaygBillingService — the Pay-As-You-Go lifecycle engine for tenant ISP
 * owners (role sAdmin).
 *
 * A PAYG tenant's users.will_expire is the *next charge date*. Every cycle:
 *   charge = base_fee + total_customers x per_user_rate + chosen add-ons
 * is debited from the tenant wallet (WalletService, idempotent per cycle).
 * On success the subscription extends CYCLE_DAYS. On insufficient balance the
 * tenant gets GRACE_DAYS of continued service, then suspension. Topping up
 * while expired/suspended triggers an immediate cycle (reactivation) via
 * runCycle() from the payment callback.
 *
 * Invoked daily by CronJob::paygBilling (cron action `payg_billing`).
 */
class PaygBillingService
{
    public const CYCLE_DAYS = 30;
    public const GRACE_DAYS = 3;
    public const ALERT_DAYS = 7;

    /** Platform operator user id (same magic id used across the codebase). */
    public const PLATFORM_USER_ID = 2;

    protected WalletService $wallet;

    public function __construct(?WalletService $wallet = null)
    {
        $this->wallet = $wallet ?? new WalletService();
    }

    /**
     * The active PAYG plan row (seeded with landing-page defaults if absent).
     *
     * @return object|array|null
     */
    public function paygPlan()
    {
        return (new AdminPackage())->paygPackage();
    }

    /**
     * Is this users row a PAYG tenant? (role sAdmin + package plan_type payg)
     */
    public function isPaygUser($user): bool
    {
        if (empty($user)) {
            return false;
        }
        $role = is_object($user) ? ($user->role ?? '') : ($user['role'] ?? '');
        $packageId = is_object($user) ? ($user->package_id ?? null) : ($user['package_id'] ?? null);
        if ($role !== 'admin' || empty($packageId)) {
            return false;
        }

        $pkg = (new AdminPackage())->find($packageId);
        if (!$pkg) {
            return false;
        }
        $type = is_object($pkg) ? ($pkg->plan_type ?? '') : ($pkg['plan_type'] ?? '');

        return $type === AdminPackage::TYPE_PAYG;
    }

    /**
     * Total customers under a tenant: direct customers plus customers under
     * the tenant's resellers — all subscription statuses (PAYG bills on total).
     */
    public function totalCustomerCount(int $sAdminId): int
    {
        $userModel = model('App\Models\User');

        $count = $userModel->builder()
            ->where('role', 'user')
            ->where('admin_id', $sAdminId)
            ->countAllResults();

        $resellers = $userModel->builder()
            ->select('id')
            ->where('role', 'resellerAdmin')
            ->where('admin_id', $sAdminId)
            ->get()->getResult();

        foreach ($resellers as $reseller) {
            $count += $userModel->builder()
                ->where('role', 'user')
                ->where('admin_id', (int) $reseller->id)
                ->countAllResults();
        }

        return (int) $count;
    }

    /**
     * Full charge breakdown for a tenant at current usage.
     *
     * @return array{base_fee: float, per_user_rate: float, total_users: int,
     *               usage_cost: float, addons: array, addon_total: float,
     *               total: float, min_topup: float, balance: float,
     *               cycle_days: int}
     */
    public function estimate(int $sAdminId): array
    {
        $plan = $this->paygPlan();
        $baseFee = (float) (is_object($plan) ? ($plan->base_fee ?? 0) : ($plan['base_fee'] ?? 0));
        $rate = (float) (is_object($plan) ? ($plan->per_user_rate ?? 0) : ($plan['per_user_rate'] ?? 0));
        $minTopup = (float) (is_object($plan) ? ($plan->min_topup ?? 0) : ($plan['min_topup'] ?? 0));

        $totalUsers = $this->totalCustomerCount($sAdminId);
        $usageCost = round($totalUsers * $rate, 2);

        $walletRow = $this->wallet->ensureWallet($sAdminId);
        $chosen = TenantWallet::chosenAddons($walletRow);
        $catalog = AdminPackage::addonCatalog($plan);

        $addons = [];
        $addonTotal = 0.0;
        foreach ($chosen as $key) {
            if (isset($catalog[$key])) {
                $addons[] = $catalog[$key];
                $addonTotal += $catalog[$key]['price'];
            }
        }

        return [
            'base_fee'      => $baseFee,
            'per_user_rate' => $rate,
            'total_users'   => $totalUsers,
            'usage_cost'    => $usageCost,
            'addons'        => $addons,
            'addon_total'   => round($addonTotal, 2),
            'total'         => round($baseFee + $usageCost + $addonTotal, 2),
            'min_topup'     => $minTopup,
            'balance'       => (float) $walletRow->balance,
            'cycle_days'    => self::CYCLE_DAYS,
        ];
    }

    /**
     * Run one billing cycle for a PAYG tenant.
     *
     * @param object $user users row (role sAdmin)
     * @param bool   $force charge even if will_expire is in the future
     *                      (used right after a reactivating top-up)
     *
     * @return array{status: string, charge?: float, next_expire?: string, message?: string}
     *               status: charged | not_due | not_payg | insufficient_grace |
     *                       insufficient_waiting | suspended | error
     */
    public function runCycle(object $user, bool $force = false): array
    {
        if (!$this->isPaygUser($user)) {
            return ['status' => 'not_payg'];
        }

        $now = time();
        $expireTs = !empty($user->will_expire) ? strtotime($user->will_expire) : 0;

        if (!$force && $expireTs > $now) {
            return ['status' => 'not_due'];
        }

        $estimate = $this->estimate((int) $user->id);
        $charge = $estimate['total'];
        $walletRow = $this->wallet->ensureWallet((int) $user->id);

        // Cycle identity = the expiry date being renewed, so retries after a
        // top-up within the same cycle can't double-charge.
        $anchor = $expireTs > 0 ? date('Y-m-d', $expireTs) : date('Y-m-d', $now);
        $reference = 'payg-cycle:' . $user->id . ':' . $anchor;
        $alreadyCharged = $this->wallet->referenceExists($reference);

        $paid = $alreadyCharged
            ? true
            : ($charge <= 0 ? true : $this->wallet->debit(
                (int) $user->id,
                $charge,
                $reference,
                'PAYG monthly charge (' . $estimate['total_users'] . ' customers)'
            ));

        $userModel = model('App\Models\User');

        if ($paid) {
            $newExpire = date('Y-m-d H:i:s', strtotime('+' . self::CYCLE_DAYS . ' days', max($now, $expireTs)));

            $userModel->update($user->id, [
                'subscription_status' => 'active',
                'last_renewed'        => date('Y-m-d H:i:s'),
                'will_expire'         => $newExpire,
            ]);

            (new \App\Models\TenantWallet())->update($walletRow->id, [
                'grace_until'             => null,
                'low_balance_notified_at' => null,
            ]);

            if (!$alreadyCharged && $charge > 0) {
                $this->recordChargePayment($user, $charge, $reference, $estimate);
            }

            log_message('info', "PAYG cycle charged: user {$user->id}, amount {$charge}, next expire {$newExpire}");

            return ['status' => 'charged', 'charge' => $charge, 'next_expire' => $newExpire];
        }

        // Insufficient balance: grace first, then suspension.
        $graceUntil = !empty($walletRow->grace_until) ? strtotime($walletRow->grace_until) : null;

        if ($graceUntil === null) {
            $graceDate = date('Y-m-d H:i:s', strtotime('+' . self::GRACE_DAYS . ' days', $now));
            (new \App\Models\TenantWallet())->update($walletRow->id, ['grace_until' => $graceDate]);

            $this->notifyTenant(
                $user,
                'Your ISP Pay BD wallet balance (BDT ' . number_format($estimate['balance'], 2) . ') cannot cover this month\'s charge of BDT '
                . number_format($charge, 2) . '. Please top up within ' . self::GRACE_DAYS . ' days to avoid suspension.'
            );

            return ['status' => 'insufficient_grace', 'charge' => $charge];
        }

        if ($now <= $graceUntil) {
            return ['status' => 'insufficient_waiting', 'charge' => $charge];
        }

        if (($user->subscription_status ?? '') !== 'inactive') {
            $userModel->update($user->id, ['subscription_status' => 'inactive']);
            $this->notifyTenant(
                $user,
                'Your ISP Pay BD account has been suspended: wallet balance could not cover the monthly charge of BDT '
                . number_format($charge, 2) . '. Top up your wallet to reactivate instantly.'
            );
            log_message('info', "PAYG tenant suspended for insufficient balance: user {$user->id}");
        }

        return ['status' => 'suspended', 'charge' => $charge];
    }

    /**
     * Low-balance pre-alert: fires once per cycle when the next charge is
     * within ALERT_DAYS and the wallet cannot cover it.
     */
    public function maybeSendLowBalanceAlert(object $user): bool
    {
        if (!$this->isPaygUser($user) || empty($user->will_expire)) {
            return false;
        }

        $expireTs = strtotime($user->will_expire);
        $daysLeft = ($expireTs - time()) / 86400;

        if ($daysLeft < 0 || $daysLeft > self::ALERT_DAYS) {
            return false;
        }

        $estimate = $this->estimate((int) $user->id);
        if ($estimate['balance'] >= $estimate['total']) {
            return false;
        }

        $walletRow = $this->wallet->ensureWallet((int) $user->id);
        if (!empty($walletRow->low_balance_notified_at)) {
            return false; // already alerted this cycle (reset on successful charge)
        }

        (new \App\Models\TenantWallet())->update($walletRow->id, [
            'low_balance_notified_at' => date('Y-m-d H:i:s'),
        ]);

        $this->notifyTenant(
            $user,
            'Heads up: your ISP Pay BD wallet has BDT ' . number_format($estimate['balance'], 2)
            . ' but your next monthly charge (' . date('d M', $expireTs) . ') is BDT '
            . number_format($estimate['total'], 2) . '. Please top up to keep your service running.'
        );

        return true;
    }

    /**
     * Revenue-reporting payments row for a successful wallet charge.
     */
    protected function recordChargePayment(object $user, float $charge, string $reference, array $estimate): void
    {
        try {
            model('App\Models\Payment')->insert([
                'user_id'     => $user->id,
                'user_type'   => 'admin',
                'admin_id'    => null,
                'paidby'      => $user->id,
                'invoice'     => 'INV-' . random_int(100000, 999999),
                'amount'      => $charge,
                'month'       => date('F'),
                'paid_via'    => 'Wallet',
                'paid_to'     => self::PLATFORM_USER_ID,
                'paid_at'     => date('Y-m-d H:i:s'),
                'status'      => 'successful',
                'created_at'  => date('Y-m-d H:i:s'),
                'custom_data' => json_encode([
                    'purpose'   => 'payg_cycle',
                    'reference' => $reference,
                    'duration'  => self::CYCLE_DAYS,
                    'breakdown' => [
                        'base_fee'     => $estimate['base_fee'],
                        'total_users'  => $estimate['total_users'],
                        'usage_cost'   => $estimate['usage_cost'],
                        'addon_total'  => $estimate['addon_total'],
                    ],
                ]),
            ]);
        } catch (\Throwable $e) {
            // The wallet ledger is the source of truth; a reporting-row failure
            // must not fail the cycle.
            log_message('error', 'PAYG charge payment record failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Best-effort SMS via the platform operator's gateway; never throws.
     */
    public function notifyTenant(object $user, string $message): void
    {
        log_message('info', 'PAYG notify user ' . $user->id . ': ' . $message);

        try {
            if (!empty($user->mobile) && function_exists('sendSms')) {
                sendSms($user->mobile, $message, self::PLATFORM_USER_ID);
            }
        } catch (\Throwable $e) {
            log_message('error', 'PAYG tenant SMS failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}
