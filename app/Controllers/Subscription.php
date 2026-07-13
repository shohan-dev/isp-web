<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Registration;
use Zapi\Modules\Shared\Rewards\Services\WebRenewRewardHelper;

class Subscription extends BaseController
{

    protected $payment_model, $user_model;

    public function __construct()
    {
        /**
         * Payment Model
         */
        $this->payment_model = model('App\Models\Payment');

        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
    }

    public function index($userId = null)
    {
        $id = $userId ?? getSession('user_id');

        if (empty($id)) {
            return redirect()->to('auth/login');
        }

        $packageModel = model('App\Models\Package');
        $userDetails = $this->user_model->find($id);

        if (!$userDetails) {
            return show_404();
        }

        $data = [
            'title' => 'My Subscription',
            'details' => $userDetails,
            'isPublic' => !empty($userId) && !getSession('user_id'),
        ];

        $admin_id = $userDetails->admin_id ?? '--';
        $admin_details = $this->user_model->find($admin_id);
        $data['admin_details'] = $admin_details;
        $created_by = $userDetails->created_by ?? '--';

        // Determine which packages to show based on the target user's role
        $targetRole = $userDetails->role;

        if ($targetRole === 'admin') {
            $package_model = model('App\Models\AdminPackage');
            // Public fixed plans + custom plans assigned to this tenant.
            // (PAYG tenants renew via the wallet, not an invoice.)
            $data['packages'] = $package_model->where(['Activity' => 'active'])
                ->groupStart()
                    ->groupStart()
                        ->groupStart()
                            ->where('plan_type', \App\Models\AdminPackage::TYPE_FIXED)
                            ->orWhere('plan_type IS NULL', null, false)
                            ->orWhere('plan_type', '')
                        ->groupEnd()
                        ->groupStart()
                            ->where('is_public', 1)
                            ->orWhere('is_public IS NULL', null, false)
                        ->groupEnd()
                    ->groupEnd()
                    ->orGroupStart()
                        ->where('plan_type', \App\Models\AdminPackage::TYPE_CUSTOM)
                        ->where('assigned_user_id', (int) $id)
                    ->groupEnd()
                    ->orWhere('id', (int) ($userDetails->package_id ?? 0))
                ->groupEnd()
                ->findAll();
        } elseif ($targetRole === 'user') {
            // Customers or Guests
            if ($created_by === 'resellerAdmin') {
                $packageModel = model('App\Models\allResellerPackage');
                $rawPackages = $packageModel->where('user_id', $admin_id)->findAll();

                $packages = [];
                foreach ($rawPackages as $package) {
                    $package['package_details'] = json_decode($package['package_details'] ?? '[]', true);
                    if (is_array($package['package_details'])) {
                        foreach ($package['package_details'] as $details) {
                            $packages[] = $details;
                        }
                    }
                }
                $data['packages'] = $packages;

                // Pass reseller validity periods if postpaid
                if ($admin_details && ($admin_details->billing_type ?? 'postpaid') === 'postpaid') {
                    $data['reseller_validity_periods'] = !empty($admin_details->reseller_validity_periods)
                        ? explode(',', $admin_details->reseller_validity_periods)
                        : ['3', '5', '7', '30'];
                }
            } else {
                $package_model = model('App\Models\Package');
                $data['packages'] = $package_model->where(['user_id' => $admin_id, 'status' => 'active'])->findAll();
            }
        } else {
            $data['packages'] = $packageModel->where(['status' => 'active'])->findAll();
        }

        // Reward points preview for customer self-service renew (web portal).
        if (($userDetails->role ?? '') === 'user' && (int) getSession('user_id') === (int) $id) {
            $pkgId = (int) ($userDetails->package_id ?? 0);
            $data['reward_redeem'] = WebRenewRewardHelper::preview((int) $id, $pkgId);
        } else {
            $data['reward_redeem'] = ['enabled' => false];
        }



        return view('subscription/details', $data);
    }


