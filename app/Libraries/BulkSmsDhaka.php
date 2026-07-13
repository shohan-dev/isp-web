<?php
namespace App\Libraries;

class BulkSmsDhaka
{
    protected $config;

    public function __construct($adminId = null)
    {
        $this->config = new \Config\SmsGateway\BulkSmsDhakaConfig($adminId);
    }

    public function sendMessage($to, $message)
    {
        $params = [
            "apikey"   => $this->config->api_key,
            "callerID" => $this->config->senderid,
            "number"   => $to,
            "message"  => $message,
            "type"     => "text"
        ];

        // Based on working Postman request, parameters should be in the URL (Query Strings)
        $url = "https://bulksmsdhaka.net/api/sendtext?" . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Explicitly set null body since parameters are in the URL
        curl_setopt($ch, CURLOPT_POSTFIELDS, ""); 
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        $return = [
            'gateway'       => 'BulkSmsDhaka', 
            'status'        => 'failed', 
            'logs'          => 'Failed to send',
            'sender_number' => $this->config->senderid,
            'message_id'    => '--'
        ];

        if ($result && is_array($result)) {
            $success = $result['Success'] ?? $result['success'] ?? false;
            $status = $result['Status'] ?? $result['status'] ?? '0';
            $message_res = $result['Message'] ?? $result['message'] ?? 'Failed to send';
            $message_id = $result['message_id'] ?? $result['MessageID'] ?? '--';

            if ($success == "true" || $success === true || $status == "1000") {
                $return['status'] = 'success';
                $return['logs'] = $message_res;
                $return['message_id'] = $message_id;
            } else {
                log_message('error', 'BulkSmsDhaka Gateway Error: ' . $response . ' [URL: ' . $url . ']');
                $return['logs'] = $message_res;
                $return['message_id'] = $message_id;
            }
        } else {
            log_message('error', 'BulkSmsDhaka Invalid Response: ' . $response . ' [URL: ' . $url . ']');
            $return['logs'] = $response ?: 'Empty response';
        }

        return $return;
    }

    public function checkBalance()
    {
        $url = "https://bulksmsdhaka.net/api/getBalance?apikey=" . $this->config->api_key;
        $response = file_get_contents($url);
        $result = json_decode($response, true);

        $return = ['gateway' => 'BulkSmsDhaka'];
        if (isset($result['Balance'])) {
            $return['status'] = 'success';
            $return['balance'] = $result['Balance'] . ' BDT';
        }
        else {
            $return['status'] = 'error';
            $return['message'] = 'Could not fetch balance';
        }
        return $return;
    }
}
