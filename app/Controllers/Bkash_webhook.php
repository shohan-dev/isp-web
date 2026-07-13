<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Bkash_webhook extends Controller
{
    public function Test()
    {
        // Phase-G5: gate before touching any payload data.
        //
        // Layer 1 — URL token: set BKASH_WEBHOOK_TOKEN in .env and append
        //   ?token=<value> to the webhook URL you register with bKash.
        //   hash_equals() prevents timing attacks.
        $expectedToken = (string) env('BKASH_WEBHOOK_TOKEN', '');
        if ($expectedToken !== '') {
            $provided = (string) ($this->request->getGet('token') ?? '');
            if (! hash_equals($expectedToken, $provided)) {
                log_message('warning', 'bKash IPN rejected: bad token from ' . $this->request->getIPAddress());
                return $this->response->setStatusCode(403)->setBody('Forbidden');
            }
        }

        // Set timezone
        date_default_timezone_set('Asia/Dhaka');

        // Get raw POST data (payload)
        $payload = file_get_contents('php://input');

        // Layer 2 — SNS origin check: verify SigningCertURL is from *.amazonaws.com
        // before doing any DB work. Zero cost — no HTTP call required.
        if (trim($payload) !== '') {
            $decoded = json_decode($payload, true);
            if (is_array($decoded) && isset($decoded['SigningCertURL'])) {
                $host = (string) parse_url($decoded['SigningCertURL'], PHP_URL_HOST);
                if (! preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/', $host)) {
                    log_message('warning', 'bKash IPN rejected: SigningCertURL host=' . $host . ' not AWS SNS');
                    return $this->response->setStatusCode(400)->setBody('Invalid origin');
                }
            }
        }
        // $payload = '{    
        //     "Type": "Notification",
        //     "MessageId": "b6bbd049-9e34-5e7f-b55d-b13201081ac4",
        //     "TopicArn": "arn:aws:sns:ap-southeast-1:797962984373:bpt_01713488998_SANDBOX",
        //     "Message": "{\"dateTime\":\"20251029045004\",\"debitMSISDN\":\"8801700000001\",\"creditOrganizationName\":\"Org 01\",\"creditShortCode\":\"ORG001\",\"trxID\":\"4J420ANOXC\",\"transactionStatus\":\"Completed\",\"transactionType\":\"1003\",\"amount\":\"10000\",\"currency\":\"BDT\",\"transactionReference\":\"806\"}",
        //     "Timestamp": "2025-10-29T04:50:04.511Z",
        //     "SignatureVersion": "1",
        //     "Signature": "nvfmSLrc+wTVovfCTp/BFuufb8Pv1qQmMMTex0wDT1+Pwq84vT4LSlfrsPSijdGz4jjyA5vGVb9XLG/9ptGptNaxQxch1XKcSaYqOS90GZu+G7S73RS14sAee2naA44IyynExSMVdVcPIm+czJ5f8wRcRPmMXUSzw/NwfagNa3o4zn+hYX1ldYyTOLKGEIzVQ+pi05Y6YOFdQpeNE9xlbeJfZAHbBLzCpG7IxRb9ZerqNBkq5Kyij4QYyNmloTV4msBltN80YXnKGHtRfnSwQp85U4EevFSmXctA1ZIkzVZQI6TCbzY/CckTCFIoIYOk7Pgy5wqsevN55LnXBND4Sw==",
        //     "SigningCertURL": "https://sns.ap-southeast-1.amazonaws.com/SimpleNotificationService-6209c161c6221fdf56ec1eb5c821d112.pem",
        //     "UnsubscribeURL": "https://sns.ap-southeast-1.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:ap-southeast-1:797962984373:bpt_01713488998_SANDBOX:d469253a-5e91-416e-8ba3-305ef7b0a76e"
        //     }';



        // Optional: Get all request headers
        // $headers = getallheaders();

        // Log folder inside writable/logs
        $logDir = WRITEPATH . 'logs/bkash_ipn/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Generate unique log filename
        $filename = $logDir . 'IPN-' . date('Y-m-d_H-i-s') . '.json';

        // Save payload
        file_put_contents($filename, $payload . PHP_EOL, FILE_APPEND);

        $this->Save_to_db($payload);

        // Optional: Add separator line
        file_put_contents($filename, "----------------------------------------" . PHP_EOL, FILE_APPEND);

        // Optional: Save headers
        // file_put_contents($filename, json_encode($headers, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

        // Log message for debugging
        log_message('info', 'bKash Webhook received and logged: ' . $filename);

        // Return a simple response to acknowledge receipt
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Webhook data logged successfully'
        ]);
    }


    private function Save_to_db($payload)
    {
        log_message('info', 'Saving bKash Webhook payload to database.');
        log_message('info', 'Raw Payload: ' . $payload);

        // Decode the outer SNS message
        $data = json_decode($payload, true);

        if (!$data || empty($data['Message'])) {
            log_message('error', 'Invalid webhook payload format.');
            return;
        }


        // Decode inner Message JSON
        $message = json_decode($data['Message'], true);
        if (!$message) {
            log_message('error', 'Invalid inner message JSON.');
            return;
        }

        // Extract fields
        $trxID = $message['trxID'] ?? null;
        $amount = $message['amount'] ?? null;
        $currency = $message['currency'] ?? null;
        $status = $message['transactionStatus'] ?? null;
        $reference = trim($message['transactionReference'] ?? '');
        $debitMSISDN = $message['debitMSISDN'] ?? null;
        $dateTime = $message['dateTime'] ?? null;
        $creditOrg = $message['creditOrganizationName'] ?? null;
        $shortCode = $message['creditShortCode'] ?? null;
        $trxType = $message['transactionType'] ?? null;


        // Try to detect user_id from reference (e.g., "  45  ", "USER45", etc.)
        $user_id = null;
        if (is_numeric($reference)) {
            $user_id = (int) $reference;
        } else {
            // Try to extract number from mixed reference like "USER45" or "INV-45"
            if (preg_match('/\d+/', $reference, $matches)) {
                $user_id = (int) $matches[0];
            } else {
                $user_id = 0; // fallback if nothing valid
            }
        }

        $details = getUserById($user_id);
        log_message('info', 'Fetched user details for ID ' . $user_id . ': ' . print_r($details, true));
        if (empty($details)) {
            log_message('error', 'User not found for ID: ' . $user_id);



            $user_data = getUserByNumber($shortCode);
            log_message('info', "user_data: " . print_r($user_data, true));
            if (!empty($user_data)) {
                $userid = $user_data['user_id'];
            } else {
                $userid = 0;
            }

            $saveData = [
                'user_id' => $user_id,
                'admin_id' => $userid,
                'paidby' => $user_id,
                'user_type' => 'user',
                'invoice' => 'INV-' . random_int(100000, 999999),
                'amount' => $amount,
                'pay_amount' => $amount,
                'month' => date('F'),
                'created_at' => date('Y-m-d'),
                'paid_at' => date('Y-m-d'),
                'paid_to' => $user_id,
                'paid_via' => 'Bkash',
                'method_trx' => $trxID,
                'comment' => $debitMSISDN,
                'status' => 'failed',
            ];
            $payment_model = model('App\Models\Payment');
            $payment_model->insert($saveData);

            log_message('info', 'Webhook data saved successfully to database but no user details found.');

            return;
        }

        $role = $details->created_by;
        $admin_id = $details->admin_id;

        //check package price

        $package = getUserPackage($details->id);
        log_message('info', 'User Package Details: ' . print_r($package, true));

        $selling_price = is_array($package)
            ? ($package['selling_price'] ?? null)
            : ($package->selling_price ?? null);

        $base_price = is_array($package)
            ? ($package['price'] ?? null)
            : ($package->price ?? null);

        // Determine final price based on role and value validity
        if ($role === 'resellerAdmin' && !empty($selling_price) && is_numeric($selling_price)) {
            $price = $selling_price;
        } else {
            $price = $base_price;
        }

        log_message('info', 'Determined package price: ' . $price);

        // BUG-02 fix: fail CLOSED when price is missing/invalid; cast amount to float
        // so '500' (string from JSON) never compares lexicographically against '--'.
        if ($price === null || $price === '' || !is_numeric($price)) {
            log_message('error', "Bkash webhook: null/invalid package price for user {$user_id}, marking failed.");
            $status = 'failed';
        } else {
            $amount = (float) $amount;
            $price  = (float) $price;
            $status = ($amount >= $price) ? 'successful' : 'failed';
        }

        $payment_model = model('App\Models\Payment');

        // Check if this transaction ID was already processed successfully
        if (!empty($trxID)) {
            $alreadyProcessed = $payment_model->where(['method_trx' => $trxID, 'status' => 'successful'])->first();
            if ($alreadyProcessed) {
                log_message('info', "Webhook: Transaction {$trxID} was already successfully processed. Skipping duplicate.");
                return;
            }
        }

        $month = date('F');
        log_message('info', 'Fetched month data: ' . json_encode($month));
        // Check if payment already exists for user and month
        $existing = $payment_model->where([
            'user_id' => $user_id,
            'month' => $month
        ])->first();



        $saveData = [
            'user_id' => $user_id,
            'admin_id' => $admin_id,
            'paidby' => $user_id,
            'user_type' => 'user',
            'invoice' => 'INV-' . random_int(100000, 999999),
            'amount' => $amount,
            'pay_amount' => $selling_price ?? $amount,
            'month' => date('F'),

            'paid_at' => date('Y-m-d'),
            'paid_to' => $admin_id,
            'paid_via' => 'Bkash',
            'method_trx' => $trxID,
            'status' => $status,
        ];




        //update will expire
        $will_expire = $details->will_expire;

        if ($status === 'successful') {
            if (!empty($will_expire) && strtotime($will_expire) > time()) {
                // Extend from existing expiry date
                $will_expire = date('Y-m-d H:i:s', strtotime($will_expire . ' +30 days'));
                log_message('info', "Extending existing expiry by 30 days → {$will_expire}");
            } else {
                // Expired or null → start new 30 days from now
                $will_expire = date('Y-m-d H:i:s', strtotime('+30 days'));
            }

            $this->provisionAfterPayment($details, $saveData);


            $user_model = model('App\Models\User');

            log_message('info', 'Updating USER ID: ' . $details->id);

            // Prepare update data
            $updateData = [
                'will_expire' => $will_expire,
                'subscription_status' => ($will_expire > date('Y-m-d H:i:s')) ? 'active' : 'inactive',
                'conn_status' => 'conn',
                'last_renewed' => date('Y-m-d H:i:s'),
            ];

            // Log the full update array
            log_message('info', 'Update Data: ' . json_encode($updateData));

            // Run update
            $updated = $user_model->update($details->id, $updateData);

            // Log update result
            if ($updated) {
                log_message('info', 'User ID ' . $details->id . ' updated successfully.');
            } else {
                log_message('error', 'Failed to update user ID ' . $details->id);
            }
        } else {
            log_message('info', 'Payment status is not successful. No further actions taken.');
            $saveData['method_trx'] = 'low amount';
            $saveData['status'] = 'failed';
        }




        if ($existing) {
            $saveData['month'] = date('F', strtotime('+1 month'));

            log_message('info', 'Updating existing payment ID ' . $existing->id . ' with data: ' . json_encode($saveData));

            $payment_model->update($existing->id, $saveData);
        } else {
            $saveData['created_at'] = date('Y-m-d');

            log_message('info', 'Prepared data for database insertion: ' . print_r($saveData, true));


            $payment_model->insert($saveData);
        }



        log_message('info', 'Webhook data saved successfully to database.');
        return;
    }


    public function get_bkash_sendmoney()
    {
        $request = service('request');

        // Try getting from POST first
        $sms = $request->getPost('sms');
        $useid = $request->getPost('user_id');

        // If empty, check if it's a JSON request
        if (!$sms) {
            $json = $request->getJSON();
            if ($json) {
                $sms = $json->sms ?? null;
                $useid = $json->user_id ?? $useid;
                log_message('info', 'Received SMS via JSON payload');
            }
        }

        $useid = $useid ?? 2;

        log_message('info', 'Received useid: ' . $useid);
        log_message('info', 'Received SMS: ' . ($sms ?? 'EMPTY'));

        if (!$sms) {
            log_message('error', 'No SMS received after checking POST and JSON');
            return $this->response->setJSON([
                'status' => false,
                'message' => 'No SMS received'
            ]);
        }

        // Extract fields from SMS
        preg_match('/Tk\s*([\d,]+\.\d{2})/i', $sms, $amountMatch);
        $amount = isset($amountMatch[1]) ? (float) str_replace(',', '', $amountMatch[1]) : 0;

        preg_match('/Ref\s*(\d+)/i', $sms, $refMatch);
        $user_id = isset($refMatch[1]) ? (int) $refMatch[1] : 0;

        preg_match('/TrxID\s*([A-Z0-9]+)/i', $sms, $trxMatch);
        $trxid = $trxMatch[1] ?? null;

        preg_match('/from\s*([\d]+)/i', $sms, $fromMatch);
        $sender = $fromMatch[1] ?? null;

        preg_match('/Balance\s*Tk\s*([\d,]+\.\d{2})/i', $sms, $balanceMatch);
        $balance = isset($balanceMatch[1]) ? (float) str_replace(',', '', $balanceMatch[1]) : null;

        preg_match('/at\s*([\d\/:\s]+)/i', $sms, $dateMatch);
        $datetime = $dateMatch[1] ?? null;

        log_message('info', "Extracted - Amount: $amount, UserID: $user_id, TrxID: $trxid, Sender: $sender, Balance: $balance, DateTime: $datetime");

        // Fetch user details
        $details = getUserById($user_id);
        $payment_model = model('App\Models\Payment');

        if (empty($details)) {
            log_message('error', 'User not found for ID: ' . $user_id);


            $saveData = [
                'user_id' => 2,
                'admin_id' => $useid,
                'paidby' => $user_id,
                'user_type' => 'user',
                'invoice' => 'INV-' . random_int(100000, 999999),
                'amount' => $amount,
                'pay_amount' => $amount,
                'month' => date('F'),
                'created_at' => date('Y-m-d'),
                'paid_at' => date('Y-m-d'),
                'paid_to' => $useid,
                'paid_via' => 'Bkash Send Money',
                'method_trx' => $trxid,
                'comment' => $sender,
                'status' => 'failed',
            ];
            $payment_model->insert($saveData);
            log_message('info', 'Payment saved for unknown user.');
            return $this->response->setJSON(['status' => false, 'message' => 'User not found']);
        }

        // Determine package price
        $package = getUserPackage($details->id);
        $selling_price = is_array($package) ? ($package['selling_price'] ?? null) : ($package->selling_price ?? null);
        $base_price = is_array($package) ? ($package['price'] ?? null) : ($package->price ?? null);

        $price = ($details->created_by === 'resellerAdmin' && !empty($selling_price)) ? $selling_price : $base_price;
        log_message('info', "Determined package price for user {$user_id}: $price");

        // Determine payment status
        $status = ($amount >= $price) ? 'successful' : 'failed';

        // Idempotency: a bKash TrxID is globally unique. If this TrxID was already
        // recorded as a successful payment, do NOT process it again — otherwise an SMS
        // redelivery re-runs provisioning and extends will_expire a second time
        // (double-credit). The TrxID is stored in the `invoice` column for this flow.
        if (!empty($trxid)) {
            $dupe = $payment_model->where(['invoice' => $trxid, 'status' => 'successful'])->first();
            if ($dupe) {
                log_message('info', "Bkash SMS: TrxID {$trxid} already processed (payment #{$dupe->id}); skipping duplicate.");
                return $this->response->setJSON(['status' => true, 'message' => 'Payment already recorded']);
            }
        }

        // Check for existing payment for this month
        $month = date('F');
        $existing = $payment_model->where(['user_id' => $user_id, 'month' => $month])->first();

        // Prepare payment data
        $saveData = [
            'user_id' => $user_id,
            /* Was $useid, which is read verbatim from this webhook's own
               (unauthenticated) POST/JSON body and falls back to the literal 2.
               That let a caller misattribute a real payment to an arbitrary
               admin_id in income reports, while the money movement below and the
               paid_to field on the next line both correctly use the resolved
               owner, so the record and the funds disagreed. $details is the
               customer row for $user_id, so $details->admin_id is their real
               owning admin. */
            'admin_id' => $details->admin_id,
            'paidby' => $user_id,
            'user_type' => 'user',
            'invoice' => $trxid,
            'amount' => $amount,
            'pay_amount' => $price ?: 0,
            'month' => $month,
            'paid_at' => date('Y-m-d'),
            'paid_to' => $details->admin_id,
            'paid_via' => 'Bkash Send Money',
            'method_trx' => 'successful',
            'status' => $status,
        ];

        log_message('info', 'saveData for payment SENDMONEY: ID ' . print_r($saveData, true));
        // Compute renewal + run provisioning BEFORE the DB transaction. Router I/O is
        // external and must not hold a transaction open; it also flags $saveData on a
        // failed enable, which must be captured in the payment row written below.
        if ($status === 'successful') {
            $will_expire = $details->will_expire;
            $will_expire = (!empty($will_expire) && strtotime($will_expire) > time())
                ? date('Y-m-d H:i:s', strtotime($will_expire . ' +30 days'))
                : date('Y-m-d H:i:s', strtotime('+30 days'));

            $this->provisionAfterPayment($details, $saveData);

            // Mirror gateway: deduct reseller fund once per TrxID (idempotent ledger ref).
            if (!empty($trxid) && is_numeric($price) && (float) $price > 0) {
                $adminUser = getUserById($details->admin_id);
                if ($adminUser && ($adminUser->role ?? '') === 'resellerAdmin') {
                    (new \App\Services\FundService())->deduct(
                        (int) $details->admin_id,
                        (float) $price,
                        'bkashsms:' . $trxid,
                        'Customer bKash SMS payment fund deduction'
                    );
                }
            }
        } else {
            log_message('info', 'Payment failed due to insufficient amount.');
            $saveData['method_trx'] = 'low amount';
        }

        // Persist the user renewal + payment row atomically, so a mid-flow failure can
        // never leave the subscription extended without a matching payment record.
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            if ($status === 'successful') {
                $updateData = [
                    'will_expire' => $will_expire,
                    'subscription_status' => 'active',
                    'conn_status' => 'conn',
                    'last_renewed' => date('Y-m-d H:i:s'),
                ];
                model('App\Models\User')->update($details->id, $updateData);
                log_message('info', 'User subscription updated for ID ' . $details->id);
            }

            // Insert or update payment
            if ($existing) {
                if ($existing->status == 'successful') {
                    $saveData['month'] = date('F', strtotime('+1 month'));
                }
                $payment_model->update($existing->id, $saveData);
                log_message('info', 'Existing payment updated: ID ' . $existing->id);
            } else {
                $saveData['created_at'] = date('Y-m-d');
                $payment_model->insert($saveData);
                log_message('info', 'New payment inserted for user ID ' . $user_id);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                log_message('error', 'Bkash SMS: money persist failed (transStatus false) for user ' . $user_id);
                return $this->response->setJSON(['status' => false, 'message' => 'Payment processing failed']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Bkash SMS: money persist exception for user ' . $user_id . ': ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Payment processing failed']);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Payment processed successfully']);
    }


    /**
     * Enable the customer's PPPoE connection after a successful payment.
     *
     * Flag `queue.webhookRouterEnabled` (default OFF): when ON, the slow MikroTik
     * I/O is offloaded to the `php spark queue:work` worker (persist->ACK->enqueue)
     * — the webhook returns fast and the worker enables the line with retry/backoff
     * behind the router circuit breaker; the payment is already recorded successful
     * on money receipt. When OFF (default) the original synchronous enable below runs
     * inline, so behaviour is byte-identical until you enable + staging-test it.
     *
     * $saveData is passed by reference so the sync path can flag a failed enable
     * exactly as the original inline code did.
     */
    private function provisionAfterPayment($details, array &$saveData): void
    {
        if (($details->conn_status ?? null) === 'conn') {
            return; // already connected — nothing to do
        }

        if (env('queue.webhookRouterEnabled', false)) {
            helper('queue');
            enqueue('router_enable', [
                'details_id' => $details->id,
                'user_id'    => $details->id,
                'router_id'  => $details->router_id,
                'pppoe_id'   => $details->pppoe_id ?? null,
            ]);
            log_message('info', "Webhook: queued router_enable for user {$details->id} (async provisioning)");

            return;
        }

        // ---- synchronous enable (flag OFF) — original inline logic, verbatim ----
                $router_client = routerClient($details->router_id);
                // $router_client = routerClient(0);


                if (!empty($router_client) && !is_array($router_client)) {
                    log_message('info', 'Calling enablePPPoEUser for user ID ' . $details->id);
                    $pppoe = getPPPoEUserUserId($router_client, $details->id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $details->pppoe_id ?? null;

                    log_message('info', "PPPoE ID for User ID {$details->id}: {$pppoe_id}");

                    $result = enablePPPoEUser($router_client, $pppoe_id);

                    if (!$result) {
                        log_message('error', "Failed to enable PPPoE details for User ID {$details->id}");

                        $pppoe_secret = $this->lookupPppoeSecret((int) $details->id);
                        $res = $pppoe_secret ? enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret) : false;
                        if ($res) {
                            log_message('info', "Successfully enabled PPPoE details for User ID {$details->id}");
                            // $user_model->update($user->id, ['activity' => 'active']);
                        }
                    }
                } else {

                    log_message('info', 'Calling enablePPPoEUserFsock for user ID ' . $details->id);
                    $pppoe = getPPPoEUserUserId($router_client, $details->id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $details->pppoe_id ?? null;

                    log_message('info', "PPPoE ID for User ID {$details->id}: {$pppoe_id}");


                    $result = enablePPPoEUserFsock($details->router_id, $pppoe_id);

                    log_message('info', 'enablePPPoEUserFsock result for user ID ' . $details->id . ': ' . ($result ? 'success' : 'failure'));

                    // BUG-07 fix: initialise before the retry block so the success
                    // branch (where the block is skipped) never reads an undefined var.
                    $results = null;
                    if (!$result) {
                        $pppoe_secret = $this->lookupPppoeSecret((int) $details->id);

                        $fp = connect_using_Fsocket($details->router_id);
                        $ppp_id = getPPPoEIdFsock($fp, $pppoe_secret);

                        log_message('info', 'Retrying enablePPPoEUserFsock for user ID ' . $details->id . ' using PPPoE ID ' . $ppp_id);
                        $results = enablePPPoEUserFsock($details->router_id, $ppp_id);

                        log_message('info', 'Successfully enabled PPPoE user via fsock for user ID ' . $details->id);
                    }
                    if (!$result && !$results) {
                        log_message('info', 'No valid router client found for user ID ' . $details->id . '. Skipping enablePPPoEUser call.');

                        $saveData['method_trx'] = 'ROUTER NOT CONNECTED';
                        $saveData['status'] = 'failed';
                    }
                }
    }

    /**
     * BUG-21: pppoe_secret extraction was duplicated in both the direct-API and
     * fsock fallback branches. Single source of truth.
     */
    private function lookupPppoeSecret(int $userId): ?string
    {
        $data = model('App\Models\UserRouterDataModel')->where('user_id', $userId)->first();
        if (!$data) return null;
        return is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null);
    }
}
