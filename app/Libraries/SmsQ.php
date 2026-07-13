<?php

namespace App\Libraries;

/**
 * SmsQ sms sending library
 * @author Pranay Chakraborty
 * @link https://github.com/pranaycb
 */

class SmsQ
{
	protected $smsq_config;

	public function __construct($adminId = null)
	{
		/**
		 * Gateway Config
		 */
		$this->smsq_config = new \Config\SmsGateway\SmsQConfig($adminId);
	}

	public function sendMessage($to, $message)
	{
		$data = [
			"ApiKey" 			=> $this->smsq_config->api_key,
			"ClientId" 			=> $this->smsq_config->clientid,
			"SenderId" 			=> $this->smsq_config->senderid,
			"MobileNumbers" 	=> $to,
			"Message" 			=> $message
		];

		$headers = [
			'Content-Type: application/json',
			'Type: json',
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_URL, "https://api.smsq.global/api/v2/SendSMS");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($response, true);

		$return = [
			'gateway'       => 'SmsQ',
			'status'        => 'failed',
			'logs'          => 'Failed to send',
			'sender_number' => $this->smsq_config->senderid ?? '',
			'message_id'    => '--',
		];

		if ($result && isset($result['ErrorCode'])) {
			if ($result['ErrorCode'] === 0) {
				$return['status'] = 'success';
				$return['logs']   = $result['MessageErrorDescription'] ?? 'Message queued successfully';
				$return['message_id'] = $result['Data'][0]['MessageId'] ?? $result['MessageId'] ?? '--';
			} else {
				log_message('error', 'SmsQ Error: ' . $response);
				$return['logs'] = $result['ErrorDescription'] ?? 'Unknown error';
			}
		} else {
			log_message('error', 'SmsQ Invalid Response: ' . $response);
			$return['logs'] = $response ?: 'Empty response';
		}

		return $return;
	}


	public function checkBalance()
	{
		$api_key = $this->smsq_config->api_key;
		$clientid = $this->smsq_config->clientid;

		$headers = ['Content-Type: application/json'];

		$url = "https://api.smsq.global/api/v2/Balance?ApiKey=" . $api_key . "&ClientId=" . $clientid;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response);

		$return_arr = [
			'gateway' => 'SmsQ',
		];

		if (!is_object($response) || !isset($response->ErrorCode)) {
			$return_arr['status'] = 'error';
			$return_arr['message'] = 'Invalid response from SmsQ';
			return $return_arr;
		}

		if ($response->ErrorCode == 0) {

			$return_arr['status'] = 'success';
			$return_arr['balance'] = $response->Data[0]->Credits;
		} else {

			$return_arr['status'] = 'error';
			$return_arr['message'] = $response->ErrorDescription;
		}

		return $return_arr;
	}
}
