<?php

/**
 * Shared post-settlement logic for redirect-style payment gateways
 * (EPS / shurjoPay / PayStation). Factored out of the per-gateway callback
 * controllers so the money-critical path — wallet top-up, reseller fund credit,
 * subscription renewal, PPPoE re-enable, reseller charge deduction and the
 * double-extension guard — lives in ONE audited place instead of being copied
 * (and drifting) across every gateway.
 *
 * Mirrors the hardened App\Controllers\Gateway\SSLCommerz::queryPayment success
 * branch line-for-line (TOCTOU re-fetch guard, atomic overdraw-blocking deduct,
 * markRenewApplied). bKash / Nagad / SSLCommerz keep their own inline copies;
 * new gateways route through here.
 */

if (!function_exists('stashPaymentMeta')) {
    /**
     * Merge key/value pairs into a payments row's custom_data JSON without
     * clobbering existing keys (e.g. purpose, duration, renew_applied_at).
     * Used by the gateway initiators to record a server-generated reference
     * (order_id / invoice / merchant txn) that the callback reads back instead
     * of trusting the attacker-suppliable redirect query.
     */
    function stashPaymentMeta($paymentId, array $kv): void
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
            $paymentModel->update($paymentId, ['custom_data' => json_encode(array_merge($decoded, $kv))]);
        } catch (\Throwable $e) {
            log_message('error', 'stashPaymentMeta failed for payment ' . $paymentId . ': ' . $e->getMessage());
        }
    }
}

if (!function_exists('readPaymentMeta')) {
    /**
     * Read a single key previously stored by stashPaymentMeta().
     */
    function readPaymentMeta($payment, string $key)
    {
        $raw = is_object($payment) ? ($payment->custom_data ?? null) : ($payment['custom_data'] ?? null);
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? ($decoded[$key] ?? null) : null;
    }
}

