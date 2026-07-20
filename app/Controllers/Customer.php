<?php

namespace App\Controllers;


use CodeIgniter\RESTful\ResourceController;
use App\Controllers\BaseController;
use Carbon\Carbon;
use App\Controllers\DateTimeZone;
use App\Libraries\DataTables;
use App\Models\AdminPackage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Controllers\CronJob;
use App\Services\TrashService;
// use App\Controllers\db;

use App\Models\Registration;
use App\models\ResellerFundingModel;
use App\Models\ConnectionDetails;
use phpseclib3\Net\SSH2;

// require 'vendor/autoload.php';
use Exception;

class Customer extends BaseController
{
    protected $router_client, $router_model, $user_model, $user_router_model;

    protected $progressFile;

    public function __construct()
    {


        // Set CORS headers
        // header("Access-Control-Allow-Origin: *");
        // header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        // header("Access-Control-Allow-Headers: Content-Type, Authorization");

        /**
         * Router Model
         */

        $this->router_model = model('App\Models\Router');
        $this->user_router_model = model('App\Models\UserRouterDataModel');

        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');

        /**
         * Sms Helper
         */
        helper('sms');
    }

    /**
     * Cached area/package options for customer list filter dropdowns.
     */
    private function withCustomerFilterLookups(array $data): array
    {
        $data['areasForFilter'] = getCachedAreaOptionsForFilter();
        $data['packagesForFilter'] = getCachedPackageOptionsForFilter();

        return $data;
    }

    /**
     * Customers
     * @action: All Customers View
     */
    public function index()
    {
        $status = $this->request->getGet('status') ?? null;
        $userId = session()->get('user_id');
        $details = $this->user_model->where(['id' => $userId])->first();
        $userRole = session()->get('user_role');

        if ($userRole === 'employee' && $details) {
            $Pre_created_by = $details->created_by;
            if ($Pre_created_by === 'admin') {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
            } else {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
                $userId = $details->admin_id ?? $userId;
            }
        }

        // Routers/resellers load lazily via modal_lookups (keeps HTML shell fast).
        $data = $this->withCustomerFilterLookups([
            'title' => 'Customers',
            'resellers' => [],
            'details' => $details,
            'status' => $status,
            'routers' => [],
        ]);

        return view('customers/all', $data);
    }

    public function inactive_index()
    {
        $userId = session()->get('user_id');
        $details = $this->user_model->where(['id' => $userId])->first();
        $userRole = session()->get('user_role');

        if ($userRole === 'employee' && $details) {
            $Pre_created_by = $details->created_by;
            if ($Pre_created_by === 'admin') {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
            } else {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
                $userId = $details->admin_id ?? $userId;
            }
        }

        $data = $this->withCustomerFilterLookups([
            'title' => 'Customers',
            'resellers' => [],
            'details' => $details,
        ]);

        return view('customers/allInactive', $data);
    }
    public function expired_index()
    {
        $userId = session()->get('user_id');
        $details = $this->user_model->where(['id' => $userId])->first();
        $userRole = session()->get('user_role');

        if ($userRole === 'employee' && $details) {
            $Pre_created_by = $details->created_by;
            if ($Pre_created_by === 'admin') {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
            } else {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
                $userId = $details->admin_id ?? $userId;
            }
        }

        $data = $this->withCustomerFilterLookups([
            'title' => 'Customers',
            'resellers' => [],
            'details' => $details,
        ]);

        return view('customers/allExpired', $data);
    }

    public function new_index()
    {
        $userId = session()->get('user_id');
        $details = $this->user_model->where(['id' => $userId])->first();
        $userRole = session()->get('user_role');

        if ($userRole === 'employee' && $details) {
            $Pre_created_by = $details->created_by;
            if ($Pre_created_by === 'admin') {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
            } else {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
                $userId = $details->admin_id ?? $userId;
            }
        }

        $data = $this->withCustomerFilterLookups([
            'title' => 'Customers',
            'resellers' => [],
            'details' => $details,
        ]);

        return view('customers/allnew', $data);
    }



    public function Excel_index()
    {
        // $area_model = model('App\Models\Area');
        // $package_model = model('App\Models\Package');
        $AdminPackage = model('App\Models\AdminPackage');
        $dats = getAllCostomer();
        log_message('info', 'Successfully called the URL: ' . print_r($dats, true));
        $userId = session()->get('user_id');

        $userModel = model('App\Models\User');



        $details = $userModel->where(['id' => $userId])->first();

        $role = $details->role;

        if ($role === 'resellerAdmin') {
            $detail = $userModel->where(['id' => $userId])->first();
            $admin_id = $detail->admin_id;

            $details = $userModel->where(['id' => $admin_id])->first();
            $userId = $admin_id;
        }
        if ($role === 'employee') {
            $created_by = $details->created_by;
            if ($created_by === 'resellerAdmin') {
                $userId = $details->admin_id;
                $detail = $userModel->where(['id' => $userId])->first();
                $admin_id = $detail->admin_id;

                $details = $userModel->where(['id' => $admin_id])->first();
                $userId = $admin_id;
            } else {
                // $detail = $userModel->where(['id' => $userId])->first();
                $admin_id = $details->admin_id;
                $details = $userModel->where(['id' => $admin_id])->first();
            }
        }



        if ($details === null || $details->status === 'inactive' || $details->subscription_status === 'inactive' || $details->conn_status != 'conn') {
            // return requestResponse('error', "Your account is not active.Update your account to create new customer", 500);
            return requestResponse('error', [
                'message' => "Your account is not active.Update your account to create new customer.",
                'limitReached' => false
            ], 500);
        }

        $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', "resellerAdmin")->findAll();
        $Customers = [];
        foreach ($userIds as $user) {
            $Customer = $userModel->where('admin_id', $user->id)->countAllResults();
            if ($Customer) {
                $Customers[] = $Customer;
            }
        }

        // Add the customers count to the total count
        $totalCustomerCount = array_sum($Customers);


        $count = $this->user_model->builder()
            ->where('role', 'user')
            ->where('admin_id', $userId)
            ->countAllResults();

        // log_message('info', 'Fetched details: ' . json_encode($details));

        $count += $totalCustomerCount;

        $package = $AdminPackage->select('duration')
            ->where('id', $details->package_id)
            ->first();

        log_message('info', 'Fetched count userIds: ' . json_encode($userIds));

        log_message('info', 'Fetched count Customers: ' . json_encode($Customers));

        log_message('info', 'Fetched count data: ' . json_encode($count));
        log_message('info', 'Fetched package data: ' . json_encode($package));

        if (!$package || !isset($package['duration'])) {
            log_message('error', 'Package data is null or missing duration key.');
            return requestResponse('error', [
                'message' => "Package details not found. Please contact support.",
                'limitReached' => false
            ], 500);
        } else {
            $data = [
                'active' => 'active',
            ];
        }

        // log_message, 'System response my Data: ' . print_r($data, true));


        return view('customers/importExcel', $data);
    }



    private $headerAliases = [
        'name' => ['Customer Name', 'Name', 'Full Name', 'Client Name', 'Client', 'User', 'Username', 'Customer', 'name_of_client'],
        'mobile' => ['Mobile', 'Phone', 'Contact', 'client_phone', 'Mobile Number', 'Phone Number', 'Cell', 'Mobile No', 'Contact Number', 'Contact No', 'Phone No'],
        'package' => ['PackageName', 'Package', 'Plan', 'Internet Plan', 'bandwidth_allocation MB', 'Profile', 'Service', 'Package Name'],
        'routername' => ['Router name', 'Router', 'Mikrotik', 'Gateway', 'Router Name', 'client_mikrotik', 'Server'],
        'email' => ['Email', 'E-mail', 'Mail'],
        'pppoeSecret' => ['PPPoE Secret', 'Secret', 'PPPoE Name', 'Login', 'Username', 'ID/IP'],
        'pppoePassword' => ['PPPoE Password', 'Password', 'Pass'],
        'connectionStatus' => ['Subscription. Status', 'Status', 'Subscription Status', 'Conn Status', 'B.Status'],
        'Status' => ['Acc. Status', 'Account Status', 'Status', 'B.Status'],
        'area' => ['Area', 'Zone', 'Region', 'Location'],
        'address' => ['Address', 'Full Address', 'Location', 'address_of_client'],
        'pppoe_profile' => ['Bandwidth', 'Profile', 'Speed', 'Package', 'ppoe_profile', 'bandwidth_allocation MB'],
        'will_expire' => ['WillExpire(m/d/y)', 'Expiry', 'Ex.Date', 'billing_cycle', 'Expiration', 'Expire Date', 'Valid Until', 'Expire']
    ];

    private function mapHeaders($headers)
    {
        $mapping = [];
        $headers = array_map('strtolower', array_map('trim', $headers));

        foreach ($this->headerAliases as $key => $aliases) {
            foreach ($aliases as $alias) {
                $index = array_search(strtolower(trim($alias)), $headers);
                if ($index !== false) {
                    $mapping[$key] = $index;
                    break;
                }
            }
        }
        return $mapping;
    }

