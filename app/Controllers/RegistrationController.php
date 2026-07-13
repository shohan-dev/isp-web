<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminPackage;
use App\Models\Permission;
use App\Models\Registration;
use App\Models\User;
use App\Services\WalletService;

class RegistrationController extends BaseController
{
    public function index()
    {
        $brandUserId = 2;
        $tenant = null;
        $isTenantPortal = false;
        $packages = [];
        $paygPackage = null;
        $paygAddons = [];

        try {
            helper('tenant');
            $brandUserId = function_exists('tenantBrandingUserId') ? tenantBrandingUserId() : 2;
            $tenant = function_exists('currentTenant') ? currentTenant() : null;
            $isTenantPortal = function_exists('isTenantRequest') && isTenantRequest();

            $packageModel = new AdminPackage();
            $packages = $packageModel->publicFixedPackages();
            $paygPackage = $packageModel->paygPackage();
            $paygAddons = AdminPackage::addonCatalog($paygPackage);
        } catch (\Throwable $e) {
            log_message('error', 'Auth registration context: ' . $e->getMessage());
        }

        // Plan preselection carried over from the landing-page pricing CTAs:
        // ?plan={id} | payg | custom, plus optional PAYG add-on prefill.
        $selectedPlan = (new AdminPackage())->resolvePlanToken(trim((string) $this->request->getGet('plan')));
        $prefillAddons = array_filter(array_map('trim', explode(',', (string) $this->request->getGet('addons'))));

        return view('auth/gate', [
            'packages' => $packages,
            'paygPackage' => $paygPackage,
            'paygAddons' => $paygAddons,
            'selectedPlan' => $selectedPlan,
            'prefillAddons' => $prefillAddons,
            'authMode' => 'register',
            'brandUserId' => $brandUserId,
            'tenant' => $tenant,
            'isTenantPortal' => $isTenantPortal,
        ]);
    }

