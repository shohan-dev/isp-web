<?php

namespace App\Controllers\Gateway;

use App\Controllers\BaseController;

class SSLCommerz extends BaseController
{
	protected $payment_model;

	public function __construct()
	{
		$this->payment_model = model('App\Models\Payment');
	}

	/**
	 * Build a server-signed, time-limited token used to re-establish the user
	 * session on the gateway return trip (consumed by App\Filters\AuthCheck).
	 * Format: "{exp}:{hmac_sha256(user_id|role|pid|exp)}". Returns just the
	 * timestamp when no signing key is configured, so it can never authenticate.
	 */
	private function buildSessionBridgeToken($userId, string $role, string $pid): string
	{
		$exp    = time() + 1800; // 30 minutes
		$secret = (string) env('security.sessionBridgeKey', '');
		if ($secret === '') {
			return (string) $exp; // no signature => AuthCheck will reject
		}
		$sig = hash_hmac('sha256', $userId . '|' . $role . '|' . $pid . '|' . $exp, $secret);

		return $exp . ':' . $sig;
	}

	/**
	 * get payment url
	 */
	public function getSSLCommerzPaymentUrl()
	{

		$this->validate(['payment_id' => ['rules' => 'required']]);

		if ($this->validation->run()) {

			$id = getPostInput('payment_id');

			// No user_type filter: tenant sAdmin renewals and wallet top-ups
			// ride this gateway too (parity with Bkash).
			$payment = $this->payment_model->where(['id' => $id, 'status' => 'pending'])->first();

			if (!empty($payment)) {

				// Merchant credentials context (platform creds for tenant-pays-platform).
				$userIdContext = paymentGatewayContext($payment);
				// The customer/payer identity stays the payment's user.
				$user = getUserById($payment->user_id);
				$payerRole = $user->role ?? 'user';

				$data = [
					'store_id' => getSetting('sslcommerz_store_id', '', $userIdContext),
					'store_passwd' => getSetting('sslcommerz_store_passwd', '', $userIdContext),
					'total_amount' => ($payment->amount + ($payment->amount * (getSetting('sslcommerz_charge', 0, $userIdContext) / 100))),
					'currency' => "BDT",
					'tran_id' => secure_random_string(10),
					'success_url' => url_to('route.payment.gateway.sslcommerz.query', $payment->id),
					'fail_url' => url_to('route.payment.gateway.sslcommerz.query', $payment->id),
					'cancel_url' => url_to('route.payment.gateway.sslcommerz.query', $payment->id),
					'emi_option' => "1",
					'cus_name' => $user->name ?? 'Customer',
					'cus_email' => $user->email ?? 'customer@example.com',
					'cus_add1' => "Dhaka",
					'cus_city' => "Dhaka",
					'cus_postcode' => "0000",
					'cus_country' => "Bangladesh",
					'cus_phone' => $user->mobile ?? '',
					'shipping_method' => 'NO',
					'product_name' => 'ISP',
					'product_category' => 'ISP',
					'product_profile' => 'non-physical-goods',

					// Server-issued, HMAC-signed, 30-min session-bridge token so the
					// gateway return can safely re-establish the session in AuthCheck
					// without trusting any attacker-suppliable value. Fails closed if
					// security.sessionBridgeKey is unset (set it in the server .env).
					'value_a' => $this->buildSessionBridgeToken($payment->user_id, $payerRole, (string) get_cookie('pid')),
					'value_b' => $payment->user_id,
					'value_c' => $payerRole,
					'value_d' => get_cookie('pid'),
				];

				$baseUrl = (getSetting('sslcommerz_sandbox_mode', 'no', $userIdContext) === 'yes') 
					? "https://sandbox.sslcommerz.com/gwprocess/v4/api.php" 
					: "https://securepay.sslcommerz.com/gwprocess/v4/api.php";

				$handle = curl_init();
				curl_setopt($handle, CURLOPT_URL, $baseUrl);
				curl_setopt($handle, CURLOPT_TIMEOUT, 30);
				curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($handle, CURLOPT_POST, 1);
				curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
				// Verify the gateway's certificate on the payment-session call too.
				curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);

				$content = curl_exec($handle);

				$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

				curl_close($handle);

				if ($code == 200 && !(curl_errno($handle))) {

					$sslcommerzResponse = $content;
				} else {

					return requestResponse('error', "FAILED TO CONNECT WITH SSLCOMMERZ API", 500);
				}

				# PARSE THE JSON RESPONSE
				$sslcz = json_decode($sslcommerzResponse, true);

				if (isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] != "") {

					return requestResponse('success', $sslcz['GatewayPageURL'], 200);
				} else {

					return requestResponse('error', "JSON Data parsing error!", 500);
				}
			} else {

				show_404();
			}
		} else {

			//validation error
			return requestResponse('validation-error', $this->validation->getErrors(), 400);
		}
	}

	/**
	 * query payment
	 */
	public function queryPayment($id)
	{

		$this->validate(['tran_id' => ['rules' => 'required']]);

		if ($this->validation->run()) {

			$payment = $this->payment_model->where(['id' => $id])->first();
			if (empty($payment)) {
				show_404();
			}
			$userIdContext = paymentGatewayContext($payment);

			$tran_id = urlencode(getPostInput('tran_id'));

			$store_id = urlencode(getSetting('sslcommerz_store_id', '', $userIdContext));

			$store_passwd = urlencode(getSetting('sslcommerz_store_passwd', '', $userIdContext));

			$queryUrl = (getSetting('sslcommerz_sandbox_mode', 'no', $userIdContext) === 'yes') 
				? "https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php" 
				: "https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php";

			$requested_url = ($queryUrl . "?tran_id=" . $tran_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json");

			$handle = curl_init();

			curl_setopt($handle, CURLOPT_URL, $requested_url);
			curl_setopt($handle, CURLOPT_TIMEOUT, 30);
			curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
			/* TLS verification was disabled on the transaction-VALIDATION call —
			   the request whose "VALID" answer decides whether a wallet credit /
			   renewal is applied. With verification off, anyone able to intercept
			   the server's outbound connection could forge that answer and get a
			   real credit for a payment that never cleared. Verify properly. */
			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);

			$result = curl_exec($handle);

			$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			$errno = curl_errno($handle);

			curl_close($handle);

			if ($code == 200 && !$errno) {
				$result = json_decode($result, true);

				if (isset($result['APIConnect']) && $result['APIConnect'] == 'DONE') {

					if (($result['element'][0]['status'] ?? null) === 'VALID') {


						$payment = $this->payment_model->where(['id' => $id])->first();

						// BUG-16: TOCTOU guard — payment row could be gone between IPN validation
						// and this re-fetch (e.g., concurrent IPN or manual deletion).
						if ($payment === null) {
							log_message('error', 'SSLCommerz IPN: payment not found after re-fetch, id=' . $id);
							return $this->response->setStatusCode(200)->setBody('INVALID_PAYMENT');
						}

						$user_model = model('App\Models\User');
						$customer_details = $user_model->where(['id' => $payment->user_id])->first();

						$data = [
							'paid_via' => 'SSLCommerz',
							'method_trx' => $tran_id,
							'paid_at' => date("Y-m-d"),
							'status' => 'successful',
						];

						// Wallet top-ups only credit the tenant wallet — no
						// will_expire / subscription changes here.
						if (paymentPurpose($payment) === 'wallet_topup') {
							$this->payment_model->update($payment->id, $data);
							applyWalletTopup($this->payment_model->find($payment->id));

							setSession('pid', $id);
							return redirect()->to(route_to('route.subscription.callback'));
						}

						if ($payment->user_type === 'resellerAdmin') {
							(new \App\Services\FundService())->add(
								(int) $payment->user_id,
								(float) $payment->amount,
								'payment:' . (int) $payment->id,
								'SSLCommerz reseller fund top-up'
							);
							$customer_details = $user_model->where(['id' => $payment->user_id])->first();
						}


						if ($payment->user_type != 'resellerAdmin') {
							$duration = null;
							if (!empty($payment->custom_data)) {
								$decoded = json_decode($payment->custom_data, true);
								if (isset($decoded['duration'])) {
									$duration = (int) $decoded['duration'];
								}
							}
							helper('subscription');
							$udata = buildSubscriptionRenewUserData((int) $payment->user_id, $payment, $duration);
						} else {
							$udata = [];
						}
						if (getUserById($payment->user_id)->role == 'user') {
							$user = getUserById($payment->user_id);

							$router_client = routerClient($user->router_id);

							if (!is_array($router_client)) {

								$pppoe = getPPPoEUserUserId($router_client, $user->id);
								$pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

								log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

								$result = enablePPPoEUser($router_client, $pppoe_id);

								if (! $result) {
									log_message('error', "Failed to enable PPPoE user for User ID {$user->id}");

									$router_model = model('App\Models\UserRouterDataModel');
									// NB: keep this off $data — it still holds the payment
									// update fields written below.
									$routerData = $router_model->where('user_id', $user->id)->first();

									$pppoe_secret = $routerData ? (is_array($routerData) ? ($routerData['pppoe_secret'] ?? null) : ($routerData->pppoe_secret ?? null)) : null;
									$res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret);
									if ($res) {
										log_message('info', "Successfully enabled PPPoE user for User ID {$user->id}");
										// $user_model->update($user->id, ['activity' => 'active']);
									}
								}

								// $user_model->update($payment->user_id, ['conn_status' => 'conn']);

							}
						}

						// $this->user_model->update($payment->user_id, ['conn_status' => 'conn']);
						// log_message('info', 'Fetched payment data: on callback' . json_encode($data));
						if (!empty($udata)) {
							$result = $user_model->update($payment->user_id, $udata);
						}
						// log_message('info', 'Fetched payment data: on bcash' . json_encode($data));

						// Prevent Subscription::callback from extending will_expire again.
						if (isset($udata['will_expire'])) {
							markRenewApplied($payment->id);
						}

						$result = $this->payment_model->update($payment->id, $data);




						$updatedPayment = $this->payment_model->find($payment->id);

						$userId = $updatedPayment->user_id ?? $updatedPayment['user_id'];
						$admin_id = $updatedPayment->admin_id ?? $updatedPayment['admin_id'];


						$admin_details = $user_model->where(['id' => $admin_id])->first();
						$package_id = $customer_details->package_id ?? $customer_details['package_id'];
						$role = $admin_details->role ?? $admin_details['role'];

						if ($role === 'resellerAdmin') {

							$fund = $admin_details->fund ?? $admin_details['fund'] ?? 0;

							$price = ResellerPackagePrice($package_id, null, $admin_id, 'resellerAdmin');
							// log_message('info', 'Fetched tprice ResellerPackagePrice($package_id): ' . json_encode($price));

							// if ($fund < $price) {
							//     return requestResponse("error", "Dont have enough fund . Please recharge.", 500);
							// }


							// Block overdraw: atomic, race-safe deduction that the DB refuses to
							// take below zero (replaces the unguarded read-modify-write). If the
							// reseller is short the balance is left unchanged and logged for
							// reconciliation. (Refusing the renewal outright requires moving this
							// gate ahead of the customer renewal above — follow-up reorder.)
							if (! (new \App\Services\FundService())->deduct((int) $admin_id, (float) $price)) {
								log_message('error', "Reseller {$admin_id} insufficient fund ({$fund}) for price {$price} on SSLCommerz payment {$payment->id}; overdraw blocked, charge not deducted.");
							}
							$transationdata = [
								'customer' => $userId,
								'admin_id' => $admin_id,
								'amount' => $price,
								'package_price' => $price,
								'active_for' => '--',
								'comments' => 'Single Customer payment renewal, paid by customer.',
							];
							// log_message('info', 'Fetched transationdata data: ' . json_encode($transationdata));
							$transationModel = model('App\Models\ResellerTransactions');
							$result = $transationModel->insert($transationdata);


							// return requestResponse('success', "New customer record added successfully", 200);


						}

						if ($result) {

							setSession('pid', $id);

							return redirect()->to(route_to('route.subscription.callback'));
						} else {
							if (!getSession('user_id')) {
								return redirect()->to(route_to('route.subscription'))->with('pay-error', 'Could not update your payment record! Please contact the administrator');
							}
							return redirect()->to(route_to('route.payment'))->with('pay-error', 'Could not update your payment record! Please contact the administrator');
						}
					} else if (($result['element'][0]['status'] ?? null) === 'FAILED') {
						if (!getSession('user_id')) {
							return redirect()->to(route_to('route.subscription'))->with('pay-error', 'Payment is failed');
						}
						return redirect()->to(route_to('route.payment'))->with('pay-error', 'Payment is failed');
					} else if (($result['element'][0]['status'] ?? null) === 'CANCELLED') {
						if (!getSession('user_id')) {
							return redirect()->to(route_to('route.subscription'))->with('pay-error', 'You have cancelled the payment');
						}
						return redirect()->to(route_to('route.payment'))->with('pay-error', 'You have cancelled the payment');
					}
				}
			}
			if (!getSession('user_id')) {
				return redirect()->to(route_to('route.subscription'))->with('pay-error', "Failed to connect with SSLCOMMERZ");
			}
			return redirect()->to(route_to('route.payment'))->with('pay-error', "Failed to connect with SSLCOMMERZ");
		} else {
			if (!getSession('user_id')) {
				return redirect()->to(route_to('route.subscription'))->with('pay-error', "Failed to validate the transaction. Transaction Id not found");
			}
			return redirect()->to(route_to('route.payment'))->with('pay-error', "Failed to validate the transaction. Transaction Id not found");
		}
	}
}
