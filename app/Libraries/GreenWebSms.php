<?php

namespace App\Libraries;

/**
 * GreenWebSms sms sending library
 * @author Pranay Chakraborty
 * @link https://github.com/pranaycb
 */

class GreenWebSms
{
	protected $greenwebsms_config;

	public function __construct($adminId = null)
	{
		/**
		 * Gateway Config File
		 */
		$this->greenwebsms_config = new \Config\SmsGateway\GreenWebSmsConfig($adminId);
	}

	public function sendMessage($to, $message)
	{

		$data = [
			'to' => $to,
			'message' => rawurlencode($message),
			'token' => $this->greenwebsms_config->token
		];

		if (!function_exists('curl_init')) {
			log_message('error', 'CURL extension is not enabled in this PHP server.');
			return ['status' => 'failed', 'logs' => 'CURL extension is not enabled in your PHP server.'];
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_URL, "https://api.greenweb.com.bd/api.php?json");
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($response, true);

		$return = [
			'gateway'       => 'GreenWeb',
			'status'        => 'failed',
			'logs'          => 'Failed to send',
			'sender_number' => $this->greenwebsms_config->token ?? '',
			'message_id'    => '--',
		];

		if ($result && isset($result[0]['status'])) {
			if ($result[0]['status'] === 'SENT') {
				$return['status'] = 'success';
				$return['logs']   = $result[0]['statusmsg'] ?? 'Message sent';
				$return['message_id'] = $result[0]['messageid'] ?? $result[0]['message_id'] ?? '--';
			} else {
				log_message('error', 'GreenWebSms Error: ' . $response);
				$return['logs'] = $result[0]['statusmsg'] ?? 'Unknown error';
			}
		} else {
			log_message('error', 'GreenWebSms Invalid Response: ' . $response);
			$return['logs'] = $response ?: 'Empty response';
		}

		return $return;
	}


	public function checkBalance()
	{
		$token = $this->greenwebsms_config->token;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_URL, 'https://api.greenweb.com.bd/g_api.php?token=' . $token . '&balance&json');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($response, true);

		$return_arr = [
			'gateway' => 'GreenWeb Sms',
		];

		if (isset($response[0]) && is_array($response[0]) && !in_array('status', $response[0])) {

			$sms_rate = $this->__rate();

			$return_arr['status'] = 'success';
			$return_arr['balance'] = ($sms_rate > 0) ? round($response[0]['response'] / $sms_rate) . ' SMS' : '0 SMS';
		}
		else {

			$return_arr['status'] = 'error';
			$return_arr['message'] = $response[0]['statusmsg'] ?? 'Invalid gateway response';
		}

		return $return_arr;
	}

	protected function __rate()
	{
		$token = $this->greenwebsms_config->token;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_URL, 'https://api.greenweb.com.bd/g_api.php?token=' . $token . '&rate&json');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($response, true);

		return $response[0]['response'];
	}
}
