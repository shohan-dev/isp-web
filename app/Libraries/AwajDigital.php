<?php

namespace App\Libraries;

class AwajDigital
{
    protected $config;
    protected $baseUrl = 'https://api.awajdigital.com/api';

    public function __construct($adminId = null)
    {
        $this->config = new \Config\SmsGateway\AwajDigitalConfig($adminId);
    }

    private function request($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->baseUrl . $endpoint;
        $token = $this->config->api_token;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        return [
            'code' => $httpCode,
            'data' => $result,
            'raw' => $response
        ];
    }

    public function checkBalance()
    {
        $response = $this->request('/balance');
        $return = ['gateway' => 'AwajDigital'];

        if ($response['code'] === 200 && isset($response['data']['success']) && $response['data']['success']) {
            $return['status'] = 'success';
            $return['balance'] = $response['data']['balance'] . ' BDT';
        } else {
            $return['status'] = 'error';
            $return['message'] = $response['data']['message'] ?? 'Could not fetch balance';
        }

        return $return;
    }

    public function getVoices()
    {
        $response = $this->request('/voices');
        return $response['data'] ?? [];
    }

    public function sendOtp($phoneNumber, $otpCode, $voice = null, $sender = null)
    {
        $data = [
            'request_id' => uniqid('otp_', true),
            'voice' => $voice ?: $this->config->default_voice,
            'sender' => $sender ?: $this->config->sender_number,
            'phone_number' => $this->formatNumber($phoneNumber),
            'otp_code' => $otpCode
        ];

        $response = $this->request('/broadcasts/otp', 'POST', $data);
        
        $return = [
            'gateway' => 'AwajDigital',
            'status' => 'failed',
            'logs' => 'Failed to send OTP'
        ];

        if ($response['code'] === 200 && isset($response['data']['success']) && $response['data']['success']) {
            $return['status'] = 'success';
            $return['logs'] = 'OTP sent successfully';
            $return['broadcast_id'] = $response['data']['broadcast']['id'] ?? null;
        } else {
            $return['logs'] = $response['data']['message'] ?? 'API Error';
        }

        return $return;
    }

    public function sendBroadcast($phoneNumbers, $voice = null, $sender = null)
    {
        if (is_string($phoneNumbers)) {
            $phoneNumbers = [$phoneNumbers];
        }

        $formattedNumbers = array_map([$this, 'formatNumber'], $phoneNumbers);

        $data = [
            'request_id'     => uniqid('bulk_', true),
            'voice'          => $voice ?: $this->config->default_voice,
            'sender'         => $sender ?: $this->config->sender_number,
            'phone_numbers'  => $formattedNumbers
        ];

        $response = $this->request('/broadcasts', 'POST', $data);

        $return = [
            'gateway' => 'AwajDigital',
            'status' => 'failed',
            'logs' => 'Failed to send broadcast'
        ];

        if ($response['code'] === 200 && isset($response['data']['success']) && $response['data']['success']) {
            $return['status'] = 'success';
            $return['logs'] = 'Broadcast initiated';
            $return['broadcast_id'] = $response['data']['broadcast']['id'] ?? null;
        } else {
            $return['logs'] = $response['data']['message'] ?? 'API Error: ' . ($response['raw'] ?? 'Unknown');
        }

        log_message('info', 'AwajDigital: sendBroadcast result: ' . json_encode($return));
        return $return;
    }

    public function sendMessage($to, $message)
    {
        // Awaj Digital is primarily Voice SMS. 
        // If we want to use it for regular text-to-speech, 
        // we might need a specific voice that supports TTS if available.
        // For now, I'll treat it as a trigger for a default voice broadcast.
        return $this->sendBroadcast($to);
    }

    private function formatNumber($number)
    {
        // Ensure 01XXXXXXXXX format as requested by API
        // Remove 88 if present
        if (strpos($number, '880') === 0) {
            return substr($number, 2);
        }
        if (strpos($number, '+880') === 0) {
            return substr($number, 3);
        }
        return $number;
    }
}