    public function submit()
    {
        // Validate form data
        $validationRules = [
            'admin_name' => 'required',
            'mobile' => 'required|is_unique[users.mobile]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[4]', // Minimum 4 characters for password
            'confirm_password' => 'required|matches[password]',
            'division' => 'required',
            'district' => 'required',
            'upazilla' => 'required',
            'address' => 'required',
            'package' => 'required',
            'customer_type' => 'required',
            'nationalid' => 'required'
        ];

        if (!$this->validate($validationRules)) {
            // Validation failed, return errors as JSON
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        // Collect input values in variables
        $organizationName = $this->request->getPost('organization_name');
        $adminName = $this->request->getPost('admin_name');
        $mobile = $this->request->getPost('mobile');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $division = $this->request->getPost('division');
        $district = $this->request->getPost('district');
        $upazilla = $this->request->getPost('upazilla');
        $address = $this->request->getPost('address');
        $planChoice = (new AdminPackage())->resolvePlanToken(trim((string) $this->request->getPost('package')));
        $customerType = $this->request->getPost('customer_type');
        $nationalId = $this->request->getPost('nationalid');
        $referenceName = $this->request->getPost('reference_name');
        $referenceMobile = $this->request->getPost('reference_mobile');
        $paygAddons = $this->request->getPost('payg_addons') ?? [];
        $customPlanNote = trim((string) $this->request->getPost('custom_plan_note'));

        // Resolve the chosen plan: a public fixed plan id, 'payg', or 'custom'.
        $packageModel = new AdminPackage();
        $planType = AdminPackage::TYPE_FIXED;
        $package = null;

        if ($planChoice === 'payg') {
            $planType = AdminPackage::TYPE_PAYG;
            $package = $packageModel->paygPackage();
            if (empty($package)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Pay-As-You-Go is not available right now. Please pick a plan or contact support.',
                ]);
            }
        } elseif ($planChoice === 'custom') {
            $planType = AdminPackage::TYPE_CUSTOM;
            if ($customPlanNote === '') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'errors' => ['custom_plan_note' => 'Please describe the plan you need.'],
                ]);
            }
        } else {
            if (!ctype_digit($planChoice)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'errors' => ['package' => 'Invalid package selected.'],
                ]);
            }
            $package = $packageModel->find((int) $planChoice);
            $pkgType = is_object($package) ? ($package->plan_type ?? 'fixed') : ($package['plan_type'] ?? 'fixed');
            $pkgActivity = strtolower((string) (is_object($package) ? ($package->Activity ?? '') : ($package['Activity'] ?? '')));
            $pkgPublic = is_object($package) ? ($package->is_public ?? 1) : ($package['is_public'] ?? 1);

            if (empty($package) || $pkgActivity !== 'active'
                || !in_array($pkgType, [AdminPackage::TYPE_FIXED, '', null], true)
                || ((int) ($pkgPublic ?? 1)) !== 1
            ) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'errors' => ['package' => 'Invalid package selected.'],
                ]);
            }
        }

        $now = date('Y-m-d H:i:s');
        $pkgId = $package ? (int) (is_object($package) ? $package->id : $package['id']) : 0;
        $trialDays = $package ? (int) (is_object($package) ? ($package->trial_days ?? 0) : ($package['trial_days'] ?? 0)) : 0;

        if ($planType === AdminPackage::TYPE_PAYG) {
            // Wallet-based plan: free trial first, then the PAYG billing cycle
            // takes over — service continues only while the wallet covers it.
            $trialDays = $trialDays > 0 ? $trialDays : 14;
            $willExpire = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));
            $subscriptionStatus = 'active';
        } elseif ($planType === AdminPackage::TYPE_CUSTOM) {
            // Custom plan request: account stays inactive until the platform
            // admin creates/assigns the tailored plan.
            $willExpire = null;
            $subscriptionStatus = 'inactive';
        } else {
            $willExpire = $trialDays > 0
                ? date('Y-m-d H:i:s', strtotime("+{$trialDays} days"))
                : calsAdminPackageExpireDate($pkgId, $now);
            $subscriptionStatus = 'active';
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Insert data into users table
            $userModel = new User();
            $userData = [
                'package_id' => $pkgId, // 0 = no plan yet (custom request pending approval)
                'area_id' => $district, // Assuming area_id corresponds to district
                'name' => $adminName,
                'designation' => 'Admin',
                'mobile' => $mobile,
                'email' => $email,
                'code' => $password,
                'password' => $hashedPassword,
                'address' => $address,
                'role' => 'admin',
                'subscription_status' => $subscriptionStatus,
                'will_expire' => $willExpire,
                'trial_ends_at' => ($subscriptionStatus === 'active' && !empty($willExpire)) ? $willExpire : null,
                'billing_type' => $planType === AdminPackage::TYPE_PAYG ? 'prepaid' : 'postpaid',
            ];
            $userModel->insert($userData);

            $insertId = $userModel->insertID();

            // Insert data into registrations table
            $registrationModel = new Registration();
            $registrationData = [
                'userid' => $insertId,
                'organization_name' => $organizationName,
                'admin_name' => $adminName,
                'mobile' => $mobile,
                'email' => $email,
                'password' => $hashedPassword,
                'division' => $division,
                'district' => $district,
                'upazilla' => $upazilla,
                'address' => $address,
                'package' => $pkgId ?: null,
                'requested_plan' => $planType === AdminPackage::TYPE_CUSTOM ? 'custom'
                    : ($planType === AdminPackage::TYPE_PAYG ? 'payg' : (string) $pkgId),
                'plan_note' => $customPlanNote !== '' ? $customPlanNote : null,
                'nationalid' => $nationalId,
                'customer_type' => json_encode($customerType),
                'reference_name' => $referenceName,
                'reference_mobile' => $referenceMobile,
            ];

            $registrationModel->insert($registrationData);

            // Insert permissions data
            $permissionModel = new Permission();
            $customerData = [
                [
                    'user_id' => $insertId,
                    'user_type' => 'user',
                    'permissions' => json_encode([

                        'support_ticket' => ['read', 'create', 'send_msg', 'update'],
                        'payment' => ['read', 'payment', 'invoice'],
                        'subscription' => ['read', 'renew'],
                        'profile_update' => ['read', 'update'],
                        'password_change' => ['update'],
                    ])
                ],
                [
                    'user_id' => $insertId,
                    'user_type' => 'employee',
                    'permissions' => json_encode([
                        'area' => ['read', 'create', 'delete', 'update'],
                        'package' => ['read', 'create'],
                        'customer' => ['read', 'create', 'delete', 'update', 'update_subscription', 'update_conn'],
                        'password_change' => ['update'],
                    ])
                ],
                [
                    'user_id' => $insertId,
                    'user_type' => 'resellerAdmin',
                    'permissions' => json_encode([
                        'area' => ['read', 'create', 'delete', 'update'],
                        'package' => ['read'],
                        'customer' => ['read', 'create', 'delete', 'update', 'update_subscription'],
                        'employee' => ['read', 'create', 'delete', 'update'],
                        'reseller' => ['read', 'update', 'update_subscription', 'update_conn'],
                        'customer_payment' => ['read', 'create', 'delete', 'update', 'invoice'],
                        'employee_payment' => ['read', 'create', 'delete', 'update'],
                        'support_ticket' => ['read', 'create', 'send_msg', 'delete', 'update'],
                        'payment' => ['read', 'payment', 'invoice'],
                        'subscription' => ['read', 'renew'],
                        'profile_update' => ['read', 'update'],
                        'password_change' => ['update'],
                    ])
                ]
            ];

            foreach ($customerData as $data) {
                $permissionModel->insert($data);
            }

            $db->transComplete();

            if (!$db->transStatus()) {
                throw new \Exception('Database transaction failed');
            }

            // PAYG tenants get their platform wallet right away so the first
            // top-up (and add-on choices) work the moment they sign in.
            if ($planType === AdminPackage::TYPE_PAYG) {
                try {
                    $walletService = new WalletService();
                    $wallet = $walletService->ensureWallet((int) $insertId);

                    $catalog = AdminPackage::addonCatalog($package);
                    $chosen = array_values(array_intersect(
                        array_map('strval', (array) $paygAddons),
                        array_keys($catalog)
                    ));
                    if (!empty($chosen)) {
                        model('App\Models\TenantWallet')->update($wallet->id, ['addons' => json_encode($chosen)]);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'PAYG wallet setup failed for user ' . $insertId . ': ' . $e->getMessage());
                }
            }

            $minTopup = $package ? (float) (is_object($package) ? ($package->min_topup ?? 0) : ($package['min_topup'] ?? 0)) : 0;

            $message = 'Registration successful';
            if ($planType === AdminPackage::TYPE_PAYG) {
                $message = 'Registration successful. Your ' . $trialDays . '-day free trial has started — sign in and add at least ৳'
                    . number_format($minTopup) . ' to your wallet so your service continues after the trial.';
            } elseif ($planType === AdminPackage::TYPE_CUSTOM) {
                $message = 'Registration submitted. Our team will review your custom plan request and activate your account shortly.';
            } elseif ($subscriptionStatus === 'active' && !empty($willExpire)) {
                $message = 'Registration successful. Your ' . $trialDays . '-day free trial has started — sign in to explore the full ISP panel.';
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $message,
                'plan_type' => $planType,
                'pending' => $planType === AdminPackage::TYPE_CUSTOM,
                'trial_days' => $trialDays,
                'trial_ends_at' => $willExpire,
            ]);
        } catch (\Exception $e) {
            $db->transRollback();

            log_message('error', 'Registration failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function fetchData()
    {
        $registrationModel = new Registration();
        $data = $registrationModel->findAll();

        // Return data in the format DataTables expects
        return $this->response->setJSON(['data' => $data]);
    }

    public function success()
    {
        return view('auth/success');
    }
}