    public function reseller_index($id = null)
    {
        $data = [
            'title' => 'My Subscription',
            'details' => $this->user_model->find($id),
        ];

        return view('reseller/subscription', $data);
    }

    public function resellers_renew()
    {
        $this->validate([
            'customer' => ['rules' => 'required'],
        ]);

        if ($this->validation->run()) {
            $id = getPostInput('customer');
            $user = getUserById($id);

            if ($user->role === 'user') {
                $router_client = routerClient($user->router_id);

                if (!is_array($router_client)) {

                    $pppoe = getPPPoEUserUserId($router_client, $user->id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                    log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

                    $result = enablePPPoEUser($router_client, $pppoe_id);

                    if (!$result) {
                        log_message('error', "Failed to enable PPPoE user for User ID {$user->id}");

                        $router_model = model('App\Models\UserRouterDataModel');
                        $data = $router_model->where('user_id', $user->id)->first();

                        $pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;
                        $res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret);
                        if ($res) {
                            log_message('info', "Successfully enabled PPPoE user for User ID {$user->id}");
                            // $user_model->update($user->id, ['activity' => 'active']);
                        }
                    }
                }
            }

            $data = [
                'subscription_status' => 'active',
                'last_renewed' => date("Y-m-d H:i:s"),
                'will_expire' => calcUserSubsRenewDate($id),
            ];

            $result = $this->user_model->update($id, $data);

            if ($result) {

                return redirect()->back()->with('pay-success', 'Payment successful');
            }
        }
    }

    public function renew()
    {
        $this->validate([
            'customer' => ['rules' => 'required'],
            'package_id' => ['rules' => 'required'],
        ]);
        $packageId = $this->request->getPost('package_id');
        log_message('info', 'Package ID: ' . $packageId);



        if ($this->validation->run()) {
            $id = getPostInput('customer');
            // $user = getUserById($id);
            $package = getUserPackage($id);


            $userModel = model('App\Models\User');
            $customer_details = $userModel->where(['id' => $id])->first();
            $admin_id = $customer_details->admin_id ?? $customer_details['admin_id'];
            $package_id = $customer_details->package_id ?? $customer_details['package_id'];
            $role = $customer_details->created_by ?? $customer_details['created_by'];
            $admin_details = $userModel->where(['id' => $admin_id])->first();
            // Access will_expire safely
            if (is_object($customer_details)) {
                $will_expire = $customer_details->will_expire ?? null;
            } elseif (is_array($customer_details)) {
                $will_expire = $customer_details['will_expire'] ?? null;
            } else {
                $will_expire = null;
            }

            if ($package_id != $packageId) {
                log_message('info', 'Package ID: is here ' . $packageId);
                $data = [
                    'package_id' => $packageId,
                    'pre_package' => $package_id,
                ];


                // $userModel->update($id, $data);

                $now = time();

                $will_expire = strtotime($will_expire);

                if (is_numeric($will_expire) && is_numeric($now)) {
                    // log_message('info', 'will_expire date: ' . date('Y-m-d H:i:s', $will_expire));
                    // log_message('info', 'now date: ' . date('Y-m-d H:i:s', $now));

                    if ($will_expire > $now) {
                        $difference = ceil(($will_expire - $now) / (60 * 60 * 24));
                        log_message('info', 'Fetched difference data: ' . json_encode($difference));
                    }
                }
            }
            if ($role === 'resellerAdmin') {

                $fund = $admin_details->fund ?? $admin_details['fund'] ?? 0;
                $billingType = $admin_details->billing_type ?? 'postpaid';
                $fundEnabled = isset($admin_details->fund_enabled) ? (bool)$admin_details->fund_enabled : true;

                if (!$fundEnabled) {
                    return requestResponse("error", "Your Reseller's funding is disabled. Please contact admin.", 500);
                }

                $price = ResellerPackagePrice($package_id, null, $admin_id, 'resellerAdmin');
                log_message('info', 'Fetched tprice ResellerPackagePrice($package_id): ' . json_encode($price));

                if ($billingType === 'prepaid' && $fund < $price) {
                    return requestResponse("error", "Your Reseller dosen't have enough fund . Please contact with him.", 500);
                }
            }



            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $id])->first();
            // $admin_id = $details->admin_id;
            $admin_id = null;

