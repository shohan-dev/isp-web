<?php

namespace App\Libraries;

/**
 * Bkash Payment Gateway
 *
 * @author Pranay Chakraborty
 * @link https://github.com.com/pranaycb
 */

class BkashPhp
{
    /**
     * Bkash config
     * @var array
     */
    private array $bkash_config;

    /**
     * Bkash request base url
     * @var string
     */
    private string $base_url;


    public function __construct()
    {
        /**
         * Load cookie helper
         */
        helper('cookie');
    }


    /**
     * Set configuration
     * @param array $config
     * @return void
     */
    public function setConfig(array $config)
    {

        if (array_key_exists('environment', $config)) {

            if ($config['environment'] === 'sandbox') {
                $this->base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout';
            } elseif ($config['environment'] === 'production') {
                $this->base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';
            } else {
                throw new \Exception("Environment " . $config['environment'] . " is not allowed. Allowed environments are: sandbox, production");
            }
        } else {
            throw new \Exception("'enviroment' parameter is required");
        }

        unset($config['environment']);

        $this->bkash_config = $config;
    }

    /**
     * Grant token
     * @return string
     */
    private function _getToken()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->base_url . "/token/grant",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'app_key' => $this->bkash_config['app_key'],
                'app_secret' => $this->bkash_config['app_secret'],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'password: ' . $this->bkash_config['password'],
                'username: ' . $this->bkash_config['username'],
            ],
        ]);
    
        $response = json_decode(curl_exec($curl));
    
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        // Log response and errors
        // log_message('info', 'bKash API Response: ' . print_r($response, true));
        // log_message('info', 'HTTP Response Code: ' . $responseCode);
    
        // log_message('info', 'Username: ' . $this->bkash_config['username']);
        // log_message('info', 'Password: ' . $this->bkash_config['password']);

        if ($err) {
            throw new \Exception($err);
        }
    
        if ($responseCode === 200 && $response->statusCode != '0000') {
            throw new \Exception($response->statusMessage);
        }
    
        set_cookie('id_token', $response->id_token, 0);
    
        return $response->id_token;
    }
    

    /**
     * Create payment
     * @param array $paymentData
     * @return object
     */
    public function createPayment(array $paymentData)
    {
        $token = $this->_getToken();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->base_url . "/create",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($paymentData),
            CURLOPT_HTTPHEADER => [
                "authorization:" . $token,
                "x-app-key:" . $this->bkash_config['app_key'],
                "content-type: application/json"
            ],
        ]);

        $response = json_decode(curl_exec($curl));

        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        }

        if ($responseCode === 200 && $response->statusCode != '0000') {

            throw new \Exception($response->statusMessage);
        }

        return $response;
    }


    /**
     * Execute payment
     * @param string $paymentId
     * @return object
     */
    public function executePayment(string $paymentId)
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->base_url . "/execute",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(['paymentID' => $paymentId]),
            CURLOPT_HTTPHEADER => [
                "authorization:" . get_cookie('id_token'),
                "x-app-key:" . $this->bkash_config['app_key'],
                "content-type: application/json"
            ],
        ]);

        $response = json_decode(curl_exec($curl));

        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        }

        if ($responseCode === 200 && $response->statusCode != '0000') {
            throw new \Exception($response->statusMessage);
        }

        return $response;
    }


    /**
     * Query payment
     * @param array $trxId
     * @return object
     */
    public function queryPayment(string $trxId)
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->base_url . "/general/searchTransaction",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(['trxID' => $trxId]),
            CURLOPT_HTTPHEADER => [
                "authorization:" . get_cookie('id_token'),
                "x-app-key:" . $this->bkash_config['app_key'],
                "content-type: application/json"
            ],
        ]);

        $response = json_decode(curl_exec($curl));

        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        }

        if ($responseCode === 200 && $response->statusCode != '0000') {
            throw new \Exception($response->statusMessage);
        }

        return $response;
    }
}
