<?php

namespace Zapi\Modules\Reseller\Customer\Services\CustomerService;

use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CustomerServicePart07Segment
{
        /**
         * POST /api/reseller/customers/{resellerId}/import-excel
            * Imports customers from excel with per-row validation and partial success reporting.
         */
        public function importExcel($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $file = $this->request->getFile('excel_file');
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                return $this->respondError((string) 'Invalid excel file upload', 400, 'REQUEST_FAILED');
            }
    
            try {
                $reader = IOFactory::createReaderForFile($file->getTempName());
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file->getTempName());
                $sheetData = $spreadsheet->getActiveSheet()->toArray();
    
                if (empty($sheetData)) {
                    return $this->respondError((string) 'Sheet data is empty', 400, 'REQUEST_FAILED');
                }
    
                $expectedHeaders = [
                    'ID',
                    'Customer Name',
                    'Mobile',
                    'PackageName',
                    'Router name',
                    'Email',
                    'PPPoE Secret',
                    'PPPoE Password',
                    'Subscription. Status',
                    'Acc. Status',
                    'Area',
                    'Address',
                    'Bandwidth',
                    'WillExpire(m/d/y)'
                ];
    
                $headerRow = array_map(function ($value) {
                    return trim((string) ($value ?? ''));
                }, $sheetData[0]);
    
                $expectedTrim = array_map('trim', $expectedHeaders);
                $headerRow = array_reverse($headerRow);
                while (!empty($headerRow) && ($headerRow[0] === '' || $headerRow[0] === null)) {
                    array_shift($headerRow);
                }
                $headerRow = array_reverse($headerRow);
    
                if ($headerRow !== $expectedTrim) {
                    return $this->respondPayload([
                        'status' => 'error',
                        'message' => 'Invalid Excel format. Header row does not match expected format.',
                        'formatError' => true,
                    ], 400);
                }
    
                $reseller = $this->user_model
                    ->where('id', (int) $resellerId)
                    ->where('role', 'resellerAdmin')
                    ->first();
    
                if (empty($reseller)) {
                    return $this->respondError((string) 'Reseller not found', 404, 'REQUEST_FAILED');
                }
    
                $resellerRow = $this->toArray($reseller);
    
                $packageModel = model('App\Models\ResellerPackages');
                $areaModel = model('App\Models\Area');
                $routerModel = model('App\Models\Router');
    
                $created = [];
                $errors = [];
                $warnings = [];
    
                for ($i = 1; $i < count($sheetData); $i++) {
                    $row = $sheetData[$i] ?? [];
                    if (empty(array_filter($row, function ($value) {
                        return trim((string) ($value ?? '')) !== '';
                    }))) {
                        continue;
                    }
    
                    $name = trim((string) ($row[1] ?? ''));
                    $mobileRaw = trim((string) ($row[2] ?? ''));
                    $packageName = trim((string) ($row[3] ?? ''));
                    $routerName = trim((string) ($row[4] ?? ''));
                    $email = trim((string) ($row[5] ?? ''));
                    $pppoeSecret = trim((string) ($row[6] ?? ''));
                    $pppoePassword = trim((string) ($row[7] ?? ''));
                    $subscriptionStatus = strtolower(trim((string) ($row[8] ?? 'active')));
                    $accountStatus = strtolower(trim((string) ($row[9] ?? 'active')));
                    $areaName = trim((string) ($row[10] ?? ''));
                    $address = trim((string) ($row[11] ?? ''));
                    $pppoeProfile = trim((string) ($row[12] ?? ''));
                    $willExpireRaw = $row[13] ?? null;
    
                    if ($name === '' || $packageName === '' || $routerName === '') {
                        $errors[] = ['row' => $i + 1, 'message' => 'Missing required fields: Customer Name, PackageName, or Router name'];
                        continue;
                    }
    
                    $mobileDigits = preg_replace('/\D+/', '', $mobileRaw ?? '');
                    $mobile = $mobileDigits !== '' ? ('0' . ltrim($mobileDigits, '0')) : '';
                    if ($mobile === '') {
                        $errors[] = ['row' => $i + 1, 'message' => 'Mobile is required'];
                        continue;
                    }
    
                    if ($pppoeSecret === '') {
                        $pppoeSecret = $mobile;
                    }
                    if ($pppoePassword === '') {
                        $pppoePassword = '1234';
                    }
    
                    if ($email === '') {
                        $email = $pppoeSecret . '@gmail.com';
                    }
    
                    $package = $packageModel
                        ->where('user_id', (int) $resellerId)
                        ->where('package_name', $packageName)
                        ->first();
                    if (empty($package)) {
                        $errors[] = ['row' => $i + 1, 'message' => 'Package not found: ' . $packageName];
                        continue;
                    }
                    $packageRow = $this->toArray($package);
                    $packageId = (int) ($packageRow['id'] ?? 0);
    
                    $router = $routerModel
                        ->where('user_id', (int) $resellerId)
                        ->where('name', $routerName)
                        ->first();
                    if (empty($router)) {
                        $errors[] = ['row' => $i + 1, 'message' => 'Router not found: ' . $routerName];
                        continue;
                    }
                    $routerRow = $this->toArray($router);
                    $routerId = (int) ($routerRow['id'] ?? 0);
    
                    $areaId = 0;
                    if ($areaName !== '') {
                        $area = $areaModel
                            ->where('user_id', (int) $resellerId)
                            ->where('area_name', $areaName)
                            ->first();
                        if (!empty($area)) {
                            $areaRow = $this->toArray($area);
                            $areaId = (int) ($areaRow['id'] ?? 0);
                        } else {
                            $warnings[] = ['row' => $i + 1, 'message' => 'Area not found, using default: ' . $areaName];
                        }
                    }
    
                    $existsEmail = $this->user_model->where('email', $email)->first();
                    if (!empty($existsEmail)) {
                        $errors[] = ['row' => $i + 1, 'message' => 'Email already exists: ' . $email];
                        continue;
                    }
    
                    $existsMobile = $this->user_model->where('mobile', $mobile)->first();
                    if (!empty($existsMobile)) {
                        $errors[] = ['row' => $i + 1, 'message' => 'Mobile already exists: ' . $mobile];
                        continue;
                    }
    
                    $routerClient = function_exists('routerClient') ? routerClient($routerId) : null;
                    if (!($routerClient instanceof \RouterOS\Client)) {
                        $errors[] = ['row' => $i + 1, 'message' => 'Router connection failed: ' . $routerName];
                        continue;
                    }
    
                    $pppoe = function_exists('getPPPoEUserByName') ? getPPPoEUserByName($routerClient, $pppoeSecret) : [];
                    $pppoeId = is_array($pppoe) ? (($pppoe[0]['id'] ?? $pppoe[0]['.id'] ?? '') ?: '') : '';
    
                    if ($pppoeId === '') {
                        $profileToUse = $pppoeProfile !== '' ? $pppoeProfile : trim((string) ($packageRow['bandwidth'] ?? ''));
                        if ($profileToUse === '') {
                            $profileToUse = 'default';
                        }
    
                        $routerAction = function_exists('createPPPoEUser')
                            ? createPPPoEUser($routerClient, [
                                'pppoe_name' => $pppoeSecret,
                                'pppoe_password' => $pppoePassword,
                                'pppoe_service' => 'pppoe',
                                'pppoe_profile' => $profileToUse,
                            ])
                            : ['status' => 'error', 'error' => 'createPPPoEUser helper unavailable'];
    
                        if (!is_array($routerAction) || (($routerAction['status'] ?? 'error') !== 'success')) {
                            $errorText = is_array($routerAction) ? ($routerAction['error'] ?? 'Failed to create PPPoE user') : 'Failed to create PPPoE user';
                            $errors[] = ['row' => $i + 1, 'message' => $errorText];
                            continue;
                        }
    
                        $pppoeId = (string) ($routerAction['pppoe_id'] ?? '');
                        if ($pppoeId === '') {
                            $pppoeAgain = function_exists('getPPPoEUserByName') ? getPPPoEUserByName($routerClient, $pppoeSecret) : [];
                            $pppoeId = is_array($pppoeAgain) ? (($pppoeAgain[0]['id'] ?? $pppoeAgain[0]['.id'] ?? '') ?: '') : '';
                        }
                    }
    
                    if ($pppoeId === '') {
                        $errors[] = ['row' => $i + 1, 'message' => 'Unable to resolve PPPoE ID after creation'];
                        continue;
                    }
    
                    $willExpire = date('Y-m-d H:i:s', strtotime('+1 day'));
                    if (!empty($willExpireRaw)) {
                        if (is_numeric($willExpireRaw)) {
                            $excelTs = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float) $willExpireRaw);
                            $willExpire = date('Y-m-d 12:00:00', $excelTs);
                        } else {
                            $parsed = strtotime((string) $willExpireRaw);
                            if ($parsed !== false) {
                                $willExpire = date('Y-m-d 12:00:00', $parsed);
                            }
                        }
                    }
    
                    $customerData = [
                        'package_id' => $packageId,
                        'area_id' => $areaId,
                        'router_id' => $routerId,
                        'name' => $name,
                        'mobile' => $mobile,
                        'email' => $email,
                        'code' => $pppoePassword,
                        'password' => password_hash($pppoePassword, PASSWORD_DEFAULT),
                        'address' => $address !== '' ? $address : '--',
                        'pppoe_id' => $pppoeId,
                        'last_renewed' => date('Y-m-d H:i:s'),
                        'will_expire' => $willExpire,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'auto_disconnect' => 'yes',
                        'role' => 'user',
                        'subscription_status' => in_array($subscriptionStatus, ['active', 'inactive'], true) ? $subscriptionStatus : 'active',
                        'status' => in_array($accountStatus, ['active', 'inactive'], true) ? $accountStatus : 'active',
                        'admin_id' => (int) $resellerId,
                        'created_by' => $resellerRow['role'] ?? 'resellerAdmin',
                    ];
    
                    try {
                        $this->user_model->insert($customerData, false);
                        $insertId = (int) $this->user_model->getInsertID();
                        if ($insertId <= 0) {
                            $errors[] = ['row' => $i + 1, 'message' => 'Failed to insert customer row'];
                            continue;
                        }
    
                        $cacheData = [
                            'user_id' => $insertId,
                            'router_id' => $routerId,
                            'router_password' => $pppoePassword,
                            'pppoe_secret' => $pppoeSecret,
                            'last_updated' => date('Y-m-d H:i:s'),
                        ];
    
                        $existingCache = $this->userRouterDataModel->where('user_id', $insertId)->first();
                        if (!empty($existingCache)) {
                            $cacheRow = $this->toArray($existingCache);
                            if (!empty($cacheRow['id'])) {
                                $this->userRouterDataModel->update((int) $cacheRow['id'], $cacheData);
                            } else {
                                $this->userRouterDataModel->where('user_id', $insertId)->set($cacheData)->update();
                            }
                        } else {
                            $this->userRouterDataModel->insert($cacheData);
                        }
    
                        $created[] = [
                            'row' => $i + 1,
                            'customer_id' => $insertId,
                            'name' => $name,
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = ['row' => $i + 1, 'message' => 'Insert failed: ' . $e->getMessage()];
                    }
                }
    
                $message = 'Excel import finished';
                if (empty($created) && !empty($errors)) {
                    $message = 'Excel import failed for all rows';
                } elseif (!empty($created) && !empty($errors)) {
                    $message = 'Excel import completed with partial success';
                } elseif (!empty($created)) {
                    $message = 'Excel data imported successfully';
                }
    
                return $this->respondPayload([
                    'status' => 'success',
                    'message' => $message,
                    'data' => [
                        'row_count' => max(0, count($sheetData) - 1),
                        'created_count' => count($created),
                        'error_count' => count($errors),
                        'warning_count' => count($warnings),
                        'created' => $created,
                        'errors' => $errors,
                        'warnings' => $warnings,
                    ],
                ]);
            } catch (\Throwable $e) {
                return $this->respondError((string) 'Failed to parse excel file: ' . $e->getMessage(), 500, 'REQUEST_FAILED');
            }
        }
    
        /**
         * Normalizes bindMacForUser() return value (bool / int / string).
         */
        private function bindMacResultIsSuccess($result): bool
        {
            if (!is_array($result)) {
                return false;
            }
            $st = $result['status'] ?? false;
            if ($st === true || $st === 1) {
                return true;
            }
            if (is_string($st)) {
                $s = strtolower(trim($st));
    
                return in_array($s, ['1', 'true', 'yes', 'on'], true);
            }
    
            return false;
        }
    
        /**
         * Customer user row for this reseller, or null.
         */
        private function resellerCustomerRow($resellerId, $customerId)
        {
            $row = $this->user_model
                ->where('id', (int) $customerId)
                ->where('admin_id', (int) $resellerId)
                ->where('role', 'user')
                ->first();
    
            return empty($row) ? null : $row;
        }
    
        /**
         * GET /api/reseller/customers/(:resellerId)/(:customerId)/mac-status
         * Same idea as web customers/mac_ajax …/check — returns current router MAC binding.
         */
        public function macStatus($resellerId = null, $customerId = null)
        {
            if (empty($resellerId) || empty($customerId)) {
                return $this->respondError((string) 'Missing reseller id or customer id', 400, 'REQUEST_FAILED');
            }
    
            if ($this->resellerCustomerRow($resellerId, $customerId) === null) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            $binding = $this->getMacBinding((int) $customerId);
    
            return $this->respondSuccess(['message' => 'OK', 'payload' => [
                    'mac_binding' => $binding,
                ],]);
        }
    
}
