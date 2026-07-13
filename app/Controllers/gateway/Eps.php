<?php

namespace App\Controllers\Gateway;

use App\Controllers\BaseController;
use App\Libraries\EpsPayment;

/**
 * EPS (Easy Payment System) gateway.
 *
 * Flow: getEpsPaymentUrl() (POST, AJAX) initializes a payment with EPS and
 * returns the RedirectURL. The customer pays on EPS, which redirects the
 * browser (GET) back to callback(). callback() re-verifies server-side with a
 * server-stored merchant transaction id (never trusting the redirect query)
 * before crediting anything, then routes through the shared settlement helper.
 */
class Eps extends BaseController
{
    protected $payment_model;

    public function __construct()
    {
        $this->payment_model = model('App\Models\Payment');
        helper('gateway_settlement');
    }

    public function getEpsPaymentUrl()
    {
        $this->validate(['payment_id' => ['rules' => 'required']]);

        if (! $this->validation->run()) {
            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }

        $id = getPostInput('payment_id');
        $payment = $this->payment_model->where(['id' => $id, 'status' => 'pending'])->first();

        if (empty($payment)) {
            show_404();
            return;
        }

        try {
            $ctx  = paymentGatewayContext($payment);
            $user = getUserById($payment->user_id);

            $merchantTxn = secure_random_string(12);
            $charge = (float) getSetting('eps_charge', 0, $ctx);
            $amount = round($payment->amount + ($payment->amount * ($charge / 100)), 2);

            $eps = new EpsPayment();
            $eps->setConfig([
                'sandbox'     => getSetting('eps_sandbox_mode', 'no', $ctx) === 'yes',
                'merchant_id' => getSetting('eps_merchant_id', '', $ctx),
                'store_id'    => getSetting('eps_store_id', '', $ctx),
                'username'    => getSetting('eps_username', '', $ctx),
                'password'    => getSetting('eps_password', '', $ctx),
                'hash_key'    => getSetting('eps_hash_key', '', $ctx),
            ]);

            $callback = url_to('route.payment.gateway.eps.callback', $payment->id);

            $redirectUrl = $eps->createPayment([
                'order_id'         => (string) $payment->invoice,
                'merchant_txn'     => $merchantTxn,
                'amount'           => $amount,
                'customer_name'    => $user->name ?? 'Customer',
                'customer_email'   => $user->email ?? 'customer@example.com',
                'customer_phone'   => $user->mobile ?? '',
                'customer_address' => 'Dhaka',
                'success_url'      => $callback,
                'fail_url'         => $callback,
                'cancel_url'       => $callback,
            ]);

            // Record the server-generated merchant txn (+ expected amount) so the
            // callback can re-verify authoritatively instead of trusting the
            // query string.
            stashPaymentMeta($payment->id, [
                'eps_merchant_txn' => $merchantTxn,
                'eps_amount'       => $amount,
            ]);

            return requestResponse('success', $redirectUrl, 200);
        } catch (\Throwable $e) {
            log_message('error', 'EPS init error: ' . $e->getMessage());
            return requestResponse('error', $e->getMessage(), 200);
        }
    }

    public function callback($id)
    {
        $payment = $this->payment_model->where(['id' => $id])->first();
        if (empty($payment)) {
            show_404();
            return;
        }

        // Idempotent: a webhook/IPN or an earlier callback may have settled it.
        if ($payment->status === 'successful') {
            setSession('pid', $id);
            return redirect()->to(route_to('route.subscription.callback'));
        }

        $merchantTxn = readPaymentMeta($payment, 'eps_merchant_txn');
        if (empty($merchantTxn)) {
            return $this->fail('Could not resolve the EPS transaction reference.');
        }

        try {
            $ctx = paymentGatewayContext($payment);
            $eps = new EpsPayment();
            $eps->setConfig([
                'sandbox'     => getSetting('eps_sandbox_mode', 'no', $ctx) === 'yes',
                'merchant_id' => getSetting('eps_merchant_id', '', $ctx),
                'store_id'    => getSetting('eps_store_id', '', $ctx),
                'username'    => getSetting('eps_username', '', $ctx),
                'password'    => getSetting('eps_password', '', $ctx),
                'hash_key'    => getSetting('eps_hash_key', '', $ctx),
            ]);

            $record = $eps->verify((string) $merchantTxn);
            if ($record === null) {
                return $this->fail('Payment was not completed.');
            }

            // Defence-in-depth: confirm the settled amount matches what we charged.
            $expected = (float) readPaymentMeta($payment, 'eps_amount');
            $paid     = (float) ($record['TotalAmount'] ?? ($record['totalAmount'] ?? 0));
            if ($expected > 0 && $paid + 0.01 < $expected) {
                log_message('error', "EPS amount mismatch for payment {$id}: paid={$paid} expected={$expected}");
                return $this->fail('Payment verification did not match this order.');
            }

            $trxId = (string) ($record['EpsTransactionId'] ?? ($record['EPSTransactionId'] ?? $merchantTxn));

            if (applyGatewaySuccess($payment, 'EPS', $trxId)) {
                return redirect()->to(route_to('route.subscription.callback'));
            }

            return $this->fail('Could not update your payment record! Please contact the administrator');
        } catch (\Throwable $e) {
            log_message('error', 'EPS callback error: ' . $e->getMessage());
            return $this->fail($e->getMessage());
        }
    }

    private function fail(string $message)
    {
        if (! getSession('user_id')) {
            return redirect()->to(route_to('route.subscription'))->with('pay-error', $message);
        }

        return redirect()->to(route_to('route.payment'))->with('pay-error', $message);
    }
}