            if (is_object($details)) {
                $admin_id = $details->admin_id ?? '--';
            } elseif (is_array($details)) {
                $admin_id = $details['admin_id'] ?? '--';
            }

            $userRole = getSession('user_role');

            if ($role === 'resellerAdmin') {
                log_message('info', 'Reseller here 2 retrieved: ');

                $userModel = model('App\Models\Registration');
                $Rdetails = $userModel->where(['userid' => $admin_id])->first();

                if ($Rdetails) {
                    log_message('info', 'Reseller here 3 retrieved: ');

                    if (is_object($package)) {
                        $discount = $Rdetails->discount ?? '--';
                        $preprice = $package->price ?? '--';
                        $preprice = ResellerPackagePrice($package_id, null, $admin_id, 'resellerAdmin');
                        log_message('info', 'Reseller discount1 retrieved: ' . $discount);
                        log_message('info', 'Reseller preprice1 retrieved: ' . $preprice);


                        $discount = isset($Rdetails->discount) ? intval($Rdetails->discount) : 0;
                        $preprice = isset($preprice) ? intval($preprice) : 0;


                        if ($preprice >= 0 && $discount >= 0) {
                            // Calculate price after applying the percentage discount
                            // $price = $preprice - ($preprice * ($discount / 100));
                            $price = $preprice;
                        } else {
                            // Set price to default if input values are not valid
                            $price = '--';
                        }
                    } elseif (is_array($package)) {
                        // Now you can use the converted values
                        $discount = isset($Rdetails['discount']) ? intval($Rdetails['discount']) : 0;
                        $preprice = isset($package['selling_price']) ? intval($package['selling_price']) : 0;

                        log_message('info', 'Reseller discount2 retrieved: ' . $discount);
                        log_message('info', 'Reseller preprice2 retrieved: ' . $preprice);
                        $preprice = ResellerPackagePrice($package_id, null, $admin_id, 'resellerAdmin');
                        // Ensure both discount and preprice are non-negative integers
                        if ($preprice >= 0 && $discount >= 0) {
                            // Calculate the price after applying the percentage discount
                            // $price = $preprice - ($preprice * ($discount / 100));
                            $price = $preprice;
                        } else {
                            // Set price to default if input values are not valid
                            $price = '--';
                        }
                    }
                    log_message('info', 'Reseller preprice retrieved: ' . $preprice);
                    log_message('info', 'Reseller price retrieved: ' . $price);
                }
            } else {
                if (is_object($package)) {
                    $price = $package->price ?? '--';
                } elseif (is_array($package)) {
                    $price = $package['price'] ?? '--';
                }
            }

            $currentMonth = date('F');

            // Find an existing unpaid payment for this user and month
            $existingPayment = $this->payment_model
                ->where(['user_id' => $id, 'month' => $currentMonth])
                ->where('status !=', 'successful')
                ->first();

            // Check if already paid for the current month
            $anyPaidPayment = $this->payment_model
                ->where(['user_id' => $id, 'month' => $currentMonth, 'status' => 'successful'])
                ->first();

            if ($anyPaidPayment && $package_id === $packageId) {
                return requestResponse('error', "A successful payment for the current month has already been received.", 400);
            }

            // Extract selected duration
            $duration = $this->request->getPost('duration') ? (int) $this->request->getPost('duration') : 30;

