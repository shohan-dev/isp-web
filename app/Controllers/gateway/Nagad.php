<?php

namespace App\Controllers\Gateway;

use App\Controllers\BaseController;

use Xenon\NagadApi\Helper;
use Xenon\NagadApi\Base;
use Xenon\NagadApi\Exception\NagadPaymentException;

class Nagad extends BaseController
{

    protected $payment_model;

    public function __construct()
    {
        $this->payment_model = model('App\Models\Payment');
    }

    /**
     * get payment url
     */
    public function getNagadPaymentUrl()
    {

        $this->validate([
            'payment_id' => ['rules' => 'required'],
        ]);

        if ($this->validation->run()) {

            $id = getPostInput('payment_id');

            // No user_type filter: tenant sAdmin renewals and wallet top-ups
            // ride this gateway too (parity with Bkash).
            $payment = $this->payment_model->where(['id' => $id, 'status' => 'pending'])->first();

            if (!empty($payment)) {

                try {
                    $userIdContext = paymentGatewayContext($payment);
                    $merchantAccount = getSetting('nagadpg_merchant_account', '', $userIdContext);
                    $merchantId = getSetting('nagadpg_merchant_id', '', $userIdContext);
                    $publicKey = getSetting('nagadpg_merchant_public_key', '', $userIdContext);

                    if (empty($merchantAccount) || empty($merchantId) || empty($publicKey)) {
                        return requestResponse('error', 'Nagad configuration is incomplete. Please check your merchant credentials.', 200);
                    }

                    // Basic check for PEM format
                    if (strpos($publicKey, '-----BEGIN') === false) {
                        return requestResponse('error', 'Invalid Nagad Public Key format. Please ensure it is a valid PEM key.', 200);
                    }

                    $config = [
                        'NAGAD_APP_ENV' => (getSetting('nagadpg_sandbox_mode', 'no', $userIdContext) === 'yes') ? 'development' : 'production',
                        'NAGAD_APP_LOG' => '1',
                        'NAGAD_APP_ACCOUNT' => $merchantAccount,
                        'NAGAD_APP_MERCHANTID' => $merchantId,
                        'NAGAD_APP_MERCHANT_PRIVATE_KEY' => getSetting('nagadpg_merchant_private_key', '', $userIdContext),
                        'NAGAD_APP_MERCHANT_PG_PUBLIC_KEY' => $publicKey,
                        'NAGAD_APP_TIMEZONE' => getSetting('nagadpg_timezone', 'Asia/Dhaka', $userIdContext),
                    ];

                    $nagad = new Base($config, [
                        'amount' => ($payment->amount + ($payment->amount * (getSetting('nagadpg_charge', 0, $userIdContext) / 100))),
                        'invoice' => secure_random_string(10), // Use random invoice for uniqueness
                        'merchantCallback' => url_to('route.payment.gateway.nagad.query', $payment->id),
                    ]);

                    $paymentUrl = $nagad->payNowWithoutRedirection($nagad);

                    return requestResponse('success', $paymentUrl, 200);
                } catch (NagadPaymentException $e) {

                    return requestResponse('error', $e->getMessage(), 200);
                }
            } else {

                show_404();
            }
        } else {

            //validation error
            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }
    }

