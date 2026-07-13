<?php

namespace Zapi\Modules\Reseller\Customer\Services\CustomerService;

use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CustomerServicePart04Segment
{
        /**
         * POST /api/reseller/customers/create/(:resellerId)/(:num?)
         */
        public function create($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
    
            $required = [
                'name',
                'package_id',
                'area_id',
                'router_id',
                'mobile',
                'address',
                'status',
                'pppoe_name',
                'pppoe_password',
                'pppoe_service',
            ];
    
            $errors = [];
            foreach ($required as $field) {
                $value = $input[$field] ?? null;
                if ($value === null || trim((string) $value) === '') {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
    
            $status = strtolower(trim((string) ($input['status'] ?? '')));
            if ($status !== '' && !in_array($status, ['active', 'inactive'], true)) {
                $errors['status'] = 'Status must be active or inactive';
            }
    
            if (!empty($errors)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', $errors);
            }
    
            $resellerObj = $this->user_model
                ->where('id', (int) $resellerId)
                ->where('role', 'resellerAdmin')
                ->first();
    
            if (empty($resellerObj)) {
                return $this->respondError((string) 'Reseller not found', 404, 'REQUEST_FAILED');
            }
    
            $reseller = $this->toArray($resellerObj);

            helper('subscription');
            $tenantId = (int) ($reseller['admin_id'] ?? 0);
            if ($tenantId > 0) {
                $limitMsg = assertTenantCanAddCustomer($tenantId);
                if ($limitMsg !== null) {
                    return $this->respondError((string) $limitMsg, 400, 'LIMIT_REACHED');
                }
            }

            $mobileDigits = preg_replace('/\D+/', '', (string) ($input['mobile'] ?? ''));
            $mobile = $mobileDigits !== '' ? ('0' . ltrim($mobileDigits, '0')) : '';
            if ($mobile === '') {
                return $this->respondError((string) 'Mobile is required', 400, 'REQUEST_FAILED');
            }
            if (strlen($mobile) < 11 || strlen($mobile) > 15) {
                return $this->respondError((string) 'Mobile number length is invalid', 400, 'REQUEST_FAILED');
            }

            $mobileExists = $this->user_model
                ->where('mobile', $mobile)
                ->first();
            if (!empty($mobileExists)) {
                return $this->respondError((string) 'Mobile already in use', 400, 'REQUEST_FAILED');
            }
    
            $email = trim((string) ($input['email'] ?? ''));
            if ($email === '') {
                $email = trim((string) $input['pppoe_name']) . '@gmail.com';
            }

            $pppoeName = trim((string) ($input['pppoe_name'] ?? ''));
            if ($pppoeName === '') {
                return $this->respondError((string) 'PPPoE username is required', 400, 'REQUEST_FAILED');
            }
            if (strlen($pppoeName) > 64) {
                return $this->respondError((string) 'PPPoE username is too long', 400, 'REQUEST_FAILED');
            }
    
            $emailExists = $this->user_model
                ->where('email', $email)
                ->first();
            if (!empty($emailExists)) {
                return $this->respondError((string) 'Email already in use', 400, 'REQUEST_FAILED');
            }
    
            if (!function_exists('routerClient') || !function_exists('createPPPoEUser')) {
                return $this->respondError((string) 'Router helper functions are not available', 500, 'REQUEST_FAILED');
            }
    
            $routerId = (int) ($input['router_id'] ?? 0);
            if ($routerId <= 0) {
                return $this->respondError((string) 'Invalid router id', 400, 'REQUEST_FAILED');
            }
    
            $routerClient = routerClient($routerId);
            if (!($routerClient instanceof \RouterOS\Client)) {
                $error = is_array($routerClient) ? ($routerClient['error'] ?? 'Router connection failed') : 'Router connection failed';
                return $this->respondError((string) $error, 500, 'REQUEST_FAILED');
            }
    
            $packageId = (int) ($input['package_id'] ?? 0);
            if ($packageId <= 0) {
                return $this->respondError((string) 'Invalid package id', 400, 'REQUEST_FAILED');
            }
    
            $pppoeProfile = trim((string) ($input['pppoe_profile'] ?? ''));
            if ($pppoeProfile === '') {
                $resellerPackageModel = model('App\Models\ResellerPackages');
                $resellerPackage = $resellerPackageModel
                    ->where('id', $packageId)
                    ->where('status', 'active')
                    ->first();
                $resellerPackageRow = $this->toArray($resellerPackage);
                $pppoeProfile = trim((string) ($resellerPackageRow['bandwidth'] ?? ''));
            }
    
            if ($pppoeProfile === '') {
                return $this->respondError((string) 'PPPoE profile is required', 400, 'REQUEST_FAILED');
            }

            $pppoePassword = trim((string) ($input['pppoe_password'] ?? ''));
            if ($pppoePassword === '') {
                return $this->respondError((string) 'PPPoE password is required', 400, 'REQUEST_FAILED');
            }
            if (strlen($pppoePassword) > 128) {
                return $this->respondError((string) 'PPPoE password is too long', 400, 'REQUEST_FAILED');
            }

            // Keep legacy "code" uniqueness stable even when users choose a common password.
            $codeCandidate = $pppoePassword;
            $suffixCounter = 1;
            while (!empty($this->user_model->where('code', $codeCandidate)->first())) {
                $codeCandidate = $pppoePassword . '_' . $suffixCounter;
                $suffixCounter++;
                if ($suffixCounter > 100) {
                    $codeCandidate = $pppoePassword . '_' . time();
                    break;
                }
            }
    
            $routerPayload = [
                'pppoe_name' => $pppoeName,
                'pppoe_password' => $pppoePassword,
                'pppoe_service' => trim((string) $input['pppoe_service']),
                'pppoe_profile' => $pppoeProfile,
            ];
    
            $routerAction = createPPPoEUser($routerClient, $routerPayload);
            if (!is_array($routerAction) || ($routerAction['status'] ?? 'error') !== 'success') {
                $error = is_array($routerAction) ? ($routerAction['error'] ?? 'Failed to create PPPoE user') : 'Failed to create PPPoE user';
                return $this->respondError((string) $error, 500, 'REQUEST_FAILED');
            }
    
            $pppoeId = $routerAction['pppoe_id'] ?? null;
            if (empty($pppoeId)) {
                return $this->respondError((string) 'Failed to receive PPPoE id from router', 500, 'REQUEST_FAILED');
            }
    
            $billingStatus = trim((string) ($input['billing_status'] ?? ''));
            $explicitExpire = trim((string) ($input['will_expire'] ?? ''));
            if ($explicitExpire !== '') {
                $normalized = str_replace('T', ' ', $explicitExpire);
                $timestamp = strtotime($normalized);
                if ($timestamp === false) {
                    return $this->respondError((string) 'Invalid datetime format for will_expire', 400, 'REQUEST_FAILED');
                }
                $willExpire = date('Y-m-d H:i:s', $timestamp);
            } elseif (strtolower($billingStatus) === 'free') {
                $willExpire = date('Y-m-d H:i:s', strtotime('+365 days'));
            } elseif ((int) ($reseller['area_id'] ?? 0) > 0) {
                $cutDay = (int) $reseller['area_id'];
                $todayDay = (int) date('d');
                $month = (int) date('m');
                $year = (int) date('Y');
                $time = date('H:i:s');
    
                if ($todayDay > $cutDay) {
                    $month++;
                    if ($month > 12) {
                        $month = 1;
                        $year++;
                    }
                }
    
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                if ($cutDay > $daysInMonth) {
                    $cutDay = $daysInMonth;
                }
    
                $willExpire = sprintf('%04d-%02d-%02d %s', $year, $month, $cutDay, $time);
            } elseif ($packageId > 0) {
                // Avoid web-session-dependent helper in API context.
                $pricingType = 'monthly';
                $resellerPackageModel = model('App\Models\ResellerPackages');
                $resellerPackage = $resellerPackageModel
                    ->where('id', $packageId)
                    ->first();
                $resellerPackageRow = $this->toArray($resellerPackage);
                $rawPricingType = strtolower(trim((string) ($resellerPackageRow['pricing_type'] ?? '')));
                if (in_array($rawPricingType, ['weekly', 'monthly', 'yearly'], true)) {
                    $pricingType = $rawPricingType;
                }

                switch ($pricingType) {
                    case 'weekly':
                        $willExpire = date('Y-m-d H:i:s', strtotime('+7 days'));
                        break;
                    case 'yearly':
                        $willExpire = date('Y-m-d H:i:s', strtotime('+1 year'));
                        break;
                    default:
                        $willExpire = date('Y-m-d H:i:s', strtotime('+1 month'));
                        break;
                }
            } else {
                $willExpire = date('Y-m-d H:i:s', strtotime('+30 days'));
            }
    
            $fund = (float) ($reseller['fund'] ?? 0);
            $packagePrice = 0.0;
            $activeForDays = max(0, (int) floor((strtotime($willExpire) - time()) / 86400));
            $newPrice = 0.0;
    
            if (function_exists('ResellerPackagePrice')) {
                $packagePrice = (float) ResellerPackagePrice($packageId, null, (int) $resellerId, 'resellerAdmin');
                if ($packagePrice < 0) {
                    $packagePrice = 0.0;
                }
                $newPrice = ($packagePrice / 30) * $activeForDays;
            }
    
            if ($fund < $newPrice) {
                if (function_exists('removePPPoEUser')) {
                    try {
                        removePPPoEUser($routerClient, $pppoeId);
                    } catch (\Throwable $e) {
                        log_message('error', 'Failed to rollback PPPoE after fund check failure: ' . $e->getMessage());
                    }
                }

                $requiredAmount = round($newPrice, 2);
                $availableAmount = round($fund, 2);
                $shortfall = round(max(0, $requiredAmount - $availableAmount), 2);

                return $this->respondError(
                    (string) 'Insufficient fund. Required: ' . $requiredAmount . ', Available: ' . $availableAmount,
                    400,
                    'REQUEST_FAILED',
                    [
                        'required_amount' => $requiredAmount,
                        'available_amount' => $availableAmount,
                        'shortfall' => $shortfall,
                    ]
                );
            }
            $prePackage = trim((string) ($input['pre_package'] ?? ''));
            if ($prePackage === '') {
                $prePackage = (string) $packageId;
            }

            $customerData = [
                'package_id' => $packageId,
                'pre_package' => $prePackage,
                'area_id' => (int) ($input['area_id'] ?? 0),
                'router_id' => $routerId,
                'name' => trim((string) $input['name']),
                'designation' => $input['designation'] ?? null,
                'mobile' => $mobile,
                'nid_number' => $input['nid_number'] ?? null,
                'email' => $email,
                'code' => $codeCandidate,
                'password' => password_hash($pppoePassword, PASSWORD_DEFAULT),
                'address' => trim((string) $input['address']),
                'pppoe_id' => $pppoeId,
                'conn_status' => $input['conn_status'] ?? null,
                'last_renewed' => date('Y-m-d H:i:s'),
                'will_expire' => $willExpire,
                'subscription_status' => $input['subscription_status'] ?? 'active',
                'auto_disconnect' => $input['auto_disconnect'] ?? 'no',
                'status' => $status,
                'created_by' => $reseller['role'] ?? 'resellerAdmin',
                'activity' => $input['activity'] ?? null,
                'posPrinter' => $input['posPrinter'] ?? null,
                'admin_id' => (int) $resellerId,
                'role' => 'user',
            ];
    
            $connectionFields = [
                'sub_area_id',
                'connection_type',
                'cable_requirement',
                'fiber_code',
                'number_of_core',
                'core_color',
                'client_type',
                'billing_status',
                'otc',
                'otc_status',
            ];
            $connectionData = [];
            foreach ($connectionFields as $field) {
                if (array_key_exists($field, $input)) {
                    $value = $input[$field];
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                    $connectionData[$field] = $value === '' ? null : $value;
                }
            }
    
            $insertId = null;
    
            $this->db->transBegin();
    
            try {
                $insertResult = $this->user_model->insert($customerData, false);
                if (!$insertResult) {
                    $modelErrors = method_exists($this->user_model, 'errors') ? (array) $this->user_model->errors() : [];
                    $dbError = (array) $this->db->error();
                    throw new \RuntimeException(
                        'Failed to create customer record | model_errors=' . json_encode($modelErrors) . ' | db_error=' . json_encode($dbError)
                    );
                }
    
                $insertId = (int) $this->user_model->getInsertID();
    
                if ($insertId <= 0) {
                    throw new \RuntimeException('Insert ID was not generated');
                }
    
                if (!empty($connectionData)) {
                    $this->saveConnectionDetails($insertId, $connectionData);
                }
    
                $cacheData = [
                    'user_id' => $insertId,
                    'router_id' => $routerId,
                    'router_password' => $routerPayload['pppoe_password'],
                    'pppoe_secret' => $routerPayload['pppoe_name'],
                    'last_updated' => date('Y-m-d H:i:s'),
                ];
    
                $existingCache = $this->userRouterDataModel->where('user_id', $insertId)->first();
                if (!empty($existingCache)) {
                    $cacheId = is_array($existingCache) ? ($existingCache['id'] ?? null) : ($existingCache->id ?? null);
                    if (!empty($cacheId)) {
                        $cacheResult = $this->userRouterDataModel->update((int) $cacheId, $cacheData);
                        if (!$cacheResult) {
                            $cacheDbError = (array) $this->db->error();
                            throw new \RuntimeException('Failed to update user_router_data cache | db_error=' . json_encode($cacheDbError));
                        }
                    } else {
                        $cacheResult = $this->userRouterDataModel->where('user_id', $insertId)->set($cacheData)->update();
                        if (!$cacheResult) {
                            $cacheDbError = (array) $this->db->error();
                            throw new \RuntimeException('Failed to update user_router_data cache row | db_error=' . json_encode($cacheDbError));
                        }
                    }
                } else {
                    $cacheResult = $this->userRouterDataModel->insert($cacheData);
                    if (!$cacheResult) {
                        $cacheErrors = method_exists($this->userRouterDataModel, 'errors') ? (array) $this->userRouterDataModel->errors() : [];
                        $cacheDbError = (array) $this->db->error();
                        throw new \RuntimeException(
                            'Failed to insert user_router_data cache | model_errors=' . json_encode($cacheErrors) . ' | db_error=' . json_encode($cacheDbError)
                        );
                    }
                }
    
                if ($newPrice > 0) {
                    // BUG-09 fix: atomic deduct; returns false on overdraw but we
                    // proceed (transaction row records what was charged regardless).
                    (new \App\Services\FundService())->deduct((int) $resellerId, (float) $newPrice);

                    $transactionModel = model('App\Models\ResellerTransactions');
                    $transactionModel->insert([
                        'customer' => $insertId,
                        'admin_id' => (int) $resellerId,
                        'amount' => $newPrice,
                        'package_price' => $packagePrice,
                        'active_for' => $activeForDays,
                        'comments' => 'API Customer Created',
                    ]);
                }
    
                if ($this->db->transStatus() === false) {
                    throw new \RuntimeException('Database transaction failed');
                }
    
                $this->db->transCommit();
            } catch (\Throwable $e) {
                $this->db->transRollback();
    
                if (!empty($pppoeId) && function_exists('removePPPoEUser')) {
                    try {
                        removePPPoEUser($routerClient, $pppoeId);
                    } catch (\Throwable $rollbackError) {
                        log_message('error', 'Failed to rollback PPPoE user after create failure: ' . $rollbackError->getMessage());
                    }
                }
    
                log_message('error', 'Failed to create customer via reseller API: ' . $e->getMessage());
    
                return $this->respondError((string) 'Customer create failed', 500, 'REQUEST_FAILED', [
                    'exception' => $e->getMessage(),
                    'db_error' => (array) $this->db->error(),
                    'input_context' => [
                        'reseller_id' => (int) $resellerId,
                        'package_id' => $packageId,
                        'area_id' => (int) ($input['area_id'] ?? 0),
                        'router_id' => $routerId,
                        'pppoe_name' => $pppoeName,
                    ],
                ]);
            }
    
            $created = $this->user_model
                ->where('id', (int) $insertId)
                ->where('admin_id', (int) $resellerId)
                ->where('role', 'user')
                ->first();
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'New customer record added successfully',
                'id' => $insertId,
                'data' => $this->enrichCustomer($created, true),
            ]);
        }
    
}
