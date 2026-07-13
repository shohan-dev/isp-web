<?php

namespace App\Libraries;

/**
 * PayStation payment gateway client (Hosted Checkout, operated by Service Hub
 * Ltd — a Bangladesh Bank licensed PSO).
 *
 * Uses the CURRENT official tokenless flow documented at
 * paystation.com.bd/documentation (verified live against sandbox):
 *
 *   Init:   POST {base}/initiate-payment   (merchantId+password in the form body) -> {payment_url}
 *   Verify: POST {base}/transaction-status (merchantId in the HEADER, body invoice_number)
 *              -> {status_code:"200", data:{trx_status:"success"=settled, payment_amount, trx_id, ...}}
 *
 * sandbox and live are DIFFERENT hosts sharing identical paths. There is no
 * hash/signature and no bearer token — credentials travel in plaintext over TLS.
 *
 * On return the gateway redirects the browser (GET) back to callback_url with
 * trx_id / status / invoice_number query params, but settlement is decided ONLY
 * by the server-to-server verify below (status_code 200 alone does NOT prove
 * payment — a found transaction can still be Failed).
 */
class PaystationPayment
{
    private string $baseUrl;
    private string $merchantId;
    private string $password;

    public function setConfig(array $config): void
    {
        $sandbox = ($config['sandbox'] ?? false) === true;
        $this->baseUrl    = $sandbox
            ? 'https://sandbox.paystation.com.bd'
            : 'https://api.paystation.com.bd';
        $this->merchantId = (string) ($config['merchant_id'] ?? '');
        $this->password   = (string) ($config['password'] ?? '');
    }

    /**
     * @return array{code:int,body:mixed,error:string}
     */
    private function request(string $url, array $headers, array $body): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $raw   = curl_exec($ch);
        $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'code'  => $code,
            'body'  => json_decode((string) $raw, true),
            'error' => $error,
        ];
    }

    /**
     * Create a payment and return the payment_url.
     *
     * @param array $payment expects: invoice_number, amount, reference,
     *              callback_url, customer_name, customer_phone, customer_email,
     *              customer_address
     */
    public function createPayment(array $payment): string
    {
        $body = [
            'merchantId'     => $this->merchantId,
            'password'       => $this->password,
            'invoice_number' => (string) $payment['invoice_number'],
            'currency'       => 'BDT',
            'payment_amount' => (float) $payment['amount'],
            'reference'      => (string) ($payment['reference'] ?? $payment['invoice_number']),
            'cust_name'      => (string) ($payment['customer_name'] ?? 'Customer'),
            'cust_phone'     => (string) ($payment['customer_phone'] ?? ''),
            'cust_email'     => (string) ($payment['customer_email'] ?? 'customer@example.com'),
            'cust_address'   => (string) ($payment['customer_address'] ?? 'Dhaka'),
            'callback_url'   => (string) $payment['callback_url'],
            'checkout_items' => 'ISP',
        ];

        $res = $this->request($this->baseUrl . '/initiate-payment', [], $body);

        $url = $res['body']['payment_url'] ?? null;
        if (empty($url)) {
            $msg = $res['body']['message'] ?? $res['error'] ?? 'Unknown error';
            throw new \RuntimeException('PayStation initialization failed: ' . $msg);
        }

        return (string) $url;
    }

    /**
     * Verify a transaction server-to-server by the invoice number we own.
     * Returns the settled data record when trx_status is success, otherwise
     * null. The caller must additionally cross-check data.payment_amount against
     * the expected amount before crediting.
     *
     * @return array<string,mixed>|null
     */
    public function verify(string $invoiceNumber): ?array
    {
        $res = $this->request(
            $this->baseUrl . '/transaction-status',
            ['merchantId: ' . $this->merchantId],
            ['invoice_number' => $invoiceNumber]
        );

        $body = $res['body'] ?? [];
        if (!is_array($body) || (string) ($body['status_code'] ?? '') !== '200') {
            return null;
        }

        $data = $body['data'] ?? [];
        $status = strtolower((string) ($data['trx_status'] ?? ''));

        // Only 'success' means settled — 'processing' means the customer started
        // but never paid, 'failed'/'refund' are not valid to provision on.
        return $status === 'success' ? $data : null;
    }
}