if (!function_exists('applyGatewaySuccess')) {
    /**
     * Apply a confirmed successful gateway payment to a pending payments row.
     *
     * The caller MUST have already verified with the gateway that the money
     * actually settled, and SHOULD short-circuit when $payment is already
     * 'successful' (double-callback) before calling this.
     *
     * On success it sets the 'pid' session and returns true, so the controller
     * can redirect to route.subscription.callback exactly as the existing
     * gateways do. Returns false only when the final payment-row update fails.
     *
     * @param object $payment   pending payments row (fresh from the model)
     * @param string $paidVia   the paid_via label to stamp (e.g. 'EPS')
     * @param string $methodTrx the gateway transaction id (stored on method_trx
     *                           and the gateway_trx idempotency column)
     */
    function applyGatewaySuccess(object $payment, string $paidVia, string $methodTrx): bool
    {
        $payment_model = model('App\Models\Payment');

        // TOCTOU guard — the row could be gone between gateway verification and
        // this re-fetch (concurrent callback / manual deletion).
        $payment = $payment_model->where(['id' => $payment->id])->first();
        if ($payment === null) {
            log_message('error', "{$paidVia} settlement: payment not found after re-fetch");

            return false;
        }

        // Idempotency: a concurrent or replayed callback (redirect refresh,
        // gateway server-ping + browser redirect, double-click) may have already
        // settled this row. Re-applying would double-debit the reseller fund and
        // double-insert the reseller transaction, so treat an already-successful
        // row as a no-op success. (The reseller deduct below is also reference-
        // idempotent to close the tighter simultaneous-callback window.)
        if (($payment->status ?? '') === 'successful') {
            log_message('info', "{$paidVia} settlement: payment {$payment->id} already successful, skipping re-apply");
            setSession('pid', $payment->id);

            return true;
        }

        $user_model = model('App\Models\User');
        $customer_details = $user_model->where(['id' => $payment->user_id])->first();

        $data = [
            'paid_via'    => $paidVia,
            'method_trx'  => $methodTrx,
            'gateway_trx' => $methodTrx,
            'paid_at'     => date('Y-m-d'),
            'status'      => 'successful',
        ];

        // Wallet top-ups only credit the tenant wallet — no will_expire /
        // subscription changes here.
        if (paymentPurpose($payment) === 'wallet_topup') {
            $payment_model->update($payment->id, $data);
            applyWalletTopup($payment_model->find($payment->id));

            setSession('pid', $payment->id);

            return true;
        }

        // Reseller fund top-up — idempotent on replay.
        if ($payment->user_type === 'resellerAdmin') {
            (new \App\Services\FundService())->add(
                (int) $payment->user_id,
                (float) $payment->amount,
                'payment:' . (int) $payment->id,
                $paidVia . ' reseller fund top-up'
            );
            $customer_details = $user_model->where(['id' => $payment->user_id])->first();
        }

        // Ordinary customer renewal — build the subscription extension.
        if ($payment->user_type != 'resellerAdmin') {
            $duration = null;
            if (!empty($payment->custom_data)) {
                $decoded = json_decode($payment->custom_data, true);
                if (isset($decoded['duration'])) {
                    $duration = (int) $decoded['duration'];
                }
            }
            helper('subscription');
            $udata = buildSubscriptionRenewUserData((int) $payment->user_id, $payment, $duration);
        } else {
            $udata = [];
        }

        // Re-enable the PPPoE secret on the router for real end users.
        $targetUser = getUserById($payment->user_id);
        if ($targetUser && $targetUser->role == 'user') {
            $router_client = routerClient($targetUser->router_id);

            if (!is_array($router_client)) {
                $pppoe = getPPPoEUserUserId($router_client, $targetUser->id);
                $pppoe_id = $pppoe[0]['.id'] ?? $targetUser->pppoe_id ?? null;

                log_message('info', "PPPoE ID for User ID {$targetUser->id}: {$pppoe_id}");

                $enabled = enablePPPoEUser($router_client, $pppoe_id);

                if (!$enabled) {
                    log_message('error', "Failed to enable PPPoE user for User ID {$targetUser->id}");

                    $router_model = model('App\Models\UserRouterDataModel');
                    $routerData = $router_model->where('user_id', $targetUser->id)->first();

                    $pppoe_secret = $routerData ? (is_array($routerData) ? ($routerData['pppoe_secret'] ?? null) : ($routerData->pppoe_secret ?? null)) : null;
                    $res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret);
                    if ($res) {
                        log_message('info', "Successfully enabled PPPoE user for User ID {$targetUser->id}");
                    }
                }
            }
        }

        if (!empty($udata)) {
            $user_model->update($payment->user_id, $udata);
        }

        // Prevent Subscription::callback from extending will_expire again.
        if (isset($udata['will_expire'])) {
            markRenewApplied($payment->id);
        }

        $result = $payment_model->update($payment->id, $data);

        $updatedPayment = $payment_model->find($payment->id);
        $userId   = $updatedPayment->user_id ?? $updatedPayment['user_id'];
        $admin_id = $updatedPayment->admin_id ?? $updatedPayment['admin_id'];

        $admin_details = $user_model->where(['id' => $admin_id])->first();
        $package_id = $customer_details->package_id ?? $customer_details['package_id'] ?? null;
        $role = $admin_details->role ?? $admin_details['role'] ?? null;

        // Reseller-owned customer paid: deduct the reseller's fund for the
        // package price and record the transaction (only when the deduction
        // actually succeeds — a failed deduction must not leave a phantom debit).
        if ($role === 'resellerAdmin' && $payment->user_type != 'resellerAdmin') {
            $fund = $admin_details->fund ?? $admin_details['fund'] ?? 0;
            $price = ResellerPackagePrice($package_id, null, $admin_id, 'resellerAdmin');

            // Reference-idempotent: a replayed callback for the same payment is a
            // no-op (parity with the wallet credit / reseller fund top-up above),
            // so the reseller is never double-charged for one renewal.
            if (! (new \App\Services\FundService())->deduct(
                (int) $admin_id,
                (float) $price,
                'reseller-charge:' . (int) $payment->id,
                $paidVia . ' customer renewal charge'
            )) {
                log_message('error', "Reseller {$admin_id} insufficient fund ({$fund}) for price {$price} on {$paidVia} payment {$payment->id}; overdraw blocked, transaction not recorded.");
            } else {
                $transationdata = [
                    'customer'      => $userId,
                    'admin_id'      => $admin_id,
                    'amount'        => $price,
                    'package_price' => $price,
                    'active_for'    => '--',
                    'comments'      => 'Single Customer payment renewal, paid by customer.',
                ];
                $transationModel = model('App\Models\ResellerTransactions');
                $transationModel->insert($transationdata);
            }
        }

        if ($result) {
            setSession('pid', $payment->id);

            return true;
        }

        return false;
    }
}