    /**
     * query payment
     */
    public function queryPayment($id)
    {

        $url = $this->request->getUri();

        $response = Helper::successResponse($url);

        try {
            $payment = $this->payment_model->where(['id' => $id])->first();
            if (empty($payment)) {
                show_404();
            }

            $userIdContext = paymentGatewayContext($payment);
            $config = [
                'NAGAD_APP_ENV' => (getSetting('nagadpg_sandbox_mode', 'no', $userIdContext) === 'yes') ? 'development' : 'production',
                'NAGAD_APP_LOG' => '1',
                'NAGAD_APP_ACCOUNT' => getSetting('nagadpg_merchant_account', '', $userIdContext),
                'NAGAD_APP_MERCHANTID' => getSetting('nagadpg_merchant_id', '', $userIdContext),
                'NAGAD_APP_MERCHANT_PRIVATE_KEY' => getSetting('nagadpg_merchant_private_key', '', $userIdContext),
                'NAGAD_APP_MERCHANT_PG_PUBLIC_KEY' => getSetting('nagadpg_merchant_public_key', '', $userIdContext),
                'NAGAD_APP_TIMEZONE' => getSetting('nagadpg_timezone', 'Asia/Dhaka', $userIdContext),
            ];

            $helper = new Helper($config);
            $response = json_decode($helper->verifyPayment($response['payment_ref_id']));

            if (!empty($response) && $response->status === 'Success') {

                $payment = $this->payment_model->where(['id' => $id])->first();

                $user_model = model('App\Models\User');
                // $userModel = model('App\Models\User');
                $customer_details = $user_model->where(['id' => $payment->user_id])->first();


                $data = [
                    'paid_via' => 'Nagad',
                    'method_trx' => $response->orderId,
                    'paid_at' => date("Y-m-d"),
                    'status' => 'successful',
                ];

                // Wallet top-ups only credit the tenant wallet — no
                // will_expire / subscription changes here.
                if (paymentPurpose($payment) === 'wallet_topup') {
                    $this->payment_model->update($payment->id, $data);
                    applyWalletTopup($this->payment_model->find($payment->id));

                    setSession('pid', $id);
                    return redirect()->to(route_to('route.subscription.callback'));
                }
                if ($payment->user_type === 'resellerAdmin') {
                    (new \App\Services\FundService())->add(
                        (int) $payment->user_id,
                        (float) $payment->amount,
                        'payment:' . (int) $payment->id,
                        'Nagad reseller fund top-up'
                    );
                    $customer_details = $user_model->where(['id' => $payment->user_id])->first();
                }


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
                if (getUserById($payment->user_id)->role == 'user') {
                    $user = getUserById($payment->user_id);

                    $router_client = routerClient($user->router_id);

                    if (!is_array($router_client)) {
                        $pppoe = getPPPoEUserUserId($router_client, $user->id);
                        $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                        log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

                        $result = enablePPPoEUser($router_client, $pppoe_id);

                        if (! $result) {
                            log_message('error', "Failed to enable PPPoE user for User ID {$user->id}");

                            $router_model = model('App\Models\UserRouterDataModel');
                            // NB: keep this off $data — it still holds the payment
                            // update fields written below.
                            $routerData = $router_model->where('user_id', $user->id)->first();

                            $pppoe_secret = $routerData ? (is_array($routerData) ? ($routerData['pppoe_secret'] ?? null) : ($routerData->pppoe_secret ?? null)) : null;
                            $res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret);
                            if ($res) {
                                log_message('info', "Successfully enabled PPPoE user for User ID {$user->id}");
                                // $user_model->update($user->id, ['activity' => 'active']);
                            }
                        }

                        // $user_model->update($payment->user_id, ['conn_status' => 'conn']);

                    }
                }

                // $this->user_model->update($payment->user_id, ['conn_status' => 'conn']);
                // log_message('info', 'Fetched payment data: on callback' . json_encode($data));
                if (!empty($udata)) {
                    $result = $user_model->update($payment->user_id, $udata);
                }
                // log_message('info', 'Fetched payment data: on bcash' . json_encode($data));

                // Prevent Subscription::callback from extending will_expire again.
                if (isset($udata['will_expire'])) {
                    markRenewApplied($payment->id);
                }

                $result = $this->payment_model->update($payment->id, $data);




                $updatedPayment = $this->payment_model->find($payment->id);

                $userId = $updatedPayment->user_id ?? $updatedPayment['user_id'];
                $admin_id = $updatedPayment->admin_id ?? $updatedPayment['admin_id'];


                $admin_details = $user_model->where(['id' => $admin_id])->first();
                $package_id = $customer_details->package_id ?? $customer_details['package_id'];
                $role = $admin_details->role ?? $admin_details['role'];

                if ($role === 'resellerAdmin') {

                    $fund = $admin_details->fund ?? $admin_details['fund'] ?? 0;

                    $price = ResellerPackagePrice($package_id, null, $admin_id, 'resellerAdmin');
                    // log_message('info', 'Fetched tprice ResellerPackagePrice($package_id): ' . json_encode($price));

                    // if ($fund < $price) {
                    //     return requestResponse("error", "Dont have enough fund . Please recharge.", 500);
                    // }


                    // BUG-03 fix: only insert the reseller transaction + proceed when
                    // the fund deduction actually succeeds. Falling through on a false
                    // return created a phantom debit (ResellerTransactions row written
                    // with no matching fund reduction) and provisioned the customer
                    // without reseller coverage.
                    if (! (new \App\Services\FundService())->deduct((int) $admin_id, (float) $price)) {
                        log_message('error', "Reseller {$admin_id} insufficient fund ({$fund}) for price {$price} on Nagad payment {$payment->id}; overdraw blocked, transaction not recorded.");
                        // Fall through to redirect — payment was already marked successful
                        // above (gateway confirmed), but the reseller deduction is skipped.
                        // A reconciliation job should catch this for manual review.
                    } else {
                        $transationdata = [
                            'customer' => $userId,
                            'admin_id' => $admin_id,
                            'amount' => $price,
                            'package_price' => $price,
                            'active_for' => '--',
                            'comments' => 'Single Customer payment renewal, paid by customer.',
                        ];
                        $transationModel = model('App\Models\ResellerTransactions');
                        $transationModel->insert($transationdata);
                    }


                    // return requestResponse('success', "New customer record added successfully", 200);


                }

                $result = $this->payment_model->where(['id' => $id])->set($data)->update();

                if ($result) {

                    setSession('pid', $id);

                    return redirect()->to(route_to('route.subscription.callback'));
                } else {

                    if (!getSession('user_id')) {
                        return redirect()->to(route_to('route.subscription'))->with('pay-error', 'Could not update your payment record! Please contact the administrator');
                    }
                    return redirect()->to(route_to('route.payment'))->with('pay-error', 'Could not update your payment record! Please contact the administrator');
                }
            } else {
                if (!getSession('user_id')) {
                    return redirect()->to(route_to('route.subscription'))->with('pay-error', 'Payment is failed!');
                }
                return redirect()->to(route_to('route.payment'))->with('pay-error', 'Payment is failed!');
            }
        } catch (\Throwable $e) {
            if (!getSession('user_id')) {
                return redirect()->to(route_to('route.subscription'))->with('pay-error', $e->getMessage());
            }
            return redirect()->to(route_to('route.payment'))->with('pay-error', $e->getMessage());
        }
    }
}