    public function preview_Excel()
    {

        // $this->progressFile = WRITEPATH . 'uploads/progress.json'; // Store progress in a file
        // file_put_contents($this->progressFile, json_encode(['progress' => 0]));
        // session()->set('progress', 0);



        $file = $this->request->getFile('excel_file');

        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'Invalid file upload');
        }

        $ext = $file->getExtension();
        if (!in_array(strtolower($ext), ['xlsx', 'xls', 'csv'])) {
            return redirect()->back()->with('error', 'Invalid file type. Only Excel files (xlsx, xls, csv) are allowed.');
        }

        // log_message, 'File uploaded: ' . $file->getName());

        // Validate file upload
        if (!$file->isValid() || $file->hasMoved()) {
            log_message('error', 'File upload failed: ' . $file->getErrorString());
            return redirect()->back()->with('error', 'Failed to upload file.');
        }

        $filePath = $file->getTempName();

        // try {
        // Use PhpSpreadsheet's reader in read-only mode for performance
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $sheetData = $sheet->toArray();

        if (empty($sheetData)) {
            log_message('error', 'Error loading spreadsheet: Sheet data is empty');
            return redirect()->back()->with('error', 'Sheet data is empty.');
        }

        // $rowCount = count($sheetData);
        // $rowCount = count(array_filter($sheetData, function ($row) {
        //     return !empty(array_filter($row, fn($value) => trim($value) !== ''));
        // }));
        $rowCount = count(array_filter($sheetData, function ($row) {
            return !empty(array_filter($row, fn($value) => trim((string) $value) !== ''));
        }));

        // log_message, 'System response count($sheetData): ' . $rowCount);
        // log_message, 'Sheet Data: ' . print_r($sheetData, true));

        $headerRow = array_map(function ($value) {
            return trim($value ?? '');
        }, $sheetData[0]);

        $mapping = $this->mapHeaders($headerRow);

        // Required fields check
        $required = ['name', 'mobile', 'package', 'routername'];
        $missing = [];
        foreach ($required as $req) {
            if (!isset($mapping[$req])) {
                $missing[] = $this->headerAliases[$req][0];
            }
        }

        if (!empty($missing)) {
            return $this->response->setJSON([
                'error' => 'Could not identify required columns: ' . implode(', ', $missing),
                'formatError' => true,
                'headers' => $headerRow,
                'mapping' => $mapping,
                'aliases' => $this->headerAliases,
                'csrf_token' => csrf_hash()
            ]);
        }

        // Check overall customer limit
        $allcustomer = getAllCostomer();
        $existingCount = $allcustomer['count'];
        $newRows = $rowCount - 1; // exclude header row
        if (!empty($allcustomer['package']['duration']) && (($existingCount + $newRows) > $allcustomer['package']['duration'])) {
            // return redirect()->back()
            //     ->with('error', "You are at your limit! Update your package to create a new customer.")
            //     ->with('limitReached', true);
            return $this->response->setJSON([
                'error' => 'You are at your limit! Update your package to create a new customer.',
                'csrf_token' => csrf_hash()
                // 'sheetData' => $sheetData,
                // 'errors' => $errors  // No errors
            ]);
        }


        $data = [];
        $routerDataMap = [];
        $errors = [];
        $routerSecretsMaps = [];
        $userId = session()->get('user_id');
        $userModel = model('App\Models\User');
        $details = $userModel->where(['id' => $userId])->first();
        log_message('info', 'Fetched count details: ' . json_encode($details));
        $created_by = $details->role;


        // Loop through the sheetData starting after the header row
        for ($i = 1; $i < $rowCount; $i++) {
            $row = $sheetData[$i];
            // if (empty(array_filter($row, fn($value) => trim($value) !== ''))) {
            //     log_message('info', "Skipping empty row at index $i");
            //     continue; // Skip this row
            // }
            $detail = $details;
            log_message('info', 'Processing row ' . $i . ': ' . json_encode($row));

            // Retrieve values using the mapping
            $name = isset($mapping['name']) ? ($row[$mapping['name']] ?? null) : null;
            $mobileRaw = isset($mapping['mobile']) ? ($row[$mapping['mobile']] ?? '') : '';
            // Normalize mobile: strip leading zeros and re-add single '0'. Empty stays empty.
            $mobileRaw = trim((string) $mobileRaw);
            if ($mobileRaw !== '' && $mobileRaw !== '0') {
                $mobile = '0' . ltrim($mobileRaw, '0');
            } else {
                $mobile = null; // Will be given a placeholder below
            }
            $package = isset($mapping['package']) ? ($row[$mapping['package']] ?? null) : null;
            $routername = isset($mapping['routername']) ? ($row[$mapping['routername']] ?? null) : null;
            $pppoeSecret = isset($mapping['pppoeSecret']) ? ($row[$mapping['pppoeSecret']] ?? 1234) : 1234;

            $emailCol = $mapping['email'] ?? null;
            $email = ($emailCol !== null && !empty($row[$emailCol]) && trim((string) $row[$emailCol]) !== '')
                ? trim((string) $row[$emailCol])
                : $pppoeSecret;

            $pppoePassword = isset($mapping['pppoePassword']) ? ($row[$mapping['pppoePassword']] ?? 1234) : 1234;
            $connectionStatus = isset($mapping['connectionStatus']) ? ($row[$mapping['connectionStatus']] ?? 'active') : 'active';
            $Status = isset($mapping['Status']) ? ($row[$mapping['Status']] ?? 'active') : 'active';
            $area = isset($mapping['area']) ? ($row[$mapping['area']] ?? null) : null;
            $address = isset($mapping['address']) ? ($row[$mapping['address']] ?? null) : null;
            $pppoe_profile = isset($mapping['pppoe_profile']) ? ($row[$mapping['pppoe_profile']] ?? null) : null;
            $will_expire = isset($mapping['will_expire']) ? ($row[$mapping['will_expire']] ?? null) : null;

            // Skip row if any essential field is missing
            if (!$name || !$package || !$routername) {
                $errors[] = "Row $i: Missing essential data.";
                continue;
            }

            // --- SMART EMAIL DUPLICATION RESOLUTION ---
            $isDuplicateEmail = false;
            if (in_array($email, $processedEmails ?? [])) {
                $isDuplicateEmail = true;
            } else {
                $existingUser = $userModel->where('email', $email)->get()->getRowArray();
                if ($existingUser) {
                    $isDuplicateEmail = true;
                }
            }

            if ($isDuplicateEmail) {
                $email = $pppoeSecret;
            }
            $processedEmails[] = $email;

            // --- SMART MOBILE DUPLICATION RESOLUTION ---
            // If mobile is empty/null, assign a unique placeholder so NOT NULL constraint is satisfied
            if (empty($mobile)) {
                $mobile = 'no-mobile-' . uniqid();
            }

            // Check if this mobile already exists in DB or in current batch
            if (in_array($mobile, $processedMobiles ?? [])) {
                $isDuplicateMobile = true;
            } else {
                $existingNumber = $userModel->where('mobile', $mobile)->get()->getRowArray();
                $isDuplicateMobile = (bool) $existingNumber;
            }

            if ($isDuplicateMobile) {
                $suffix = 1;
                $originalMobile = $mobile;
                do {
                    $mobile = substr($originalMobile, 0, 15) . '-d' . $suffix;
                    $suffix++;
                    $existsInDB = $userModel->where('mobile', $mobile)->get()->getRowArray();
                    $existsInExcel = in_array($mobile, $processedMobiles ?? []);
                } while ($existsInDB || $existsInExcel);
            }

            $processedMobiles[] = $mobile;
            $created_by = $detail->role;
            log_message('info', 'Fetched count created_by: ' . json_encode($created_by));
            // Fetch package detail based on user role
            if (getSession('user_role') === 'admin') {
                $package_model = model('App\Models\Package');
                $packages = $package_model->where('user_id', $userId)->where(['package_name' => $package])->first();
            } elseif (getSession('user_role') === 'employee') {
                if ($created_by === 'admin') {
                    $userId = $detail->admin_id;
                    $package_model = model('App\Models\Package');
                    $packages = $package_model->where('user_id', $userId)->where(['package_name' => $package])->first();
                } else {
                    // $userId = $detail->admin_id;
                    // $detail = $userModel->where(['id' => $userId])->first();
                    $package_model = model('App\Models\ResellerPackages');
                    $packages = $package_model->where('user_id', $detail->admin_id)->where(['package_name' => $package])->first();
                }
            } else {
                $package_model = model('App\Models\ResellerPackages');
                $packages = $package_model->where('user_id', $detail->admin_id)->where(['package_name' => $package])->first();
            }
            $package_id = $packages->id ?? ($packages['id'] ?? '--');
            if (!$packages) {
                $errors[] = "Row $i: Package '$package' not found. It will be auto-created.";
            }

            // Get area info
            $area_model = model('App\Models\Area');
            $datas = $area_model->where('user_id', $userId)->where(['area_name' => $area])->first();
            $area_id = $datas->id ?? '--';
            if (!$datas) {
                if (!empty($area)) {
                    $errors[] = "Row $i: Area '$area' not found. It will be auto-created.";
                }
            }

            // Get router information
            $routerModel = model('App\Models\Router');
            if (getSession('user_role') === 'admin') {
                $router = $routerModel->where(['user_id' => $userId, 'name' => $routername])->first();
            } elseif (getSession('user_role') === 'employee') {
                log_message('info', 'Fetched count created_by: ' . json_encode($created_by));

                if ($created_by === 'admin') {
                    $userId = $detail->admin_id;
                    $router = $routerModel->where(['user_id' => $userId, 'name' => $routername])->first();
                } else {
                    log_message('info', 'Fetched count detail%%: ' . json_encode($detail));

                    $userId = $detail->admin_id;
                    $detail = $userModel->where(['id' => $userId])->first();
                    log_message('info', 'Fetched count detail%%%%: ' . json_encode($detail));

                    $admin_id = $detail->admin_id;
                    $router = $routerModel->where(['user_id' => $admin_id, 'name' => $routername])->first();
                }
                // log_message('info', 'Fetched count admin_id: ' . json_encode($admin_id));

            } else {
                $admin_id = $detail->admin_id;
                $router = $routerModel->where(['user_id' => $admin_id, 'name' => $routername])->first();
            }
            if (!$router) {
                $errors[] = "Row $i: Failed to get the router ($routername). Data will not be inserted with default router.";
                continue;
            } else {
                $router_id = $router->id;
                $router_client = routerClient($router_id);
                if (!empty($router_client)) {
                    if (is_array($router_client)) {
                        // Convert only if it's meant to be an object
                        $errors[] = "Row $i: Failed to get the router_client info ($routername). Data will not be inserted with default router.";
                        continue;
                    }
                    // Pre-fetch all PPPoE secrets for this router to avoid O(N) network calls in loop
                    if (!isset($routerSecretsMaps[$router_id])) {
                        helper('router');
                        $allPppUsers = getAllPPPoEUsers($router_client);
                        $tempMap = [];
                        if (is_array($allPppUsers)) {
                            foreach ($allPppUsers as $u) {
                                if (isset($u['name'])) {
                                    $tempMap[$u['name']] = $u['.id'] ?? $u['id'] ?? null;
                                }
                            }
                        }
                        $routerSecretsMaps[$router_id] = $tempMap;
                    }

                    $pppoe_id = $routerSecretsMaps[$router_id][$pppoeSecret] ?? null;

                    if ($pppoe_id) {
                        log_message('info', "PPPoE User already exists. ID: $pppoe_id");
                    } else {
                        $router_action = createPPPoEUser($router_client, [
                            'pppoe_name' => $pppoeSecret,
                            'pppoe_password' => $pppoePassword,
                            'pppoe_service' => 'pppoe',
                            'pppoe_profile' => $pppoe_profile,
                        ]);
                        log_message('info', 'Router action response: ' . json_encode($router_action));
                        if (is_array($router_action) && isset($router_action['status']) && $router_action['status'] === 'success') {
                            $pppoe_id = $router_action['pppoe_id'] ?? null;
                            if ($pppoe_id) {
                                $routerSecretsMaps[$router_id][$pppoeSecret] = $pppoe_id;
                            }
                            log_message('info', "Successfully created PPPoE User. New ID: " . ($pppoe_id ?? 'Not Found'));
                        } else {
                            $errorMsg = $router_action['error'] ?? 'Unknown error';
                            log_message('error', "Failed to create PPPoE User: " . $errorMsg);
                            $errors[] = "Row $i: Failed to create PPPoE User - " . $errorMsg;
                        }
                    }
                }
            }

            // Adjust admin details if user is an employee
            // 'will_expire' => (!empty($will_expire) && strtotime($will_expire))
            //         ? date('Y-m-d', strtotime($will_expire)) . ' 12:00:00'
            //         : date('Y-m-d H:i:s', strtotime("+30 days")),


            // --- SMART EXPIRY LOGIC ---
            $final_expiry = null;
            if (!empty($will_expire)) {
                $trimmed_expire = trim((string) $will_expire);
                if (is_numeric($trimmed_expire) && (int) $trimmed_expire >= 1 && (int) $trimmed_expire <= 31) {
                    $targetDay = (int) $trimmed_expire;
                    $todayDay = (int) date('d');
                    if ($todayDay < $targetDay) {
                        $final_expiry = date('Y-m-') . str_pad($targetDay, 2, '0', STR_PAD_LEFT) . ' 12:00:00';
                    } else {
                        $final_expiry = date('Y-m-', strtotime('+1 month')) . str_pad($targetDay, 2, '0', STR_PAD_LEFT) . ' 12:00:00';
                    }
                } elseif (is_numeric($trimmed_expire) && (float) $trimmed_expire > 30000) {
                    $final_expiry = date('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($trimmed_expire)) . ' 12:00:00';
                } elseif (strtotime($trimmed_expire)) {
                    $final_expiry = date('Y-m-d', strtotime($trimmed_expire)) . ' 12:00:00';
                }
            }
            if (!$final_expiry) {
                $final_expiry = date('Y-m-d', strtotime('+1 day')) . ' 12:00:00';
            }

            $data[] = [
                'package_id' => $package_id ?? '--',
                'area_id' => $area_id ?? '--',
                'router_id' => $router_id ?? '--',
                'name' => $name ?? '--',
                'mobile' => $mobile ?? '--',
                'email' => $email,
                'code' => $pppoePassword ?? 1234,
                'password' => password_hash((string) ($pppoePassword ?? 1234), PASSWORD_DEFAULT),
                'address' => $address ?? '--',
                'pppoe_id' => $pppoe_id ?? '--',
                'last_renewed' => date('Y-m-d H:i:s'),
                'will_expire' => $final_expiry,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'auto_disconnect' => 'yes',
                'role' => 'user',
                'subscription_status' => $connectionStatus,
                'status' => $Status,
                'admin_id' => $userId,
                'created_by' => $created_by,
                // Extra info for preview
                'pppoe_secret' => $pppoeSecret,
                'pppoe_password' => $pppoePassword,
                'router_name' => $routername,
                'package_name' => $package,
                'area_name' => $area,
                'pppoe_profile' => $pppoe_profile
            ];

            $routerDataMap[] = [
                'mobile' => $mobile,
                'pppoe_secret' => $pppoeSecret,
                'router_password' => $pppoePassword,
                'pppoe_profile' => $pppoe_profile ?? null,
                'router_id' => $router_id
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'previewData' => $data,
            'routerDataMap' => $routerDataMap,
            'errors' => $errors,
            'rowCount' => count($data),
            'csrf_token' => csrf_hash()
        ]);
    }

    public function process_import()
    {
        $dataRaw = $this->request->getPost('data');
        $routerDataMapRaw = $this->request->getPost('routerDataMap');

        $data = is_string($dataRaw) ? json_decode($dataRaw, true) : $dataRaw;
        $routerDataMap = is_string($routerDataMapRaw) ? json_decode($routerDataMapRaw, true) : $routerDataMapRaw;

        if (empty($data)) {
            return $this->response->setJSON([
                'error' => 'No data received for import.',
                'csrf_token' => csrf_hash()
            ]);
        }

        $userId = session()->get('user_id');
        $userModel = model('App\Models\User');
        $details = $userModel->where(['id' => $userId])->first();
        $created_by = $details->role;

        if (session()->get('user_role') === 'employee') {
            $userId = $details->admin_id;
            $created_by = $details->created_by;
        }

        $areaModel = model('App\Models\Area');
        $packageModel = (getSession('user_role') === 'admin' || $created_by === 'admin')
            ? model('App\Models\Package')
            : model('App\Models\ResellerPackages');

        // Handle fund check for reseller
        if ($details->role === 'resellerAdmin' || $created_by === 'resellerAdmin') {
            $totalPackagePrice = 0;
            foreach ($data as $entry) {
                if (!empty($entry['package_id']) && $entry['package_id'] !== '--') {
                    $totalPackagePrice += ResellerPackagePrice($entry['package_id']) ?? 0;
                }
            }

            $hasPermission = userHasPermission('Resellers', 'daily_payment_generate') || userHasPermission('reseller', 'daily_payment_generate');

            if (!$hasPermission) {
                $fund = $details->fund ?? 0;
                $billing_type = $details->billing_type ?? 'postpaid';
                if ($billing_type === 'prepaid' && $fund < $totalPackagePrice) {
                    return $this->response->setJSON([
                        'error' => 'Dont have enough fund. Please recharge!',
                        'csrf_token' => csrf_hash()
                    ]);
                }
                // Deduct fund — atomic & race-safe. The `$fund < $totalPackagePrice`
                // guard above enforces sufficiency; this closes the TOCTOU window so two
                // concurrent requests cannot both pass the check and overdraw the balance.
                if (! (new \App\Services\FundService())->deduct((int) $userId, (float) $totalPackagePrice)) {
                    return $this->response->setJSON([
                        'error' => 'Dont have enough fund. Please recharge!',
                        'csrf_token' => csrf_hash()
                    ]);
                }
            }
        }

        $successCount = 0;
        $transationModel = model('App\Models\ResellerTransactions');

        foreach ($data as $index => $row) {
            // --- AUTO CREATE AREA ---
            if (($row['area_id'] === '--' || empty($row['area_id'])) && !empty($row['area_name'])) {
                $existingArea = $areaModel->where('user_id', $userId)->where('area_name', $row['area_name'])->first();
                if ($existingArea) {
                    $row['area_id'] = $existingArea->id;
                } else {
                    $areaModel->insert([
                        'user_id' => $userId,
                        'area_name' => $row['area_name'],
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $row['area_id'] = $areaModel->getInsertID();
                }
            }

            // --- AUTO CREATE PACKAGE ---
            if (($row['package_id'] === '--' || empty($row['package_id'])) && !empty($row['package_name'])) {
                $existingPkg = $packageModel->where('user_id', $userId)->where('package_name', $row['package_name'])->first();
                if ($existingPkg) {
                    $row['package_id'] = $existingPkg->id ?? $existingPkg['id'];
                } else {
                    $packageModel->insert([
                        'user_id' => $userId,
                        'package_name' => $row['package_name'],
                        'price' => 0, // Manual update later
                        'selling_price' => '--',
                        'bandwidth' => 0,      // Manual update later
                        'status' => 'active',
                        'pricing_type' => 'monthly',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $newPkgId = $packageModel->getInsertID();
                    $row['package_id'] = $newPkgId;

                    // Append to all_reseller_packages JSON for this reseller
                    $allResellerPackageModel = model('App\Models\allResellerPackage');
                    $existingRecord = $allResellerPackageModel->where('user_id', $userId)->first();
                    
                    $newPkgDetails = [
                        'id' => $newPkgId,
                        'package_name' => $row['package_name'],
                        'price' => 0,
                        'selling_price' => '--',
                        'bandwidth' => 0,
                        'package_type' => 'monthly',
                        'preview' => '--'
                    ];

                    if ($existingRecord) {
                        $detailsArr = is_string($existingRecord['package_details'])
                            ? json_decode($existingRecord['package_details'], true)
                            : $existingRecord['package_details'];
                        if (!is_array($detailsArr)) {
                            $detailsArr = [];
                        }
                        
                        $found = false;
                        foreach ($detailsArr as $d) {
                            if (($d['id'] ?? '') == $newPkgId) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $detailsArr[] = $newPkgDetails;
                            $allResellerPackageModel->update($existingRecord['id'], [
                                'package_details' => json_encode($detailsArr)
                            ]);
                        }
                    } else {
                        $allResellerPackageModel->insert([
                            'user_id' => $userId,
                            'package_details' => json_encode([$newPkgDetails])
                        ]);
                    }
                }
            }

            // Remove helper fields used for preview
            unset($row['pppoe_secret'], $row['pppoe_password'], $row['router_name'], $row['package_name'], $row['area_name'], $row['pppoe_profile']);

            // --- RE-VALIDATE MOBILE UNIQUENESS BEFORE INSERT ---
            // The preview may have assigned a suffix, but a previous partial import run
            // may have already inserted it. Always re-check and re-resolve here.
            $insertMobile = $row['mobile'] ?? '';
            if (empty($insertMobile) || $insertMobile === '--') {
                $insertMobile = 'no-mobile-' . uniqid();
            }
            $existsInDB = $userModel->where('mobile', $insertMobile)->get()->getRowArray();
            if ($existsInDB) {
                $suffix = 1;
                $originalMobile = $insertMobile;
                do {
                    $insertMobile = substr($originalMobile, 0, 15) . '-d' . $suffix;
                    $suffix++;
                    $existsInDB = $userModel->where('mobile', $insertMobile)->get()->getRowArray();
                } while ($existsInDB);
            }
            $row['mobile'] = $insertMobile;

            if ($this->user_model->insert($row)) {
                $insertId = $this->user_model->getInsertID();
                $successCount++;

                // Router data insertion
                $rMap = $routerDataMap[$index] ?? null;
                if ($rMap) {
                    $this->user_router_model->insert([
                        'user_id' => $insertId,
                        'router_id' => $rMap['router_id'],
                        'pppoe_secret' => $rMap['pppoe_secret'],
                        'router_password' => $rMap['router_password'],
                        'pppoe_profile' => $rMap['pppoe_profile'] ?? null,
                        'last_updated' => date('Y-m-d H:i:s'),
                    ]);
                }

                // Transaction log for reseller
                if (($details->role === 'resellerAdmin' || $created_by === 'resellerAdmin') && !isset($hasPermission)) {
                    $transationModel->insert([
                        'customer' => $insertId,
                        'admin_id' => $userId,
                        'amount' => ResellerPackagePrice($row['package_id']) ?? 0,
                        'comments' => 'Customer Created via Excel'
                    ]);
                }
            }
        }

        return $this->response->setJSON([
            'success' => "Successfully imported $successCount customers!",
            'csrf_token' => csrf_hash()
        ]);
    }


    public function getProgress()
    {
        $progress = session()->get('progress') ?? 0;
        log_message('info', 'Progress Retrieved: ' . $progress);
        return $this->response->setJSON(['progress' => $progress]);
    }


    /**
     * Customers
     * @action: Fetch Customers
     */




    public function fetch()
    {
        // $to = '8801610585100';
        // $message = 'Cron job executed to update user statuses.';
        // $result = sendSms($to, $message);
        // log_message('info', 'Cron SMS Result: ' . json_encode($result));
        // // Log the process
        // if (isset($result['status']) && $result['status'] === 'success') {
        //     log_message('info', "Cron SMS Success: Message sent to $to. Gateway Response: " . $result['logs']);
        // } else {
        //     $errorMsg = $result['logs'] ?? 'Unknown Error';
        //     log_message('error', "Cron SMS Failed: Could not send to $to. Error: " . $errorMsg);
        // }

        // $pythonRunner = new \App\Controllers\PythonRunner();
        // $result = $pythonRunner->run();

        // log_message('info', 'Python Script Result: ' . json_encode($result));
        $status = $this->request->getPost('status');
        // Phase 1.5b: defense-in-depth clamp (the bounded LIMIT is enforced in
        // App\Libraries\DataTables::limit(); this keeps the logged/echoed value sane).
        $length = (int) $this->request->getPost('length');
        if ($length <= 0 || $length > 1000) {
            $length = 1000;
        }
        $start = max(0, (int) $this->request->getPost('start'));

        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        // Release the file-session lock early: this DataTables endpoint only
        // READS session (here and in the per-row closures) and never writes it,
        // so the user's concurrent requests (the 3s dashboard poll, rapid
        // re-sorts/searches) stop serializing behind this slow grid query.
        // $_SESSION stays readable after the close. (Phase 2 / T3)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        // Let client abort (SPA leave / full reload) free this worker instead of
        // finishing a discarded DataTables payload on single-threaded spark serve.
        ignore_user_abort(false);
        if (connection_aborted()) {
            return $this->response->setStatusCode(499)->setBody('');
        }

        $currrentDate = date('Y-m-d H:i:s');
        $details = $this->user_model->where(['id' => $userId])->first();
        $emp_admin_id = $details->admin_id;
        $area_id = $details->area_id;

        $area_filter = $this->request->getPost('area_filter');
        $package_filter = $this->request->getPost('package_filter');
        $connection_filter = $this->request->getPost('connection_filter');
        $acc_status_filter = $this->request->getPost('acc_status_filter');
        $expiry_filter = $this->request->getPost('expiry_filter');

        $currentMonth = date('F');
        $lastMonthName = date('F', strtotime('-1 month'));
        $now = date('Y-m-d');
        if (connection_aborted()) {
            return $this->response->setStatusCode(499)->setBody('');
        }
        $db = \Config\Database::connect();
        $escCur = $db->escape($currentMonth);
        $escLast = $db->escape($lastMonthName);

        // Cheap total: scoped users only (no payment joins / correlated subselects).
        $totalBuilder = $db->table('users')->where('users.role', 'user');
        if ($userole === 'employee') {
            if (!is_array($area_id)) {
                $area_id = explode(',', (string) $area_id);
            }
            $area_id = array_values(array_filter(array_map('trim', $area_id)));
            $totalBuilder->where('users.admin_id', $emp_admin_id);
            if (!empty($area_id)) {
                $totalBuilder->whereIn('users.area_id', $area_id);
            }
        } else {
            $totalBuilder->where('users.admin_id', $userId);
        }
        if ($status === 'due') {
            $totalBuilder->groupStart()
                ->where('users.subscription_status !=', 'active')
                ->orWhere('users.will_expire <', $now)
                ->orWhere("(SELECT COUNT(*) FROM payments WHERE user_id = users.id AND month = {$escCur} AND status = 'successful') = 0", null, false)
                ->groupEnd();
        } elseif ($status === 'active') {
            $totalBuilder->where('users.subscription_status', 'active')->where('users.will_expire >=', date('Y-m-d H:i:s'))->where('users.conn_status', 'conn');
        } elseif ($status === 'expired') {
            $totalBuilder->where('users.subscription_status', 'active')->where('users.will_expire <', date('Y-m-d H:i:s'));
        } elseif ($status === 'inactive') {
            $totalBuilder->where('users.status', 'inactive');
        }
        $trueTotal = (clone $totalBuilder)->countAllResults('', false);

        // Latest payment status per month via JOINs (avoids per-row correlated subselects).
        $payCurSql = "(SELECT p.user_id, p.status FROM payments p INNER JOIN (SELECT user_id, MAX(id) AS mid FROM payments WHERE month = {$escCur} GROUP BY user_id) t ON t.mid = p.id)";
        $payLastSql = "(SELECT p.user_id, p.status FROM payments p INNER JOIN (SELECT user_id, MAX(id) AS mid FROM payments WHERE month = {$escLast} GROUP BY user_id) t ON t.mid = p.id)";

        $data = $db->table('users')
            ->select('users.id, users.name, users.mobile, users.address, users.area_id, users.router_id, users.package_id')
            ->select('users.created_by, users.will_expire, users.last_renewed, users.subscription_status')
            ->select('users.activity, users.status, users.conn_status, users.created_at, users.pppoe_id')
            ->select('user_router_data.pppoe_secret AS pppoe_secret, user_router_data.router_password AS router_password, user_router_data.pppoe_profile AS pppoe_profile')
            ->select('areas.area_name AS area_name, routers.name AS router_name, users.status AS acc_status')
            ->select('COALESCE(p_admin.package_name, p_reseller.package_name) as joined_package_name')
            ->select('COALESCE(p_admin.price, p_reseller.selling_price, p_reseller.price) as joined_package_price')
            ->select('pay_cur.status AS current_payment_status, pay_last.status AS last_payment_status')
            ->join('user_router_data', 'user_router_data.user_id = users.id', 'left')
            ->join('areas', 'areas.id = users.area_id', 'left')
            ->join('routers', 'routers.id = users.router_id', 'left')
            ->join('packages as p_admin', 'p_admin.id = users.package_id', 'left')
            ->join('reseller_packages as p_reseller', 'p_reseller.id = users.package_id', 'left')
            ->join($payCurSql . ' pay_cur', 'pay_cur.user_id = users.id', 'left', false)
            ->join($payLastSql . ' pay_last', 'pay_last.user_id = users.id', 'left', false)
            ->where('users.role', 'user');

        if ($status === 'due') {
            $data->groupStart()
                ->where('users.subscription_status !=', 'active')
                ->orWhere('users.will_expire <', $now)
                ->orWhere('(pay_cur.status IS NULL OR pay_cur.status != \'successful\')', null, false)
                ->groupEnd();
        }

        if ($userole === 'employee') {
            if (!is_array($area_id)) {
                $area_id = explode(',', (string) $area_id);
            }
            $area_id = array_values(array_filter(array_map('trim', $area_id)));
            $data->where('users.admin_id', $emp_admin_id);
            if (!empty($area_id)) {
                $data->whereIn('users.area_id', $area_id);
            }
        } else {
            $data->where('users.admin_id', $userId);
        }

        // Apply dropdown filters
        if ($area_filter)
            $data->where('users.area_id', $area_filter);
        if ($package_filter)
            $data->where('users.package_id', $package_filter);
        // connection_filter: Online/Offline from DB activity (live MikroTik poll removed
        // from the list UI — it blocked single-threaded spark serve during sidebar nav).
        if ($connection_filter === 'active') {
            $data->where('users.activity', 'active');
        } elseif ($connection_filter === 'inactive') {
            $data->groupStart()
                ->where('users.activity !=', 'active')
                ->orWhere('users.activity', null)
            ->groupEnd();
        }
        if ($acc_status_filter)
            $data->where('users.conn_status', $acc_status_filter);  // conn=Connected toggle, disconn=Disconnected toggle


        // --- Server-side STATUS filters ---
        if ($status === 'active') {
            $data->where('users.subscription_status', 'active')->where('users.will_expire >=', date('Y-m-d H:i:s'))->where('users.conn_status', 'conn');
        } elseif ($status === 'expired') {
            $data->where('users.subscription_status', 'active')->where('users.will_expire <', date('Y-m-d H:i:s'));
        } elseif ($status === 'inactive') {
            $data->where('users.status', 'inactive');
        }
        // No else — All Customers shows every customer of this admin/reseller regardless of status.


        if ($expiry_filter) {
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');

            if ($expiry_filter === 'expired_today') {
                $data->where('users.will_expire >=', $today . ' 00:00:00')
                    ->where('users.will_expire <', $now);
            } elseif ($expiry_filter === 'expired_yesterday') {
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $data->where('users.will_expire >=', $yesterday . ' 00:00:00')
                    ->where('users.will_expire <=', $yesterday . ' 23:59:59');
            } elseif ($expiry_filter === 'expired_7') {
                $last7 = date('Y-m-d', strtotime('-7 days'));
                $data->where('users.will_expire >=', $last7 . ' 00:00:00')
                    ->where('users.will_expire <', $now);
            } elseif ($expiry_filter === 'expired_30') {
                $last30 = date('Y-m-d', strtotime('-30 days'));
                $data->where('users.will_expire >=', $last30 . ' 00:00:00')
                    ->where('users.will_expire <', $now);
            } elseif ($expiry_filter === 'expired_65') {
                $last65 = date('Y-m-d', strtotime('-65 days'));
                $data->where('users.will_expire <=', $last65 . ' 23:59:59');
            } elseif ($expiry_filter === 'due_today') {
                $data->where('users.will_expire >=', $now)
                    ->where('users.will_expire <=', $today . ' 23:59:59');
            } elseif ($expiry_filter === 'due_tomorrow') {
                $tomorrow = date('Y-m-d', strtotime('+1 day'));
                $data->where('users.will_expire >=', $tomorrow . ' 00:00:00')
                    ->where('users.will_expire <=', $tomorrow . ' 23:59:59');
            } elseif ($expiry_filter === 'due_3') {
                $next3 = date('Y-m-d', strtotime('+3 days'));
                $data->where('users.will_expire >', $now)
                    ->where('users.will_expire <=', $next3 . ' 23:59:59');
            } elseif ($expiry_filter === 'due_5') {
                $next5 = date('Y-m-d', strtotime('+5 days'));
                $data->where('users.will_expire >', $now)
                    ->where('users.will_expire <=', $next5 . ' 23:59:59');
            } elseif ($expiry_filter === 'due_7') {
                $next7 = date('Y-m-d', strtotime('+7 days'));
                $data->where('users.will_expire >', $now)
                    ->where('users.will_expire <=', $next7 . ' 23:59:59');
            } elseif ($expiry_filter === 'paid_1') {
                $tomorrow = date('Y-m-d', strtotime('+1 day'));
                $data->where('users.will_expire >=', $tomorrow . ' 00:00:00');
            } elseif ($expiry_filter === 'paid_3') {
                $next3 = date('Y-m-d', strtotime('+3 days'));
                $data->where('users.will_expire >=', $next3 . ' 00:00:00');
            } elseif ($expiry_filter === 'paid_7') {
                $next7 = date('Y-m-d', strtotime('+7 days'));
                $data->where('users.will_expire >=', $next7 . ' 00:00:00');
            }
        }




        // $data = $data->get()->getResultArray();   // or your query result
        // log_message('info', 'Datatable Raw Data: ' . json_encode($data));


        // [perf] removed hot-path compiled-SQL log: log_message('debug', 'DataTable SQL Query BEFORE: ' . $data->getCompiledSelect(false));
        $datatables = new DataTables($data);
        $datatables->setRecordsTotal($trueTotal);







        if (userHasPermission('customer', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('id', function ($row) {
            return $row->id;  // actual database ID
        });


        $datatables->addColumn('name', function ($row) {
            return '<a style="background-color:var(--info-100, #dbeafe); color:var(--info-600, #1d4ed8); padding:4px 8px; border-radius:6px; font-weight:500; text-decoration:none;" 
              href="' . route_to('route.customer.details', $row->id) . '">'
                . $row->name .
                '</a>';
        });






        $datatables->addColumn('mobile', function ($row) {
            $phone = trim($row->mobile ?? '');
            $callLink = $phone ? 'tel:' . preg_replace('/\D/', '', $phone) : '#';
            $cleanPhone = preg_replace('/\D/', '', $phone);
            if (strpos($phone, '+88') === 0) {
                $formattedPhone = $cleanPhone;
            } elseif (strpos($phone, '0') === 0) {
                $formattedPhone = '+880' . substr($cleanPhone, 1);
            } elseif ($phone) {
                $formattedPhone = '+880' . substr($cleanPhone, 1);
            } else {
                $formattedPhone = '';
            }
            $whatsappLink = $formattedPhone ? 'https://wa.me/' . $formattedPhone : '#';

            return '<div style="display:flex; justify-content:space-between; align-items:center; min-width:180px; background:var(--success-100, #dcfce7); color:var(--success-600, #15803d); border-radius:6px; padding:4px;">
                <span style="background:var(--success-100, #dcfce7); color:var(--success-600, #15803d); padding:2px 8px; border-radius:50px; display:inline-flex; align-items:center;">'
                . ($phone ?: '-') .
                '</span>
                <div style="display:flex; gap:8px;">
                    <a href="' . $callLink . '" style="color:var(--success-600, #15803d); font-size:20px; margin-top: 2px; " title="Call"' . (!$phone ? ' disabled' : '') . '>
                        <i class="fa-solid fa-phone"></i>
                    </a>
                    <a href="' . $whatsappLink . '" target="_blank" style="color:var(--success-600, #15803d); font-size:24px;" title="WhatsApp"' . (!$phone ? ' disabled' : '') . '>
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                </div>
            </div>';
        });





        $datatables->addColumn('package', function ($row) {
            $name = $row->joined_package_name ?? '--';
            $price = $row->joined_package_price ?? '--';

            return '<div style="line-height:1.2;">
                <strong style="font-size:16px;">' . htmlspecialchars($name) . '</strong><br>
                <span style="color:black; font-size:16px; margin-top:2px; display:block;">৳' . htmlspecialchars($price) . '</span>
            </div>';
        });

        $datatables->addColumn('area_name', function ($row) {
            return $row->area_name ?? '--';
        });
        $datatables->format('created_at', function ($value) {

            return date("d-m-Y, h:i a", strtotime($value));
        });

        $datatables->addColumn('router_name', function ($row) {
            return $row->router_name ?? '--';
        });

        $datatables->addColumn('pppoe_secret', function ($row) {
            return $row->pppoe_secret ?? '---';
        });
        $datatables->addColumn('router_password', function ($row) {
            return $row->router_password ?? '---';
        });





        $datatables->addColumn('conn_status', function ($row) {
            $color = ($row->activity === 'active') ? '#16a34a' : '#dc2626';
            $bg = ($row->activity === 'active') ? '#dcfce7' : '#fee2e2';
            $text = ($row->activity === 'active') ? 'Online' : 'Offline';
            return '<span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500;">' . $text . '</span>';
        });


        $datatables->addColumn('address', function ($row) {

            return $row->address ?? '--';
        });


        $datatables->addColumn('acc_status', function ($row) {
            $checked = $row->conn_status === 'conn' ? 'checked' : '';
            return '
                <div style="display: flex; justify-content: center; align-items: center;">
                    <label class="toggle-switch">
                        <input type="checkbox" class="conn-switch" data-id="' . $row->id . '" ' . $checked . '>
                        <span class="slider"></span>
                    </label>
                </div>
            ';
        });





        $datatables->addColumn('pppoe_id', function ($row) {

            return $row->pppoe_id ?? '--';
        });
        $datatables->addColumn('router_id', function ($row) {

            return $row->router_id ?? '--';
        });

        $datatables->addColumn('payment_expiry_sort', function ($row) {
            $today = \Carbon\Carbon::today();
            $willExpire = \Carbon\Carbon::parse($row->will_expire);
            $daysRemaining = $today->diffInDays($willExpire, false); // can be negative

            $now = date('Y-m-d');
            $renewalDate = $row->last_renewed;
            $subscriptionEnd = $row->will_expire;

            // Last payment month (safe)
            $lastPaymentMonth = !empty($renewalDate)
                ? date('F', strtotime($renewalDate))
                : null;

            // Expiring month (safe)
            $expiringMonth = !empty($subscriptionEnd)
                ? date('F', strtotime($subscriptionEnd))
                : null;

            // log_message, 'name : ' . $row->name . 'Renewal Date: ' . $renewalDate);
            // log_message, 'name : ' . $row->name . 'Subscription End: ' . $subscriptionEnd);
            // log_message, 'name : ' . $row->name . ' Last Payment Month: ' . $lastPaymentMonth);
            // log_message, 'name : ' . $row->name . ' Expiring Month: ' . $expiringMonth);


            // Check payment statuses only if months are valid
            $initialStatus = $row->last_payment_status ?? 'Unknown';
            $finalStatus = $row->current_payment_status ?? 'Unknown';


            // // log_message, 'Last Payment Month: ' . $lastPaymentMonth);
            // // log_message, 'Expiring Month: ' . $expiringMonth);
            // log_message, 'name : ' . $row->name . 'Initial Status:  ' . $initialStatus);
            // log_message, 'name : ' . $row->name . 'Final Status: ' . $finalStatus);

            $bothUnknown =
                ($initialStatus === 'Unknown' || $initialStatus === null) &&
                ($finalStatus === 'Unknown' || $finalStatus === null);

            $url = route_to('route.customer.payment.user', $row->id);

            if (!empty($row->will_expire) && date('Y', strtotime($row->will_expire)) === '2050') {
                return '<a href="' . $url . '" style="text-decoration:none;">
                            <span style="background:var(--info-100, #e0f2fe); color:var(--info-700, #0369a1); padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                Free
                            </span>
                        </a>';
            }

            if ($row->subscription_status === 'active') {
                if (!empty($subscriptionEnd) && strtotime($now) <= strtotime($subscriptionEnd)) {
                    // Show "paid" only when CURRENT month payment is successful.
                    // If current month is still pending, show "due" even if last month was paid.
                    if ($bothUnknown || $finalStatus === 'successful') {
                        $bg = '#dcfce7';
                        $color = '#15803d';
                        return '<a href="' . $url . '" style="text-decoration:none;">
                                    <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                        paid (' . $daysRemaining . ' left)
                                    </span>
                                </a>';
                    } else {
                        $bg = '#fef3c7';
                        $color = '#b45309';
                        return '<a href="' . $url . '" style="text-decoration:none;">
                                    <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                        due (' . $daysRemaining . ' left)
                                    </span>
                                </a>';
                    }
                } else {
                    $bg = '#fee2e2';
                    $color = '#ef4a31ff';
                    return '<a href="' . $url . '" style="text-decoration:none;">
                                <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                    expired (' . abs($daysRemaining) . ' days ago)
                                </span>
                            </a>';
                }
            }


            $bg = '#fee2e2';
            $color = '#dd1717ff';
            return '<a href="' . $url . '" style="text-decoration:none;">
                        <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                            Inactive
                        </span>
                    </a>';
        });




        $datatables->addColumn('action', function ($row) {
            return $this->renderCustomerListActions($row);
        });




        $datatables->except([
            'id',
            'area_id',
            'router_id',
            'package_id',
            'designation',
            'last_renewed',
            'will_expire',
            'subscription_status',
            'pppoe_id',
            'address',
            'email',
            'role',
            'password',
            'updated_at',
            'admin_id',
        ]);

        $datatables->addColumnAliases([
            'users.id' => 'id',
            'users.name' => 'name',
            'users.package_id' => 'package',
            'areas.area_name' => 'area_name',
            'users.mobile' => 'mobile',
            'users.address' => 'address',
            'routers.name' => 'router_name',
            'user_router_data.pppoe_secret' => 'pppoe_secret',
            'user_router_data.router_password' => 'router_password',
            'users.will_expire' => 'payment_expiry_sort',
            'users.activity' => 'conn_status',
            'users.status' => 'acc_status',
        ]);

        $datatables->asObject();

        if (connection_aborted()) {
            return $this->response->setStatusCode(499)->setBody('');
        }

        return $datatables->generate();
    }





    public function getPPPoEUserStatus()
    {
        log_message('info', 'getPPPoEUserStatus called');

        $routerId = $this->request->getPost('router_id');
        $pppoeIds = $this->request->getPost('pppoe_ids');

        if (empty($routerId) || empty($pppoeIds)) {
            return $this->response->setJSON(['error' => 'Invalid input']);
        }

        // Release the file-session lock BEFORE MikroTik I/O. Without this, every
        // customers draw's per-router status poll holds the session exclusively
        // for up to ~15s and serializes concurrent /customers/fetch + /dashboard
        // AJAX — intermittent infinite "Loading…" when navigating while polls run.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        ignore_user_abort(false);
        if (connection_aborted()) {
            return $this->response->setStatusCode(499)->setBody('');
        }

        $finalResults = [];

        try {
            $router_client = routerClient($routerId);
            if (!$router_client) {
                return $this->response->setJSON([$routerId => ['error' => 'Router connection failed']]);
            }

            $active_user = getactive_user($router_client);
            $active_ids = array_column($active_user['data']['activeusers'] ?? [], 'name');
            $active_ids_lower = array_map(function($id) {
                return strtolower(trim($id));
            }, $active_ids);

            // log_message('info', 'Router pppoeIds: ' . json_encode($pppoeIds));
            log_message('info', 'Active PPPoE IDs: ' . json_encode($active_ids));
            $results = [];
            foreach ($pppoeIds as $pppoe_id) {
                $results[$pppoe_id] = in_array(strtolower(trim($pppoe_id)), $active_ids_lower, true);
            }

            $finalResults[$routerId] = $results;
        } catch (\Exception $e) {
            log_message('error', 'Error in getPPPoEUserStatus: ' . $e->getMessage());
            return $this->response->setJSON(['error' => 'Processing failed']);
        }

        return $this->response->setJSON($finalResults);
    }



    public function inactive_fetch()
    {
        $userId = session()->get('user_id');

        $userole = session()->get('user_role');

        // Release the file-session lock early (read-only grid; session is only
        // read here + in row closures, never written). (Phase 2 / T3)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $details = $this->user_model->where(['id' => $userId])->first();
        $emp_admin_id = $details->admin_id;
        $area_id = $details->area_id;
        $currrentDate = date('Y-m-d H:i:s');

        $area_filter = $this->request->getPost('area_filter');
        $package_filter = $this->request->getPost('package_filter');
        $connection_filter = $this->request->getPost('connection_filter');
        $acc_status_filter = $this->request->getPost('acc_status_filter');

        $db = \Config\Database::connect();
        if ($userole === 'employee') {

            $data = $db->table('users')
                ->select('users.*, users.id AS id, user_router_data.pppoe_secret AS pppoe_secret, user_router_data.router_password AS router_password')
                ->select('areas.area_name AS area_name, routers.name AS router_name, users.status AS acc_status')
                ->join('user_router_data', 'user_router_data.user_id = users.id', 'left')
                ->join('areas', 'areas.id = users.area_id', 'left')
                ->join('routers', 'routers.id = users.router_id', 'left')
                ->where([
                    'users.role' => 'user',
                    'users.admin_id' => $emp_admin_id,
                    'users.area_id' => $area_id,
                ])
                ->where('users.conn_status', 'disconn');
        } else {
            $data = $db->table('users')
                ->select('users.*, users.id AS id, user_router_data.pppoe_secret AS pppoe_secret, user_router_data.router_password AS router_password')
                ->select('areas.area_name AS area_name, routers.name AS router_name, users.status AS acc_status')
                ->join('user_router_data', 'user_router_data.user_id = users.id', 'left')
                ->join('areas', 'areas.id = users.area_id', 'left')
                ->join('routers', 'routers.id = users.router_id', 'left')
                ->where([
                    'users.role' => 'user',
                    'users.admin_id' => $userId,
                ])
                ->where('users.conn_status', 'disconn');
        }

        if ($area_filter)
            $data->where('users.area_id', $area_filter);
        if ($package_filter)
            $data->where('users.package_id', $package_filter);
        if ($connection_filter)
            $data->where('users.activity', $connection_filter);
        // Note: acc_status_filter is not applied here because inactive_fetch already
        // enforces conn_status = 'disconn' at the base query level to avoid SQL conflict.

        // if ($userole === 'employee') {
        //     $data = $this->user_model->builder()
        //         ->select('*')
        //         ->where('role', 'user')
        //         ->where('admin_id', $emp_admin_id)
        //         ->where('subscription_status', 'inactive')
        //         // ->where('conn_status', 'disconn')
        //         ->orderBy('id', 'desc');
        // } else {
        //     $data = $this->user_model->builder()
        //         ->select('*')
        //         ->where('role', 'user')
        //         ->where('admin_id', $userId)
        //         ->where('subscription_status', 'inactive')
        //         // ->orWhere('conn_status', 'disconn')
        //         // ->orWhere('status', 'inactive')
        //         ->orderBy('id', 'desc');
        // }




        $datatables = new DataTables($data);

        $datatables->addSequenceNumber('serial');

        $datatables->addColumn('mobile', function ($row) {
            $phone = trim($row->mobile ?? '');
            $callLink = $phone ? 'tel:' . preg_replace('/\D/', '', $phone) : '#';
            $cleanPhone = preg_replace('/\D/', '', $phone);
            if (strpos($phone, '+88') === 0) {
                $formattedPhone = $cleanPhone;
            } elseif (strpos($phone, '0') === 0) {
                $formattedPhone = '+880' . substr($cleanPhone, 1);
            } elseif ($phone) {
                $formattedPhone = '+880' . substr($cleanPhone, 1);
            } else {
                $formattedPhone = '';
            }
            $whatsappLink = $formattedPhone ? 'https://wa.me/' . $formattedPhone : '#';

            return '<div style="display:flex; justify-content:space-between; align-items:center; min-width:180px; background:var(--success-100, #dcfce7); color:var(--success-600, #15803d); border-radius:6px; padding:4px;">
                <span style="background:var(--success-100, #dcfce7); color:var(--success-600, #15803d); padding:2px 8px; border-radius:50px; display:inline-flex; align-items:center;">'
                . ($phone ?: '-') .
                '</span>
                <div style="display:flex; gap:8px;">
                    <a href="' . $callLink . '" style="color:var(--success-600, #15803d); font-size:20px; margin-top: 2px; " title="Call"' . (!$phone ? ' disabled' : '') . '>
                        <i class="fa-solid fa-phone"></i>
                    </a>
                    <a href="' . $whatsappLink . '" target="_blank" style="color:var(--success-600, #15803d); font-size:24px;" title="WhatsApp"' . (!$phone ? ' disabled' : '') . '>
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                </div>
            </div>';
        });


        if (userHasPermission('customer', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('package', function ($row) {

            $role = $row->created_by;

            if ($role === 'resellerAdmin') {
                $package = getUserPackage($row->id); // returns array
                $name = $package['package_name'] ?? '--';

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
                    $price = $base_price ?? '--';
                }

                // Standard design for resellerAdmin
                // Standard design for regular users
                return '<div style="line-height:1.2;">
                    <strong style="font-size:16px;">' . htmlspecialchars($name) . '</strong><br>
                    <span style="color:black; font-size:16px; margin-top:2px; display:block;">৳' . htmlspecialchars($price) . '</span>
                </div>';
            }

            $package = getUserPackage($row->id); // returns object
            $name = $package->package_name ?? '--';
            $price = $package->price ?? '--';

            // Standard design for regular users
            return '<div style="line-height:1.2;">
                <strong style="font-size:16px;">' . htmlspecialchars($name) . '</strong><br>
                <span style="color:black; font-size:16px; margin-top:2px; display:block;">৳' . htmlspecialchars($price) . '</span>
            </div>';
        });

        $datatables->format('created_at', function ($value) {

            return date("d-m-Y, h:i a", strtotime($value));
        });
        $datatables->addColumn('area_name', function ($row) {
            return $row->area_name ?? '--';
        });

        $datatables->addColumn('router_name', function ($row) {
            return $row->router_name ?? '--';
        });

        $datatables->addColumn('pppoe_secret', function ($row) {
            return $row->pppoe_secret ?? '---';
        });

        $datatables->addColumn('router_password', function ($row) {
            return $row->router_password ?? '---';
        });





        // $datatables->format('auto_disconnect', function ($value) {

        //     return ($value === 'yes') ? '<span class="badge label-success">Yes</span>' : '<span class="badge label-danger">No</span>';
        // });

        $datatables->addColumn('conn_status', function ($row) {
            // // log_message, 'Row Data: ' . print_r($row, true));

            return ($row->activity === "active") ? '<span class="badge label-success">Online</span>' : '<span class="badge label-danger">Offline</span>';
        });

        $datatables->addColumn('payment_expiry_sort', function ($row) {
            $today = \Carbon\Carbon::today();
            $willExpire = \Carbon\Carbon::parse($row->will_expire);
            $daysRemaining = $today->diffInDays($willExpire, false); // can be negative

            $now = date('Y-m-d');
            $renewalDate = $row->last_renewed;
            $subscriptionEnd = $row->will_expire;

            // Last payment month (safe)
            $lastPaymentMonth = !empty($renewalDate)
                ? date('F', strtotime($renewalDate))
                : null;

            // Expiring month (safe)
            $expiringMonth = !empty($subscriptionEnd)
                ? date('F', strtotime($subscriptionEnd))
                : null;

            // log_message, 'name : ' . $row->name . 'Renewal Date: ' . $renewalDate);
            // log_message, 'name : ' . $row->name . 'Subscription End: ' . $subscriptionEnd);
            // log_message, 'name : ' . $row->name . ' Last Payment Month: ' . $lastPaymentMonth);
            // log_message, 'name : ' . $row->name . ' Expiring Month: ' . $expiringMonth);


            // Check payment statuses only if months are valid
            $initialStatus = $lastPaymentMonth
                ? checkPaymentStatus($row->id, $lastPaymentMonth)
                : 'Unknown';

            $finalStatus = $expiringMonth
                ? checkPaymentStatus($row->id, $expiringMonth)
                : 'Unknown';


            // // log_message, 'Last Payment Month: ' . $lastPaymentMonth);
            // // log_message, 'Expiring Month: ' . $expiringMonth);
            // log_message, 'name : ' . $row->name . 'Initial Status:  ' . $initialStatus);
            // log_message, 'name : ' . $row->name . 'Final Status: ' . $finalStatus);

            $bothUnknown =
                ($initialStatus === 'Unknown' || $initialStatus === null) &&
                ($finalStatus === 'Unknown' || $finalStatus === null);

            $url = route_to('route.customer.payment.user', $row->id);

            if (!empty($row->will_expire) && date('Y', strtotime($row->will_expire)) === '2050') {
                return '<a href="' . $url . '" style="text-decoration:none;">
                            <span style="background:var(--info-100, #e0f2fe); color:var(--info-700, #0369a1); padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                Free
                            </span>
                        </a>';
            }

            if ($row->subscription_status === 'active') {
                if (strtotime($now) <= strtotime($subscriptionEnd)) {
                    if ($bothUnknown || $initialStatus === 'successful' || $finalStatus === 'successful') {
                        $bg = '#dcfce7';
                        $color = '#15803d';
                        return '<a href="' . $url . '" style="text-decoration:none;">
                                    <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                        paid (' . $daysRemaining . ' left)
                                    </span>
                                </a>';
                    } else {
                        $bg = '#fef3c7';
                        $color = '#b45309';
                        return '<a href="' . $url . '" style="text-decoration:none;">
                                    <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                        due (' . $daysRemaining . ' left)
                                    </span>
                                </a>';
                    }
                } else {
                    $bg = '#fee2e2';
                    $color = '#ef4a31ff';
                    return '<a href="' . $url . '" style="text-decoration:none;">
                                <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                    expired (' . abs($daysRemaining) . ' days ago)
                                </span>
                            </a>';
                }
            }


            $bg = '#fee2e2';
            $color = '#dd1717ff';
            return '<a href="' . $url . '" style="text-decoration:none;">
                        <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                            Inactive
                        </span>
                    </a>';
        });




        $datatables->addColumn('acc_status', function ($row) {
            $checked = $row->conn_status === 'conn' ? 'checked' : '';
            return '
                <div style="display: flex; justify-content: center; align-items: center;">
                    <label class="toggle-switch">
                        <input type="checkbox" class="conn-switch" data-id="' . $row->id . '" ' . $checked . '>
                        <span class="slider"></span>
                    </label>
                </div>
            ';
        });

        // $datatables->addColumn('status', function ($row) {

        //     return ($row->conn_status === 'conn') ? '<span class="badge label-success">Active</span>' : '<span class="badge label-danger">Inactive</span>';
        // });

        $datatables->addColumn('action', function ($row) {
            return $this->renderCustomerListActions($row);
        });

        // $datatables->except([
        //     'id',
        //     'area_id',
        //     'router_id',
        //     'package_id',
        //     'designation',
        //     'last_renewed',
        //     'will_expire',
        //     'subscription_status',
        //     'pppoe_id',
        //     'address',
        //     'email',
        //     'role',
        //     'password',
        //     'updated_at',
        //     'admin_id',
        // ]);

        $datatables->addColumnAliases([
            'users.id' => 'id',
            'users.name' => 'name',
            'users.package_id' => 'package',
            'areas.area_name' => 'area_name',
            'users.mobile' => 'mobile',
            'users.address' => 'address',
            'routers.name' => 'router_name',
            'user_router_data.pppoe_secret' => 'pppoe_secret',
            'user_router_data.router_password' => 'router_password',
            'users.will_expire' => 'payment_expiry_sort',
            'users.activity' => 'conn_status',
            'users.status' => 'acc_status',
        ]);

        $datatables->asObject();

        return $datatables->generate();
    }

    public function expired_fetch()
    {
        $started = microtime(true);
        log_message('info', 'expired_fetch start');

        try {
            $userId = session()->get('user_id');
            $currrentDate = date('Y-m-d H:i:s');
            $userole = session()->get('user_role');

            // Release the file-session lock early (read-only grid).
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            ignore_user_abort(false);
            if (connection_aborted()) {
                return $this->response->setStatusCode(499)->setBody('');
            }

            $details = $this->user_model->where(['id' => $userId])->first();
            if (!$details) {
                return $this->response->setStatusCode(401)->setJSON([
                    'draw' => (int) $this->request->getPost('draw'),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Session expired',
                ]);
            }

            $emp_admin_id = $details->admin_id;
            $area_id = $details->area_id;
            $area_filter = $this->request->getPost('area_filter');
            $package_filter = $this->request->getPost('package_filter');
            $connection_filter = $this->request->getPost('connection_filter');
            $acc_status_filter = $this->request->getPost('acc_status_filter');
            $db = \Config\Database::connect();

            // Minimal joins only — package/payment enrichment was making this
            // endpoint hang under load and leave the skeleton forever.
            $data = $db->table('users')
                ->select('users.id, users.name, users.mobile, users.address, users.area_id, users.router_id, users.package_id')
                ->select('users.created_by, users.will_expire, users.last_renewed, users.subscription_status')
                ->select('users.activity, users.status, users.conn_status, users.created_at')
                ->select('user_router_data.pppoe_secret AS pppoe_secret, user_router_data.router_password AS router_password')
                ->select('areas.area_name AS area_name, routers.name AS router_name, users.status AS acc_status')
                ->select('COALESCE(packages.package_name, reseller_packages.package_name, "--") AS joined_package_name', false)
                ->select('COALESCE(packages.price, reseller_packages.selling_price, reseller_packages.price, "--") AS joined_package_price', false)
                ->join('user_router_data', 'user_router_data.user_id = users.id', 'left')
                ->join('areas', 'areas.id = users.area_id', 'left')
                ->join('routers', 'routers.id = users.router_id', 'left')
                ->join('packages', 'packages.id = users.package_id', 'left')
                ->join('reseller_packages', 'reseller_packages.id = users.package_id', 'left')
                ->where('users.role', 'user')
                ->where('users.will_expire <', $currrentDate)
                ->groupStart()
                    ->where('users.conn_status !=', 'disconn')
                    ->orWhere('users.conn_status', null)
                ->groupEnd();

            if ($userole === 'employee') {
                if (!is_array($area_id)) {
                    $area_id = explode(',', (string) $area_id);
                }
                $area_id = array_values(array_filter(array_map('trim', $area_id), static fn ($v) => $v !== ''));
                $data->where('users.admin_id', $emp_admin_id);
                if (!empty($area_id)) {
                    $data->whereIn('users.area_id', $area_id);
                }
            } else {
                $data->where('users.admin_id', $userId);
            }

            if ($area_filter) {
                $data->where('users.area_id', $area_filter);
            }
            if ($package_filter) {
                $data->where('users.package_id', $package_filter);
            }
            // UI sends active|inactive; DB stores activity=active for online.
            if ($connection_filter === 'active') {
                $data->where('users.activity', 'active');
            } elseif ($connection_filter === 'inactive') {
                $data->groupStart()
                    ->where('users.activity !=', 'active')
                    ->orWhere('users.activity', null)
                    ->orWhere('users.activity', '')
                ->groupEnd();
            }
            if ($acc_status_filter === 'conn') {
                $data->where('users.conn_status', 'conn');
            }

            $datatables = new DataTables($data);
            $datatables->addSequenceNumber('serial');

            $datatables->addColumn('mobile', function ($row) {
                $phone = trim($row->mobile ?? '');
                $callLink = $phone ? 'tel:' . preg_replace('/\D/', '', $phone) : '#';
                $cleanPhone = preg_replace('/\D/', '', $phone);
                if (strpos($phone, '+88') === 0) {
                    $formattedPhone = $cleanPhone;
                } elseif (strpos($phone, '0') === 0) {
                    $formattedPhone = '+880' . substr($cleanPhone, 1);
                } elseif ($phone) {
                    $formattedPhone = '+880' . substr($cleanPhone, 1);
                } else {
                    $formattedPhone = '';
                }
                $whatsappLink = $formattedPhone ? 'https://wa.me/' . $formattedPhone : '#';

                return '<div style="display:flex; justify-content:space-between; align-items:center; min-width:180px; background:var(--success-100, #dcfce7); color:var(--success-600, #15803d); border-radius:6px; padding:4px;">
                <span style="background:var(--success-100, #dcfce7); color:var(--success-600, #15803d); padding:2px 8px; border-radius:50px; display:inline-flex; align-items:center;">'
                    . ($phone ?: '-') .
                    '</span>
                <div style="display:flex; gap:8px;">
                    <a href="' . $callLink . '" style="color:var(--success-600, #15803d); font-size:20px; margin-top: 2px; " title="Call"' . (!$phone ? ' disabled' : '') . '>
                        <i class="fa-solid fa-phone"></i>
                    </a>
                    <a href="' . $whatsappLink . '" target="_blank" style="color:var(--success-600, #15803d); font-size:24px;" title="WhatsApp"' . (!$phone ? ' disabled' : '') . '>
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                </div>
            </div>';
            });

            if (userHasPermission('customer', 'delete')) {
                $datatables->addColumn('select', function ($row) {
                    return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
                });
            }

            $datatables->addColumn('name', function ($row) {
                return '<a style="background-color:var(--info-100, #dbeafe); color:var(--info-600, #1d4ed8); padding:4px 8px; border-radius:6px; font-weight:500; text-decoration:none;" href="'
                    . route_to('route.customer.details', $row->id) . '">'
                    . esc($row->name ?? '-') . '</a>';
            });

            $datatables->addColumn('package', function ($row) {
                $name = $row->joined_package_name ?? '--';
                $price = $row->joined_package_price ?? '--';
                return '<div style="line-height:1.2;">
                <strong style="font-size:16px;">' . htmlspecialchars((string) $name) . '</strong><br>
                <span style="color:black; font-size:16px; margin-top:2px; display:block;">৳' . htmlspecialchars((string) $price) . '</span>
            </div>';
            });

            $datatables->addColumn('area_name', function ($row) {
                return $row->area_name ?? '--';
            });
            $datatables->addColumn('router_name', function ($row) {
                return $row->router_name ?? '--';
            });
            $datatables->addColumn('pppoe_secret', function ($row) {
                return $row->pppoe_secret ?? '---';
            });
            $datatables->addColumn('router_password', function ($row) {
                return $row->router_password ?? '---';
            });

            $datatables->addColumn('conn_status', function ($row) {
                return ($row->activity === 'active')
                    ? '<span class="badge label-success">Online</span>'
                    : '<span class="badge label-danger">Offline</span>';
            });

            $datatables->addColumn('payment_expiry_sort', function ($row) {
                $url = route_to('route.customer.payment.user', $row->id);
                if (!empty($row->will_expire) && date('Y', strtotime($row->will_expire)) === '2050') {
                    return '<a href="' . $url . '" style="text-decoration:none;">
                            <span style="background:var(--info-100, #e0f2fe); color:var(--info-700, #0369a1); padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">Free</span></a>';
                }
                $daysAgo = 0;
                if (!empty($row->will_expire)) {
                    $daysAgo = (int) abs(\Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($row->will_expire), false));
                }
                return '<a href="' . $url . '" style="text-decoration:none;">
                        <span style="background:#fee2e2; color:#ef4a31ff; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">expired (' . $daysAgo . ' days ago)</span></a>';
            });

            $datatables->addColumn('acc_status', function ($row) {
                $checked = ($row->conn_status ?? '') === 'conn' ? 'checked' : '';
                return '<div style="display: flex; justify-content: center; align-items: center;">
                    <label class="toggle-switch">
                        <input type="checkbox" class="conn-switch" data-id="' . $row->id . '" ' . $checked . '>
                        <span class="slider"></span>
                    </label>
                </div>';
            });

            $datatables->addColumn('action', function ($row) {
                return $this->renderCustomerListActions($row);
            });

            $datatables->addColumnAliases([
                'users.id' => 'id',
                'users.name' => 'name',
                'users.package_id' => 'package',
                'areas.area_name' => 'area_name',
                'users.mobile' => 'mobile',
                'users.address' => 'address',
                'routers.name' => 'router_name',
                'user_router_data.pppoe_secret' => 'pppoe_secret',
                'user_router_data.router_password' => 'router_password',
                'users.will_expire' => 'payment_expiry_sort',
                'users.activity' => 'conn_status',
                'users.status' => 'acc_status',
            ]);
            $datatables->asObject();

            if (connection_aborted()) {
                return $this->response->setStatusCode(499)->setBody('');
            }

            $out = $datatables->generate();
            log_message('info', 'expired_fetch done ms=' . round((microtime(true) - $started) * 1000, 1));
            return $out;
        } catch (\Throwable $e) {
            log_message('error', 'expired_fetch failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'draw' => (int) $this->request->getPost('draw'),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load expired customers',
            ]);
        }
    }

    public function new_fetch()
    {
        $userId = session()->get('user_id');
        // log_message('info', 'Expired Fetch called by User ID: ' . $userId);
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currrentDate = date('Y-m-d H:i:s');

        $userole = session()->get('user_role');

        // Release the file-session lock early (read-only grid; session is only
        // read here + in row closures, never written). (Phase 2 / T3)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $details = $this->user_model->where(['id' => $userId])->first();
        $emp_admin_id = $details->admin_id;
        $area_id = $details->area_id;

        $area_filter = $this->request->getPost('area_filter');
        $package_filter = $this->request->getPost('package_filter');
        $connection_filter = $this->request->getPost('connection_filter');
        $acc_status_filter = $this->request->getPost('acc_status_filter');
        $db = \Config\Database::connect();
        if ($userole === 'employee') {

            $data = $db->table('users')
                ->select('users.*, users.id AS id, user_router_data.pppoe_secret AS pppoe_secret, user_router_data.router_password AS router_password')
                ->select('areas.area_name AS area_name, routers.name AS router_name, users.status AS acc_status')
                ->join('user_router_data', 'user_router_data.user_id = users.id', 'left')
                ->join('areas', 'areas.id = users.area_id', 'left')
                ->join('routers', 'routers.id = users.router_id', 'left')
                ->where([
                    'users.role' => 'user',
                    'users.admin_id' => $emp_admin_id,
                    'users.area_id' => $area_id,
                ])
                ->where('MONTH(users.created_at)', $currentMonth)
                ->where('YEAR(users.created_at)', $currentYear);
        } else {
            $data = $db->table('users')
                ->select('users.*, users.id AS id, user_router_data.pppoe_secret AS pppoe_secret, user_router_data.router_password AS router_password')
                ->select('areas.area_name AS area_name, routers.name AS router_name, users.status AS acc_status')
                ->join('user_router_data', 'user_router_data.user_id = users.id', 'left')
                ->join('areas', 'areas.id = users.area_id', 'left')
                ->join('routers', 'routers.id = users.router_id', 'left')
                ->where([
                    'users.role' => 'user',
                    'users.admin_id' => $userId,
                ])
                ->where('MONTH(users.created_at)', $currentMonth)
                ->where('YEAR(users.created_at)', $currentYear);
        }

        if ($area_filter)
            $data->where('users.area_id', $area_filter);
        if ($package_filter)
            $data->where('users.package_id', $package_filter);
        if ($connection_filter)
            $data->where('users.activity', $connection_filter);
        if ($acc_status_filter)
            $data->where('users.conn_status', $acc_status_filter);

        // if ($userole === 'employee') {
        //     $data = $this->user_model->builder()
        //         ->select('*')
        //         ->where('role', 'user')
        //         ->where('admin_id', $emp_admin_id)
        //         ->where('subscription_status', 'inactive')
        //         // ->where('conn_status', 'disconn')
        //         ->orderBy('id', 'desc');
        // } else {
        //     $data = $this->user_model->builder()
        //         ->select('*')
        //         ->where('role', 'user')
        //         ->where('admin_id', $userId)
        //         ->where('subscription_status', 'inactive')
        //         // ->orWhere('conn_status', 'disconn')
        //         // ->orWhere('status', 'inactive')
        //         ->orderBy('id', 'desc');
        // }




        $datatables = new DataTables($data);
        $datatables->except(['pppoe_secret', 'router_password']);

        $datatables->addSequenceNumber('serial');

        $datatables->addColumn('mobile', function ($row) {
            $phone = trim($row->mobile ?? '');
            $callLink = $phone ? 'tel:' . preg_replace('/\D/', '', $phone) : '#';
            $cleanPhone = preg_replace('/\D/', '', $phone);
            if (strpos($phone, '+88') === 0) {
                $formattedPhone = $cleanPhone;
            } elseif (strpos($phone, '0') === 0) {
                $formattedPhone = '+880' . substr($cleanPhone, 1);
            } elseif ($phone) {
                $formattedPhone = '+880' . substr($cleanPhone, 1);
            } else {
                $formattedPhone = '';
            }
            $whatsappLink = $formattedPhone ? 'https://wa.me/' . $formattedPhone : '#';

            return '<div style="display:flex; justify-content:space-between; align-items:center; min-width:180px; background:var(--success-100, #dcfce7); color:var(--success-600, #15803d); border-radius:6px; padding:4px;">
                <span style="background:var(--success-100, #dcfce7); color:var(--success-600, #15803d); padding:2px 8px; border-radius:50px; display:inline-flex; align-items:center;">'
                . ($phone ?: '-') .
                '</span>
                <div style="display:flex; gap:8px;">
                    <a href="' . $callLink . '" style="color:var(--success-600, #15803d); font-size:20px; margin-top: 2px; " title="Call"' . (!$phone ? ' disabled' : '') . '>
                        <i class="fa-solid fa-phone"></i>
                    </a>
                    <a href="' . $whatsappLink . '" target="_blank" style="color:var(--success-600, #15803d); font-size:24px;" title="WhatsApp"' . (!$phone ? ' disabled' : '') . '>
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                </div>
            </div>';
        });


        if (userHasPermission('customer', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('package', function ($row) {

            $role = $row->created_by;

            if ($role === 'resellerAdmin') {
                $package = getUserPackage($row->id); // returns array
                $name = $package['package_name'] ?? '--';

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
                    $price = $base_price ?? '--';
                }

                // Standard design for resellerAdmin
                // Standard design for regular users
                return '<div style="line-height:1.2;">
                    <strong style="font-size:16px;">' . htmlspecialchars($name) . '</strong><br>
                    <span style="color:black; font-size:16px; margin-top:2px; display:block;">৳' . htmlspecialchars($price) . '</span>
                </div>';
            }

            $package = getUserPackage($row->id); // returns object
            $name = $package->package_name ?? '--';
            $price = $package->price ?? '--';

            // Standard design for regular users
            return '<div style="line-height:1.2;">
                <strong style="font-size:16px;">' . htmlspecialchars($name) . '</strong><br>
                <span style="color:black; font-size:16px; margin-top:2px; display:block;">৳' . htmlspecialchars($price) . '</span>
            </div>';
        });
        $datatables->format('created_at', function ($value) {

            return date("d-m-Y, h:i a", strtotime($value));
        });

        $datatables->addColumn('area_name', function ($row) {
            return $row->area_name ?? '--';
        });

        $datatables->addColumn('router_name', function ($row) {
            return $row->router_name ?? '--';
        });

        $datatables->addColumn('pppoe_secret', function ($row) {
            return $row->pppoe_secret ?? '---';
        });

        $datatables->addColumn('router_password', function ($row) {
            return $row->router_password ?? '---';
        });





        // $datatables->format('auto_disconnect', function ($value) {

        //     return ($value === 'yes') ? '<span class="badge label-success">Yes</span>' : '<span class="badge label-danger">No</span>';
        // });

        $datatables->addColumn('conn_status', function ($row) {
            // // log_message, 'Row Data: ' . print_r($row, true));

            return ($row->activity === "active") ? '<span class="badge label-success">Online</span>' : '<span class="badge label-danger">Offline</span>';
        });

        $datatables->addColumn('payment_expiry_sort', function ($row) {
            $today = \Carbon\Carbon::today();
            $willExpire = \Carbon\Carbon::parse($row->will_expire);
            $daysRemaining = $today->diffInDays($willExpire, false); // can be negative

            $now = date('Y-m-d');
            $renewalDate = $row->last_renewed;
            $subscriptionEnd = $row->will_expire;

            // Last payment month (safe)
            $lastPaymentMonth = !empty($renewalDate)
                ? date('F', strtotime($renewalDate))
                : null;

            // Expiring month (safe)
            $expiringMonth = !empty($subscriptionEnd)
                ? date('F', strtotime($subscriptionEnd))
                : null;

            // log_message, 'name : ' . $row->name . 'Renewal Date: ' . $renewalDate);
            // log_message, 'name : ' . $row->name . 'Subscription End: ' . $subscriptionEnd);
            // log_message, 'name : ' . $row->name . ' Last Payment Month: ' . $lastPaymentMonth);
            // log_message, 'name : ' . $row->name . ' Expiring Month: ' . $expiringMonth);


            // Check payment statuses only if months are valid
            $initialStatus = $lastPaymentMonth
                ? checkPaymentStatus($row->id, $lastPaymentMonth)
                : 'Unknown';

            $finalStatus = $expiringMonth
                ? checkPaymentStatus($row->id, $expiringMonth)
                : 'Unknown';


            // // log_message, 'Last Payment Month: ' . $lastPaymentMonth);
            // // log_message, 'Expiring Month: ' . $expiringMonth);
            // log_message, 'name : ' . $row->name . 'Initial Status:  ' . $initialStatus);
            // log_message, 'name : ' . $row->name . 'Final Status: ' . $finalStatus);

            $bothUnknown =
                ($initialStatus === 'Unknown' || $initialStatus === null) &&
                ($finalStatus === 'Unknown' || $finalStatus === null);

            $url = route_to('route.customer.payment.user', $row->id);

            if (!empty($row->will_expire) && date('Y', strtotime($row->will_expire)) === '2050') {
                return '<a href="' . $url . '" style="text-decoration:none;">
                            <span style="background:var(--info-100, #e0f2fe); color:var(--info-700, #0369a1); padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                Free
                            </span>
                        </a>';
            }

            if ($row->subscription_status === 'active') {
                if (strtotime($now) <= strtotime($subscriptionEnd)) {
                    if ($bothUnknown || $initialStatus === 'successful' || $finalStatus === 'successful') {
                        $bg = '#dcfce7';
                        $color = '#15803d';
                        return '<a href="' . $url . '" style="text-decoration:none;">
                                    <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                        paid (' . $daysRemaining . ' left)
                                    </span>
                                </a>';
                    } else {
                        $bg = '#fef3c7';
                        $color = '#b45309';
                        return '<a href="' . $url . '" style="text-decoration:none;">
                                    <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                        due (' . $daysRemaining . ' left)
                                    </span>
                                </a>';
                    }
                } else {
                    $bg = '#fee2e2';
                    $color = '#ef4a31ff';
                    return '<a href="' . $url . '" style="text-decoration:none;">
                                <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                                    expired (' . abs($daysRemaining) . ' days ago)
                                </span>
                            </a>';
                }
            }


            $bg = '#fee2e2';
            $color = '#dd1717ff';
            return '<a href="' . $url . '" style="text-decoration:none;">
                        <span style="background:' . $bg . '; color:' . $color . '; padding:2px 8px; border-radius:50px; font-weight:500; cursor:pointer;">
                            Inactive
                        </span>
                    </a>';
        });



        $datatables->addColumn('area_name', function ($row) {
            return $row->area_name ?? '--';
        });

        $datatables->addColumn('router_name', function ($row) {
            return $row->router_name ?? '--';
        });

        $datatables->addColumn('pppoe_secret', function ($row) {
            return $row->pppoe_secret ?? '---';
        });

        $datatables->addColumn('router_password', function ($row) {
            return $row->router_password ?? '---';
        });

        $datatables->addColumn('acc_status', function ($row) {
            $checked = $row->conn_status === 'conn' ? 'checked' : '';
            return '
                <div style="display: flex; justify-content: center; align-items: center;">
                    <label class="toggle-switch">
                        <input type="checkbox" class="conn-switch" data-id="' . $row->id . '" ' . $checked . '>
                        <span class="slider"></span>
                    </label>
                </div>
            ';
        });

        // $datatables->addColumn('status', function ($row) {

        //     return ($row->conn_status === 'conn') ? '<span class="badge label-success">Active</span>' : '<span class="badge label-danger">Inactive</span>';
        // });

        $datatables->addColumn('action', function ($row) {
            return $this->renderCustomerListActions($row);
        });

        // $datatables->except([
        //     'id',
        //     'area_id',
        //     'router_id',
        //     'package_id',
        //     'designation',
        //     'last_renewed',
        //     'will_expire',
        //     'subscription_status',
        //     'pppoe_id',
        //     'address',
        //     'email',
        //     'role',
        //     'password',
        //     'updated_at',
        //     'admin_id',
        // ]);

        $datatables->addColumnAliases([
            'users.id' => 'id',
            'users.name' => 'name',
            'users.package_id' => 'package',
            'areas.area_name' => 'area_name',
            'users.mobile' => 'mobile',
            'users.address' => 'address',
            'routers.name' => 'router_name',
            'user_router_data.pppoe_secret' => 'pppoe_secret',
            'user_router_data.router_password' => 'router_password',
            'users.will_expire' => 'payment_expiry_sort',
            'users.activity' => 'conn_status',
            'users.status' => 'acc_status',
        ]);

        $datatables->asObject();

        return $datatables->generate();
    }


    public function exportCustomers()
    {
        // Fetch customer data as per your logic
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        $builder = $this->user_model->builder()
            ->select('*')
            ->where('role', 'user');

        if ($userole === 'super_admin') {
            // Admin sees all
        } elseif ($userole === 'employee') {
            $details = $this->user_model->where(['id' => $userId])->first();
            $emp_admin_id = $details->admin_id;
            $area_id = $details->area_id;
            if (!is_array($area_id)) {
                $area_id = explode(',', $area_id);
            }
            $area_id = array_filter(array_map('trim', $area_id));
            $builder->where('admin_id', $emp_admin_id);
            if (!empty($area_id)) {
                $builder->whereIn('area_id', $area_id);
            }
        } else {
            // resellerAdmin and sAdmin see their own
            $builder->where('admin_id', $userId);
        }

        $customers = $builder->orderBy('id', 'desc')->get()->getResult();

        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set the headers for the Excel file
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Customer Name');
        $sheet->setCellValue('C1', 'Package');
        $sheet->setCellValue('D1', 'Mobile');
        $sheet->setCellValue('E1', 'Router');
        $sheet->setCellValue('F1', 'PPPoE Secret');
        $sheet->setCellValue('G1', 'PPPoE Password');
        $sheet->setCellValue('H1', 'Conn. Status');
        $sheet->setCellValue('I1', 'Acc. Status');
        $sheet->setCellValue('J1', 'PPPOE ID');
        $sheet->setCellValue('K1', 'Email');


        // Add customer data to the sheet
        $rowNumber = 2;
        foreach ($customers as $customer) {
            $reseller_pkg = getUserPackage($customer->id);
            $sheet->setCellValue('A' . $rowNumber, $customer->id);
            $sheet->setCellValue('B' . $rowNumber, $customer->name);
            $sheet->setCellValue('C' . $rowNumber, $reseller_pkg->package_name ?? $reseller_pkg['package_name'] ?? '--');
            $sheet->setCellValue('D' . $rowNumber, $customer->mobile);
            $sheet->setCellValue('E' . $rowNumber, getRouterById($customer->router_id)->name ?? '--');
            $sheet->setCellValue('F' . $rowNumber, getRouterPassById($customer->id)['pppoe_secret'] ?? '--');
            $sheet->setCellValue('G' . $rowNumber, getRouterPassById($customer->id)['router_password'] ?? '--');
            $sheet->setCellValue('H' . $rowNumber, $customer->conn_status === 'conn' ? 'Connected' : 'Disconnected');
            $sheet->setCellValue('I' . $rowNumber, $customer->status === 'active' ? 'Active' : 'Inactive');
            $sheet->setCellValue('J' . $rowNumber, $customer->pppoe_id ?? '--');
            $sheet->setCellValue('K' . $rowNumber, $customer->email ?? '--');


            $rowNumber++;
        }
        // // log_message, 'System response getUserPackage: ' . print_r(getUserPackage($customer->id), true));

        // Create an Excel file and prompt for download
        $filename = 'customers_' . date('Ymd') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }


    /**
     * Customers
     * @action: New Customer
     */
    public function new()
    {
        $area_model = model('App\Models\Area');
        $package_model = model('App\Models\Package');
        $AdminPackage = model('App\Models\AdminPackage');

        $userId = session()->get('user_id');

        $userModel = model('App\Models\User');
        $details = $userModel->where(['id' => $userId])->first();

        if ($details === null) {
            return redirect()->to(route_to('route.auth.login'))->with('error', 'Session expired. Please log in again.');
        }

        $packages = null;
        $area = null;
        $routers = null;

        $referralId = (int) trim((string) (service('request')->getGet('referral_id') ?? ''));
        if ($referralId > 0) {
            $referralSetup = $this->loadReferralNewCustomerContext($referralId, $details, $area_model, $package_model, $AdminPackage);
            if ($referralSetup instanceof \CodeIgniter\HTTP\RedirectResponse) {
                return $referralSetup;
            }
            if (is_array($referralSetup)) {
                $packages = $referralSetup['packages'];
                $area = $referralSetup['areas'];
                $routers = $referralSetup['routers'];
            }
        }

        $role = $details->role;
        $admin_id = $details->admin_id;
        $router = $details->router_id;
        $status = $details->status;

        if ($packages === null) {
        // log_message('info', 'Fetched user data: ' . json_encode($details));

        if ($role === 'admin') {
            if ($details === null || $details->status === 'inactive' || $details->subscription_status === 'inactive' || $details->conn_status != 'conn') {
                return requestResponse('error', "Your account is not active.Update your account to create new customer", 500);
            }
            $count = $this->user_model->builder()
                ->where('role', 'user')
                ->where('admin_id', $userId)
                ->countAllResults();

            //get resellers customners,,
            $resellers = $this->user_model
                ->where('role', 'resellerAdmin')
                ->where('admin_id', $userId)
                ->findAll();

            // Extract reseller IDs
            $resellerIds = array_column($resellers, 'id');

            // Initialize total count
            $totalCount = $count;

            // Loop through each reseller ID and count users
            foreach ($resellerIds as $id) {
                $counts = $this->user_model->builder()
                    ->where('role', 'user')
                    ->where('admin_id', $id)
                    ->countAllResults();

                $totalCount += $counts; // Add count to total
            }

            // Log the total count
            // log_message('info', 'Total user count for all resellers: ' . $totalCount);

            $data['posPrinter'] = $totalCount;
            $result = $this->user_model->where(['id' => $userId])->set($data)->update();




            $package = $AdminPackage->select('duration')
                ->where('id', $details->package_id)
                ->first();

            // log_message('info', 'Fetched count data: ' . json_encode($count));
            // log_message('info', 'Fetched package data: ' . json_encode($package));
            if ($status === 'inactive') {
                // log_message('info', 'Fetched count data: equal');

                //  return requestResponse('error', "You are at your limit ! Update your package to create new customer", 500);

                return requestResponse('error', [
                    'message' => "You are currently unable to add customer! Contact your admin.",
                    'limitReached' => true
                ], 500);
            }
            if (!empty($package['duration']) && $totalCount >= $package['duration']) {
                // log_message('info', 'Fetched count data: equal');

                //  return requestResponse('error', "You are at your limit ! Update your package to create new customer", 500);

                return requestResponse('error', [
                    'message' => "You are at your limit......! Update your package to create a new customer.",
                    'limitReached' => true
                ], 500);
            }


            // log_message('info', 'resellerAdmin: ' );

            $packages = $package_model
                ->where('user_id', $userId)
                ->findAll();

            $area = $area_model->where('status', 'active')->where('user_id', $userId)->findAll();
            // log_message('info', 'Fetched package_model data: ' . json_encode($package_model));

            // For resellerAdmin, filter routers by router_id
            $routers = $this->router_model->where('status', 'active')
                ->where('user_id', $userId)
                ->findAll();
        } elseif ($role === 'resellerAdmin') {



            $details = $userModel->where(['id' => $admin_id])->first();

            if ($details === null || $details->status === 'inactive' || $details->subscription_status === 'inactive' || $details->conn_status != 'conn') {
                return requestResponse('error', "Your account is not active.Update your account to create new customer", 500);
            }
            $count = $this->user_model->builder()
                ->where('role', 'user')
                ->where('admin_id', $admin_id)
                ->countAllResults();
            // log_message('info', 'Total user count for all resellers: ' . $count);

            //get resellers customners,,
            $resellers = $this->user_model
                ->where('role', 'resellerAdmin')
                ->where('admin_id', $admin_id)
                ->findAll();

            // Extract reseller IDs
            $resellerIds = array_column($resellers, 'id');
            // log_message('info', 'Reseller IDs: ' . json_encode($resellerIds));
            // Initialize total count

            $totalCount = $count;

            // Loop through each reseller ID and count users
            foreach ($resellerIds as $id) {
                $counts = $this->user_model->builder()
                    ->where('role', 'user')
                    ->where('admin_id', $id)
                    ->countAllResults();

                $totalCount += $counts; // Add count to total
            }

            // Log the total count
            // log_message('info', 'Total user count for all resellers: ' . $totalCount);

            $data['posPrinter'] = $totalCount;
            $result = $this->user_model->where(['id' => $admin_id])->set($data)->update();



            $package = $AdminPackage->select('duration')
                ->where('id', $details->package_id)
                ->first();

            // log_message('info', 'Fetched count data: ' . json_encode($count));
            // log_message('info', 'Fetched package data: ' . json_encode($package));
            if ($status === 'inactive') {
                // log_message('info', 'Fetched count data: equal');

                //  return requestResponse('error', "You are at your limit ! Update your package to create new customer", 500);

                return requestResponse('error', [
                    'message' => "You are currently unable to add customer! Contact your admin.",
                    'limitReached' => true
                ], 500);
            }



            if (!empty($package['duration']) && $totalCount >= $package['duration']) {
                // log_message('info', 'Fetched count data: equal');

                //  return requestResponse('error', "You are at your limit ! Update your package to create new customer", 500);

                return requestResponse('error', [
                    'message' => "You are at your limit......! Update your package to create a new customer.",
                    'limitReached' => true
                ], 500);
            }






            // $pmodel = model('App\Models\ResellerPackages');
            // // log_message('info', 'resellerAdmin: ' );

            // $packages = $pmodel
            //     ->where('user_id', $admin_id)
            //     ->findAll();

            $packageModel = model('App\Models\allResellerPackage');
            $rawPackages = $packageModel->where('user_id', $userId)->findAll();

            // Decode the package_details JSON field
            $packages = [];
            foreach ($rawPackages as $package) {
                $detailsArr = is_string($package['package_details']) 
                    ? json_decode($package['package_details'], true) 
                    : $package['package_details'];
                if (is_array($detailsArr)) {
                    foreach ($detailsArr as $details) {
                        $packages[] = $details;
                    }
                }
            }

            $area = $area_model->where('status', 'active')->where('user_id', $userId)->findAll();
            // log_message('info', 'Fetched package_model data: ' . json_encode($package_model));

            // For resellerAdmin, filter routers by router_id
            $routers = $this->router_model->where('status', 'active')
                ->where('id', $router)
                ->findAll();
        } else {
            $created_by = (string) ($details->created_by ?? '');
            if ($created_by === 'admin') {
                $userId = (int) ($details->admin_id ?? 0);
                if ($userId <= 0) {
                    return requestResponse('error', 'Admin account is not linked to your profile.', 500);
                }
                $details = $userModel->where(['id' => $userId])->first();
                if ($details === null || $details->status === 'inactive' || $details->subscription_status === 'inactive' || $details->conn_status != 'conn') {
                    return requestResponse('error', "Your account is not active.Update your account to create new customer", 500);
                }
                $count = $this->user_model->builder()
                    ->where('role', 'user')
                    ->where('admin_id', $userId)
                    ->countAllResults();

                //get resellers customners,,
                $resellers = $this->user_model
                    ->where('role', 'resellerAdmin')
                    ->where('admin_id', $userId)
                    ->findAll();

                // Extract reseller IDs
                $resellerIds = array_column($resellers, 'id');

                // Initialize total count
                $totalCount = $count;

                // Loop through each reseller ID and count users
                foreach ($resellerIds as $id) {
                    $counts = $this->user_model->builder()
                        ->where('role', 'user')
                        ->where('admin_id', $id)
                        ->countAllResults();

                    $totalCount += $counts; // Add count to total
                }

                // Log the total count
                // log_message('info', 'Total user count for all resellers: ' . $totalCount);

                $data['posPrinter'] = $totalCount;
                $result = $this->user_model->where(['id' => $userId])->set($data)->update();




                $package = $AdminPackage->select('duration')
                    ->where('id', $details->package_id)
                    ->first();

                // log_message('info', 'Fetched count data: ' . json_encode($count));
                // log_message('info', 'Fetched package data: ' . json_encode($package));
                if ($status === 'inactive') {
                    // log_message('info', 'Fetched count data: equal');

                    //  return requestResponse('error', "You are at your limit ! Update your package to create new customer", 500);

                    return requestResponse('error', [
                        'message' => "You are currently unable to add customer! Contact your admin.",
                        'limitReached' => true
                    ], 500);
                }
                if (!empty($package['duration']) && $totalCount >= $package['duration']) {
                    // log_message('info', 'Fetched count data: equal');

                    //  return requestResponse('error', "You are at your limit ! Update your package to create new customer", 500);

                    return requestResponse('error', [
                        'message' => "You are at your limit......! Update your package to create a new customer.",
                        'limitReached' => true
                    ], 500);
                }

                $packages = $package_model
                    ->where('user_id', $userId)
                    ->findAll();

                $area = $area_model->where('status', 'active')->where('user_id', $userId)->findAll();

                $routers = $this->router_model->where('status', 'active')
                    ->where('user_id', $userId)
                    ->findAll();
            } else {
                $resellerId = (int) ($admin_id ?: ($details->admin_id ?? 0));
                if ($resellerId <= 0) {
                    return requestResponse('error', 'Reseller admin is not linked to your account.', 500);
                }

                $resellerAdmin = $userModel->where(['id' => $resellerId])->first();
                if ($resellerAdmin === null) {
                    return requestResponse('error', 'Reseller admin account not found.', 500);
                }

                $sAdminId = (int) ($resellerAdmin->admin_id ?? 0);
                if ($sAdminId <= 0) {
                    return requestResponse('error', 'Parent admin is not configured correctly.', 500);
                }

                $userId = $resellerId;
                $admin_id = $sAdminId;
                $details = $userModel->where(['id' => $admin_id])->first();

                if ($details === null || $details->status === 'inactive' || $details->subscription_status === 'inactive' || $details->conn_status != 'conn') {
                    return requestResponse('error', "Your account is not active.Update your account to create new customer", 500);
                }
                $count = $this->user_model->builder()
                    ->where('role', 'user')
                    ->where('admin_id', $admin_id)
                    ->countAllResults();
                // log_message('info', 'Total user count for all resellers: ' . $count);

                //get resellers customners,,
                $resellers = $this->user_model
                    ->where('role', 'resellerAdmin')
                    ->where('admin_id', $admin_id)
                    ->findAll();

                // Extract reseller IDs
                $resellerIds = array_column($resellers, 'id');
                // log_message('info', 'Reseller IDs: ' . json_encode($resellerIds));
                // Initialize total count

                $totalCount = $count;

                // Loop through each reseller ID and count users
                foreach ($resellerIds as $id) {
                    $counts = $this->user_model->builder()
                        ->where('role', 'user')
                        ->where('admin_id', $id)
                        ->countAllResults();

                    $totalCount += $counts; // Add count to total
                }

                // Log the total count
                // log_message('info', 'Total user count for all resellers: ' . $totalCount);

                $data['posPrinter'] = $totalCount;
                $result = $this->user_model->where(['id' => $admin_id])->set($data)->update();



                $package = $AdminPackage->select('duration')
                    ->where('id', $details->package_id)
                    ->first();

                // log_message('info', 'Fetched count data: ' . json_encode($count));
                // log_message('info', 'Fetched package data: ' . json_encode($package));
                if ($status === 'inactive') {
                    // log_message('info', 'Fetched count data: equal');

                    //  return requestResponse('error', "You are at your limit ! Update your package to create new customer", 500);

                    return requestResponse('error', [
                        'message' => "You are currently unable to add customer! Contact your admin.",
                        'limitReached' => true
                    ], 500);
                }



                if (!empty($package['duration']) && $totalCount >= $package['duration']) {
                    // log_message('info', 'Fetched count data: equal');

                    //  return requestResponse('error', "You are at your limit ! Update your package to create new customer", 500);

                    return requestResponse('error', [
                        'message' => "You are at your limit......! Update your package to create a new customer.",
                        'limitReached' => true
                    ], 500);
                }






                $pmodel = model('App\Models\ResellerPackages');
                // log_message('info', 'resellerAdmin: ' );

                $packages = $pmodel
                    ->where('user_id', $resellerId)
                    ->findAll();

                $area = $area_model->where('status', 'active')->where('user_id', $userId)->findAll();
                // log_message('info', 'Fetched package_model data: ' . json_encode($package_model));

                // For resellerAdmin, filter routers by router_id
                $routers = $this->router_model->where('status', 'active')
                    ->where('id', $router)
                    ->findAll();
            }
        }

            if ($packages === null) {
                if (strtolower((string) $role) === 'super_admin') {
                    return $this->redirectNewCustomerError('Open a pending referral from Referral & Reward to complete customer setup.');
                }
                return $this->redirectNewCustomerError('You do not have permission to create customers.', base_url('customers'));
            }
        }

        $data = [
            'title' => 'New Customer',
            'areas' => $area ?? null,
            'packages' => $packages ?? null,
            'routers' => $routers ?? null,  // Set routers based on the role
            'referral_prefill' => null,
        ];

        // Referral lead completion — pre-fill from query params + referral record.
        $getParam = static function (string $key): string {
            $v = service('request')->getGet($key);
            return ($v !== null && $v !== '') ? trim((string) $v) : '';
        };

        $referralId = (int) ($getParam('referral_id') ?: ($this->request->getGet('referral_id') ?? 0));
        $paramName    = $getParam('name');
        $paramMobile  = $getParam('mobile');
        $paramEmail   = $getParam('email');
        $paramNid     = $getParam('nid');
        $paramPackage = (int) $getParam('package_id');

        if ($referralId > 0 || $paramName !== '' || $paramMobile !== '') {
            $prefill = [
                'referral_id'    => $referralId,
                'referrer_name'  => '',
                'referral_code'  => '',
                'name'           => $paramName,
                'mobile'         => $paramMobile,
                'email'          => $paramEmail,
                'nid'            => $paramNid,
                'package_id'     => $paramPackage,
            ];

            if ($referralId > 0) {
                try {
                    $refModel = new \Zapi\Modules\Shared\Rewards\Models\ReferralModel();
                    $refRow = $refModel->find($referralId);
                    if ($refRow) {
                        $referrer = $this->user_model->find((int) ($refRow->referrer_id ?? 0));
                        $prefill['referrer_name'] = (string) ($referrer->name ?? '');
                        $prefill['referral_code'] = (string) ($refRow->referral_code ?? '');
                        if ($prefill['name'] === '') {
                            $prefill['name'] = (string) ($refRow->referee_name ?? '');
                        }
                        if ($prefill['mobile'] === '') {
                            $prefill['mobile'] = (string) ($refRow->referee_mobile ?? '');
                        }
                        if ($prefill['email'] === '') {
                            $prefill['email'] = (string) ($refRow->referee_email ?? '');
                        }
                        if ($prefill['nid'] === '') {
                            $prefill['nid'] = (string) ($refRow->referee_nid ?? '');
                        }
                        if ($prefill['package_id'] <= 0) {
                            $prefill['package_id'] = (int) ($refRow->package_id ?? 0);
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'referral prefill: ' . $e->getMessage());
                }
            }

            if ($prefill['name'] !== '' || $prefill['mobile'] !== '' || $referralId > 0) {
                $data['referral_prefill'] = $prefill;
                if ($referralId > 0) {
                    $data['title'] = 'Complete Referral Customer';
                }
            }
        }
        // log_message('info', 'Fetched package data: ' . json_encode($data));


        return view('customers/new', $data);
    }


    public function fetchDataByAreaId()
    {
        $areaId = $this->request->getPost('area_id');
        // Load the AreaSub model
        $subarea_model = model('App\Models\AreaSub');

        // Fetch data from the database where user_id (or whatever column matches areaId) matches the selected areaId
        $data = $subarea_model
            ->select('*') // Select all columns (adjust as needed)
            ->where('user_id', $areaId) // Assuming 'area_id' is the column you want to filter by
            ->orderBy('id', 'desc') // Ordering the data by ID, descending
            ->findAll(); // Retrieve all matching records

        // Log the data for debugging purposes
        log_message('info', 'fetchDataByAreaId data: ' . print_r($data, true));

        // Return the result as a JSON response
        return $this->response->setJSON([
            'status' => 'success',
            'response' => $data
        ]);
    }

    /**
     * Customers
     * @action: New Customer Create
     */
    public function create()
    {
        $referralId = (int) getPostInput('referral_id');
        $isReferralComplete = $referralId > 0;
        $refRow = null;
        $legacyRefereeId = 0;

        if ($isReferralComplete) {
            $refModel = new \Zapi\Modules\Shared\Rewards\Models\ReferralModel();
            $refRow = $refModel->find($referralId);
            if (!$refRow || !in_array($refRow->status ?? '', ['pending', 'flagged'], true)) {
                return requestResponse('error', 'Referral is no longer pending setup.', 400);
            }
            $legacyRefereeId = (int) ($refRow->referee_id ?? 0);
            if ($legacyRefereeId > 0) {
                $legacyUser = $this->user_model->find($legacyRefereeId);
                if (!$legacyUser || ($legacyUser->status ?? '') !== 'pending') {
                    return requestResponse('error', 'Referral customer is no longer pending setup.', 400);
                }
            }
        }

        $validationRules = [
            'name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter Customer\'s name',
                ]
            ],
            'package_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select a package',
                ]
            ],
            'area_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select service area',
                ]
            ],
            // 'sub_area_id' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Select service sub_area',
            //     ]
            // ],
            'mobile' => [
                'rules' => ($isReferralComplete && $legacyRefereeId > 0)
                    ? 'required|is_unique[users.mobile,id,' . $legacyRefereeId . ']'
                    : 'required|is_unique[users.mobile]',
                'errors' => [
                    'required' => 'Enter customer\'s mobile number',
                    'is_unique' => 'Another account is using this number',
                ]
            ],
            'address' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter customer\'s address',
                ]
            ],
            // 'email' => [
            //     // 'rules' => 'required|is_unique[users.email]',
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Enter customer\'s email',
            //         'is_unique' => 'Another account is using this email'
            //     ]
            // ],
            // 'password' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Enter a password',
            //     ]
            // ],
            // 're_password' => [
            //     'rules' => 'required|matches[password]',
            //     'errors' => [
            //         'required' => 'Rewrite the password',
            //         'matches' => 'Passwords doesn\'t matched'
            //     ]
            // ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select account status',
                ]
            ],

            'pppoe_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter a username for the PPPoE account',
                ]
            ],
            'pppoe_password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter a password for the PPPoE account',
                ]
            ],
            'pppoe_service' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select PPPoE service',
                ]
            ],
            // 'otc' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Select OTC',
            //     ]
            // ],
        ];


        $userole = session()->get('user_role');

        // Conditionally add validation for pppoe_profile based on user role
        if ($userole != 'user') {
            $validationRules['pppoe_profile'] = [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select PPPoE profile',
                ]
            ];


            $validationRules['router_id'] = [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select mikrotik router',
                ]
            ];
        }

        // Validate the request
        $this->validate($validationRules);


        if ($this->validation->run()) {

            $userId = session()->get('user_id');

            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $userId])->first();
            $router_id = getPostInput('router_id') ?? $details->router_id;
            $package_id = getPostInput('package_id') ?? $details->package_id;


            $preview = 0;
            $billing_status = getPostInput('billing_status');

            if ($details->role === 'resellerAdmin') {
                $fund = $details->fund ?? 0;
                // // log_message, 'details data: ' . json_encode($details, true));

                // $package_id = $details->package_id;

                $price = ResellerPackagePrice($package_id);
                $preview = (int) ResellerPackagePreview($package_id);
                // log_message, 'preview data: ' . json_encode($preview, true));

                // if ($fund < $price) {
                //     return requestResponse("error", "Dont have enough fund . Please recharge.", 500);
                // }
            }



            $created_by = $details->role;
            $area = $details->area_id;
            $is_reseller_creation = ($created_by === 'resellerAdmin');
            if ($billing_status === 'free' || $billing_status === 'Free') {
                if ($is_reseller_creation) {
                    $will_expire = date('Y-m-d H:i:s', strtotime('+30 days'));
                    log_message('info', 'Reseller created free user, setting 30 days expiry: ' . $will_expire);
                } else {
                    $roleToCheck = strtolower(trim(session()->get('user_role') ?? ''));
                    if ($roleToCheck !== 'admin' && !userHasPermission('customer', 'free_customer_create')) {
                        return requestResponse("error", "You do not have permission to create Free customers.", 403);
                    }
                    $will_expire = '2050-12-31 23:59:59';
                    log_message('info', 'will_expire : ' . json_encode($will_expire));
                }
            } elseif ($created_by === 'resellerAdmin' && $area > 0) {
                log_message('info', 'Area based expiry selected. Area: ' . $area);
                $now = date("Y-m-d H:i:s");
                $today_day = date('d');
                $month = date('m');
                $year = date('Y');
                $time = date('H:i:s');

                // If today's day is greater than $area, move to next month
                if ($today_day > $area) {
                    $month++;
                    if ($month > 12) {
                        $month = 1;
                        $year++;
                    }
                }

                // Handle invalid day for shorter months (like 30 Feb → last day of Feb)
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                if ($area > $days_in_month) {
                    $area = $days_in_month;
                }

                // Build expiry date in same format
                $will_expire = sprintf('%04d-%02d-%02d %s', $year, $month, $area, $time);

                log_message('info', 'will_expire : ' . json_encode($will_expire));

                // echo "Expiry Date: " . $expiry_date;
            } elseif ($created_by === 'resellerAdmin' && $preview >= 0) {
                if ($preview == 0) {
                    // Expire immediately (today)
                    $will_expire = date('Y-m-d H:i:s');
                    log_message('info', 'will_expire : ' . json_encode($will_expire));
                } else {
                    // Expire after $preview days
                    $will_expire = date('Y-m-d H:i:s', strtotime("+$preview days"));
                    log_message('info', 'will_expire : ' . json_encode($will_expire));
                }
            } elseif ($created_by === 'resellerAdmin') {
                $will_expire = calPackageExpireDate($package_id, date('Y-m-d H:i:s'));
            } elseif ($created_by === 'admin') {

                //if custoimer less then then or error
                $will_expire = calsAdminPackageExpireDate($package_id, date('Y-m-d H:i:s'));
            } else {
                $will_expire = calPackageExpireDate($package_id, date('Y-m-d H:i:s'));
            }

            if ($details->role === 'resellerAdmin') {
                $now = date("Y-m-d H:i:s");
                log_message('info', 'Current time: ' . $now);
                if ($billing_status === 'free' || $billing_status === 'Free') {
                    $difference = 0;
                    $new_price = 0.0;
                } else {
                    $difference = floor((strtotime($will_expire) - strtotime($now)) / (60 * 60 * 24));
                    // Phase 5 (MT-1): canonical proration via the BillingService seam.
                    $new_price = round((new \App\Services\BillingService())
                        ->quote((float) $price, (int) $difference), 2);
                }

                $billing_type = $details->billing_type ?? 'postpaid';
                if ($billing_type === 'prepaid' && $fund < $new_price) {
                    return requestResponse("error", "Dont have enough fund . Please recharge.", 500);
                }
            }

            $router_client = routerClient($router_id);


            if ($router_client instanceof \RouterOS\Client) {

                $package_model = model('App\Models\ResellerPackages');
                // For resellerAdmin, filter routers by router_id
                $routers = $package_model->where('status', 'active') // Use the model instance directly
                    ->where('id', $package_id)
                    ->findAll();
                // Ensure there is at least one router returned
                $userole = session()->get('user_role');
                $pppoe_name = getPostInput('pppoe_name');
                $pppoe_password = getPostInput('pppoe_password');

                // Conditionally add validation for pppoe_profile based on user role
                if ($userole === 'resellerAdmin') {
                    // Access the first router's bandwidth
                    $pppoe_profile = getPostInput('pppoe_profile') ?? $routers[0]['bandwidth'];
                } else {
                    // Handle case when no routers are found
                    $pppoe_profile = getPostInput('pppoe_profile');
                }

                $router_action = createPPPoEUser($router_client, [
                    'pppoe_name' => $pppoe_name,
                    'pppoe_password' => $pppoe_password,
                    'pppoe_service' => getPostInput('pppoe_service'),
                    'pppoe_profile' => $pppoe_profile,
                ]);
                $router_actions = [
                    'pppoe_name' => getPostInput('pppoe_name'),
                    'pppoe_password' => getPostInput('pppoe_password'),
                    'pppoe_service' => getPostInput('pppoe_service'),
                    'pppoe_profile' => $pppoe_profile,
                ];

                if ($router_action['status'] === 'error') {
                    // return requestResponse('error', "PPPOE secret with the same name already exists.", 500);
                    return requestResponse('error', $router_action['error'], 500);
                }

                // // log_message, 'router_action data: ' . json_encode($router_action, true));

                if ($router_action['status'] === 'success' || !empty($router_action)) {
                    log_message('info', 'Fetched router_actions data: ' . json_encode($router_actions));
                    $pppoe_id = $router_action['pppoe_id'];

                    $pass = $pppoe_password;

                    $userRole = session()->get('user_role');

                    if ($isReferralComplete && $refRow) {
                        $ownerId = $this->resolveReferralOwnerId($refRow);
                        if ($ownerId > 0) {
                            $userId = $ownerId;
                            $ownerDetails = $userModel->find($ownerId);
                            if ($ownerDetails !== null) {
                                $details = $ownerDetails;
                                $created_by = 'admin';
                            }
                        }
                    } elseif ($userRole === 'employee') {
                        $userId = $details->admin_id;
                        $details = $userModel->where(['id' => $userId])->first();
                        $created_by = $details->role;
                    }
                    $data = [
                        'name' => getPostInput('name'),
                        'package_id' => $package_id,
                        'area_id' => getPostInput('area_id'),
                        'router_id' => getPostInput('router_id') ?? $details->router_id,
                        'mobile' => getPostInput('mobile'),
                        'address' => getPostInput('address'),
                        'email' => !empty(getPostInput('email')) ? getPostInput('email') : $pppoe_name . '@gmail.com',
                        'code' => $pass,
                        'password' => password_hash($pppoe_password, PASSWORD_DEFAULT),
                        'pppoe_id' => $pppoe_id,
                        'will_expire' => $will_expire,
                        'subscription_status' => 'active',
                        'auto_disconnect' => getPostInput('auto_disconnect') ?? 'no',
                        'role' => 'user',
                        'status' => getPostInput('status'),
                        'admin_id' => $userId,
                        'created_by' => $created_by,
                    ];





                    $hasPermission =
                        userHasPermission('Resellers', 'daily_payment_generate') ||
                        userHasPermission('reseller', 'daily_payment_generate');



                    if ($details->role === 'resellerAdmin') {
                        if (!$hasPermission) {


                            // Block overdraw: atomic, race-safe deduction the DB refuses to
                            // take below zero (replaces the unguarded read-modify-write). On a
                            // short balance the fund is left unchanged and logged.
                            if (!empty($userId) && ! (new \App\Services\FundService())->deduct((int) $userId, (float) $new_price)) {
                                log_message('error', "User {$userId} insufficient fund ({$fund}) for price {$new_price}; overdraw blocked, charge not deducted.");
                            }
                        }
                    }

                    if ($isReferralComplete && $refRow) {
                        $nid = trim((string) ($refRow->referee_nid ?? ''));
                        if ($nid !== '') {
                            $data['nid_number'] = $nid;
                        }
                    }

                    if ($isReferralComplete && $legacyRefereeId > 0) {
                        $result = $this->user_model->update($legacyRefereeId, $data);
                        $insertId = $legacyRefereeId;
                    } else {
                        $result = $this->user_model->insert($data, false);
                        $insertId = $this->user_model->getInsertID();
                    }
                    log_message('info', 'Fetched result data: ' . json_encode($data));

                    if (!empty($insertId)) {
                        // Insert into user_router_data
                        $this->user_router_model->insert([
                            'user_id' => $insertId,
                            'router_id' => $data['router_id'],
                            'pppoe_secret' => $pppoe_name,
                            'router_password' => $pppoe_password,
                            'pppoe_profile' => $pppoe_profile ?? null,
                            'last_updated' => date('Y-m-d H:i:s'),
                        ]);
                        $connection_data = [
                            // New Connection Details Fields
                            'user_id' => $insertId ?? '--',
                            'sub_area_id' => getPostInput('sub_area_id') ?? '--',
                            'connection_type' => getPostInput('connection_type'),
                            'cable_requirement' => (getPostInput('cable_requirement') === '') ? null : getPostInput('cable_requirement'),
                            'fiber_code' => getPostInput('fiber_code'),
                            'number_of_core' => (getPostInput('number_of_core') === '') ? null : getPostInput('number_of_core'),
                            'core_color' => getPostInput('core_color'),
                            'client_type' => getPostInput('client_type'),
                            'billing_status' => $billing_status,
                            'otc' => getPostInput('otc') ?? '00',
                            // 'created_at' => date('Y-m-d H:i:s')
                        ];


                        log_message('info', 'Fetched connection_data data: ' . json_encode($connection_data));
                        $ConnectionDetails = model('App\Models\ConnectionData');

                        $result = $ConnectionDetails->insert($connection_data);

                        if ($is_reseller_creation && ($billing_status === 'free' || $billing_status === 'Free')) {
                            $loggedInUserId = session()->get('user_id');
                            $userModel = model('App\Models\User');
                            $loggedInUser = $userModel->find($loggedInUserId);
                            
                            $resellerId = $loggedInUserId;
                            $parentAdminId = $loggedInUser->admin_id;
                            
                            if ($loggedInUser->role === 'employee') {
                                $parentReseller = $userModel->find($loggedInUser->admin_id);
                                if ($parentReseller && $parentReseller->role === 'resellerAdmin') {
                                    $resellerId = $parentReseller->id;
                                    $parentAdminId = $parentReseller->admin_id;
                                }
                            }
                            
                            $freeReqModel = model('App\Models\FreeUserRequest');
                            $freeReqModel->insert([
                                'user_id' => $insertId,
                                'reseller_id' => $resellerId,
                                'admin_id' => $parentAdminId,
                                'status' => 'pending'
                            ]);

                            $parentAdmin = $userModel->find($parentAdminId);
                            if ($parentAdmin) {
                                $subject = "Free User Creation Approval Required";
                                $message = "Hello " . ($parentAdmin->name ?? 'Admin') . ",<br><br>" .
                                           "Reseller <strong>" . ($loggedInUser->name ?? '') . "</strong> has requested to create a Free customer:<br>" .
                                           "Customer Name: " . getPostInput('name') . "<br>" .
                                           "Customer PPPoE Username: " . getPostInput('pppoe_name') . "<br><br>" .
                                           "This user has been created with a temporary 30-day expiration. Please review and approve/reject this request on the Free User Requests page.";
                                sendMail($parentAdmin->email, $subject, $message);
                            }
                        }

                        if ($details->role === 'resellerAdmin') {
                            $transationdata = [
                                'customer' => $insertId,
                                'admin_id' => $userId,
                                'amount' => $new_price ?? '0',
                                'package_price' => $price,
                                'active_for' => $difference,
                                'comments' => 'Single Customer Created'
                            ];
                            log_message('info', 'Fetched transationdata data: ' . json_encode($transationdata));
                            $transationModel = model('App\Models\ResellerTransactions');
                            $result = $transationModel->insert($transationdata);
                        }
                    }



                    $smsFailedMessage = '';
                    try {
                        // event: user_created | default template: 2 (Greetings To Client)
                        sendEventSms('user_created', $data, (int) $userId, 2, $router_actions, $pass);
                    } catch (\Throwable $e) {
                        log_message('error', 'SMS Sending Failed: ' . $e->getMessage());
                        $smsFailedMessage = " (but SMS failed)";
                    }

                    if ($isReferralComplete && $refRow) {
                        try {
                            if ($legacyRefereeId <= 0 && !empty($insertId)) {
                                (new \Zapi\Modules\Shared\Rewards\Models\ReferralModel())->update($referralId, [
                                    'referee_id' => (int) $insertId,
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                            }
                            $approve = (new \Zapi\Modules\Shared\Rewards\Services\ReferralWorkflow())
                                ->approve($referralId, (int) session()->get('user_id'));
                            if (!($approve['ok'] ?? false)) {
                                log_message('warning', 'Referral approve after setup: ' . ($approve['message'] ?? ''));
                            }
                        } catch (\Throwable $e) {
                            log_message('error', 'Referral approve after setup failed: ' . $e->getMessage());
                        }
                        return requestResponse('success', 'Referral customer activated successfully' . $smsFailedMessage, 200);
                    }

                    return requestResponse('success', "New customer record added successfully" . $smsFailedMessage, 200);
                }


                return requestResponse('error', $router_action['error'], 500);
            }



            return requestResponse('error', "Failed to connect to Mikrotik Router. Please check connection.", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Customers
     * @action: Delete Customers
     */
    public function delete()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            /* Nothing here scoped the ids to the caller's tenant, so any user
               holding the customer-delete permission could delete another ISP's
               customers by submitting their ids. Reject the whole batch if any id
               is out of scope, rather than partially deleting. */
            foreach ($ids as $candidateId) {
                $candidate = getUserById($candidateId);
                if (empty($candidate) || !$this->actorOwnsCustomer($candidate)) {
                    log_message('error', 'Blocked cross-tenant customer delete for id ' . json_encode($candidateId));
                    return requestResponse('error', 'Access denied: one or more customers do not belong to your account', 403);
                }
            }

            for ($i = 0; $i < count($ids); $i++) {

                $user = getUserById($ids[$i]);

                if (is_null($user->router_id) || $user->router_id === '0' || $user->router_id === null) {
                    log_message('error', "Router client is null for router_id: " . ($user->router_id ?? 'N/A'));
                    // Skip this iteration
                } else {
                    $router_client = routerClient($user->router_id);
                    if (is_array($router_client)) {
                        return requestResponse('error', $router_client['error'], 500);
                    }

                    $userId = session()->get('user_id');

                    // Fetch the router data
                    $data = $this->router_model->builder()
                        ->select('*')
                        ->where('id', $user->router_id)
                        ->orderBy('id', 'desc')
                        ->get()
                        ->getRow();  // Retrieve a single row

                    log_message('info', 'Fetched router data: ' . json_encode($data));
                    // Check if the router is active
                    if ($data && $data->status === 'active') {
                        log_message('info', 'Fetched $data && $data->status === active data: ');
                        $pppoe = getPPPoEUserUserId($router_client, $user->id);
                        $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                        log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

                        removePPPoEUser($router_client, $pppoe_id);
                    }
                }
            }

            $users = $this->user_model->whereIn('id', $ids)->findAll();
            $result = (new TrashService())->trash('customer', $users);
            if ($result > 0) {
                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected", 400);
    }



    /**
     * Customers
     * @action: Customer Details View
     */

    public function refreshOltData()
    {
        $callerid = $this->request->getGet('callerid');
        log_message('info', 'Received callerid: ' . $callerid);

        $userId = $this->request->getGet('user_id') ?? null;
        $router_id = $this->request->getGet('router_id') ?? null;

        log_message('info', 'Received userId: ' . $userId);

        try {
            $oltController = new OltController();
            $onu = $oltController->getOnuByMac($callerid);

            // Log for debugging if needed
            log_message('info', "Refreshed ONU data for MAC $callerid: " . json_encode($onu));
        } catch (Exception $e) {
            log_message('error', 'Error fetching ONU data: ' . $e->getMessage());
            $onu = null;
        }
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $onu
        ]);
    }
    /**
     * True when the acting session may see/modify this customer record.
     *
     * details()/edit()/delete() looked customers up by id alone, so a tenant admin
     * holding the customer permission could read, edit (edit() renders the
     * plaintext PPPoE password) or delete ANOTHER ISP's customer just by changing
     * the id in the URL. Ownership rules, mirroring the chain the rest of the app
     * already uses: super_admin (platform owner) sees everything; admin and
     * resellerAdmin own the customers whose admin_id is their own id; an admin
     * also owns customers held by resellers beneath them (getSAdminIdForUser walks
     * that chain, exactly as canAccessReseller does); an employee matches the
     * account they report to.
     */
    private function actorOwnsCustomer($customer): bool
    {
        $role = getSession('user_role');
        if ($role === 'super_admin') {
            return true;
        }

        if (empty($customer)) {
            return false;
        }

        $ownerId = (int) (is_object($customer) ? ($customer->admin_id ?? 0) : ($customer['admin_id'] ?? 0));
        $actorId = (int) getSession('user_id');

        if ($ownerId !== 0 && $ownerId === $actorId) {
            return true;
        }

        if ($role === 'employee') {
            $me = $this->user_model->where(['id' => $actorId])->first();
            if ((int) ($me->admin_id ?? 0) === $ownerId && $ownerId !== 0) {
                return true;
            }
        }

        if ($role === 'admin' && $ownerId !== 0) {
            helper('user');
            if (function_exists('getSAdminIdForUser') && (int) getSAdminIdForUser($ownerId) === $actorId) {
                return true;
            }
        }

        return false;
    }

    public function details($id)
    {
        // DB-only first paint — MikroTik is loaded via get_mikrotik_info after HTML.
        $details = $this->user_model->where(['id' => $id, 'role' => 'user'])->first();

        if (empty($details)) {
            return show_404();
        }
        if (!$this->actorOwnsCustomer($details)) {
            log_message('error', 'Blocked cross-tenant customer details view for id ' . $id);
            return show_404();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $usageSince = date('Y-m-d', strtotime('-30 days'));
        $usage_model = model('App\Models\UserDataUsageModel');
        $usage = $usage_model
            ->where('admin_id', $id)
            ->where('date >=', $usageSince)
            ->orderBy('date', 'ASC')
            ->findAll();

        $ConnectionDetails = model('App\Models\ConnectionData');
        $ConnDetails = $ConnectionDetails->where('user_id', $id)->findAll();

        $routerRow = getRouterById($details->router_id);
        $routerNameDb = is_object($routerRow) ? ($routerRow->name ?? '--') : '--';

        $routerDataRow = model('App\Models\UserRouterDataModel')->where(['user_id' => $id])->first();
        $pppoeSecret = $routerDataRow->pppoe_secret ?? ($details->pppoe_id ?? '--');
        $pppoePassword = $routerDataRow->router_password ?? '--';
        $pppoeProfile = $routerDataRow->pppoe_profile ?? '--';

        $userPkg = function_exists('getUserPackage') ? getUserPackage($id) : null;
        $area = function_exists('getUserArea') ? getUserArea($id) : null;
        $subArea = null;
        if (!empty($ConnDetails[0]['sub_area_id']) && function_exists('getUserSubArea')) {
            $subArea = getUserSubArea($ConnDetails[0]['sub_area_id']);
        }

        return view('customers/details', [
            'title' => 'Customer Details',
            'details' => $details,
            'usage' => $usage,
            'interfaces' => [],
            'router' => $routerNameDb,
            'pppoe' => [
                'name' => $pppoeSecret,
                'password' => $pppoePassword,
                'profile' => $pppoeProfile,
                'service' => '--',
                'disabled' => null, // unknown until MikroTik AJAX
            ],
            'active_session' => null,
            'router_name' => '--',
            'ConnDetails' => $ConnDetails,
            'routerdata' => null,
            'callerid' => null,
            'mikrotik_pending' => true,
            'user_package' => $userPkg,
            'user_area' => $area,
            'user_sub_area' => $subArea,
        ]);
    }

    /**
     * Live MikroTik snapshot for the details page (post-paint AJAX).
     * One connect + filtered secret/active prints — no full interface dump / OUI scrape.
     */
    public function get_mikrotik_info()
    {
        $id = (int) ($this->request->getGet('user_id') ?? 0);
        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Missing user_id']);
        }

        $details = $this->user_model->where(['id' => $id, 'role' => 'user'])->first();
        if (empty($details) || !$this->actorOwnsCustomer($details)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Not found']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        ignore_user_abort(false);

        try {
            $client = routerClient($details->router_id);
            if (!($client instanceof \RouterOS\Client)) {
                return $this->response->setJSON([
                    'ok' => false,
                    'offline' => true,
                    'error' => 'Unable to connect to router',
                    'pppoe' => null,
                    'active_session' => null,
                    'callerid' => null,
                    'online' => false,
                ]);
            }

            $routerDataRow = model('App\Models\UserRouterDataModel')->where(['user_id' => $id])->first();
            $pppName = $routerDataRow->pppoe_secret ?? null;
            if ($pppName === null || $pppName === '') {
                $secrets = getPPPoEUserUserId($client, $id);
                $pppName = $secrets[0]['name'] ?? null;
            }

            $secret = [];
            $active = null;
            if (!empty($pppName)) {
                $secretRows = getPPPoEUserByName($client, $pppName);
                $secret = is_array($secretRows[0] ?? null) ? $secretRows[0] : [];
                $activeQuery = (new \RouterOS\Query('/ppp/active/print'))->where('name', $pppName);
                $activeRows = $client->query($activeQuery)->read();
                $active = !empty($activeRows[0]) ? $activeRows[0] : null;
            }

            $callerid = $active['caller-id'] ?? ($secret['last-caller-id'] ?? null);

            return $this->response->setJSON([
                'ok' => true,
                'offline' => false,
                'pppoe' => $secret ?: [
                    'name' => $pppName ?? '--',
                    'password' => $routerDataRow->router_password ?? '--',
                    'profile' => $routerDataRow->pppoe_profile ?? '--',
                    'service' => '--',
                    'disabled' => 'true',
                ],
                'active_session' => $active,
                'callerid' => $callerid,
                'online' => !empty($active),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'get_mikrotik_info user ' . $id . ': ' . $e->getMessage());
            return $this->response->setJSON([
                'ok' => false,
                'offline' => true,
                'error' => 'Router communication error',
                'pppoe' => null,
                'active_session' => null,
                'callerid' => null,
                'online' => false,
            ]);
        }
    }

    /**
     * Lazy lookups for Transfer / Change-router modals (kept off the list HTML path).
     */
    public function modal_lookups()
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        $details = $this->user_model->where(['id' => $userId])->first();
        if (!$details) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false]);
        }

        if ($userRole === 'employee') {
            $userId = $details->admin_id;
            $details = $this->user_model->where(['id' => $userId])->first();
            if ($details && ($details->created_by ?? '') !== 'admin') {
                $userId = $details->admin_id;
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $resellers = $this->user_model
            ->select('id, name')
            ->where('role', 'resellerAdmin')
            ->where('admin_id', $userId)
            ->findAll();
        $routers = $this->router_model
            ->select('id, name')
            ->where('user_id', $userId)
            ->findAll();

        return $this->response->setJSON([
            'ok' => true,
            'resellers' => array_map(static function ($r) {
                return ['id' => (int) $r->id, 'name' => (string) $r->name];
            }, $resellers ?: []),
            'routers' => array_map(static function ($r) {
                return ['id' => (int) $r->id, 'name' => (string) $r->name];
            }, $routers ?: []),
        ]);
    }

    public function kick($id)
    {
        $details = $this->user_model->find($id);
        if (!$details) {
            return redirect()->back()->with('error', 'Customer not found.');
        }

        $router_client = routerClient($details->router_id);
        if (!($router_client instanceof \RouterOS\Client)) {
            return redirect()->back()->with('error', 'Unable to connect to router.');
        }

        // Get PPPoE user to find the name
        $pppoe_user = getPPPoEUserUserId($router_client, $id);
        $pppoe_name = $pppoe_user[0]['name'] ?? null;

        if (empty($pppoe_name)) {
            return redirect()->back()->with('error', 'PPPoE name not found for this user.');
        }

        try {
            // Find active session
            $query = (new \RouterOS\Query('/ppp/active/print'))
                ->where('name', $pppoe_name);
            $active_users = $router_client->query($query)->read();

            if (!empty($active_users)) {
                $removedCount = 0;
                foreach ($active_users as $session) {
                    $sessionId = $session['.id'] ?? $session['id'] ?? null;
                    if ($sessionId) {
                        $removeQuery = (new \RouterOS\Query('/ppp/active/remove'))
                            ->equal('.id', $sessionId);
                        $router_client->query($removeQuery)->read();
                        $removedCount++;
                    }
                }

                if ($removedCount > 0) {
                    return redirect()->back()->with('success', 'User session refreshed successfully. They should reconnect shortly.');
                }
            }

            return redirect()->back()->with('info', 'User is not currently active on the router.');
        } catch (\Exception $e) {
            log_message('error', 'Error kicking user ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Router error: ' . $e->getMessage());
        }
    }



    /**
     * Customers
     * @action: Edit Customer View
     */

    public function edit($id)
    {
        $details = $this->user_model->where(['id' => $id, 'role' => 'user'])->first();

        // edit() renders the customer's plaintext PPPoE password — never let this
        // page open for a customer outside the caller's tenant.
        if (!empty($details) && !$this->actorOwnsCustomer($details)) {
            log_message('error', 'Blocked cross-tenant customer edit for id ' . $id);
            return show_404();
        }

        if (!empty($details)) {

            $area_model = model('App\Models\Area');
            $package_model = model('App\Models\Package');

            $router_client = routerClient($details->router_id);
            $ConnectionDetails = model('App\Models\ConnectionData');

            // $result = $ConnectionDetails->insert($connection_data);

            $ConnDetails = $ConnectionDetails // Use the model instance directly
                ->where('user_id', $id)
                ->findAll();

            if ($router_client instanceof \RouterOS\Client) {

                $pppoe = getPPPoEUserUserId($router_client, $id);
                $pppoe_id = $pppoe[0]['.id'] ?? $details->pppoe_id ?? null;

                log_message('info', "PPPoE ID for User ID {$id}: {$pppoe_id}");


                log_message('info', 'Fetched ConnDetails data 1: ' . json_encode($details));

                $user_ppp = getPPPoEUser($router_client, $pppoe_id);
                log_message('info', 'Fetched ConnDetails data 2: ' . json_encode($user_ppp));

                $userId = session()->get('user_id');


                $userole = session()->get('user_role');

                $detail = $this->user_model->where(['id' => $userId])->first();
                $emp_admin_id = $detail->admin_id;
                $created_by = $detail->created_by;

                if ($userole === 'employee') {
                    $userId = $emp_admin_id;
                }

                if ($userole === 'admin' || $created_by === 'admin') {
                    // $userId = $emp_admin_id;
                    // log_message('info', 'Fetched router_client data 3: ' . json_encode($router_client));
                    $packages = $package_model->where('user_id', $userId)->where('status', 'active')->findAll();
                }


                if ($userole != 'admin' && $created_by != 'admin') {
                    $packageModel = model('App\Models\allResellerPackage');
                    $rawPackages = $packageModel->where('user_id', $userId)->findAll();

                    // Decode the package_details JSON field
                    $packages = [];
                    foreach ($rawPackages as $package) {
                        $detailsArr = is_string($package['package_details']) 
                            ? json_decode($package['package_details'], true) 
                            : $package['package_details'];
                        if (is_array($detailsArr)) {
                            foreach ($detailsArr as $detail) {
                                $packages[] = $detail;
                            }
                        }
                    }

                    // Extract just the package numbers for comparison
                    $packageNumbers = array_map(function ($p) {
                        return is_object($p) ? $p->package_name : $p['package_name'];
                    }, $packages);
                }

                log_message('info', 'Fetched packages edit data: ' . json_encode($packages));
                // Fetch PPPoE profiles
                $profiles = getPPPoEProfiles($router_client);

                if ($userole != 'admin' && $created_by != 'admin') {
                    // Step 1: Filter profiles by matching number
                    $profiles = array_values(array_filter($profiles, function ($profile) use ($packageNumbers) {
                        if (preg_match('/\d+/', $profile, $profileMatches)) {
                            $profileNum = $profileMatches[0];
                            foreach ($packageNumbers as $pkg) {
                                if (preg_match('/\d+/', $pkg, $pkgMatches)) {
                                    if ($profileNum === $pkgMatches[0]) {
                                        return true;
                                    }
                                }
                            }
                        }
                        return false;
                    }));

                    // Step 2: Decide best variant for each number using letter-aware matching
                    $bestVariant = [];
                    foreach ($packageNumbers as $pkg) {
                        $pkgName = strtolower(trim($pkg));
                        if (!preg_match('/\d+/', $pkgName, $pkgNumMatch))
                            continue;
                        $pkgNum = $pkgNumMatch[0];
                        $pkgWords = preg_split('/[^a-z0-9]+/', $pkgName, -1, PREG_SPLIT_NO_EMPTY);

                        // Get profiles that share this number
                        $matchingProfiles = array_filter($profiles, function ($p) use ($pkgNum) {
                            return preg_match('/\d+/', $p, $pMatch) && $pMatch[0] === $pkgNum;
                        });

                        $bestMatch = null;
                        $highestScore = -1;

                        foreach ($matchingProfiles as $profile) {
                            $profileWords = preg_split('/[^a-z0-9]+/', strtolower($profile), -1, PREG_SPLIT_NO_EMPTY);

                            // Score: count partial word matches between package and profile
                            $score = 0;
                            foreach ($profileWords as $pw) {
                                foreach ($pkgWords as $kw) {
                                    if (strpos($pw, $kw) !== false || strpos($kw, $pw) !== false) {
                                        $score++;
                                    }
                                }
                            }

                            if ($score > $highestScore || ($score === $highestScore && $bestMatch === null)) {
                                $highestScore = $score;
                                $bestMatch = $profile;
                            }
                        }

                        if ($bestMatch) {
                            $bestVariant[$pkgNum] = $bestMatch;
                        }
                    }

                    // Step 3: Keep only best variants
                    $profiles = array_values(array_filter($profiles, function ($p) use ($bestVariant) {
                        if (preg_match('/\d+/', $p, $pMatch)) {
                            $num = $pMatch[0];
                            return isset($bestVariant[$num]) && $bestVariant[$num] === $p;
                        }
                        return false;
                    }));

                    // Now $profiles contains the filtered profiles exactly like JS
                }

                // Log filtered profiles

                // Pass filtered profiles to view
                $unmaskedPassword = $user_ppp[0]['password'] ?? '--';
                $routerPassData = function_exists('getRouterPassById') ? getRouterPassById($details->id) : null;
                if (is_array($routerPassData) && !empty($routerPassData['router_password']) && !preg_match('/^\*+$/', $routerPassData['router_password'])) {
                    $unmaskedPassword = $routerPassData['router_password'];
                }

                $data = [
                    'title' => 'Update Customer',
                    'profiles' => $profiles, // use filtered list
                    'areas' => $area_model->where('status', 'active')->where('user_id', $userId)->findAll(),
                    'packages' => $packages,
                    'details' => $details,
                    'ConnDetails' => $ConnDetails ?? null,
                    'router' => getRouterById($details->router_id)->name ?? '--',
                    'pppoe_name' => $user_ppp[0]['name'] ?? '--',
                    'pppoe_password' => $unmaskedPassword,
                    'pppoe_service' => $user_ppp[0]['service'] ?? '--',
                    'pppoe_profile' => $user_ppp[0]['profile'] ?? '--',
                ];
                log_message('info', 'Fetched ConnDetails data: ' . json_encode($data));


                return view('customers/edit', $data);
            }

            return view('routers/error', [
                'title' => 'Mikrotik Error',
                'error' => $router_client['error'] ?? 'Users router info not found',
                'router_id' => $details->router_id,
            ]);
        }

        show_404();
    }


    /**
     * Customers
     * @action: Update Customer
     */
    public function update($id)
    {
        $this->validate([
            'name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter Customer\'s name',
                ]
            ],
            'area_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select service area',
                ]
            ],
            'mobile' => [
                'rules' => 'required|is_unique[users.mobile, id, ' . $id . ']',
                'errors' => [
                    'required' => 'Enter customer\'s mobile number',
                    'is_unique' => 'Another account is using this number',
                ]
            ],
            'address' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter customer\'s address',
                ]
            ],
            // 'email' => [
            //     'rules' => 'required|is_unique[users.email, id, ' . $id . ']',
            //     'errors' => [
            //         'required' => 'Enter customer\'s email',
            //         'is_unique' => 'Another account is using this email'
            //     ]
            // ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select account status',
                ]
            ],
            'pppoe_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter a username for the PPPoE account',
                ]
            ],
            'pppoe_password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter a password for the PPPoE account',
                ]
            ],
            'pppoe_service' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select PPPoE service',
                ]
            ],

        ]);

        if (!empty(getPostInput('password')) || !empty(getPostInput('re_password'))) {

            $this->validate([
                'password' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Enter a password',
                    ]
                ],
                're_password' => [
                    'rules' => 'required|matches[password]',
                    'errors' => [
                        'required' => 'Rewrite the password',
                        'matches' => 'Passwords doesn\'t matched'
                    ]
                ],

            ]);
        }

        if ($this->validation->run()) {

            //processResellerPayment,call this , and just update the reseller funding , 

            $user_data = $this->user_model->find($id);

            if (!$user_data) {
                return requestResponse('error', 'Customer not found.', 404);
            }

            $router_client = routerClient($user_data->router_id);

            $pppoe_name = getPostInput('pppoe_name');
            $pppoe_password = getPostInput('pppoe_password');

            if ($router_client instanceof \RouterOS\Client) {

                $pppoe = getPPPoEUserUserId($router_client, $user_data->id);
                $pppoe_id = $pppoe[0]['.id'] ?? $user_data->pppoe_id ?? null;

                log_message('info', "PPPoE ID for User ID {$user_data->id}: {$pppoe_id}");



                $user_ppp = getPPPoEUser($router_client, $pppoe_id);


                $pppoe_profile = getPostInput('pppoe_profile') ?? $user_ppp[0]['profile'] ?? '--';

                $router_action = updatePPPoEUser($router_client, [
                    'pppoe_name' => $pppoe_name,
                    'pppoe_password' => $pppoe_password,
                    'pppoe_service' => getPostInput('pppoe_service'),
                    'pppoe_profile' => $pppoe_profile,
                    'pppoe_id' => $pppoe_id,
                ]);

                $data = [
                    'name' => getPostInput('name'),
                    'nid_number' => (getPostInput('nid_number') === '') ? null : getPostInput('nid_number'),
                    'area_id' => getPostInput('area_id'),
                    'mobile' => getPostInput('mobile'),
                    'address' => getPostInput('address'),
                    'email' => getPostInput('email'),
                    'auto_disconnect' => getPostInput('auto_disconnect') ?? 'no',
                    'status' => getPostInput('status'),
                ];
                if (getPostInput('package_id') !== null) {
                    $data['package_id'] = getPostInput('package_id');
                }
                $billing_status = getPostInput('billing_status');
                $connectionDetailsData = [
                    'connection_type' => getPostInput('connection_type'),
                    'cable_requirement' => (getPostInput('cable_requirement') === '') ? null : getPostInput('cable_requirement'),
                    'fiber_code' => getPostInput('fiber_code'),
                    'number_of_core' => (getPostInput('number_of_core') === '') ? null : getPostInput('number_of_core'),
                    'core_color' => getPostInput('core_color'),
                    'client_type' => getPostInput('client_type'),
                    'billing_status' => $billing_status,
                    'otc' => getPostInput('otc'),
                    'sub_area_id' => getPostInput('sub_area_id'),
                ];

                /**
                 * Check if created a new user
                 * @action: Update the pppoe id in datatabase
                 */


                if (is_array($router_action)) {

                    if ($router_action['status'] != 'success') {

                        return requestResponse('error', $router_action['error'], 500);
                    }
                    $routerDataModel = model('App\Models\UserRouterDataModel');
                    $routerData = $routerDataModel->where('user_id', $id)->first();
                    log_message('info', 'Fetched routerData data: ' . json_encode($routerData));

                    $updateRouterData = [
                        'router_password' => $pppoe_password,
                        'pppoe_secret' => $pppoe_name,
                        'pppoe_profile' => $pppoe_profile,
                        'last_updated' => date('Y-m-d H:i:s')
                    ];

                    if ($routerData) {
                        $routerDataId = is_object($routerData) ? $routerData->id : ($routerData['id'] ?? null);
                        if ($routerDataId) {
                            $routerDataModel->update($routerDataId, $updateRouterData);
                        }
                    } else {
                        $updateRouterData['user_id'] = $id;
                        $updateRouterData['router_id'] = $user_data->router_id ?? 0;
                        $routerDataModel->insert($updateRouterData);
                    }
                    log_message('info', 'Updated routerData data: ' . json_encode($router_action));
                    $data['pppoe_id'] = $router_action['pppoe_id'];
                }

                if (!empty(getPostInput('password'))) {

                    $data['password'] = password_hash(getPostInput('password'), PASSWORD_DEFAULT);
                }
                log_message('info', 'Fetched ConnDetails data: ' . json_encode($data));
                $loggedInUserId = session()->get('user_id');
                $userModel = model('App\Models\User');
                $loggedInUser = $userModel->find($loggedInUserId);
                $updater_role = $loggedInUser->role;
                if ($updater_role === 'employee') {
                    $updater_role = $loggedInUser->created_by;
                }
                $is_reseller_update = ($updater_role === 'resellerAdmin');

                $ConnectionDetails = model('App\Models\ConnectionData');
                $existingRecord = $ConnectionDetails->where('user_id', $id)->first();
                $old_billing_status = null;
                if ($existingRecord) {
                    $old_billing_status = is_object($existingRecord) ? $existingRecord->billing_status : ($existingRecord['billing_status'] ?? null);
                }

                if ($billing_status === 'free' || $billing_status === 'Free') {
                    if ($is_reseller_update) {
                        $will_expire = date('Y-m-d H:i:s', strtotime('+30 days'));
                        log_message('info', 'Reseller updated user to free, setting 30 days expiry: ' . $will_expire);
                    } else {
                        $roleToCheck = strtolower(trim(session()->get('user_role') ?? ''));
                        if ($roleToCheck !== 'admin' && !userHasPermission('customer', 'free_customer_create')) {
                            return requestResponse('error', 'You do not have permission to update customer billing status to Free.', 403);
                        }
                        $will_expire = '2050-12-31 23:59:59';
                        log_message('info', 'will_expire : ' . json_encode($will_expire));
                    }
                } elseif (($old_billing_status === 'free' || $old_billing_status === 'Free') && ($billing_status !== 'free' && $billing_status !== 'Free')) {
                    $will_expire = date('Y-m-d H:i:s', strtotime('+30 days'));
                    log_message('info', 'Billing status changed from Free to ' . $billing_status . ', setting 30 days expiry: ' . $will_expire);
                }
                $data['will_expire'] = $will_expire ?? $user_data->will_expire;

                $result = $this->user_model->where(['id' => $id, 'role' => 'user'])->set($data)->update();

                if ($result) {
                    if ($is_reseller_update && ($billing_status === 'free' || $billing_status === 'Free')) {
                        $resellerId = $loggedInUserId;
                        $parentAdminId = $loggedInUser->admin_id;
                        
                        if ($loggedInUser->role === 'employee') {
                            $parentReseller = $userModel->find($loggedInUser->admin_id);
                            if ($parentReseller && $parentReseller->role === 'resellerAdmin') {
                                $resellerId = $parentReseller->id;
                                $parentAdminId = $parentReseller->admin_id;
                            }
                        }
                        
                        $freeReqModel = model('App\Models\FreeUserRequest');
                        $existingReq = $freeReqModel->where(['user_id' => $id, 'status' => 'pending'])->first();
                        if (!$existingReq) {
                            $freeReqModel->insert([
                                'user_id' => $id,
                                'reseller_id' => $resellerId,
                                'admin_id' => $parentAdminId,
                                'status' => 'pending'
                            ]);
                            
                            $parentAdmin = $userModel->find($parentAdminId);
                            if ($parentAdmin) {
                                $subject = "Free User Update Approval Required";
                                $message = "Hello " . ($parentAdmin->name ?? 'Admin') . ",<br><br>" .
                                           "Reseller <strong>" . ($loggedInUser->name ?? '') . "</strong> has requested to change customer <strong>" . $user_data->name . "</strong> to Free:<br>" .
                                           "Customer Name: " . getPostInput('name') . "<br>" .
                                           "Customer PPPoE Username: " . getPostInput('pppoe_name') . "<br><br>" .
                                           "This user has been updated with a temporary 30-day expiration. Please review and approve/reject this request on the Free User Requests page.";
                                sendMail($parentAdmin->email, $subject, $message);
                            }
                        }
                    }

                    $ConnectionDetails = model('App\Models\ConnectionData');

                    $existingRecord = $ConnectionDetails->where('user_id', $id)->first();

                    if ($existingRecord) {
                        $result = $ConnectionDetails->where(['user_id' => $id])->set($connectionDetailsData)->update();
                        log_message('info', 'Updated connection details for user_id: ' . $id);
                    } else {
                        $connectionDetailsData['user_id'] = $id;
                        $result = $ConnectionDetails->insert($connectionDetailsData);
                        log_message('info', 'Inserted new connection details for user_id: ' . $id);
                    }

                    return requestResponse('success', "Customer record updated successfully", 200);
                }

                return requestResponse('error', "Something went wrong! Please try again", 500);
            }

            return requestResponse('error', "Failed to connect to Mikrotik Router. Please check connection.", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Customers
     * @action: Customer Subscription
     */
    public function subscription($id)
    {
        // backupDatabaseAndSendEmail();
        $ids = $this->request->getGet('ids');
        if (!empty($ids)) {
            log_message('info', 'Fetched ids data: ' . json_encode($ids));
        }
        // Handle multiple users if "ids" exists
        if (!empty($ids)) {
            $idArray = explode(',', $ids);
            $userNames = [];
            $packageIds = [];

            foreach ($idArray as $uid) {
                $user = getUserById($uid); // Assuming you have this method
                if ($user) {
                    $userNames[] = $user->name;
                    $packageIds[] = $user->package_id;
                }
            }

            log_message('info', 'Fetched user names: ' . json_encode($userNames));
            log_message('info', 'Fetched package IDs: ' . json_encode($packageIds));
        }

        $details = $this->user_model->where(['id' => $id])->first();
        $payment_model = model('App\Models\Payment');

        // Get current month name
        $currentMonth = date('F'); // e.g., "September"

        log_message('info', 'Current id: ' . $id);
        // Fetch payment for this user and current month
        $payment_details = $payment_model->where([
            'user_id' => $id,
            'month' => $currentMonth
        ])->first();
        $payment_months = $payment_model->where([
            'user_id' => $id,
        ])->findAll();

        // log_message('info', 'Fetched payment_months: ' . json_encode($payment_months));

        if (!empty($details)) {

            $admin = $this->user_model->where(['id' => $details->admin_id])->first();
            log_message('info', 'Fetched details: ' . json_encode($details->admin_id));

            if ($details->created_by === 'resellerAdmin') {

                $router_client = routerClient($details->router_id);

                if ($router_client instanceof \RouterOS\Client) {
                    $userId = session()->get('user_id');

                    $pppoe = getPPPoEUserUserId($router_client, $id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $details->pppoe_id ?? null;

                    log_message('info', "PPPoE ID for User ID {$id}: {$pppoe_id}");


                    $user_ppp = getPPPoEUser($router_client, $pppoe_id);

                    $packageModel = model('App\Models\allResellerPackage');
                    $rawPackages = $packageModel->where('user_id', $userId)->findAll();

                    // Decode the package_details JSON field
                    $packages = [];
                    foreach ($rawPackages as $package) {
                        $detailsArr = is_string($package['package_details']) 
                            ? json_decode($package['package_details'], true) 
                            : $package['package_details'];
                        if (is_array($detailsArr)) {
                            foreach ($detailsArr as $detail) {
                                $packages[] = $detail;
                            }
                        }
                    }

                    // Extract just the package numbers for comparison
                    $packageNumbers = array_map(function ($p) {
                        return is_object($p) ? $p->package_name : $p['package_name'];
                    }, $packages);

                    // Fetch PPPoE profiles
                    $profiles = getPPPoEProfiles($router_client);

                    log_message('info', 'Fetched profiles before filtering: ' . json_encode($profiles));
                    log_message('info', 'Fetched packageNumbers: ' . json_encode($packageNumbers));

                    $userole = session()->get('user_role');
                    $created_by = $details->created_by;

                    if ($userole != 'admin' && $created_by != 'admin') {
                        // Step 1: Filter profiles by matching number
                        $profiles = array_values(array_filter($profiles, function ($profile) use ($packageNumbers) {
                            if (preg_match('/\d+/', $profile, $profileMatches)) {
                                $profileNum = $profileMatches[0];
                                foreach ($packageNumbers as $pkg) {
                                    if (preg_match('/\d+/', $pkg, $pkgMatches)) {
                                        if ($profileNum === $pkgMatches[0]) {
                                            return true;
                                        }
                                    }
                                }
                            }
                            return false;
                        }));

                        // Step 2: Decide best variant for each number using letter-aware matching
                        $bestVariant = [];
                        foreach ($packageNumbers as $pkg) {
                            $pkgName = strtolower(trim($pkg));
                            if (!preg_match('/\d+/', $pkgName, $pkgNumMatch))
                                continue;
                            $pkgNum = $pkgNumMatch[0];
                            $pkgWords = preg_split('/[^a-z0-9]+/', $pkgName, -1, PREG_SPLIT_NO_EMPTY);

                            // Get profiles that share this number
                            $matchingProfiles = array_filter($profiles, function ($p) use ($pkgNum) {
                                return preg_match('/\d+/', $p, $pMatch) && $pMatch[0] === $pkgNum;
                            });

                            $bestMatch = null;
                            $highestScore = -1;

                            foreach ($matchingProfiles as $profile) {
                                $profileWords = preg_split('/[^a-z0-9]+/', strtolower($profile), -1, PREG_SPLIT_NO_EMPTY);

                                // Score: count partial word matches between package and profile
                                $score = 0;
                                foreach ($profileWords as $pw) {
                                    foreach ($pkgWords as $kw) {
                                        if (strpos($pw, $kw) !== false || strpos($kw, $pw) !== false) {
                                            $score++;
                                        }
                                    }
                                }

                                if ($score > $highestScore || ($score === $highestScore && $bestMatch === null)) {
                                    $highestScore = $score;
                                    $bestMatch = $profile;
                                }
                            }

                            if ($bestMatch) {
                                $bestVariant[$pkgNum] = $bestMatch;
                            }
                        }

                        // Step 3: Keep only best variants
                        $profiles = array_values(array_filter($profiles, function ($p) use ($bestVariant) {
                            if (preg_match('/\d+/', $p, $pMatch)) {
                                $num = $pMatch[0];
                                return isset($bestVariant[$num]) && $bestVariant[$num] === $p;
                            }
                            return false;
                        }));

                        // Now $profiles contains the filtered profiles exactly like JS
                    }
                } else {
                    return view('routers/error', [
                        'title' => 'Mikrotik Error',
                        'error' => $router_client['error'] ?? 'Users router info not found',
                        'router_id' => $details->router_id,
                    ]);
                }

                log_message('info', 'Fetched packages data: ' . json_encode($packages));

                $package_model = model('App\Models\ResellerPackages');
                $last_packages = $package_model->where(['status' => 'Active'])->where(['user_id' => $admin->admin_id])->findAll();
                // log_message('info', 'Fetched last packages for admin: ' . json_encode($last_packages));
                // log_message('info', 'Fetched $details->admin_id data: ' . json_encode($admin));
                // log_message('info', 'Fetched $profiles data: ' . json_encode($profiles));
                // Build package to profile map
                $packageProfileMap = [];
                if (!empty($packages)) {
                    foreach ($packages as $pkg) {
                        $pkgId = is_object($pkg) ? ($pkg->id ?? null) : ($pkg['id'] ?? null);
                        $pkgProfile = is_object($pkg) ? ($pkg->mikrotik_profile ?? null) : ($pkg['mikrotik_profile'] ?? null);
                        if ($pkgId && $pkgProfile) {
                            $packageProfileMap[$pkgId] = $pkgProfile;
                        }
                    }
                }

                $data = [
                    'title' => 'Customer\'s Subscription',
                    'details' => $details,
                    'admin_details' => $admin,
                    'profiles' => $profiles,
                    'payment_details' => $payment_details,
                    'multiple' => !empty($ids) ? 'true' : 'false',
                    'userNames' => $userNames ?? [],
                    'ids' => $ids ?? [],
                    'packageIds' => $packageIds ?? [],
                    'packages' => $packages ?? $package_model->where(['status' => 'Active'])->where(['user_id' => $admin->admin_id])->findAll(),
                    'payment_months' => $payment_months ?? [],
                    'pppoe_profile' => $user_ppp[0]['profile'] ?? '--',
                    'package_profile_map' => $packageProfileMap,
                ];
                // log_message('info', 'Fetched data1: ' . json_encode($data));
            } else {



                $package_model = model('App\Models\Package');

                $data = [
                    'title' => 'Customer\'s Subscription',
                    'details' => $details,
                    'payment_details' => $payment_details,
                    'multiple' => !empty($ids) ? 'true' : 'false',
                    'userNames' => $userNames ?? [],
                    'ids' => $ids ?? [],
                    'packageIds' => $packageIds ?? [],
                    'packages' => $package_model->where(['status' => 'active'])->where(['user_id' => $details->admin_id])->findAll(),
                    'payment_months' => $payment_months ?? [],
                ];
                log_message('info', 'Fetched data2: ' . json_encode($data));
            }
            return view('customers/subscription', $data);
        }

        show_404();
    }


    public function Resellersubscription($id)
    {
        $details = $this->user_model->where(['id' => $id, 'role' => 'user'])->first();

        if (!empty($details)) {

            if ($details->created_by === 'resellerAdmin') {

                $package_model = model('App\Models\ResellerPackages');

                $data = [
                    'title' => 'Customer\'s Subscription',
                    'details' => $details,
                    'packages' => $package_model->where(['status' => 'active'])->findAll(),
                ];
            }
            // else {
            //     $package_model = model('App\Models\Package');

            //     $data = [
            //         'title' => 'Customer\'s Subscription',
            //         'details' => $details,
            //         'packages' => $package_model->where(['status' => 'active'])->findAll(),
            //     ];
            // }
            return view('customers/subscription', $data);
        }

        show_404();
    }


    /**
     * Customers
     * @action: Get Router Profiles
     */
    public function getProfiles()
    {
        $this->validate(['router' => ['rules' => 'required']]);

        if ($this->validation->run()) {

            $router_id = getPostInput('router');

            $router = $this->router_model->where(['id' => $router_id, 'status' => 'active'])->first();

            $html = '';

            if (!empty($router)) {

                $router_client = routerClient($router_id);

                if ($router_client instanceof \RouterOS\Client) {

                    $profiles = getPPPoEProfiles($router_client);

                    if (!empty($profiles)) {

                        $html .= '<option value="">--Select--</option>';

                        foreach ($profiles as $profile) {

                            $html .= '<option value="' . $profile . '">' . $profile . '</option>';
                        }
                    } else {

                        $html .= '<option value="">No profile found in this router!</option>';
                    }
                } else {

                    $html .= '<option value="">Cound not connect with router!</option>';
                }
            } else {

                $html .= '<option value="">Router not found!</option>';
            }

            return requestResponse('successs', $html, 200);
        }

        // log_message('error', 'Error calling the router: ' );

        return requestResponse('error', $this->validation->getErrors()['router'], 400);
    }









    public function updateSubscription($id)
    {
        $statusOnly = $this->request->getPost('status_only') === '1';

        // For full recharge (Section 2), will_expire is required.
        // For status-only update (Section 1), skip this validation.
        if (!$statusOnly) {
            $validationRules = [
                'will_expire' => [
                    'rules'  => 'required',
                    'errors' => ['required' => 'Select expire date']
                ],
            ];
            if (!$this->validate($validationRules)) {
                return requestResponse('validation-error', $this->validation->getErrors(), 400);
            }
        }

        try {
            $multiple = $this->request->getPost('multiple');

            if ($multiple === 'true') {
                return $this->processMultipleSubscriptions($id);
            } else {
                return $this->processSingleSubscription($id);
            }
        } catch (Exception $e) {
            log_message('error', 'Subscription update failed: ' . $e->getMessage());
            return requestResponse('error', 'Subscription update failed', 500);
        }
    }

    private function processMultipleSubscriptions($id)
    {
        $ids = json_decode($this->request->getPost('userNames'), true);
        $ids = explode(',', $ids);
        $ids = array_map('trim', $ids);

        if (empty($ids) || !is_array($ids)) {
            return requestResponse('error', 'No valid user IDs provided.', 400);
        }

        log_message('info', 'Processing multiple users: ' . json_encode($ids));

        $errors = [];
        $successCount = 0;

        foreach ($ids as $userId) {
            try {
                $result = $this->processUserSubscription($userId);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errors[] = "User {$userId}: " . $result['message'];
                }
            } catch (\Throwable $e) {
                log_message('error', "Error processing ID $userId: " . $e->getMessage());
                $errors[] = "User {$userId}: Processing failed (" . $e->getMessage() . ")";
            }
        }

        if (!empty($errors)) {
            log_message('error', 'Errors encountered: ' . implode(', ', $errors));
            return requestResponse('error', implode(', ', $errors), 500);
        }

        return requestResponse('success', "Successfully updated {$successCount} subscriptions", 200);
    }

    private function processSingleSubscription($userId)
    {
        $result = $this->processUserSubscription($userId);

        if ($result['success']) {
            return requestResponse('success', $result['message'], 200);
        } else {
            return requestResponse('error', $result['message'], 500);
        }
    }

    private function processUserSubscription($userId)
    {
        // ── SECTION 1: Status-only update — no fund deduction, no expiry change ──
        if ($this->request->getPost('status_only') === '1') {
            return $this->processStatusOnlyPayment($userId);
        }

        // ── SECTION 2: Full recharge — existing flow ──
        $db = \Config\Database::connect();
        // Start database transaction for data consistency
        $db->transStart();

        try {
            $will_expire = getPostInput('will_expire');
            $selectedMonth = getPostInput('month');
            $package_id = getPostInput('package_id');
            $now = date("Y-m-d H:i:s");

            // Validate dates and month
            $validationResult = $this->validateSubscriptionDates($will_expire, $selectedMonth);
            if (!$validationResult['success']) {
                $db->transRollback();
                return $validationResult;
            }

            // Normalize will_expire date
            $will_expire = $this->normalizeDateTime($will_expire);

            // Get user details
            $user_details = $this->user_model->where(['id' => $userId, 'role' => 'user'])->first();
            if (!$user_details) {
                $db->transRollback();
                return ['success' => false, 'message' => 'User not found'];
            }

            // Determine package ID
            if (empty($package_id)) {
                $package_id = $user_details->package_id;
            }

            if (empty($package_id)) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Please select a valid package.'];
            }

            // Check package price for reseller
            $user_role = session()->get('user_role');
            if ($user_role === 'resellerAdmin') {
                $tprice = ResellerPackagePrice($package_id);
                if (empty($tprice)) {
                    $db->transRollback();
                    return ['success' => false, 'message' => 'Please select a valid package.'];
                }

                // Get reseller admin details
                $admin_id = $user_details->admin_id;
                $admin_details = $this->user_model->find($admin_id);
                if ($admin_details && ($admin_details->billing_type ?? 'postpaid') === 'postpaid') {
                    $duration = (int)getPostInput('duration');
                    $allowedStr = !empty($admin_details->reseller_validity_periods) ? $admin_details->reseller_validity_periods : '3,5,7,30';
                    $allowed = array_map('trim', explode(',', $allowedStr));
                    if (!in_array((string)$duration, $allowed)) {
                        $db->transRollback();
                        return ['success' => false, 'message' => 'You can only increase as these days: ' . implode(', ', $allowed)];
                    }
                }
            }

            // Calculate subscription details
            $subscriptionData = $this->calculateSubscriptionData($user_details, $will_expire, $package_id, $selectedMonth);
            if (!$subscriptionData['success']) {
                $db->transRollback();
                return $subscriptionData;
            }

            $status = getPostInput('status');
            $isActive = ($will_expire > $now);

            // Update user subscription
            $updateData = [
                'package_id'          => $package_id,
                'last_renewed'        => date('Y-m-d H:i:s'),
                'will_expire'         => $will_expire,
                'subscription_status' => $isActive ? 'active' : 'inactive',
                'conn_status'         => $isActive ? 'conn' : 'disconn'
            ];

            $created_at = getPostInput('created_at');
            if (!empty($created_at)) {
                $updateData['created_at'] = $this->normalizeDateTime($created_at);
            }

            $result = $this->user_model->update($userId, $updateData);
            if (!$result) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Failed to update user subscription'];
            }

            // Handle router operations
            $routerResult = $this->handleRouterOperations($user_details, $isActive);
            if (!$routerResult['success']) {
                $db->transRollback();
                return $routerResult;
            }

            // Process payment (creates payment record)
            $paymentResult = $this->processPayment($userId, $user_details, $subscriptionData, $selectedMonth);
            if (!$paymentResult['success']) {
                $db->transRollback();
                return $paymentResult;
            }

            // Send SMS notification
            $db->transComplete();
            if ($status === 'successful') {
                try {
                    $updateDataTmp = [
                        'package_id'          => $package_id,
                        'last_renewed'        => date('Y-m-d H:i:s'),
                        'will_expire'         => $will_expire,
                        'subscription_status' => $isActive ? 'active' : 'inactive',
                    ];
                    $this->sendSubscriptionSMS($userId, $user_details, $updateDataTmp, $subscriptionData['price'] ?? null);
                } catch (Exception $e) {
                    // Just log the SMS error, don't let it affect the main process
                    log_message('error', "SMS sending failed for user {$userId}: " . $e->getMessage());
                }
            }

            return ['success' => true, 'message' => 'Subscription updated successfully'];
        } catch (Exception $e) {
            $db->transRollback();
            log_message('error', "User subscription processing failed for {$userId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Subscription processing failed'];
        }
    }

    /**
     * Section 1: Payment status-only update.
     * Marks a payment month as successful/pending WITHOUT:
     *   - Deducting reseller fund
     *   - Changing the customer's package
     *   - Changing the expiry date
     *   - Touching the router
     */
    private function processStatusOnlyPayment($userId)
    {
        try {
            $payment_model = model('App\Models\Payment');

            // Fields from Section 1 (prefixed with s1_)
            $month       = getPostInput('s1_month');
            $status      = getPostInput('s1_status');
            $paid_via    = getPostInput('s1_paid_via');
            $method_trx  = getPostInput('s1_method_trx');
            $description = getPostInput('s1_description');
            $currentUserId = session()->get('user_id');

            // Basic validation
            if (empty($month)) {
                return ['success' => false, 'message' => 'Please select a payment month.'];
            }
            if (empty($status)) {
                return ['success' => false, 'message' => 'Please select a payment status.'];
            }
            if (empty($paid_via)) {
                return ['success' => false, 'message' => 'Please select a payment method.'];
            }

            // Get user details
            $user_details = $this->user_model->where(['id' => $userId, 'role' => 'user'])->first();
            if (!$user_details) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            // Check for existing payment record for this user+month
            $existing = $payment_model->where([
                'user_id' => $userId,
                'month'   => $month,
            ])->first();

            $paydata = [
                'user_id'    => $userId,
                'user_type'  => 'user',
                'admin_id'   => $user_details->admin_id,
                'paidby'     => $currentUserId,
                'paid_to'    => $currentUserId,
                'month'      => $month,
                'paid_via'   => $paid_via,
                'method_trx' => $method_trx,
                'comment'    => $description,
                'status'     => $status,
            ];

            if ($status === 'successful') {
                $paydata['paid_at'] = date('Y-m-d H:i:s');
            }

            if ($existing) {
                // Keep existing amount unchanged; only update status-related fields
                $payment_model->update($existing->id, $paydata);
                log_message('info', "Status-only payment UPDATED for user {$userId}, month: {$month}, status: {$status}");
            } else {
                // Insert a new payment record with amount = 0 (no charge)
                $paydata['invoice']    = 'INV-' . random_int(100000, 999999);
                $paydata['amount']     = 0;
                $paydata['pay_amount'] = 0;
                $paydata['created_at'] = date('Y-m-d H:i:s');
                $payment_model->insert($paydata);
                log_message('info', "Status-only payment INSERTED for user {$userId}, month: {$month}, status: {$status}");
            }

            return [
                'success' => true,
                'message' => 'Payment status updated successfully. No fund deducted.',
            ];
        } catch (\Exception $e) {
            log_message('error', "processStatusOnlyPayment failed for {$userId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Payment status update failed.'];
        }
    }

    private function validateSubscriptionDates($will_expire, $selectedMonth)
    {
        $now = date("Y-m-d H:i:s");

        // Normalize will_expire date
        $will_expire_normalized = str_replace('T', ' ', $will_expire);
        $will_expire_timestamp = strtotime($will_expire_normalized);
        $now_timestamp = strtotime($now);

        if ($will_expire_timestamp === false) {
            return ['success' => false, 'message' => 'Invalid expiration date format'];
        }

        // Extract month names
        $month_now = date('F', $now_timestamp);
        $month_will_expire = date('F', $will_expire_timestamp);

        log_message('info', "Date validation - Current: $month_now, Will expire: $month_will_expire, Selected: $selectedMonth");

        // Check month consistency
        $allowedMonths = [$month_now, $month_will_expire, date('F', strtotime('+1 month', $now_timestamp))];

        if (!in_array($selectedMonth, $allowedMonths)) {
            return ['success' => false, 'message' => 'Selected month is not allowed based on current or expiration date.'];
        }

        return ['success' => true];
    }

    private function calculateSubscriptionData($user_details, $will_expire, $package_id, $selectedMonth)
    {
        $now = time();
        $user_role = session()->get('user_role');
        $pre_package_id = $user_details->package_id;

        // If a duration in days is provided directly in the request (e.g., from postpaid resellers), use it directly
        $durationPost = getPostInput('duration');
        if (!empty($durationPost) && is_numeric($durationPost)) {
            $difference = (int) $durationPost;
        } else {
            // If will_expire is empty, default to today
            $will_expire_timestamp = !empty($will_expire) ? strtotime($will_expire) : $now;
            $prewill_expire = !empty($user_details->will_expire) ? strtotime($user_details->will_expire) : $now;

            // Calculate days difference
            if ($pre_package_id != $package_id) {
                // Package changed → reset calculation
                $difference = ceil(($will_expire_timestamp - $now) / (60 * 60 * 24));
            } elseif ($user_details->subscription_status === 'active' && $prewill_expire > $now) {
                // Same package & active → extend from previous expiry
                $difference = ceil(($will_expire_timestamp - $prewill_expire) / (60 * 60 * 24));
            } else {
                // Expired or inactive → fresh calculation
                $difference = ceil(($will_expire_timestamp - $now) / (60 * 60 * 24));
            }
        }

        log_message('info', "Subscription difference calculated: {$difference} days");

        if ($difference < 0) {
            return ['success' => false, 'message' => 'Select the expiration date correctly.'];
        }

        // Calculate price for reseller
        if ($user_role === 'resellerAdmin') {
            $tprice = ResellerPackagePrice($package_id);
            $originalPrice = ResellerPackagePrice($package_id, "true");

            // Phase 5 (MT-1): canonical proration via the BillingService seam.
            // round to 2dp for the varchar payments.amount the gateway reads.
            $price = round((new \App\Services\BillingService())
                ->quote((float) $tprice, (int) $difference), 2);

            // Check fund availability
            $current_user_id = session()->get('user_id');
            $current_user = $this->user_model->find($current_user_id);
            $fund = $current_user->fund ?? 0;
            $billing_type = $current_user->billing_type ?? 'postpaid';

            if ($billing_type === 'prepaid' && $fund < $price) {
                return ['success' => false, 'message' => "Don't have enough fund. Please recharge."];
            }

            return [
                'success' => true,
                'difference' => $difference,
                'price' => $price,
                'tprice' => $tprice,
                'originalPrice' => $originalPrice,
                'fund_deduction' => $price
            ];
        }

        return [
            'success' => true,
            'difference' => $difference,
            'price' => 0,
            'tprice' => 0,
            'originalPrice' => 0,
            'fund_deduction' => 0
        ];
    }

    private function handleRouterOperations($user_details, $isActive)
    {
        try {
            $router_client = routerClient($user_details->router_id);
            $user = getUserById($user_details->id);


            $pppoe = getPPPoEUserUserId($router_client, $user->id);
            $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

            log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");



            if ($isActive) {
                // Enable PPPoE user
                if ($router_client instanceof \RouterOS\Client) {



                    $user_ppp = getPPPoEUser($router_client, $pppoe_id);

                    // log_message('info', 'Fetched PPPoE user data: ' . json_encode($user_ppp));

                    $pppoe_name = $user_ppp[0]['name'] ?? '--';
                    $pppoe_password = $user_ppp[0]['password'] ?? '--';
                    $pppoe_service = $user_ppp[0]['service'] ?? '--';
                    // $pppoe_profile = $user_ppp[0]['profile'] ?? '--';


                    $router_action = updatePPPoEUser($router_client, [
                        'pppoe_name' => $pppoe_name,
                        'pppoe_password' => $pppoe_password,
                        'pppoe_service' => $pppoe_service,
                        'pppoe_profile' => getPostInput('pppoe_profile'),
                        'pppoe_id' => $pppoe_id,
                    ]);

                    $result = enablePPPoEUser($router_client, $pppoe_id);
                    if (!$result) {
                        // Fallback method
                        $router_model = model('App\Models\UserRouterDataModel');
                        $data = $router_model->where('user_id', $user->id)->first();
                        $pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;
                        $result = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret, $pppoe_id);
                    }
                } else {
                    $result = enablePPPoEUserFsock($user_details->router_id, $pppoe_id);
                    if (!$result) {
                        // Fallback method for fsock
                        $router_model = model('App\Models\UserRouterDataModel');
                        $data = $router_model->where('user_id', $user->id)->first();
                        $pppoe_secret = is_array($data) ? $data['pppoe_secret'] : ($data->pppoe_secret ?? null);

                        if ($pppoe_secret) {
                            $fp = connect_using_Fsocket($user_details->router_id);
                            $ppp_id = getPPPoEIdFsock($fp, $pppoe_secret);
                            $result = enablePPPoEUserFsock($user_details->router_id, $ppp_id);
                        }
                    }
                }

                // INSTANT SYNC: Update user_router_data table with current router state
                if ($router_client instanceof \RouterOS\Client) {
                    $routerDataModel = model('App\Models\UserRouterDataModel');
                    $existingRouterData = $routerDataModel->where(['user_id' => $user->id, 'router_id' => $user_details->router_id])->first();

                    // Fetch fresh data from router to ensure DB is perfectly in sync
                    $freshMkt = getPPPoEUser($router_client, $pppoe_id);
                    if (!empty($freshMkt) && isset($freshMkt[0]['name'])) {
                        $mktName = $freshMkt[0]['name'];
                        $mktPass = $freshMkt[0]['password'] ?? '';
                        $mktProf = $freshMkt[0]['profile'] ?? '';

                        $syncData = [
                            'user_id' => $user->id,
                            'router_id' => $user_details->router_id,
                            'pppoe_secret' => $mktName,
                            'router_password' => $mktPass,
                            'pppoe_profile' => $mktProf,
                            'last_updated' => date('Y-m-d H:i:s')
                        ];

                        if ($existingRouterData) {
                            $routerDataModel->update($existingRouterData->id, $syncData);
                        } else {
                            $routerDataModel->insert($syncData);
                        }
                    }
                }
            } else {
                // Disable PPPoE user
                if ($router_client instanceof \RouterOS\Client) {
                    disablePPPoEUser($router_client, $pppoe_id);
                }
            }

            return ['success' => true];
        } catch (Exception $e) {
            log_message('error', "Router operation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Router operation failed'];
        }
    }

    private function processPayment($userId, $user_details, $subscriptionData, $selectedMonth)
    {
        $payment_model = model('App\Models\Payment');
        $month = getPostInput('month');

        // Check existing payment
        $existing = $payment_model->where([
            'user_id' => $userId,
            'month' => $month
        ])->first();

        $user_role = session()->get('user_role');
        log_message('info', "Processing payment for user role: {$user_role}");

        if ($user_role === 'resellerAdmin') {
            // Handle reseller payment
            return $this->processResellerPayment($userId, $user_details, $subscriptionData, $selectedMonth, $existing);
        } else {
            // Handle admin payment
            return $this->processAdminPayment($userId, $user_details, $selectedMonth, $existing);
        }
    }

    private function processResellerPayment($userId, $user_details, $subscriptionData, $selectedMonth, $existing)
    {
        $payment_model = model('App\Models\Payment');
        $current_user_id = session()->get('user_id');
        $status = getPostInput('status');

        $paymentRef = $existing
            ? 'sub:' . (int) $existing->id
            : 'sub:' . (int) $userId . ':' . $selectedMonth;

        if ($status === 'successful') {
            $fundService = new \App\Services\FundService();
            if (! $fundService->deduct(
                (int) $current_user_id,
                (float) $subscriptionData['fund_deduction'],
                $paymentRef,
                'Customer subscription payment'
            )) {
                return ['success' => false, 'error' => 'Insufficient fund'];
            }

            // Create transaction record
            $transationModel = model('App\Models\ResellerTransactions');
            $transationdata = [
                'customer' => $userId,
                'admin_id' => $current_user_id,
                'amount' => $subscriptionData['price'],
                'package_price' => $subscriptionData['tprice'],
                'active_for' => $subscriptionData['difference'],
                'comments' => 'Customer Subscription Updated'
            ];
            $transationModel->insert($transationdata);
        }

        // Prepare payment data
        $paydata = [
            'user_id' => $userId,
            'user_type' => 'user',
            'admin_id' => $user_details->admin_id,
            'paidby' => $current_user_id,
            'invoice' => 'INV-' . random_int(100000, 999999),
            'amount' => $subscriptionData['price'],
            'pay_amount' => $subscriptionData['tprice'],
            'month' => $selectedMonth,
            'paid_via' => getPostInput('paid_via'),
            'paid_to' => session()->get('user_id'),
            'method_trx' => getPostInput('method_trx'),
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'successful') {
            $paydata['paid_at'] = date('Y-m-d H:i:s');
        }

        log_message('info', "Processing reseller payment: " . json_encode($paydata));
        // Update or insert payment
        if ($existing) {
            $paydata['amount'] = (float) $existing->amount + (float) $subscriptionData['price'];
            $payment_model->update($existing->id, $paydata);
        } else {
            $payment_model->insert($paydata);
        }

        return ['success' => true];
    }

    private function processAdminPayment($userId, $user_details, $selectedMonth, $existing)
    {
        $payment_model = model('App\Models\Payment');
        $package = getUserPackage($userId);
        $tprice = is_array($package) ? $package['price'] : (is_object($package) ? $package->price : 0);

        $paydata = [
            'user_id' => $userId,
            'user_type' => 'user',
            'admin_id' => $user_details->admin_id,
            'paidby' => session()->get('user_id'),
            'invoice' => 'INV-' . random_int(100000, 999999),
            'amount' => $tprice,
            'pay_amount' => $tprice,
            'month' => $selectedMonth,
            'paid_via' => getPostInput('paid_via'),
            'paid_to' => session()->get('user_id'),
            'method_trx' => getPostInput('method_trx'),
            'status' => getPostInput('status'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        if (getPostInput('status') === 'successful') {
            $paydata['paid_at'] = date('Y-m-d H:i:s');
        }

        if ($existing) {
            $payment_model->update($existing->id, $paydata);
        } else {
            $payment_model->insert($paydata);
        }

        return ['success' => true];
    }

    private function sendSubscriptionSMS($userId, $user_details, $updateData, $amount = null)
    {
        try {
            $smsData = [
                'id' => $userId,
                'user_id' => $userId,
                'name' => $user_details->name ?? '--',
                'mobile' => $user_details->mobile ?? '--',
                'admin_id' => $user_details->admin_id ?? null,
                'amount' => $amount,
                'package_id' => $updateData['package_id'],
                'last_renewed' => $updateData['last_renewed'],
                'will_expire' => $updateData['will_expire'],
                'subscription_status' => $updateData['subscription_status'],
            ];
            // event: payment_done | default template: 13 (customer Bill payment)
            sendEventSms('payment_done', $smsData, $smsData['user_id'] ?? null, 13);
        } catch (\Throwable $e) {
            log_message('error', 'SMS Sending Failed: ' . $e->getMessage());
            // Don't fail the entire process if SMS fails
        }
    }

    private function normalizeDateTime($datetime)
    {
        if (empty($datetime)) {
            return date('Y-m-d H:i:s');
        }

        // Replace 'T' with space (common in datetime-local inputs)
        $datetime = str_replace('T', ' ', $datetime);

        // Ensure will_expire has seconds, if not, append ":00"
        // YYYY-MM-DD HH:MM is 16 characters
        if (strlen($datetime) === 16) {
            $datetime .= ":00";
        }

        // If it's only YYYY-MM-DD (10 chars), append current time
        if (strlen($datetime) === 10) {
            $datetime .= ' ' . date('H:i:s');
        }

        return $datetime;
    }



    /**
     * Customers
     * @action: Update Customer's Connection Status
     */
    public function updateConnStatus()
    {
        $this->validate([
            'user' => [
                'rules' => 'required|is_not_unique[users.id, id, ' . getPostInput("user") . ']',
            ],
            'status' => [
                'rules' => 'required',
            ],
        ]);

        if ($this->validation->run()) {

            $user_id = getPostInput('user') ?? 0;
            $status  = getPostInput('status') ?? 0;

            log_message('info', 'Fetched user_id data: ' . json_encode($user_id));
            log_message('info', 'Fetched status data: ' . json_encode($status));

            $user = getUserById($user_id);

            // -----------------------------------------------------------------
            // Guard: block reseller from enabling an expired user
            // (Admin/sAdmin can enable freely regardless of expiry)
            // -----------------------------------------------------------------
            $currentRole = session()->get('user_role');
            if (($status === 'active' || $status === 'conn') && $currentRole === 'resellerAdmin') {
                $will_expire = $user->will_expire ?? null;
                if (empty($will_expire) || strtotime($will_expire) < time()) {
                    log_message('warning', "Blocked enable for User ID {$user->id} (resellerAdmin) — subscription expired or missing: {$will_expire}");
                    return requestResponse('error', 'Cannot enable: user subscription has expired. Please renew first.', 400);
                }
            }

            $router_client = routerClient($user->router_id);

            if ($router_client instanceof \RouterOS\Client) {

                // Always fetch the live PPPoE .id from the router
                $pppoe = getPPPoEUserUserId($router_client, $user->id);
                $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

                // Activate connection
                if ($status === 'active' || $status === 'conn') {

                    // Fetch pppoe_secret for fallback methods
                    $router_model = model('App\Models\UserRouterDataModel');
                    $router_data  = $router_model->where('user_id', $user->id)->first();
                    $pppoe_secret = is_array($router_data) ? $router_data['pppoe_secret'] : ($router_data->pppoe_secret ?? null);

                    $enabled = false;

                    // Layer 1 – RouterOS library: enable by PPPoE .id
                    $enabled = enablePPPoEUser($router_client, $pppoe_id);
                    if ($enabled) {
                        log_message('info', "[L1-RouterOS] Enabled PPPoE for User ID {$user->id}");
                    }

                    // Layer 2 – RouterOS library: enable by PPPoE secret name
                    if (!$enabled && $pppoe_secret) {
                        log_message('warning', "[L2-RouterOS-Secret] Trying enable by secret for User ID {$user->id}");
                        $enabled = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret, $pppoe_id);
                        if ($enabled) {
                            log_message('info', "[L2-RouterOS-Secret] Enabled PPPoE for User ID {$user->id}");
                        }
                    }

                    // Layer 3 – Fsock: enable by PPPoE .id (auto-tries ports 8728/8729)
                    if (!$enabled && $pppoe_id) {
                        log_message('warning', "[L3-Fsock-ID] Trying fsock enable for User ID {$user->id}");
                        $enabled = enablePPPoEUserFsock($user->router_id, $pppoe_id);
                        if ($enabled) {
                            log_message('info', "[L3-Fsock-ID] Enabled PPPoE for User ID {$user->id}");
                        }
                    }

                    // Layer 4 – Fsock: look up live PPPoE .id by secret, then enable
                    if (!$enabled && $pppoe_secret) {
                        log_message('warning', "[L4-Fsock-Secret] Trying fsock enable via secret lookup for User ID {$user->id}");
                        $fp = connect_using_Fsocket($user->router_id);
                        if ($fp) {
                            $live_ppp_id = getPPPoEIdFsock($fp, $pppoe_secret);
                            if ($live_ppp_id) {
                                writeSentence($fp, ["/ppp/secret/enable", "=numbers=$live_ppp_id"]);
                                while (true) {
                                    $resp = readSentence($fp);
                                    if (empty($resp)) break;
                                    if ($resp[0] === '!done') { $enabled = true; break; }
                                    if ($resp[0] === '!trap') break;
                                }
                                if ($enabled) {
                                    log_message('info', "[L4-Fsock-Secret] Enabled PPPoE for User ID {$user->id} via live ID {$live_ppp_id}");
                                }
                            }
                            fclose($fp);
                        }
                    }

                    // Layer 5 – SSH via phpseclib (port 22)
                    if (!$enabled) {
                        log_message('warning', "[L5-SSH] Trying SSH enable for User ID {$user->id}");
                        $ssh_id = $pppoe_id ?: $pppoe_secret;
                        if ($ssh_id) {
                            $enabled = enablePPPoEUserSSH($user->router_id, $ssh_id);
                            if ($enabled) {
                                log_message('info', "[L5-SSH] Enabled PPPoE for User ID {$user->id}");
                            } else {
                                log_message('error', "[ALL LAYERS FAILED] Could not enable PPPoE for User ID {$user->id}");
                            }
                        }
                    }

                    // Always update DB — best-effort enable was attempted above
                    $this->user_model->update($user_id, ['conn_status' => 'conn', 'posPrinter' => 'conn', 'status' => 'active']);
                    return requestResponse('success', 'Connection activated successfully', 200);
                }


                // Deactivate connection — use live $pppoe_id (not stale $user->pppoe_id)
                disablePPPoEUser($router_client, $pppoe_id);

                $this->user_model->update($user_id, ['conn_status' => 'disconn', 'status' => 'inactive']);
                return requestResponse('success', 'Connection disabled successfully', 200);
            }

            // ── Router offline: RouterOS library unavailable — start from Fsock ──
            if ($status === 'active' || $status === 'conn') {

                // Fetch pppoe_secret for fsock fallbacks
                $router_model = model('App\Models\UserRouterDataModel');
                $router_data  = $router_model->where('user_id', $user->id)->first();
                $pppoe_secret = is_array($router_data) ? $router_data['pppoe_secret'] : ($router_data->pppoe_secret ?? null);
                $pppoe_id_db  = $user->pppoe_id ?? null;

                $enabled = false;

                // Layer 3 – Fsock by stored PPPoE id
                if ($pppoe_id_db) {
                    $enabled = enablePPPoEUserFsock($user->router_id, $pppoe_id_db);
                    if ($enabled) log_message('info', "[Router-Offline/L3-Fsock] Enabled for User ID {$user->id}");
                }

                // Layer 4 – Fsock secret lookup
                if (!$enabled && $pppoe_secret) {
                    $fp = connect_using_Fsocket($user->router_id);
                    if ($fp) {
                        $live_ppp_id = getPPPoEIdFsock($fp, $pppoe_secret);
                        if ($live_ppp_id) {
                            writeSentence($fp, ["/ppp/secret/enable", "=numbers=$live_ppp_id"]);
                            while (true) {
                                $resp = readSentence($fp);
                                if (empty($resp)) break;
                                if ($resp[0] === '!done') { $enabled = true; break; }
                                if ($resp[0] === '!trap') break;
                            }
                            if ($enabled) log_message('info', "[Router-Offline/L4-Fsock-Secret] Enabled for User ID {$user->id}");
                        }
                        fclose($fp);
                    }
                }

                // Layer 5 – SSH
                if (!$enabled) {
                    $ssh_id = $pppoe_id_db ?: $pppoe_secret;
                    if ($ssh_id) {
                        $enabled = enablePPPoEUserSSH($user->router_id, $ssh_id);
                        if ($enabled) log_message('info', "[Router-Offline/L5-SSH] Enabled for User ID {$user->id}");
                        else log_message('error', "[Router-Offline/ALL LAYERS FAILED] Could not enable PPPoE for User ID {$user->id}");
                    }
                }

                $this->user_model->update($user_id, ['conn_status' => 'conn', 'posPrinter' => 'conn', 'status' => 'active']);
                return requestResponse('success', $enabled ? 'Connection activated successfully' : 'Connection activated in database (Router unreachable)', 200);

            } else {
                $this->user_model->update($user_id, ['conn_status' => 'disconn', 'status' => 'inactive']);
                return requestResponse('success', 'Connection disabled in database (Router Offline)', 200);
            }
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }




    public function transfer()
    {

        // Retrieve POST data
        $ids             = $this->request->getPost('ids');
        $resellerId      = $this->request->getPost('reseller_id');
        $packageId       = $this->request->getPost('package_id');
        $packageName     = $this->request->getPost('package_name');
        $transferToAdmin = $this->request->getPost('transfer_to_admin'); // '1' when transferring back to admin

        log_message('info', 'Fetched ids data: ' . json_encode($ids));
        log_message('info', 'Fetched resellerId data: ' . json_encode($resellerId));
        log_message('info', 'Fetched packageId data: ' . json_encode($packageId));
        log_message('info', 'Fetched packageName data: ' . json_encode($packageName));
        log_message('info', 'Fetched transferToAdmin data: ' . json_encode($transferToAdmin));

        if (empty($resellerId)) {
            return $this->response->setJSON(['response' => 'Reseller is required.'], 400);
        }

        if (empty($packageId)) {
            return $this->response->setJSON(['response' => 'Package selection is required.'], 400);
        }

        $userRole = session()->get('user_role');
        $userId   = session()->get('user_id');
        $details  = $this->user_model->where(['id' => $userId])->first();
        log_message('info', 'Fetched userId: ' . json_encode($userId));
        $Pre_created_by = $details->created_by;

        if ($userRole === 'resellerAdmin') {
            $created_by = 'admin';
        } elseif ($userRole === 'employee') {
            if ($Pre_created_by === 'resellerAdmin') {
                $created_by = 'admin';
            } else {
                $created_by = 'resellerAdmin';
            }
        } else {
            $created_by = 'resellerAdmin';
        }

        // Validate that the selected package belongs to the destination
        $packageValid = false;

        if ($transferToAdmin == '1') {
            // Transferring to admin — validate against the `packages` table (sAdmin's packages)
            // When impersonating, use original admin's ID; otherwise use the current user's admin_id
            if (session()->has('original_user')) {
                $adminUserId = session()->get('original_user')['user_id'];
            } else {
                $adminUserId = $resellerId; // reseller_id holds the admin's user ID in this case
            }
            $packageModel = model('App\\Models\\Package');
            $adminPkg     = $packageModel->where('id', $packageId)->where('user_id', $adminUserId)->first();
            if ($adminPkg) {
                $packageValid = true;
                // Use the original admin's ID as the destination
                $resellerId   = $adminUserId;
                $created_by   = 'admin'; // customers going back to admin have sAdmin created_by
            }
        } else {
            // Transferring to a reseller — validate against allResellerPackage cache
            $allResellerPackageModel = model('App\\Models\\allResellerPackage');
            $rawPackages             = $allResellerPackageModel->where('user_id', $resellerId)->findAll();
            foreach ($rawPackages as $package) {
                $detailsArr = is_string($package['package_details'])
                    ? json_decode($package['package_details'], true)
                    : $package['package_details'];
                if (is_array($detailsArr)) {
                    foreach ($detailsArr as $detailsPackage) {
                        if (((string) ($detailsPackage['id'] ?? '') === (string) $packageId)
                            && ($packageName === null || trim((string) ($detailsPackage['package_name'] ?? '')) === trim((string) $packageName))) {
                            $packageValid = true;
                            break 2;
                        }
                    }
                }
            }

            // Fallback: check reseller_packages table directly
            if (! $packageValid) {
                $resellerPkgModel = model('App\\Models\\ResellerPackages');
                $directPkg        = $resellerPkgModel->where('id', $packageId)->where('user_id', $resellerId)->first();
                if ($directPkg) {
                    $packageValid = true;
                }
            }
        }

        if (! $packageValid) {
            return $this->response->setJSON(['response' => 'Selected package is not available for the chosen destination.'], 400);
        }

        if (empty($ids) || ! is_array($ids)) {
            return $this->response->setJSON(['response' => 'No customers selected.'], 400);
        }

        /* Ownership check. The loop below reassigns admin_id/created_by/package_id
           for whatever ids the caller submits, with no verification that those
           customers belong to the caller — so a tenant admin could transfer
           another tenant's customers into their own account. Accept only
           customers currently owned by the acting scope: the caller's own user id
           (admin / resellerAdmin own their customers) or the admin they report to
           (employees), mirroring how fetch() resolves scope above. super_admin is
           the platform owner and is not tenant-scoped. */
        if ($userRole !== 'super_admin') {
            // admin / resellerAdmin own their customers directly (admin_id == their
            // own id). An employee acts on behalf of the account they belong to, so
            // they additionally match that account's id — but nobody else does,
            // otherwise a reseller could reach their parent admin's customers.
            $allowedOwnerIds = [(int) $userId];
            if ($userRole === 'employee') {
                $allowedOwnerIds[] = (int) ($details->admin_id ?? 0);
            }
            $allowedOwnerIds = array_values(array_filter($allowedOwnerIds));

            foreach ($ids as $customerId) {
                $customer = $this->user_model->where('id', (int) $customerId)->first();
                $ownerId  = (int) (is_object($customer) ? ($customer->admin_id ?? 0) : ($customer['admin_id'] ?? 0));

                if (empty($customer) || ! in_array($ownerId, $allowedOwnerIds, true)) {
                    log_message('error', 'transfer(): blocked out-of-scope customer id ' . json_encode($customerId));
                    return $this->response->setJSON(
                        ['response' => 'Access denied: one or more selected customers do not belong to your account.'],
                        403
                    );
                }
            }
        }

        // Loop through each selected customer and update the reseller_id and new package
        foreach ($ids as $customerId) {
            log_message('info', 'Fetched created_by: ' . json_encode($created_by));
            $updateData = ['admin_id' => $resellerId, 'created_by' => $created_by, 'package_id' => $packageId];
            $this->user_model->update($customerId, $updateData);
        }

        return $this->response->setJSON(['response' => 'Customers transferred successfully.']);
    }

    public function pingUserApi()
    {
        $startTime = microtime(true);
        log_message('info', 'pingUserApi called at: ' . date('Y-m-d H:i:s'));

        $router_id = $this->request->getGet('router_id');
        $name = $this->request->getGet('name');

        log_message('info', 'Fetched router_id data: ' . json_encode($router_id));
        $result = pingUser($router_id, $name);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime; // in seconds

        log_message('info', 'pingUser execution time: ' . $executionTime . ' seconds');
        // log_message('info', 'Ping result: ' . json_encode($result));

        return $this->response->setJSON($result);
    }

    public function mac_ajax($user_id, $action = 'check')
    {
        helper('router_helper');

        $user_model = model('App\Models\User');
        $user = $user_model->where('id', $user_id)->first();

        if (!$user) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'User not found',
                'mac' => null
            ]);
        }

        switch ($action) {
            case 'bind':
                $bindResult = bindMacForUser($user_id);
                log_message('info', "Bind MAC for User ID {$user_id}: " . $bindResult['message']);
                $mac = $bindResult['mac'] ?? null;
                $status = !empty($mac) ? false : true;
                $message = $bindResult['message'];
                break;

            case 'unbind':
                $success = removeMacBind($user_id);
                log_message('info', "Unbind MAC for User ID {$user_id}: " . ($success ? 'Success' : 'Failed'));
                $mac = null;
                $status = $success ? false : true;
                $message = $success ? 'MAC unbind successful' : 'MAC unbind failed';
                break;

            case 'check':
            default:
                $check = isMacBound($user_id);
                $mac = $check['mac'] ?? null;
                $status = $check['status'] ?? false;
                $message = $status ? 'MAC is bound' : 'MAC is not bound';
                break;
        }

        return $this->response->setJSON([
            'status' => $status,
            'mac' => $mac,
            'message' => $message
        ]);
    }

    public function changeRouter()
    {
        $userIds = $this->request->getPost('ids');
        $newRouterId = $this->request->getPost('router_id');
        log_message('info', 'changeRouter called with userIds: ' . json_encode($userIds) . ' and newRouterId: ' . json_encode($newRouterId));

        if (empty($userIds) || empty($newRouterId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid data. Please select users and a router.']);
        }

        $ids = is_array($userIds) ? $userIds : explode(',', $userIds);

        try {
            // Optimization: Single update query per table for multiple IDs
            $this->user_model->whereIn('id', $ids)->set(['router_id' => $newRouterId])->update();
            $this->user_router_model->whereIn('user_id', $ids)->set(['router_id' => $newRouterId])->update();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Router updated successfully for ' . count($ids) . ' customers.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Batch Router Change Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Update failed: ' . $e->getMessage()
            ]);
        }
    }

    public function freeRequests()
    {
        $role = session()->get('user_role');
        if (!in_array($role, ['super_admin', 'admin'])) {
            return show_404();
        }

        $adminId = session()->get('user_id');

        $db = \Config\Database::connect();
        $builder = $db->table('free_user_requests r');
        $builder->select('r.*, c.name as customer_name, c.email as customer_email, res.name as reseller_name, c.will_expire as customer_expiry');
        $builder->join('users c', 'c.id = r.user_id', 'inner');
        $builder->join('users res', 'res.id = r.reseller_id', 'inner');
        $builder->where('r.admin_id', $adminId);
        $builder->orderBy('r.id', 'DESC');
        $requests = $builder->get()->getResultArray();

        $data = [
            'title' => 'Free User Requests',
            'requests' => $requests
        ];

        return view('customers/free-requests', $data);
    }

    public function approveFreeRequest()
    {
        $role = session()->get('user_role');
        if (!in_array($role, ['super_admin', 'admin'])) {
            return requestResponse('error', 'Unauthorized', 403);
        }

        $requestId = $this->request->getPost('id');
        $freeReqModel = model('App\Models\FreeUserRequest');
        $request = $freeReqModel->find($requestId);

        if (!$request) {
            return requestResponse('error', 'Request not found', 404);
        }

        if ($request['status'] !== 'pending') {
            return requestResponse('error', 'Request is already processed', 400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $freeReqModel->update($requestId, ['status' => 'approved']);

        $userModel = model('App\Models\User');
        $userModel->update($request['user_id'], [
            'will_expire' => '2050-12-31 23:59:59'
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return requestResponse('error', 'Transaction failed', 500);
        }

        $reseller = $userModel->find($request['reseller_id']);
        $customer = $userModel->find($request['user_id']);
        if ($reseller && $customer) {
            $subject = "Free User Request Approved";
            $message = "Hello " . ($reseller->name ?? 'Reseller') . ",<br><br>" .
                       "Your request to make customer <strong>" . ($customer->name ?? '') . "</strong> Free has been approved by your admin.<br>" .
                       "The customer's expiration date has been updated to 2050-12-31.<br><br>" .
                       "Thank you.";
            sendMail($reseller->email, $subject, $message);
        }

        return requestResponse('success', 'Request approved successfully', 200);
    }

    public function rejectFreeRequest()
    {
        $role = session()->get('user_role');
        if (!in_array($role, ['super_admin', 'admin'])) {
            return requestResponse('error', 'Unauthorized', 403);
        }

        $requestId = $this->request->getPost('id');
        $freeReqModel = model('App\Models\FreeUserRequest');
        $request = $freeReqModel->find($requestId);

        if (!$request) {
            return requestResponse('error', 'Request not found', 404);
        }

        if ($request['status'] !== 'pending') {
            return requestResponse('error', 'Request is already processed', 400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $freeReqModel->update($requestId, ['status' => 'rejected']);

        $ConnectionDetails = model('App\Models\ConnectionData');
        $ConnectionDetails->where('user_id', $request['user_id'])->set(['billing_status' => 'active'])->update();

        $db->transComplete();

        if ($db->transStatus() === false) {
            return requestResponse('error', 'Transaction failed', 500);
        }

        $userModel = model('App\Models\User');
        $reseller = $userModel->find($request['reseller_id']);
        $customer = $userModel->find($request['user_id']);
        if ($reseller && $customer) {
            $subject = "Free User Request Rejected";
            $message = "Hello " . ($reseller->name ?? 'Reseller') . ",<br><br>" .
                       "Your request to make customer <strong>" . ($customer->name ?? '') . "</strong> Free has been rejected by your admin.<br>" .
                       "The customer's billing status has been set to Active with their current temporary expiration date.<br><br>" .
                       "Thank you.";
            sendMail($reseller->email, $subject, $message);
        }

        return requestResponse('success', 'Request rejected successfully', 200);
    }

    /**
     * Compact row actions for customer list tables (presentation only).
     */
    private function renderCustomerListActions($row): string
    {
        $id = is_object($row) ? $row->id : ($row['id'] ?? 0);
        $html = '<div class="ipb-row-actions">';

        $html .= '<a href="' . route_to('route.customer.details', $id) . '" class="ipb-row-btn tone-info" title="View details" data-toggle="tooltip" data-placement="top" data-instant-nav>'
            . '<i class="fas fa-eye" aria-hidden="true"></i><span class="sr-only">View</span></a>';

        $subUrl = base_url('subscription/' . $id);
        $html .= '<button type="button" class="ipb-row-btn tone-violet ipb-copy-sub-link" title="Copy subscription link" data-toggle="tooltip" data-placement="top" data-link="' . esc($subUrl, 'attr') . '">'
            . '<i class="fas fa-link" aria-hidden="true"></i><span class="sr-only">Copy link</span></button>';

        if (userHasPermission('customer', 'update')) {
            $html .= '<a href="' . route_to('route.customer.edit', $id) . '" class="ipb-row-btn tone-brand" title="Update customer" data-toggle="tooltip" data-placement="top" data-instant-nav>'
                . '<i class="fas fa-user-edit" aria-hidden="true"></i><span class="sr-only">Update</span></a>';
        }

        $role = getSession('user_role');
        if (in_array($role, ['admin', 'resellerAdmin', 'employee'], true)) {
            $html .= '<a href="' . route_to('route.customer.subscription', $id) . '" class="ipb-row-btn tone-success" title="Recharge" data-toggle="tooltip" data-placement="top" data-instant-nav>'
                . '<i class="fas fa-bolt" aria-hidden="true"></i><span class="sr-only">Recharge</span></a>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    private function redirectNewCustomerError(string $message, ?string $redirectTo = null)
    {
        return redirect()->to($redirectTo ?? base_url('reward-center'))->with('rwd_error', $message);
    }

    private function resolveReferralOwnerId(object $referralRow): int
    {
        $ownerId = (int) ($referralRow->owner_id ?? 0);
        if ($ownerId > 0) {
            return $ownerId;
        }

        $referrer = model('App\Models\User')->find((int) ($referralRow->referrer_id ?? 0));
        if ($referrer === null) {
            return 0;
        }

        return (int) ($referrer->admin_id ?? 0);
    }

    private function canCompleteReferralSetup(object $actor, object $referralRow, int $ownerId): bool
    {
        $rawRole = (string) ($actor->role ?? '');
        $role = strtolower($rawRole);
        if ($role === 'super_admin') {
            return true;
        }

        $inScope = false;
        if ($role === 'admin') {
            $inScope = (int) $actor->id === $ownerId;
        } elseif ($role === 'reselleradmin') {
            $inScope = (int) ($actor->admin_id ?? 0) === $ownerId;
        } elseif ($role === 'employee') {
            helper('user');
            $inScope = (int) getSAdminIdForUser((int) $actor->id) === $ownerId;
        }

        if (!$inScope) {
            return false;
        }

        helper('user');

        return userHasPermission('referral', 'update', $rawRole, (int) $actor->id);
    }

    /**
     * @return string|null Error message when the sAdmin cannot add customers.
     */
    private function assertSAdminCanAddCustomer(int $sAdminId, $AdminPackage): ?string
    {
        helper('subscription');
        unset($AdminPackage);

        return assertTenantCanAddCustomer($sAdminId);
    }

    /**
     * @return array{packages: array, areas: array, routers: array}|\CodeIgniter\HTTP\RedirectResponse|null
     */
    private function loadReferralNewCustomerContext(int $referralId, object $actor, $area_model, $package_model, $AdminPackage)
    {
        try {
            $refModel = new \Zapi\Modules\Shared\Rewards\Models\ReferralModel();
            $refRow = $refModel->find($referralId);
            if (!$refRow) {
                return $this->redirectNewCustomerError('Referral not found.');
            }
            if (!in_array($refRow->status ?? '', ['pending', 'flagged'], true)) {
                return $this->redirectNewCustomerError('This referral is no longer pending setup.');
            }

            $ownerId = $this->resolveReferralOwnerId($refRow);
            if ($ownerId <= 0) {
                return $this->redirectNewCustomerError('Referral is not linked to an ISP admin.');
            }
            if (!$this->canCompleteReferralSetup($actor, $refRow, $ownerId)) {
                return $this->redirectNewCustomerError('You do not have permission to complete this referral.');
            }

            $limitMsg = $this->assertSAdminCanAddCustomer($ownerId, $AdminPackage);
            if ($limitMsg !== null) {
                return $this->redirectNewCustomerError($limitMsg);
            }

            return [
                'packages' => $package_model->where('user_id', $ownerId)->findAll(),
                'areas' => $area_model->where('status', 'active')->where('user_id', $ownerId)->findAll(),
                'routers' => $this->router_model->where('status', 'active')->where('user_id', $ownerId)->findAll(),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'referral new customer: ' . $e->getMessage());

            return $this->redirectNewCustomerError('Could not load referral setup.');
        }
    }
}

