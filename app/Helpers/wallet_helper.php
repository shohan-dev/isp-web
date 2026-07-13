<?php

/**
 * Tenant platform-wallet helpers shared by the payment gateways
 * (Bkash / Nagad / SSLCommerz), Subscription::callback and the PAYG cron.
 */

use App\Services\PaygBillingService;
use App\Services\WalletService;

if (!function_exists('paymentPurpose')) {
    /**
     * The declared purpose of a payments row ('wallet_topup', 'payg_cycle', …)
     * from its custom_data JSON. Null for ordinary renewal invoices.
     *
     * @param object|array|null $payment
     */
    function paymentPurpose($payment): ?string
    {
        if (empty($payment)) {
            return null;
        }
        $raw = is_object($payment) ? ($payment->custom_data ?? null) : ($payment['custom_data'] ?? null);
        if (empty($raw)) {
            return null;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) && !empty($decoded['purpose']) ? (string) $decoded['purpose'] : null;
    }
}

if (!function_exists('paymentGatewayContext')) {
    /**
     * Which user's gateway settings should process a payments row.
     *
     * Customer-pays-tenant invoices keep their existing behavior (the payer's
     * settings prefix, which resolves to the tenant's merchant credentials).
     * Platform-bound payments — a tenant sAdmin paying the platform (renewal
     * or wallet top-up), recognizable by an empty admin_id — must use the
     * PLATFORM operator's credentials (user id 2), matching the context that
     * Payment::makePayment already uses to render the gateway page.
     *
     * @param object|array|null $payment
     * @return int|string
     */
    function paymentGatewayContext($payment)
    {
        $userId = is_object($payment) ? ($payment->user_id ?? null) : ($payment['user_id'] ?? null);
        $adminId = is_object($payment) ? ($payment->admin_id ?? null) : ($payment['admin_id'] ?? null);

        if (empty($adminId) && !empty($userId)) {
            $payer = getUserById($userId);
            if ($payer && ($payer->role ?? '') === 'admin') {
                return PaygBillingService::PLATFORM_USER_ID;
            }
        }

        return $userId;
    }
}

if (!function_exists('applyWalletTopup')) {
    /**
     * Credit a successful top-up payment into the tenant wallet (idempotent —
     * safe to call from both the gateway callback and Subscription::callback)
     * and, if the tenant is an expired/suspended PAYG account whose balance
     * now covers the cycle, reactivate immediately.
     *
     * @param object $payment successful payments row with purpose wallet_topup
     */
    function applyWalletTopup(object $payment): bool
    {
        try {
            $walletService = new WalletService();
            $credited = $walletService->credit(
                (int) $payment->user_id,
                (float) $payment->amount,
                'payment:' . $payment->id,
                'Wallet top-up via ' . ($payment->paid_via ?? 'gateway') . ' (' . ($payment->invoice ?? '') . ')'
            );

            if (!$credited) {
                log_message('error', 'Wallet top-up credit failed for payment ' . $payment->id);

                return false;
            }

            // Reactivation: expired or suspended PAYG tenants get billed for a
            // fresh cycle the moment the wallet can cover it.
            $user = getUserById($payment->user_id);
            $billing = new PaygBillingService($walletService);

            if ($user && $billing->isPaygUser($user)) {
                $expired = empty($user->will_expire) || strtotime($user->will_expire) <= time();
                $suspended = ($user->subscription_status ?? '') === 'inactive';

                if ($expired || $suspended) {
                    $result = $billing->runCycle($user, true);
                    log_message('info', 'PAYG reactivation after top-up for user ' . $user->id . ': ' . json_encode($result));
                }
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'applyWalletTopup failed for payment ' . ($payment->id ?? '?') . ': ' . $e->getMessage());

            return false;
        }
    }
}

if (!function_exists('renewAlreadyApplied')) {
    /**
     * Has this payment's subscription extension already been written to the
     * users row? Guards against the gateway callback AND Subscription::callback
     * each extending will_expire for the same payment.
     *
     * @param object|array|null $payment
     */
    function renewAlreadyApplied($payment): bool
    {
        if (empty($payment)) {
            return false;
        }
        $raw = is_object($payment) ? ($payment->custom_data ?? null) : ($payment['custom_data'] ?? null);
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) && !empty($decoded['renew_applied_at']);
    }
}

if (!function_exists('markRenewApplied')) {
    /**
     * Record on the payments row that its subscription extension has been
     * applied, preserving any existing custom_data keys.
     */
    function markRenewApplied($paymentId): void
    {
        try {
            $paymentModel = model('App\Models\Payment');
            $payment = $paymentModel->find($paymentId);
            if (!$payment) {
                return;
            }

            $raw = is_object($payment) ? ($payment->custom_data ?? null) : ($payment['custom_data'] ?? null);
            $decoded = json_decode((string) $raw, true);
            if (!is_array($decoded)) {
                $decoded = [];
            }
            $decoded['renew_applied_at'] = date('Y-m-d H:i:s');

            $paymentModel->update($paymentId, ['custom_data' => json_encode($decoded)]);
        } catch (\Throwable $e) {
            log_message('error', 'markRenewApplied failed for payment ' . $paymentId . ': ' . $e->getMessage());
        }
    }
}
