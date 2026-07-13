<?php

namespace App\Controllers\Gateway;

use App\Controllers\BaseController;
use App\Libraries\ShurjopayPayment;

/**
 * shurjoPay gateway.
 *
 * Flow: getShurjopayPaymentUrl() (POST, AJAX) initializes a payment and returns
 * the checkout_url. shurjoPay redirects the browser (GET) back to callback()
 * with its own ?order_id=. callback() re-verifies server-side and cross-checks
 * the verification record's customer_order_id and amount against the payment
 * before crediting, since the order_id arrives from the browser.
 */
class Shurjopay extends BaseController
{
    protected $payment_model;

    public function __construct()
    {
        $this->payment_model = model('App\Models\Payment');
        helper('gateway_settlement');
    }

    private function client($ctx): ShurjopayPayment
    {
        $sp = new ShurjopayPayment();
        $sp->setConfig([
            'sandbox'  => getSetting('shurjopay_sandbox_mode', 'no', $ctx) === 'yes',
            'username' => getSetting('shurjopay_username', '', $ctx),
            'password' => getSetting('shurjopay_password', '', $ctx),
            'prefix'   => getSetting('shurjopay_prefix', 'sp', $ctx),
        ]);

        return $sp;
    }

    public function getShurjopayPaymentUrl()
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

            // Our own order id — unique per attempt, recorded so the callback can
            // confirm the verification record refers to THIS payment.
            $orderId = $payment->invoice . '-' . secure_random_string(6);
            $charge  = (float) getSetting('shurjopay_charge', 0, $ctx);
            $amount  = round($payment->amount + ($payment->amount * ($charge / 100)), 2);

            $callback = url_to('route.payment.gateway.shurjopay.callback', $payment->id);

            $checkoutUrl = $this->client($ctx)->createPayment([
                'order_id'         => $orderId,
                'amount'           => $amount,
                'return_url'       => $callback,
                'cancel_url'       => $callback,
                'client_ip'        => $this->request->getIPAddress(),
                'customer_name'    => $user->name ?? 'Customer',
                'customer_phone'   => $user->mobile ?? '',
                'customer_email'   => $user->email ?? 'customer@example.com',
                'customer_address' => 'Dhaka',
            ]);

            stashPaymentMeta($payment->id, [
                'shurjopay_order_id' => $orderId,
                'shurjopay_amount'   => $amount,
            ]);

            return requestResponse('success', $checkoutUrl, 200);
        } catch (\Throwable $e) {
            log_message('error', 'shurjoPay init error: ' . $e->getMessage());
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

        if ($payment->status === 'successful') {
            setSession('pid', $id);
            return redirect()->to(route_to('route.subscription.callback'));
        }

        $spOrderId = getGetInput('order_id');
        if (empty($spOrderId)) {
            return $this->fail('Payment was cancelled or the reference is missing.');
        }

        try {
            $ctx    = paymentGatewayContext($payment);
            $record = $this->client($ctx)->verify((string) $spOrderId);

            if ($record === null) {
                return $this->fail('Payment was not completed.');
            }

            // Cross-check the settled record actually belongs to THIS payment.
            $ourOrderId = (string) readPaymentMeta($payment, 'shurjopay_order_id');
            $expected   = (float) readPaymentMeta($payment, 'shurjopay_amount');
            $recOrderId = (string) ($record['customer_order_id'] ?? '');
            // shurjoPay misspells the settled-amount field as "recived_amount".
            $recAmount  = (float) ($record['recived_amount'] ?? ($record['received_amount'] ?? ($record['amount'] ?? 0)));

            if ($ourOrderId === '' || $recOrderId !== $ourOrderId || $recAmount + 0.01 < $expected) {
                log_message('error', "shurjoPay callback mismatch for payment {$id}: our={$ourOrderId} rec={$recOrderId} amt={$recAmount} exp={$expected}");
                return $this->fail('Payment verification did not match this order.');
            }

            $trxId = (string) ($record['bank_trx_id'] ?? ($record['sp_order_id'] ?? $spOrderId));

            if (applyGatewaySuccess($payment, 'shurjoPay', $trxId)) {
                return redirect()->to(route_to('route.subscription.callback'));
            }

            return $this->fail('Could not update your payment record! Please contact the administrator');
        } catch (\Throwable $e) {
            log_message('error', 'shurjoPay callback error: ' . $e->getMessage());
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
