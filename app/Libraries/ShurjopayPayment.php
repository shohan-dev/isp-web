<?php

namespace App\Libraries;

/**
 * shurjoPay payment gateway client.
 *
 * Endpoint/field contract taken from the official PHP plugin
 * (github.com/shurjopay-plugins/sp-plugin-php).
 *
 *   Auth:   POST {base}/api/get_token         body {username,password} -> {token,store_id}
 *   Pay:    POST {base}/api/secret-pay        (Bearer) -> {checkout_url}
 *   Verify: POST {base}/api/verification      (Bearer) body {order_id} -> [{sp_code:"1000"=paid}]
 *
 * On return the gateway redirects the browser (GET) back to return_url with an
 * `order_id` query param, which is then passed to verifyPayment().
 */
class ShurjopayPayment
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $prefix;

    private string $token = '';
    private string $storeId = '';

    public function setConfig(array $config): void
    {
        $sandbox = ($config['sandbox'] ?? false) === true;
        $this->baseUrl  = $sandbox
            ? 'https://sandbox.shurjopayment.com'
            : 'https://engine.shurjopayment.com';
        $this->username = (string) ($config['username'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
        $this->prefix   = (string) ($config['prefix'] ?? 'sp');
    }

    /**
     * @return array{code:int,body:mixed,error:string}
     */
    private function request(string $url, array $body, bool $json, ?string $bearer = null): array
    {
        $headers = [];
        if ($json) {
            $headers[] = 'Content-Type: application/json';
        }
        if ($bearer !== null) {
            $headers[] = 'Authorization: Bearer ' . $bearer;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json ? json_encode($body) : http_build_query($body));
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

    private function authenticate(): void
    {
        // Send as JSON, not form-urlencoded: shurjoPay passwords can contain '&'
        // (the sandbox password does), which a urlencoded body would split into a
        // second field and fail auth (sp_code 1064).
        $res = $this->request(
            $this->baseUrl . '/api/get_token',
            ['username' => $this->username, 'password' => $this->password],
            true
        );

        $token   = $res['body']['token'] ?? null;
        $storeId = $res['body']['store_id'] ?? null;
        if (empty($token) || empty($storeId)) {
            throw new \RuntimeException('shurjoPay authentication failed' . (!empty($res['error']) ? ': ' . $res['error'] : ''));
        }

        $this->token   = (string) $token;
        $this->storeId = (string) $storeId;
    }

    /**
     * Initialize a payment and return the checkout_url.
     *
     * @param array $payment expects: order_id, amount, return_url, cancel_url,
     *              client_ip, customer_name, customer_phone, customer_email,
     *              customer_address, customer_city
     */
    public function createPayment(array $payment): string
    {
        $this->authenticate();

        $body = [
            'token'            => $this->token,
            'store_id'         => $this->storeId,
            'prefix'           => $this->prefix,
            'currency'         => 'BDT',
            'return_url'       => (string) $payment['return_url'],
            'cancel_url'       => (string) $payment['cancel_url'],
            'amount'           => (float) $payment['amount'],
            'order_id'         => (string) $payment['order_id'],
            'discount_amount'  => 0,
            'disc_percent'     => 0,
            'client_ip'        => (string) ($payment['client_ip'] ?? '0.0.0.0'),
            'customer_name'    => (string) ($payment['customer_name'] ?? 'Customer'),
            'customer_phone'   => (string) ($payment['customer_phone'] ?? ''),
            'customer_email'   => (string) ($payment['customer_email'] ?? 'customer@example.com'),
            'customer_address' => (string) ($payment['customer_address'] ?? 'Dhaka'),
            'customer_city'    => 'Dhaka',
            'customer_state'   => 'Dhaka',
            'customer_postcode' => '1000',
            'customer_country' => 'Bangladesh',
        ];

        $res = $this->request($this->baseUrl . '/api/secret-pay', $body, true, $this->token);

        $checkout = $res['body']['checkout_url'] ?? null;
        if (empty($checkout)) {
            $msg = $res['body']['message'] ?? $res['error'] ?? 'Unknown error';
            throw new \RuntimeException('shurjoPay initialization failed: ' . $msg);
        }

        return (string) $checkout;
    }

    /**
     * Verify by shurjoPay order_id (the SP-prefixed id the gateway returns on
     * the callback). Returns the verification record when sp_code == 1000
     * (settled), otherwise null. The caller must cross-check the record's
     * customer_order_id / amount against the payment it is settling, because
     * the order_id arrives from the browser redirect and is attacker-suppliable.
     *
     * @return array<string,mixed>|null
     */
    public function verify(string $shurjopayOrderId): ?array
    {
        $this->authenticate();

        $res = $this->request(
            $this->baseUrl . '/api/verification',
            ['order_id' => $shurjopayOrderId],
            true,
            $this->token
        );

        // Verification returns a list; take the first element.
        $body = $res['body'] ?? [];
        $record = (isset($body[0]) && is_array($body[0])) ? $body[0] : $body;
        $spCode = (string) ($record['sp_code'] ?? '');

        return $spCode === '1000' ? $record : null;
    }
}