            // For reseller admin customers, validate duration if reseller is postpaid
            if ($role === 'resellerAdmin') {
                $billingType = $admin_details->billing_type ?? 'postpaid';
                if ($billingType === 'postpaid') {
                    $allowedStr = !empty($admin_details->reseller_validity_periods) ? $admin_details->reseller_validity_periods : '3,5,7,30';
                    $allowed = array_map('trim', explode(',', $allowedStr));
                    if (!in_array((string)$duration, $allowed)) {
                        return requestResponse('error', 'You can only increase as these days: ' . implode(', ', $allowed), 400);
                    }
                }
            }

            // Adjust price based on duration (pro-rate by daily price)
            if ($price !== null && $price !== '--') {
                $price = round(((float)$price / 30) * $duration);
            }

            if ($package_id != $packageId) {
                if ($role === 'resellerAdmin') {
                    $new_monthly_price = ResellerPackagePrice($packageId, null, $admin_id, 'resellerAdmin');
                } else {
                    $new_pkg = getUserPackage($id, $packageId);
                    $new_monthly_price = $new_pkg->price ?? 0;
                }

                $new_monthly_price = is_numeric($new_monthly_price) ? (float) $new_monthly_price : 0;

                $price = ((int) ($difference ?? 0)) > 0
                    ? round((new \App\Services\BillingService())->quote($new_monthly_price, (int) $difference), 2)
                    : round((float) $new_monthly_price, 2);
            }

            $loggedInRole = getSession('user_role');
            if ($role === 'resellerAdmin' && ($loggedInRole === 'resellerAdmin' || $loggedInRole === 'employee')) {
                $reseller = $userModel->find($admin_id);
                if (!$reseller) {
                    return requestResponse("error", "Reseller details not found.", 404);
                }

                $fund = (float) ($reseller->fund ?? 0);
                $billingType = $reseller->billing_type ?? 'postpaid';
                $fundEnabled = isset($reseller->fund_enabled) ? (bool) $reseller->fund_enabled : true;

                if (!$fundEnabled) {
                    return requestResponse("error", "Your Reseller's funding is disabled. Please contact admin.", 500);
                }

                if ($billingType === 'prepaid' && $fund < $price) {
                    return requestResponse("error", "You do not have enough fund. Please recharge.", 500);
                }

                if ($billingType === 'prepaid') {
                    if (! (new \App\Services\FundService())->deduct((int) $reseller->id, (float) $price)) {
                        return requestResponse("error", "You do not have enough fund. Please recharge.", 500);
                    }
                }

                $data = [
                    'user_id' => $id,
                    'admin_id' => $admin_id,
                    'paidby' => getSession('user_id'),
                    'user_type' => 'user',
                    'invoice' => 'INV-' . random_int(100000, 999999),
                    'amount' => $price,
                    'month' => $currentMonth,
                    'paid_via' => 'Fund',
                    'paid_to' => getSession('user_id'),
                    'paid_at' => date('Y-m-d H:i:s'),
                    'status' => 'successful',
                    'created_at' => date('Y-m-d H:i:s'),
                    'custom_data' => json_encode(['duration' => $duration]),
                ];

                if ($existingPayment) {
                    $this->payment_model->update($existingPayment->id, $data);
                } else {
                    $this->payment_model->insert($data);
                }

                $newExpiry = calcUserSubsRenewDate($id, $duration);
                $userModel->update($id, [
                    'package_id' => $packageId,
                    'pre_package' => ($package_id != $packageId) ? $package_id : ($customer_details->pre_package ?? null),
                    'subscription_status' => 'active',
                    'last_renewed' => date('Y-m-d H:i:s'),
                    'will_expire' => $newExpiry,
                    'conn_status' => 'conn',
                ]);

                $router_client = routerClient($customer_details->router_id);
                if ($router_client instanceof \RouterOS\Client) {
                    $pppoe = getPPPoEUserUserId($router_client, $id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $customer_details->pppoe_id ?? null;
                    $result = enablePPPoEUser($router_client, $pppoe_id);
                    if (!$result) {
                        $router_model = model('App\Models\UserRouterDataModel');
                        $routerData = $router_model->where('user_id', $id)->first();
                        $pppoe_secret = is_array($routerData) ? $routerData['pppoe_secret'] : $routerData->pppoe_secret;
                        enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret, $pppoe_id);
                    }
                }

                $transationModel = model('App\Models\ResellerTransactions');
                $transationModel->insert([
                    'customer' => $id,
                    'admin_id' => $reseller->id,
                    'amount' => $price,
                    'package_price' => ResellerPackagePrice($packageId),
                    'active_for' => $duration,
                    'comments' => 'Customer Recharge via Fund',
                ]);

                try {
                    $smsUser = getUserById($id);
                    $smsMerged = [
                        'user_id' => $id,
                        'admin_id' => $admin_id,
                        'amount' => $price,
                        'month' => $currentMonth,
                        'will_expire' => $newExpiry,
                        'name' => $smsUser ? ($smsUser->name ?? '--') : '--',
                        'mobile' => $smsUser ? ($smsUser->mobile ?? '--') : '--',
                        'email' => $smsUser ? ($smsUser->email ?? '--') : '--',
                        'package_id' => $smsUser ? ($smsUser->package_id ?? '--') : '--',
                    ];
                    sendEventSms('payment_done', $smsMerged, (int) $admin_id, 13);
                } catch (\Exception $e) {
                    log_message('error', 'SMS Sending Failed: ' . $e->getMessage());
                }

                return requestResponse('success', [
                    'msg' => 'Customer subscription recharged and extended successfully.',
                    'payment_url' => route_to('route.customer'),
                ], 200);
            }

