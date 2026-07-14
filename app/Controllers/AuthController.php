<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminPackage;
use App\Models\User;
use App\Models\ContactModel;
use App\Models\AuditLogModel;
use App\Models\LandingTestimonial;
use Config\Services;
use Exception;

class AuthController extends BaseController
{
    public function __construct()
    {
        helper('text');
    }

    /**
     * Authentication
     * @action: Login View
     */
    public function index()
    {
        $brandUserId = 2;
        $tenant = null;
        $isTenantPortal = false;

        try {
            helper('tenant');
            $brandUserId = function_exists('tenantBrandingUserId') ? tenantBrandingUserId() : 2;
            $tenant = function_exists('currentTenant') ? currentTenant() : null;
            $isTenantPortal = function_exists('isTenantRequest') && isTenantRequest();
        } catch (\Throwable $e) {
            log_message('error', 'Auth login branding context: ' . $e->getMessage());
        }

        return view('auth/gate', [
            'authMode' => 'login',
            'brandUserId' => $brandUserId,
            'tenant' => $tenant,
            'isTenantPortal' => $isTenantPortal,
        ]);
    }

    public function footer()
    {
        return view('dashboard/footer');
    }

    public function pricing()
    {
        $packageModel = new AdminPackage();
        $packageModel->checkFeaturesColumn();
        $data['packages'] = $packageModel->where(['Activity' => 'active'])->findAll();

        log_message('info', 'Active packages: ' . json_encode($data['packages']));
        return view('dashboard/pricing', $data);
    }

    public function home()
    {
        helper(['utility', 'tenant']);
        if (!empty(getSession('user_id')) && !empty(getSession('user_role'))) {
            return redirect()->to(route_to('route.dashboard'));
        }

        // Tenant portals use branded login, not the platform marketing site.
        if (function_exists('isTenantRequest') && isTenantRequest()) {
            return redirect()->to(route_to('route.auth.login'));
        }

        $data['active_admins'] = 0;
        $data['active_users'] = 0;
        try {
            $user_model = new User();
            $data['active_admins'] = (int) $user_model->where(['role' => 'admin', 'status' => 'active'])->countAllResults();
            $data['active_users'] = (int) $user_model->where(['role' => 'user', 'status' => 'active'])->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'Landing stats load failed: ' . $e->getMessage());
        }

        try {
            $pricingPayload = (new \App\Models\AdminPackage())->landingPricingPayload();
            $data['lpPricing'] = [
                'tiers'  => $pricingPayload['tiers'],
                'payg'   => $pricingPayload['payg'],
                'addons' => $pricingPayload['addons'],
            ];
            $data['lpFixedPlans'] = $pricingPayload['fixedPlans'];
        } catch (\Throwable $e) {
            log_message('error', 'Landing pricing load failed: ' . $e->getMessage());
        }

        try {
            $data['lpTestimonials'] = (new LandingTestimonial())->getActiveForLanding();
        } catch (\Throwable $e) {
            log_message('error', 'Landing testimonials load failed: ' . $e->getMessage());
            $data['lpTestimonials'] = [];
        }

        $firstTestimonial = $data['lpTestimonials'][0] ?? null;
        if (is_array($firstTestimonial) && !empty($firstTestimonial['name'])) {
            $data['lpCaseStudy'] = [
                'name'            => (string) ($firstTestimonial['name'] ?? ''),
                'company'         => (string) ($firstTestimonial['company'] ?? ''),
                'metric'          => (string) ($firstTestimonial['role'] ?? ''),
                'quote'           => (string) ($firstTestimonial['quote'] ?? ''),
                'logo_url'        => '',
                'screenshot_url'  => '',
            ];
        }

