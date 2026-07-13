<?php

namespace App\Libraries;

/**
 * BulkSmsBd sms sending library
 * @author Pranay Chakraborty
 * @link https://github.com/pranaycb
 */

class BulkSmsBd
{
	protected $bulksmsbd_config;

	public function __construct($adminId = null)
	{
		/**
		 * Gateway Config File
		 */
		$this->bulksmsbd_config = new \Config\SmsGateway\BulkSmsBdConfig($adminId);
	}

	public function sendMessage($to, $message)
	{
		if (!function_exists('curl_init')) {
			log_message('error', 'CURL extension is not installed/enabled on this server');
			return ['status' => 'error', 'logs' => 'CURL not available'];
		}
		$data = [
			"api_key" => $this->bulksmsbd_config->api_key,
			"senderid" => $this->bulksmsbd_config->senderid,
			"number" => $to,
			"message" => $message
			// "message" 	=> urlencode($message)
		];

		log_message('info', 'Updated bulksms message body: ' . print_r($data, true));
		if (!function_exists('curl_init')) {
			log_message('error', 'CURL extension is not enabled in this PHP server.');
			return ['status' => 'failed', 'logs' => 'CURL extension is not enabled in your PHP server. Please enable php_curl in php.ini'];
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_URL, "https://bulksmsbd.net/api/smsapi");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($response, true);

		$return = [
			'gateway'       => 'BulkSmsBd',
			'status'        => 'failed',
			'logs'          => 'Failed to send',
			'sender_number' => $this->bulksmsbd_config->senderid ?? '',
			'message_id'    => '--',
		];

		if ($result && isset($result['response_code'])) {
			if ($result['response_code'] == 202) {
				$return['status'] = 'success';
				$return['logs']   = $result['success_message'] ?? 'SMS Submitted Successfully';
				$return['message_id'] = $result['send_message_id'] ?? '--';
			} else {
				log_message('error', 'BulkSmsBd Error: ' . $response);
				$return['logs'] = $result['error_message'] ?? 'Unknown error';
			}
		} else {
			log_message('error', 'BulkSmsBd Invalid Response: ' . $response);
			$return['logs'] = $response ?: 'Empty response';
		}

		return $return;
	}

	public function sendotrMessage($to, $message)
	{
		return $this->sendMessage($to, $message);
	}


	public function checkBalance()
	{
		if (!function_exists('curl_init')) {
			log_message('error', 'CURL extension is not installed/enabled on this server');
			return ['status' => 'error', 'logs' => 'CURL not available'];
		}
		$data = ["api_key" => $this->bulksmsbd_config->api_key];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_URL, "https://bulksmsbd.net/api/getBalanceApi");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		log_message('debug', 'result data: ' . print_r($ch, true));
		$response = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($response);

		log_message('debug', 'result data: response' . print_r($response, true));


		$return_arr = [
			'gateway' => 'BulkSmsBd',
		];

		if (empty($response->error_message)) {

			$return_arr['status'] = 'success';
			$return_arr['balance'] = ($response->balance ?? '0') . ' SMS';
		}
		else {

			$return_arr['status'] = 'error';
			$return_arr['message'] = $response->error_message ?? 'Unknown error';
		}

		return $return_arr;
	}
}
