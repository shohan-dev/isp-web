<?php

namespace App\Controllers\Gateway;

use App\Controllers\BaseController;
use App\Libraries\PaystationPayment;

/**
 * PayStation gateway.
 *
 * Flow: getPaystationPaymentUrl() (POST, AJAX) creates a payment and returns
 * the payment_url. PayStation redirects the browser (GET) back to callback()
 * with ?trx_id&trx_status&invoice_number. callback() re-verifies server-side
 * with retrive-transaction keyed by the server-stored invoice number before
 * crediting.
 */
class Paystation extends BaseController
{
    protected $payment_model;

    public function __construct()
    {
        $this->payment_model = model('App\Models\Payment');
        helper('gateway_settlement');
    }

    private function client($ctx): PaystationPayment
    {
        $ps = new PaystationPayment();
        $ps->setConfig([
            'sandbox'     => getSetting('paystation_sandbox_mode', 'no', $ctx) === 'yes',
            'merchant_id' => getSetting('paystation_merchant_id', '', $ctx),
            'password'    => getSetting('paystation_password', '', $ctx),
        ]);

        return $ps;
    }

    public function getPaystationPaymentUrl()
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

            // Invoice numbers must be unique per merchant on PayStation.
            $invoiceNumber = $payment->invoice . '-' . secure_random_string(6);
            $charge = (float) getSetting('paystation_charge', 0, $ctx);
            $amount = round($payment->amount + ($payment->amount * ($charge / 100)), 2);

            $callback = url_to('route.payment.gateway.paystation.callback', $payment->id);

            $paymentUrl = $this->client($ctx)->createPayment([
                'invoice_number'   => $invoiceNumber,
                'amount'           => $amount,
                'reference'        => (string) $payment->invoice,
                'callback_url'     => $callback,
                'customer_name'    => $user->name ?? 'Customer',
                'customer_phone'   => $user->mobile ?? '',
                'customer_email'   => $user->email ?? 'customer@example.com',
                'customer_address' => 'Dhaka',
            ]);

            stashPaymentMeta($payment->id, [
                'paystation_invoice' => $invoiceNumber,
                'paystation_amount'  => $amount,
            ]);

            return requestResponse('success', $paymentUrl, 200);
        } catch (\Throwable $e) {
            log_message('error', 'PayStation init error: ' . $e->getMessage());
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

        $invoiceNumber = readPaymentMeta($payment, 'paystation_invoice');
        if (empty($invoiceNumber)) {
            return $this->fail('Could not resolve the PayStation transaction reference.');
        }

        try {
            $ctx = paymentGatewayContext($payment);

            // Verify server-to-server by the invoice number we own — never trust
            // the browser callback's status param (status_code 200 alone does not
            // prove payment).
            $data = $this->client($ctx)->verify((string) $invoiceNumber);
            if ($data === null) {
                return $this->fail('Payment was not completed.');
            }

            $expected = (float) readPaymentMeta($payment, 'paystation_amount');
            $paid     = (float) ($data['payment_amount'] ?? 0);
            if ($expected > 0 && $paid + 0.01 < $expected) {
                log_message('error', "PayStation amount mismatch for payment {$id}: paid={$paid} expected={$expected}");
                return $this->fail('Payment verification did not match this order.');
            }

            $stamp = (string) ($data['trx_id'] ?? $invoiceNumber);

            if (applyGatewaySuccess($payment, 'PayStation', $stamp)) {
                return redirect()->to(route_to('route.subscription.callback'));
            }

            return $this->fail('Could not update your payment record! Please contact the administrator');
        } catch (\Throwable $e) {
            log_message('error', 'PayStation callback error: ' . $e->getMessage());
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
