<?php

namespace App\Libraries;

/**
 * EPS (Easy Payment System) payment gateway client.
 *
 * Endpoint/field contract taken from the official PHP SDK
 * (github.com/EPS-PG/EPS_PHP) and cross-checked against the EPS Python SDK.
 *
 *   Auth:   POST {base}Auth/GetToken
 *   Init:   POST {base}EPSEngine/InitializeEPS
 *   Verify: GET  {base}EPSEngine/CheckMerchantTransactionStatus?merchantTransactionId=..
 *
 * Every request carries `x-hash: base64(hmac_sha512(<data>, hash_key))` where
 * <data> is the username for GetToken and the merchantTransactionId for the
 * Init / Verify calls. Authenticated calls also carry `Authorization: Bearer`.
 */
class EpsPayment
{
    private string $baseUrl;
    private string $merchantId;
    private string $storeId;
    private string $username;
    private string $password;
    private string $hashKey;

    public function setConfig(array $config): void
    {
        $sandbox = ($config['sandbox'] ?? false) === true;
        $this->baseUrl    = $sandbox
            ? 'https://sandboxpgapi.eps.com.bd/v1/'
            : 'https://pgapi.eps.com.bd/v1/';
        $this->merchantId = (string) ($config['merchant_id'] ?? '');
        $this->storeId    = (string) ($config['store_id'] ?? '');
        $this->username   = (string) ($config['username'] ?? '');
        $this->password   = (string) ($config['password'] ?? '');
        $this->hashKey    = (string) ($config['hash_key'] ?? '');
    }

    /** x-hash header value for a given signed input. */
    private function hash(string $data): string
    {
        return base64_encode(hash_hmac('sha512', $data, $this->hashKey, true));
    }

    /**
     * @param string        $method GET|POST
     * @param array|null    $body   JSON body for POST
     * @param string        $xhash  precomputed x-hash
     * @param string|null   $bearer optional bearer token
     * @return array{code:int,body:mixed,error:string}
     */
    private function request(string $method, string $url, ?array $body, string $xhash, ?string $bearer = null): array
    {
        $headers = [
            'Content-Type: application/json',
            'x-hash: ' . $xhash,
        ];
        if ($bearer !== null) {
            $headers[] = 'Authorization: Bearer ' . $bearer;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        if ($method === 'POST' && $body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

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

    /** @return string bearer token, or throws on failure. */
    public function getToken(): string
    {
        $res = $this->request(
            'POST',
            $this->baseUrl . 'Auth/GetToken',
            ['userName' => $this->username, 'password' => $this->password],
            $this->hash($this->username)
        );

        $token = $res['body']['token'] ?? null;
        if (empty($token)) {
            throw new \RuntimeException('EPS authentication failed' . (!empty($res['error']) ? ': ' . $res['error'] : ''));
        }

        return (string) $token;
    }

    /**
     * Initialize a payment and return the RedirectURL the customer is sent to.
     *
     * @param array $payment expects: order_id, merchant_txn, amount,
     *              customer_name, customer_email, customer_phone,
     *              customer_address, success_url, fail_url, cancel_url
     */
    public function createPayment(array $payment): string
    {
        $token = $this->getToken();

        $body = [
            'merchantId'            => $this->merchantId,
            'storeId'               => $this->storeId,
            'CustomerOrderId'       => (string) $payment['order_id'],
            'merchantTransactionId' => (string) $payment['merchant_txn'],
            'transactionTypeId'     => 1, // WEB
            'totalAmount'           => (float) $payment['amount'],
            'successUrl'            => (string) $payment['success_url'],
            'failUrl'               => (string) $payment['fail_url'],
            'cancelUrl'             => (string) $payment['cancel_url'],
            'customerName'          => (string) ($payment['customer_name'] ?? 'Customer'),
            'customerEmail'         => (string) ($payment['customer_email'] ?? 'customer@example.com'),
            'customerAddress'       => (string) ($payment['customer_address'] ?? 'Dhaka'),
            'customerCity'          => 'Dhaka',
            'customerState'         => 'Dhaka',
            'customerPostcode'      => '1000',
            'customerCountry'       => 'Bangladesh',
            'customerPhone'         => (string) ($payment['customer_phone'] ?? ''),
            'productName'           => 'ISP',
            'productProfile'        => 'non-physical-goods',
            'productCategory'       => 'ISP',
        ];

        $res = $this->request(
            'POST',
            $this->baseUrl . 'EPSEngine/InitializeEPS',
            $body,
            $this->hash((string) $payment['merchant_txn']),
            $token
        );

        $redirect = $res['body']['RedirectURL'] ?? null;
        if (empty($redirect)) {
            $msg = $res['body']['ErrorMessage'] ?? $res['error'] ?? 'Unknown error';
            throw new \RuntimeException('EPS initialization failed: ' . $msg);
        }

        return (string) $redirect;
    }

    /**
     * Verify a transaction server-to-server (the only trustworthy confirmation —
     * the browser-redirect Status param is unsigned and forgeable). Returns the
     * status record when EPS reports it settled, otherwise null. The caller must
     * additionally cross-check TotalAmount against the expected amount before
     * crediting.
     *
     * The authoritative settlement field is the PascalCase `Status` == "Success"
     * (case-insensitive); older demos also use lowercase keys, so both are read.
     *
     * @return array<string,mixed>|null
     */
    public function verify(string $merchantTxn): ?array
    {
        $token = $this->getToken();

        $res = $this->request(
            'GET',
            $this->baseUrl . 'EPSEngine/CheckMerchantTransactionStatus?merchantTransactionId=' . urlencode($merchantTxn),
            null,
            $this->hash($merchantTxn),
            $token
        );

        $body = $res['body'] ?? [];
        if (!is_array($body)) {
            return null;
        }

        $status = $body['Status']
            ?? $body['transactionStatus']
            ?? $body['status']
            ?? ($body['data']['Status'] ?? ($body['data']['status'] ?? ''));
        $status = strtolower((string) $status);

        return in_array($status, ['success', 'completed'], true) ? $body : null;
    }
}