            $customPayload = ['duration' => $duration];
            if ($package_id != $packageId) {
                $userModel->update($id, ['pending_package_id' => (int) $packageId]);
                $customPayload['target_package_id'] = (int) $packageId;
                $customPayload['change_type'] = 'package_change';
            }

            $data = [
                'user_id' => $id,
                'admin_id' => $admin_id,
                'paidby' => getSession('user_id') ?? $id, // Self-paid if guest
                'user_type' => 'user',
                'invoice' => 'INV-' . random_int(100000, 999999),
                'amount' => $price,
                'month' => $currentMonth,
                'created_at' => date('Y-m-d H:i:s'),
                'custom_data' => json_encode($customPayload),
            ];

            log_message('info', 'Reseller details retrieved: ' . json_encode($data));

            if ($existingPayment) {
                // If package changed or something, update existing. 
                // If same package, this essentially refreshes the timestamp/data but returns the same record ID.
                $result = $this->payment_model->update($existingPayment->id, $data);
                $paymentId = $existingPayment->id;
            } else {
                $result = $this->payment_model->insert($data);
                $paymentId = $this->payment_model->getInsertID();
            }

            if ($result) {
                $payment_url = route_to('route.payment.pay', $paymentId);

                // Apply reward-point discount hold (web portal renew).
                $redeemPoints = (int) ($this->request->getPost('redeem_points') ?? 0);
                $rewardNote = '';
                if ($redeemPoints > 0 && is_numeric($price)) {
                    $redeem = WebRenewRewardHelper::apply((int) $id, (int) $paymentId, (float) $price, $redeemPoints);
                    if ($redeem['points'] > 0) {
                        $this->payment_model->update($paymentId, ['amount' => $redeem['payable']]);
                        $rewardNote = ' Reward discount: ' . $redeem['points'] . ' points (BDT '
                            . number_format($redeem['discount'], 2) . ' off).';
                    }
                }

                $msg = ($existingPayment && $package_id === $packageId
                    ? 'A payment invoice for this month was already generated. You can proceed to pay now.'
                    : ($package_id != $packageId
                        ? 'Your current package stays active until payment completes. An invoice has been created for the new package — pay now or anytime from My Payment.'
                        : 'Payment invoice is generated. You can make payment anytime from `My Payment` option. Or if you want to pay now then click on `Pay` button below'))
                    . $rewardNote;

                // Success response
                return requestResponse('success', [
                    'msg' => $msg,
                    'payment_url' => $payment_url,
                ], 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    public function reseller_renew()
    {

        // Validate input
        $this->validate([
            'Customer' => ['rules' => 'required'],
        ]);

        if ($this->validation->run()) {


            $id = getPostInput('Customer');
            $amount = (int) getPostInput('amount');
            log_message('info', 'Amount: ' . $amount);


            // Fetch user and package information
            $user = getUserById($id);
            $package = getAdminPackage($id);

            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $id])->first();

            $Registration = new Registration();
            $rdetails = $Registration->where(['userid' => $id])->first();
            // $admin_id = $details->admin_id;
            $admin_id = null;

            if (is_object($details)) {
                $admin_id = $details->admin_id ?? '--';
            } elseif (is_array($details)) {
                $admin_id = $details['admin_id'] ?? '--';
            }


            if (!empty($amount) && is_numeric($amount) && $amount > 0) {
            } else {

                if (!$user || !$package) {
                    log_message('error', 'User or package not found.');
                    return requestResponse('error', 'User or package not found', 404);
                }


                $currentMonth = date('F');

                // Check if a payment already exists for this user in the current month
                $existingPayment = $this->payment_model
                    ->where(['user_id' => $id, 'paidby' => session()->get('user_id'), 'month' => $currentMonth])->first();

                $package_details = $package['price'];
                // Prepare data for insertion

                if (!empty($rdetails->discount) && is_numeric($rdetails->discount)) {
                    $package_details = $package_details - $rdetails->discount;
                } elseif (!empty($rdetails['discount']) && is_numeric($rdetails['discount'])) {
                    $package_details = $package_details - $rdetails['discount'];
                }
            }


            if (!empty($amount) && is_numeric($amount) && $amount > 0) {
                $data = [
                    'user_id' => $id,
                    'admin_id' => $admin_id,
                    'paidby' => session()->get('user_id'),
                    'user_type' => 'resellerAdmin',
                    'invoice' => 'INV-' . random_int(100000, 999999),
                    'amount' => $amount,
                    'month' => date('F'),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            } else {
                $data = [
                    'user_id' => $id,
                    'admin_id' => $admin_id,
                    'paidby' => session()->get('user_id'),
                    'user_type' => 'user',
                    'invoice' => 'INV-' . random_int(100000, 999999),
                    'amount' => $package_details,
                    'month' => date('F'),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }

            log_message('info', 'Reseller details retrieved: ' . json_encode($data));
            
            // Insert or Update payment data
            if (!empty($existingPayment)) {
                $result = $this->payment_model->update($existingPayment->id, $data);
                $paymentId = $existingPayment->id;
                $msg = 'A payment invoice for this month was already generated. You can proceed to pay now.';
            } else {
                $result = $this->payment_model->insert($data);
                $paymentId = $this->payment_model->getInsertID();
                $msg = 'Payment invoice is generated. You can make payment anytime from `My Payment` option. Or if you want to pay now then click on `Pay` button below';
            }

            if ($result) {
                log_message('info', 'Payment data processed successfully.');

                $payment_url = route_to('route.payment.pay', $paymentId);

                // Return success response
                return requestResponse('success', [
                    'msg' => $msg,
                    'payment_url' => $payment_url,
                ], 200);
            }

            log_message('error', 'Failed to insert/update payment data.');
            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        log_message('error', 'Validation failed: ' . json_encode($this->validation->getErrors()));
        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    public function callback()
    {
        if (empty(getSession('pid')))
            show_404();

        $payment = $this->payment_model
            ->where([
                'id' => getSession('pid'),

                'status' => 'successful'
            ])
            ->first();

        if (!empty($payment)) {

            $user = getUserById($payment->user_id);

            if (empty($user))
                show_404();

            /**
             * Reset session
             */
            setSession([
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);

            /**
             * Wallet top-ups: credit the tenant wallet (idempotent — the
             * gateway callback may have credited already) and show the wallet.
             * They must never extend will_expire.
             */
            if (paymentPurpose($payment) === 'wallet_topup') {
                applyWalletTopup($payment);
                session()->remove('pid');

                return redirect()->route('route.wallet')->with('pay-success', 'Top-up successful! The balance has been added to your wallet.');
            }

            /**
             * Mikrotik Router Client
             */


            if ($user->role === 'user') {
                $router_client = routerClient($user->router_id);

                if (!is_array($router_client)) {

                    $pppoe = getPPPoEUserUserId($router_client, $user->id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                    log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

                    $result = enablePPPoEUser($router_client, $pppoe_id);

                    if (!$result) {
                        log_message('error', "Failed to enable PPPoE user for User ID {$user->id}");

                        $router_model = model('App\Models\UserRouterDataModel');
                        $data = $router_model->where('user_id', $user->id)->first();

                        $pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;
                        $res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret);
                        if ($res) {
                            log_message('info', "Successfully enabled PPPoE user for User ID {$user->id}");
                            // $user_model->update($user->id, ['activity' => 'active']);
                        }
                    }
                } else {

                    $pppoe = getPPPoEUserUserId($router_client, $user->id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                    log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");


                    $result = enablePPPoEUserFsock($user->router_id, $pppoe_id);

                    if (!$result) {
                        $router_model = model('App\Models\UserRouterDataModel');
                        $data = $router_model->where('user_id', $user->id)->first();

                        $pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;

                        // ".id":"*8000D6B6","name":"sb744rony",
                        $fp = connect_using_Fsocket($user->router_id);
                        $ppp_id = getPPPoEIdFsock($fp, $pppoe_secret);

                        log_message('info', 'Retrying enablePPPoEUserFsock for user ID ' . $user->id . ' using PPPoE ID ' . $ppp_id);
                        $result = enablePPPoEUserFsock($user->router_id, $ppp_id);

                        log_message('info', 'Successfully enabled PPPoE user via fsock for user ID ' . $user->id);
                    } else {
                        log_message('info', 'No valid router client found for user ID ' . $user->id . '. Skipping enablePPPoEUser call.');

                    }
                }
            }

            if ($user->role != 'resellerAdmin') {
                $duration = null;
                if (!empty($payment->custom_data)) {
                    $customDecoded = json_decode($payment->custom_data, true);
                    if (isset($customDecoded['duration'])) {
                        $duration = (int) $customDecoded['duration'];
                    }
                }
                helper('subscription');
                $data = buildSubscriptionRenewUserData((int) $payment->user_id, $payment, $duration);
            } else {
                $data = [
                    'last_renewed' => date("Y-m-d H:i:s"),

                ];
            }
            // $this->user_model->update($payment->user_id, ['conn_status' => 'conn']);
            log_message('info', 'Fetched payment data: on callback' . json_encode($data));
            $result = $this->user_model->update($payment->user_id, $data);

            if ($result) {

                session()->remove('pid');
                if ($user->role === 'resellerAdmin') {

                    $model = model('App\Models\ResellerFundingModel');

                    $data = [
                        'customer' => $user->id,                // or actual customer id
                        'admin_id' => $user->id ?? 1,  // adjust as needed
                        'amount' => $payment->amount,
                        'received_amount' => $payment->amount,         // same if full payment
                        'paid_via' => $payment->paid_via ?? 'BKash', // adjust dynamically
                        'invoice_number' => $payment->method_trx ?? '',
                        'received_date' => date('Y-m-d'),
                        'comments' => 'Payment received via myself',
                        'status' => 'successful',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    $model->insert($data);

                    return redirect()->route('route.Admin.subscription', [$user->id]);
                }


                return redirect()->route('route.subscription');
            }


            return redirect()->route('route.payment')->with('pay-error', 'Something went wrong! Please try again');
        }

        show_404();
    }
}