        return view('dashboard/home', $data);
    }

    public function store()
    {
        // $user_model = new User();
        // $data['active_admins'] = (int) $user_model->where(['role' => 'admin', 'status' => 'active'])->countAllResults();
        // $data['active_users'] = (int) $user_model->where(['role' => 'user', 'status' => 'active'])->countAllResults();
        $recaptchaResponse = $this->request->getPost('g-recaptcha-response');
        $secretKey = env('recaptcha.secretKey', '');

        $responseKeys = null;
        try {
            $response = Services::curlrequest()->post('https://www.google.com/recaptcha/api/siteverify', [
                'connect_timeout' => 3,
                'timeout'         => 5,
                'http_errors'     => false,
                'form_params'     => [
                    'secret'   => $secretKey,
                    'response' => $recaptchaResponse,
                ],
            ]);
            $responseKeys = json_decode((string) $response->getBody(), true);
        } catch (\Throwable $e) {
            log_message('error', 'reCAPTCHA verify failed: ' . $e->getMessage());
        }

        if (!is_array($responseKeys) || empty($responseKeys['success'])) {
            $data['error'] = 'Failed to send message. Please complete your recaptcha correctly.';
            return redirect()->to(route_to('route.auth.home') . '#lp-contact')
                ->with('success', $data['success'] ?? null)
                ->with('error', $data['error'] ?? null);
        } else {



            // Instantiate the ContactModel
            $contactModel = new ContactModel();

            // Ensure the table exists
            $contactModel->createTableIfNotExists();

            // Retrieve and validate the form data
            $datas = [
                'name' => $this->request->getPost('name'),
                'phone' => $this->request->getPost('phone'),
                'email' => $this->request->getPost('email'),
                'message' => $this->request->getPost('message'),
                'inquiry_type' => $this->request->getPost('inquiryType'),
            ];

            log_message('info', 'Updated message result: ' . print_r($datas, true));

            // Save the datas to the datasbase
            if ($contactModel->insert($datas)) {
                try {
                    $message = 'A new inquiry has been submitted. Details: Name: ' . $datas['name'] .
                        ', Phone: ' . $datas['phone'] .
                        ', Email: ' . $datas['email'] .
                        ', Message: ' . $datas['message'];

                    $recipients = array_filter(array_map('trim', explode(',', (string) env(
                        'contact.notifyEmails',
                        'mdsa134867@gmail.com,parvezrahman9696@gmail.com,tohidurhasan09@gmail.com'
                    ))));
                    $result = false;
                    foreach ($recipients as $i => $recipient) {
                        $sent = sendMail($recipient, 'New Inquiry Submitted', $message);
                        if ($i === 0) {
                            $result = $sent;
                        }
                    }

                    if ($result) {
                        $data['success'] = 'Your message has been sent successfully!';
                    } else {
                        $data['error'] = 'Failed to send message. Please try again later.';
                    }
                } catch (Exception $e) {
                    log_message('info', 'Updated message result: ' . print_r($e->getMessage(), true));
                    $data['error'] = 'Something went wrong: ' . $e->getMessage();
                }


                // Redirect with a success message
                return redirect()->to(route_to('route.auth.home') . '#lp-contact')
                    ->with('success', $data['success'] ?? null)
                    ->with('error', $data['error'] ?? null);
            } else {
                // Redirect with an error message
                $data['error'] = 'Something went wrong while inserting data.';
                return redirect()->to(route_to('route.auth.home') . '#lp-contact')
                    ->with('success', $data['success'] ?? null)
                    ->with('error', $data['error'] ?? null);
            }
        }
    }



    public function registration()
    {
        $packageModel = model('App\Models\AdminPackage');
        $packages = $packageModel->where(['Activity' => 'active'])->findAll();

        return view('auth/gate', [
            'authMode' => 'register',
            'packages' => $packages,
            'isTenantPortal' => false,
        ]);
    }
    public function exhome()
    {
        $user_model = new User();
        $userId = session()->get('user_id');
        $details = $user_model->where(['id' => $userId])->first();

        $data = [
            'price' => '--',
            'isPayg' => false,
            'isCustomPending' => false,
            'walletBalance' => 0,
            'estimatedCharge' => 0,
        ];

        if ($details && !empty($details->package_id)) {
            $AdminPackage = model('App\Models\AdminPackage');
            $package = $AdminPackage->where('id', $details->package_id)->first();
            $data['price'] = $package['price'] ?? '--';

            if (($package['plan_type'] ?? '') === \App\Models\AdminPackage::TYPE_PAYG) {
                try {
                    $billing = new \App\Services\PaygBillingService();
                    $estimate = $billing->estimate((int) $details->id);
                    $data['isPayg'] = true;
                    $data['walletBalance'] = $estimate['balance'];
                    $data['estimatedCharge'] = $estimate['total'];
                } catch (\Throwable $e) {
                    log_message('error', 'exhome PAYG estimate failed: ' . $e->getMessage());
                }
            }
        } elseif ($details) {
            // No package yet = custom plan request awaiting platform approval.
            $data['isCustomPending'] = true;
        }

        return view('dashboard/exhome', $data);
    }

    // public function validateLogin()
    // {
    //     $this->validate([
    //         'email' => [
    //             'rules' => 'required',
    //             'errors' => [
    //                 'required' => 'Enter your email',
    //             ]
    //         ],
    //         'password' => [
    //             'rules' => 'required',
    //             'errors' => [
    //                 'required' => 'Enter your password',
    //             ]
    //         ],
    //     ]);

    //     if ($this->validation->run()) {

    //         $email = getPostInput('email');
    //         $password = getPostInput('password');

    //         $userModel = model('App\Models\User');

    //         $data = $userModel->where(['email' => $email])->first();

    //         if(!empty($data)) {

    //             if(password_verify($password, $data->password)) {

    //                 if($data->status === 'active') {

    //                     setSession([
    //                         'user_id' => $data->id,
    //                         'user_role' => $data->role,
    //                     ]);

    //                     return requestResponse('success', [
    //                         'msg' => 'Login successful. Redirecting to the dashboard...',
    //                         'redirect' => route_to('route.dashboard'),
    //                     ], 200);
    //                 }

    //                 return requestResponse('error', 'Your account is currently disabled', 403);
    //             }

    //             return requestResponse('validation-error', [
    //                 'password' => 'Password is wrong'
    //             ], 400);
    //         }

    //         return requestResponse('validation-error', [
    //             'email' => 'Email id is wrong'
    //         ], 400);
    //     }

    //     return requestResponse('validation-error', $this->validation->getErrors(), 400);
    // }

    public function validateLogin()
    {
        // CI_ENVIRONMENT=development → skip password verification (email must still exist).
        $skipPasswordCheck = (ENVIRONMENT === 'development');

        $this->validate([
            'email' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter your email',
                ]
            ],
            'password' => [
                'rules' => $skipPasswordCheck ? 'permit_empty' : 'required',
                'errors' => [
                    'required' => 'Enter your password',
                ]
            ],
        ]);

        if ($this->validation->run()) {

            $email = trim(getPostInput('email'));
            $password = getPostInput('password');
            $userModel = new \App\Models\User();

            log_message('info', 'Starting login validation for: ' . $email);

            // Attempt 1: Check exact email as entered
            $data = $userModel->where('email', $email)->first();

            // Attempt 2: Fallback to @gmail.com if not found
            if (empty($data)) {
                $fallbackEmail = $email . '@gmail.com';
                log_message('info', 'Email not found as-is, trying fallback: ' . $fallbackEmail);
                $data = $userModel->where('email', $fallbackEmail)->first();
                if (!empty($data)) {
                    $email = $fallbackEmail;
                }
            }

            if (!empty($data)) {
                log_message('info', 'User record found for: ' . $email);
                $isPasswordVerified = $skipPasswordCheck || password_verify((string) $password, (string) $data->password);
                if ($isPasswordVerified) {
                    if ($skipPasswordCheck) {
                        log_message('info', 'Password check skipped (development) for: ' . $email);
                    } else {
                        log_message('info', 'Password verified for: ' . $email);
                    }

                    // Multi-tenant portal binding (subdomain must match user's tenant).
                    helper('tenant');
                    $resolvedTenantId = function_exists('resolveUserTenantId') ? resolveUserTenantId($data) : null;
                    if (function_exists('isTenantRequest') && isTenantRequest()) {
                        $portalTenantId = currentTenantId();
                        if (($data->role ?? '') === 'super_admin') {
                            return requestResponse('error', 'Platform admin must sign in on the main domain.', 403);
                        }
                        if (empty($portalTenantId) || empty($resolvedTenantId) || (int) $portalTenantId !== (int) $resolvedTenantId) {
                            return requestResponse('error', 'This account does not belong to this portal.', 403);
                        }
                        if (function_exists('currentTenant') && currentTenant() && strtolower((string) (currentTenant()->status ?? '')) === 'suspended') {
                            return requestResponse('error', 'This portal is suspended. Contact the platform administrator.', 403);
                        }
                    }

                    if ($data->role == 'admin' && $data->subscription_status === 'inactive') {
                        log_message('info', 'User has inactive subscription status');
                        // return view('dashboard/exhome', $data);
                        // return view('dashboard/exhome', ['user' => $data]);

                        setSession([
                            'user_id' => $data->id,
                            'user_role' => $data->role,
                            'status' => 'inactive',
                            'tenant_id' => $resolvedTenantId,
                        ]);

                        $session = session();
                        // log_message('info', 'Impersonated User Session: ' . json_encode($session->get()));

                        return requestResponse('success', [
                            'msg' => 'Login successful. Redirecting to the dashboard...',
                            'redirect' => route_to('route.exhome'),
                            'session_data' => $session->get(),
                        ], 200);
                    }

                    $isUserExpired = ($data->role === 'user' && ($data->status === 'inactive' || $data->subscription_status === 'inactive') && !empty($data->will_expire) && strtotime($data->will_expire) <= time());

                    if ($isUserExpired) {
                        log_message('info', 'Expired user logging in, redirecting to exhome.');

                        // Resolve admin_id for session
                        $admin_id = 2; // default fallback
                        $a_role = Model('App\Models\User')->where(['id' => $data->id])
                            ->select('created_by, admin_id')
                            ->first();

                        if (!empty($a_role) && $a_role->created_by != '') {
                            if ($a_role->created_by == 'super_admin' || $a_role->created_by == 'admin') {
                                $admin_id = $a_role->admin_id;
                            }

                            if ($a_role->created_by == 'resellerAdmin') {
                                $a_id = $a_role->admin_id;
                                $resellerUser = Model('App\Models\User')->where(['id' => $a_id])
                                    ->select('admin_id')
                                    ->first();
                                if ($resellerUser) {
                                    $admin_id = $resellerUser->admin_id;
                                }
                            }
                        }

                        setSession([
                            'user_id' => $data->id,
                            'user_role' => $data->role,
                            'admin_id' => $admin_id,
                            'status' => 'inactive',
                            'tenant_id' => $resolvedTenantId,
                        ]);

                        $session = session();

                        return requestResponse('success', [
                            'msg' => 'Login successful. Redirecting to the dashboard...',
                            'redirect' => route_to('route.exhome'),
                            'session_data' => $session->get(),
                        ], 200);
                    }

                    if ($data->status === 'active') {
                        $admin_id = 2; // default fallback if no role/created_by branch below matches
                        if ($data->role == 'super_admin') {
                            $admin_id = $data->id;
                        }
                        if ($data->role == 'admin' || $data->role == 'resellerAdmin') {
                            $admin_id = model('App\Models\User')->where('id', $data->id)->first()->admin_id;
                        }


                        if ($data->role == 'employee' || $data->role == 'user') {

                            // Fetch the role-related data
                            $a_role = Model('App\Models\User')->where(['id' => $data->id])
                                ->select('created_by, admin_id')
                                ->first();




                            // Check if 'admin_id' is empty or null, if so, set default value to 2
                            if ($a_role->created_by == '') {
                                log_message('info', 'user null data check - no role found');

                                $admin_id = 2;  // Default value when admin_id is missing
                                log_message('info', 'user null data check ' . $admin_id);
                            } else {
                                // Proceed with your original logic when admin_id is not empty
                                if ($a_role->created_by == 'super_admin' || $a_role->created_by == 'admin') {
                                    $admin_id = $a_role->admin_id;
                                }

                                if ($a_role->created_by == 'resellerAdmin') {
                                    $a_id = $a_role->admin_id;
                                    $admin_id = Model('App\Models\User')->where(['id' => $a_id])
                                        ->select('admin_id')
                                        ->first()
                                        ->admin_id;
                                }
                            }

                            // You can log admin_id for debugging purposes
                            // log_message('info', 'Final admin_id: ' . $admin_id);
                        }


                        // if($data->role == 'user'){
                        //     $a_role = Model('App\Models\User')->where(['id' => $data->id])->select('created_by, admin_id')->first();
                        //     log_message('info', 'useradlkasjdiwjdlasd' . json_encode($a_role));

                        //     if($a_role->created_by == 'super_admin'|| $a_role->created_by == 'admin'){
                        //         $admin_id = $a_role->admin_id;

                        //     }
                        //     if($a_role->created_by == 'resellerAdmin'){
                        //         $a_id = $a_role->admin_id;
                        //         $admin_id = Model('App\Models\User')->where(['id' => $a_id])->select('admin_id')->first()->admin_id;

                        //     }  
                        // }



                        setSession([
                            'user_id' => $data->id,
                            'user_role' => $data->role,
                            'admin_id' => $admin_id,
                            'tenant_id' => $resolvedTenantId,
                        ]);
                        $session = session();

                        // ✅ Load the session config properly
                        $sessionSavePath = ini_get('session.save_path');
                        if (empty($sessionSavePath)) {
                            $sessionSavePath = WRITEPATH . 'session';
                        }

                        // Get the session ID
                        $sessionId = session_id();

                        // Construct full path to the current session file
                        $sessionFile = rtrim($sessionSavePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ci_session' . $sessionId;

                        // (Optional) Log the session file path
                        // log_message('info', 'Current session file: ' . $sessionFile);
                        // Extract only the session token (after 'ci_session')
                        $sessionNameOnly = str_replace('ci_session', '', basename($sessionFile));

                        log_message('info', 'Session token: ' . $sessionNameOnly);

                        if ($data->role == 'employee' || $data->role == 'user') {
                            // === Save Audit Log ===
                            $auditModel = new AuditLogModel();

                            // $public_ip = $this->request->getPost('ip_address');
                            // $local_ip = $this->request->getPost('device_ip');

                            $public_ip = getPublicIP(); // Calls ipify API
                            $local_ip = getLocalIP();


                            $logData = [
                                'user_id' => $data->id,
                                'action' => 'login', // optional
                                'entity' => 'user',  // optional
                                'client' => $this->request->getPost('browser_os'), // maps to browser_os
                                'router' => $this->request->getPost('platform'),   // maps to platform
                                'details' => json_encode([
                                    'screen' => $this->request->getPost('screen'),
                                    'timezone' => $this->request->getPost('timezone'),
                                    'cores' => $this->request->getPost('cores'),
                                    'ram' => $this->request->getPost('ram'),
                                ]),
                                'actor' => $data->role,
                                'ip_address' => json_encode([   // <-- save both IPs as JSON
                                    'public_ip' => $public_ip,
                                    'local_ip' => $local_ip
                                ]),
                                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                                'created_at' => date('Y-m-d H:i:s'),
                            ];

                            log_message('info', 'Audit Log Data: ' . json_encode($logData));
                            // Save the audit log
                            $auditModel->log($logData);
                        }
                        // log_message('info', 'Impersonated User Session: ' . json_encode($session->get()));

                        return requestResponse('success', [
                            'msg' => 'Login successful. Redirecting to the dashboard...',
                            'redirect' => route_to('route.dashboard'),
                            'user_id' => $data->id,
                            'user_role' => $data->role,
                            'admin_id' => $admin_id,
                            'file_name' => $sessionNameOnly,

                        ], 200);
                    }

                    return requestResponse('error', 'Your account is currently disabled', 403);
                }

                log_message('error', 'Login Failed - Password mismatch for: ' . $email);
                return requestResponse('error', 'Password is wrong', 400);
            }

            return requestResponse('error', 'Email id is wrong', 400);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Authentication
     * @action: Password Reset View
     */
    public function forgot()
    {
        $brandUserId = 2;
        $tenant = null;
        $isTenantPortal = false;

        try {
            helper('tenant');
            $brandUserId = function_exists('tenantBrandingUserId') ? tenantBrandingUserId() : 2;
            $tenant = function_exists('currentTenant') ? currentTenant() : null;
            $isTenantPortal = function_exists('isTenantRequest') && isTenantRequest();
        } catch (\Throwable $e) {
            log_message('error', 'Auth forgot branding context: ' . $e->getMessage());
        }

        return view('auth/forgot', [
            'brandUserId' => $brandUserId,
            'tenant' => $tenant,
            'isTenantPortal' => $isTenantPortal,
        ]);
    }

    /**
     * Authentication
     * @action: Password Reset Validation
     */
    public function validateForgot()
    {
        $this->validate([
            'email' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter your email id',
                ]
            ],
        ]);

        if ($this->validation->run()) {

            $email = getPostInput('email');

            $userModel = model('App\Models\User');
            $data = $userModel->where(['email' => $email])->first();

            if (!empty($data)) {

                $requestModel = model('App\Models\ResetRequest');

                $code = random_string('alnum', 40);

                $requestModel->insert([
                    'user_id' => $data->id,
                    'code' => $code,
                    'valid_till' => date("Y-m-d H:i:s", strtotime('+1 day')),
                ]);

                $link = base_url(route_to('route.auth.forgot.reset') . '?email=' . $email . '&code=' . $code);

                // Debug log to check email parameters
                log_message('debug', 'Sending email to: ' . $email . ' with link: ' . $link);

                $isSend = sendMail(
                    $email,
                    getSetting('app_name') . ' | Password Reset',
                    view('emails/reset-password-request', [
                        'user' => $data->name,
                        'link' => $link
                    ]),
                );

                if ($isSend) {
                    return requestResponse('success', 'Password reset request is successful. You will receive a password reset link via email shortly', 200);
                }

                // Log email sending error
                log_message('error', 'Failed to send email to: ' . $email . '. Check your SMTP settings or network issues.');

                return requestResponse('error', 'Something went wrong! Please try again', 500);
            }

            return requestResponse('validation-error', [
                'email' => 'Email id is wrong'
            ], 400);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Authentication
     * @action: Reset New Password
     */
    public function resetPassword()
    {
        if (!empty(getGetInput('code'))) {

            $requestModel = model('App\Models\ResetRequest');

            $code = getGetInput('code');

            $request_data = $requestModel->where(['code' => $code, 'valid_till >= ' => date("Y-m-d H:i:s")])->first();

            if (!empty($request_data)) {

                $userid = $request_data->user_id;

                $userModel = model('App\Models\User');

                $data = $userModel->find($userid);

                if (!empty($data)) {

                    $newpass = random_string('numeric', 6);


                    $isSend = sendMail(
                        $data->email,
                        getSetting('app_name') . ' | Password Reset Successful',
                        view('emails/password-reset-success', [
                            'user' => $data->name,
                            'email' => $data->email,
                            'password' => $newpass
                        ]),
                    );

                    if ($isSend) {

                        $userModel->update($data->id, [
                            'code' => $newpass,
                            'password' => password_hash($newpass, PASSWORD_DEFAULT)
                        ]);

                        // Phase 2: a password reset kills outstanding JWT access tokens.
                        helper('token');
                        revokeUserTokens($data->id);

                        $datas = [
                            'email' => $data->email,
                            'password' => $newpass,
                            'admin_id' => $data->admin_id,
                            'user_id' => $data->id,
                        ];
                        // event: password_reset | default template: 5
                        try {
                            sendEventSms('password_reset', $datas, null, 5);
                        } catch (\Throwable $e) {
                            log_message('error', 'Password Reset SMS Failed: ' . $e->getMessage());
                        }

                        $requestModel->delete($request_data->id);

                        session()->setFlashdata('success', 'Your password has been successfully reset! You will receive the new login password via email shortly.');
                    }

                    session()->setFlashdata('error', 'Something went wrong! Please try again');
                }
            }

            session()->setFlashdata('error', 'The password reset link is invalid');
        }

        return redirect()->route('route.auth.forgot');
    }
}
