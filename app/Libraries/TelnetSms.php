<?php

namespace App\Libraries;

/**
 * Telnet SMS Gateway library
 * Handles both Username/Password and API Token authentication
 */

class TelnetSms
{
    protected $telnet_config;

    public function __construct($adminId = null)
    {
        /**
         * Gateway Config File
         * Assuming you have a config file that maps settings to these properties
         */
        $this->telnet_config = new \Config\SmsGateway\TelnetConfig($adminId);
    }

    public function sendMessage($to, $message)
    {
        // FIX 1: Ensure number starts with 88
        if (substr($to, 0, 2) !== '88') {
            $to = '88' . $to;
        }

        if (!empty($this->telnet_config->api_token)) {
            $url = "https://api.sms.telnet.com.bd/api/send-sms";
            $payload = [
                "msisdn"       => $to,
                "cli"          => $this->telnet_config->cli,
                "message"      => $message,
                "api_token"    => $this->telnet_config->api_token,
                "scheduleTime" => ""
            ];
        } else {
            $url = "https://api.sms.telnet.com.bd/api/sendRequest";
            $payload = [
                "msisdn"       => $to,
                "cli"          => $this->telnet_config->cli,
                "message"      => $message,
                "username"     => $this->telnet_config->username,
                "password"     => $this->telnet_config->password,
                "scheduleTime" => ""
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        log_message('debug', 'Telnet Raw Response: ' . $response);

        $return = [
            'gateway'       => 'Telnet',
            'status'        => 'failed',
            'logs'          => 'Failed to send',
            'sender_number' => $this->telnet_config->cli ?? '',
            'message_id'    => '--',
        ];

        if ($result && isset($result['status_code']) && $result['status_code'] == 200) {
            $return['status'] = 'success';
            $return['logs']   = $result['smsinfo'][0]['status_message'] ?? 'Successfully stored in message queue';
            $return['message_id'] = $result['smsinfo'][0]['messageid'] ?? $result['smsinfo'][0]['message_id'] ?? '--';
        } else {
            log_message('error', 'Telnet SMS Error: ' . $response);
            if (isset($result['error_message']) && $result['error_message'] !== false) {
                $return['logs'] = $result['error_message'];
            } else {
                $return['logs'] = 'Gateway Error (Status Code: ' . ($result['status_code'] ?? 'Unknown') . ')';
            }
        }

        return $return;
    }

    /**
     * Alias for sendMessage to maintain compatibility with your controller
     */
    public function sendotrMessage($to, $message)
    {
        return $this->sendMessage($to, $message);
    }

    public function checkBalance()
    {
        // The endpoint used in your successful screenshot
        $url = "https://api.sms.telnet.com.bd/api/check-balance";

        // Prepare the payload for the JSON body
        if (!empty($this->telnet_config->api_token)) {
            $url = "https://api.sms.telnet.com.bd/api/check-current-balance";

            $data = ["api_token" => $this->telnet_config->api_token];
        } else {
            $data = [
                "username" => $this->telnet_config->username,
                "password" => $this->telnet_config->password
            ];
        }

        $payload = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); // Must be POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // Data in the Body
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Crucial: Inform the server you are sending JSON
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $return_arr = ['gateway' => 'Telnet'];

        if ($error) {
            $return_arr['status'] = 'error';
            $return_arr['message'] = "Connection Error: " . $error;
            return $return_arr;
        }

        $result = json_decode($response);
        log_message('debug', 'Telnet Balance Response: ' . $response);

        // Based on your success screen: status is "SUCCESS" and balance is a number
        if (isset($result->status) && $result->status === "SUCCESS") {
            $return_arr['status'] = 'success';
            $return_arr['balance'] = ($result->balance ?? '0.00') . ' BDT';
        } else {
            $return_arr['status'] = 'error';
            $return_arr['message'] = $result->error_message ?? 'Invalid Request Payload';
        }

        return $return_arr;
    }
}
