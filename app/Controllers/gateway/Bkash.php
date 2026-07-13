<?php

namespace App\Controllers\Gateway;

use App\Controllers\BaseController;

use App\Libraries\BkashPhp;

class Bkash extends BaseController
{

    protected $bkash_config, $payment_model;

    public function __construct()
    {
        /**
         * Bkash Config File
         */
        $this->bkash_config = new \Config\PaymentGateway\BkashConfig();

        /**
         * Payment Model
         */
        $this->payment_model = model('App\Models\Payment');
    }

    /**
     * Bkash Payment Gateway
     * @action: Get Payment url
     */
    public function getBkashPaymentUrl()
    {

        $this->validate([
            'payment_id' => ['rules' => 'required'],
        ]);

        if ($this->validation->run()) {

            $id = getPostInput('payment_id');

            $payment = $this->payment_model->where(['id' => $id, 'status' => 'pending'])->first();

            if (!empty($payment)) {

                $trxId = $payment->invoice;

                // Platform-bound payments (tenant renewals / wallet top-ups) must
                // run on the platform operator's merchant credentials.
                $gatewayCtx = paymentGatewayContext($payment);

                $paymentData = array(
                    'mode' => '0011',

                    'amount' => ($payment->amount + ($payment->amount * ((int) getSetting('bkashpg_charge', 'amount') / 100))),

                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'payerReference' => 'payment',
                    'merchantInvoiceNumber' => $trxId,
                    'callbackURL' => url_to('route.payment.gateway.bkash.callback', $id),
                );

                try {

                    $bkash = new BkashPhp();

                    $bkash->setConfig([
                        'app_key' => getSetting('bkashpg_app_key', '', $gatewayCtx),
                        'app_secret' => getSetting('bkashpg_app_secret', '', $gatewayCtx),
                        'username' => getSetting('bkashpg_username', '', $gatewayCtx),
                        'password' => getSetting('bkashpg_password', '', $gatewayCtx),
                        'environment' => (getSetting('bkashpg_sandbox_mode', 'no', $gatewayCtx) == 'yes') ? 'sandbox' : 'production',
                    ]);

                    // log_message('info', 'User bkash details for user ' . $payment->user_id . ': ' . print_r($bkash, true));


                    $bkashResult = $bkash->createPayment($paymentData);

                    if (!empty($bkashResult->bkashURL)) {

                        return requestResponse('success', $bkashResult->bkashURL, 200);
                    }

                    return requestResponse('error', "Something went wrong! Please try again", 500);
                } catch (\Throwable $e) {

                    return requestResponse('error', $e->getMessage(), 500);
                }
            }

            return requestResponse('error', 'Invalid transaction data', 400);
        }
        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Bkash Payment Gateway
     * @action: Query Payment
     */
    public function callback($id)
    {
        $paymentID = getGetInput('paymentID');

        $status = getGetInput('status');

        log_message('info', "[Bkash Callback Debug] Initiated callback for Payment URL ID: {$id}. status = {$status}, paymentID = {$paymentID}");

        if (empty($paymentID) || empty($status)) {
            log_message('error', "[Bkash Callback Debug] paymentID or status parameter is empty. Aborting request.");
            show_404();
        }

        $payment = $this->payment_model->where(['id' => $id])->first();

        if (!empty($payment)) {
            if ($payment->status === 'successful') {
                log_message('info', "[Bkash Callback Debug] Payment record {$id} is already successful (processed by Webhook/IPN). Skipping executePayment.");
                setSession('pid', $id);
                return redirect()->route('route.subscription.callback');
            }

            log_message('info', "[Bkash Callback Debug] Found payment record. Invoice: {$payment->invoice}, Amount: {$payment->amount}, User ID: {$payment->user_id}");

            if ($status == 'cancel') {
                log_message('info', "[Bkash Callback Debug] Status is cancel. Redirecting user back.");
                return redirect()->route('route.payment')->with('pay-error', "You've cancelled the payment");
            }

            try {
                $gatewayCtx = paymentGatewayContext($payment);
                log_message('info', "[Bkash Callback Debug] Configuring Bkash API settings for context ID: {$gatewayCtx} (payment user {$payment->user_id})");
                $bkash = new BkashPhp();

                $bkash->setConfig([
                    'app_key' => getSetting('bkashpg_app_key', '', $gatewayCtx),
                    'app_secret' => getSetting('bkashpg_app_secret', '', $gatewayCtx),
                    'username' => getSetting('bkashpg_username', '', $gatewayCtx),
                    'password' => getSetting('bkashpg_password', '', $gatewayCtx),
                    'environment' => (getSetting('bkashpg_sandbox_mode', 'no', $gatewayCtx) == 'yes') ? 'sandbox' : 'production',
                ]);

                log_message('info', "[Bkash Callback Debug] Executing Bkash payment execution for paymentID: {$paymentID}");
                $response = $bkash->executePayment($paymentID);
                log_message('info', "[Bkash Callback Debug] Execute Payment Response: " . json_encode($response));

                if ($response->transactionStatus === 'Completed') {

                    // Wallet top-ups only credit the tenant wallet — they never
                    // touch will_expire / subscription_status here.
                    if (paymentPurpose($payment) === 'wallet_topup') {
                        $this->payment_model->update($payment->id, [
                            'paid_via' => 'Bkash',
                            'method_trx' => $response->trxID,
                            'paid_at' => date('Y-m-d H:i:s'),
                            'status' => 'successful',
                        ]);

                        applyWalletTopup($this->payment_model->find($payment->id));

                        setSession('pid', $id);
                        return redirect()->route('route.subscription.callback');
                    }
                    log_message('info', "[Bkash Callback Debug] bKash transaction Completed successfully. Updating payment record only (subscription dates handled by Subscription::callback).");
                    $user_model = model('App\\Models\\User');
                    $customer_details = $user_model->where(['id' => $payment->user_id])->first();

                    // Only update the payment record here.
                    // will_expire, subscription_status, conn_status and PPPoE enabling
                    // are handled by Subscription::callback to avoid double date extension.
                    $data = [
                        'paid_via' => 'Bkash',
                        'method_trx' => $response->trxID,
                        'paid_at' => date("Y-m-d"),
                        'status' => 'successful',
                    ];

                    // For reseller fund top-up, credit via FundService (idempotent on replay).
                    if ($payment->user_type === 'resellerAdmin') {
                        (new \App\Services\FundService())->add(
                            (int) $payment->user_id,
                            (float) $payment->amount,
                            'payment:' . (int) $payment->id,
                            'Bkash reseller fund top-up'
                        );
                        $customer_details = $user_model->where(['id' => $payment->user_id])->first();
                    }


                    if (getSession('user_role') != 'resellerAdmin' && $payment->user_type != 'resellerAdmin') {
                        log_message('info', "[Bkash Callback Debug] Non-reseller payment. Building subscription renew data...");
                        $duration = null;
                        if (!empty($payment->custom_data)) {
                            $decoded = json_decode($payment->custom_data, true);
                            if (isset($decoded['duration'])) {
                                $duration = (int) $decoded['duration'];
                            }
                        }
                        helper('subscription');
                        $udata = buildSubscriptionRenewUserData((int) $payment->user_id, $payment, $duration);
                        log_message('info', "[Bkash Callback Debug] Subscription renew data: " . json_encode($udata));
                    } else {
                        $udata = [];
                    }

                    $targetUser = getUserById($payment->user_id);
                    $targetUserRole = $targetUser ? $targetUser->role : null;
                    log_message('info', "[Bkash Callback Debug] Target User Role: {$targetUserRole}");

                    if ($targetUserRole == 'user' && $payment->user_type != 'resellerAdmin') {
                        $user = $targetUser;

                        $router_client = routerClient($user->router_id);

                        if (!is_array($router_client)) {
                            log_message('info', "[Bkash Callback Debug] Router Client configured. Enabling PPPoE user...");
                            $pppoe = getPPPoEUserUserId($router_client, $user->id);
                            $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                            log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

                            $result = enablePPPoEUser($router_client, $pppoe_id);

                            if (! $result) {
                                log_message('error', "Failed to enable PPPoE user for User ID {$user->id}. Retrying via pppoe_secret...");

                                $router_model = model('App\Models\UserRouterDataModel');
                                $data = $router_model->where('user_id', $user->id)->first();

                                $pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;
                                $res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret);
                                if ($res) {
                                    log_message('info', "Successfully enabled PPPoE user for User ID {$user->id}");
                                }
                            }
                        }
                    }

                    log_message('info', "[Bkash Callback Debug] Updating user details in database...");
                    if (!empty($udata)) {
                        $result = $user_model->update($payment->user_id, $udata);
                        log_message('info', "[Bkash Callback Debug] User details update result: " . json_encode($result));
                    }

                    // buildSubscriptionRenewUserData already marks renew applied when needed.
                    if (isset($udata['will_expire']) && !renewAlreadyApplied($payment)) {
                        markRenewApplied($payment->id);
                    }

                    log_message('info', "[Bkash Callback Debug] Updating payment record in database...");
                    $result = $this->payment_model->update($payment->id, $data);
                    log_message('info', "[Bkash Callback Debug] Payment record update result: " . json_encode($result));

                    $updatedPayment = $this->payment_model->find($payment->id);

                    $userId = $updatedPayment->user_id ?? $updatedPayment['user_id'];
                    $admin_id = $updatedPayment->admin_id ?? $updatedPayment['admin_id'];

                    $admin_details = $user_model->where(['id' => $admin_id])->first();

                    $package_id = $customer_details->package_id ?? $customer_details['package_id'];
                    $role = $admin_details->role ?? $admin_details['role'];
                    
                    log_message('info', "[Bkash Callback Debug] Admin Role: {$role}, Package ID: {$package_id}");
                    if ($role === 'resellerAdmin' && $payment->user_type != 'resellerAdmin') {
                        log_message('info', "[Bkash Callback Debug] Resolving reseller package price and updating reseller fund...");
                        $fund = $admin_details->fund ?? $admin_details['fund'] ?? 0;

                        $price = ResellerPackagePrice($package_id, null, $admin_id, 'resellerAdmin');

                        // Block overdraw: atomic, race-safe deduction that the DB refuses to
                        // take below zero (replaces the unguarded read-modify-write). If the
                        // reseller is short the balance is left unchanged and logged for
                        // reconciliation. (Refusing the renewal outright requires moving this
                        // gate ahead of the customer renewal above — follow-up reorder.)
                        if (! (new \App\Services\FundService())->deduct((int) $admin_id, (float) $price)) {
                            log_message('error', "Reseller {$admin_id} insufficient fund ({$fund}) for price {$price} on Bkash payment {$payment->id}; overdraw blocked, charge not deducted.");
                        }
                        $transationdata = [
                            'customer' => $userId,
                            'admin_id' => $admin_id,
                            'amount' => $price,
                            'package_price' => $price,
                            'active_for' => '--',
                            'comments' => 'Single Customer payment renewal, paid by customer.',
                        ];
                        
                        $transationModel = model('App\\Models\\ResellerTransactions');
                        $result = $transationModel->insert($transationdata);
                        log_message('info', "[Bkash Callback Debug] Reseller transaction record inserted: " . json_encode($result));
                    }

                    if ($result) {
                        log_message('info', "[Bkash Callback Debug] Flow complete. Setting session pid and redirecting to subscription callback.");
                        setSession('pid', $id);
                        return redirect()->route('route.subscription.callback');
                    }

                    log_message('error', "[Bkash Callback Debug] Flow failed at payment record update. Redirecting user back.");
                    return redirect()->route('route.payment')->with('pay-error', 'Could not update your payment record! Please contact the administrator');
                }

                log_message('error', "[Bkash Callback Debug] Execute Payment did not return completed status. Message: " . ($response->errorMessage ?? 'Unknown error'));
                return redirect()->route('route.payment')->with('pay-error', $response->errorMessage);
            } catch (\Throwable $e) {
                log_message('error', '[Bkash Callback Debug] Bkash payment error: ' . $e->getMessage() . ' at file ' . $e->getFile() . ' line ' . $e->getLine());
                return redirect()->route('route.payment')->with('pay-error', $e->getMessage());
            }
        }

        log_message('error', "[Bkash Callback Debug] Payment records with URL ID {$id} not found in database. Aborting.");
        return redirect()->route('route.payment')->with('pay-error', "Payment records not found! Please contact the administrator");
    }
}
