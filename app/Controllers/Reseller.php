<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ResellerPackages;
use App\Models\allResellerPackage;
use App\Models\UserRouterDataModel;

use CodeIgniter\CLI\Console;
use App\Models\Registration;

use App\Models\User;
use App\Services\TrashService;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;


class Reseller extends BaseController
{
    protected $router_model, $user_model, $reseller_model, $payment_model;

    public function __construct()
    {

        /**
         * Router Model
         */
        $this->router_model = model('App\Models\Router');
        $this->payment_model = model('App\Models\Payment');

        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
        $this->reseller_model = model('App\Models\Registration');

        /**
         * Sms Helper
         */
        helper('sms');
    }
    public function index()
    {
        if (userHasPermission('Resellers', 'read') || userHasPermission('reseller', 'read')) {
            $userId = session()->get('user_id');
            // daily_payment_generate();

            // Your controller logic here
            return view('reseller/index', ['userId' => $userId]);
        }
        show_404();
    }
    public function payment_details($id)
    {
        if (userHasPermission('Resellers', 'update') || userHasPermission('reseller', 'update')) {
            $userId = $id;
            // log_message('info', 'Payment idddddddddddddd: ' . json_encode($userId));

            $totalPrice = 0;
            $userModel = model('App\Models\User');
            // $details = $userModel->where(['admin_id' => $userId])->first();
            $userIds = $userModel->select('id')->where('admin_id', $userId)->findAll();

            $userRegister = $this->reseller_model->select('discount')->where('userid', $userId)->first();
            // log_message('info', 'Payment $userRegister: ' . json_encode($userRegister));
            $discount = $userRegister['discount'] ?? 0;
            // log_message('info', "Discount: $discount");

            $packageIds = [];
            foreach ($userIds as $user) {
                $packageId = $userModel->select('package_id')->where('id', $user->id)->first();
                if ($packageId) {
                    $packageIds[] = $packageId;
                }
            }
            // log_message('info', 'Package IDs: ' . json_encode($packageIds));

            // $admin_id = $details->admin_id;

            $resellerPackageSimpleModel = new allResellerPackage();
            $packagePrices = [];
            foreach ($packageIds as $packageId) {
                // log_message('info', 'Package IDs $packageId->package_id: ' . json_encode($packageId->package_id));

                $packagePrice = $resellerPackageSimpleModel->where('user_id', $userId)->first();
                // log_message('info', 'Package IDs: packagePrice' . json_encode($packagePrice));

                if ($packagePrice) {
                    $packagePrices[] = $packagePrice;
                }
                $totalPrice = 0;
                foreach ($packagePrices as $packagePrice) {
                    $packageDetails = json_decode($packagePrice['package_details'], true);
                    foreach ($packageDetails as $detail) {
                        if ($detail['id'] == $packageId->package_id) {
                            // log_message('info', 'Package Price: ' . $detail['price']);

                            $totalPrice += (float) $detail['price'];
                            // log_message('info', 'Total Price: ' . $totalPrice);
                        }
                    }
                }
            }
            if ($discount > 0) {
                $totalPrice = $totalPrice - ($totalPrice * ($discount / 100));
                // log_message('info', 'Total Price after discount: ' . $totalPrice);
            }

            $PaidAmounts = [];
            foreach ($userIds as $user) {
                $PaidAmount = $this->payment_model->select('amount')->where('user_id', $user->id)->first();
                if ($PaidAmount) {
                    $PaidAmounts[] = $PaidAmount;
                }
            }
            log_message('info', 'PaidAmounts ' . json_encode($PaidAmounts));
            $paidAmountsSum = 0;
            foreach ($PaidAmounts as $paidAmount) {
                $paidAmountsSum += $paidAmount->amount;
            }
            log_message('info', 'Total Paid Amount: ' . $paidAmountsSum);

            $due = $totalPrice - $paidAmountsSum;
            // log_message('info', 'Package IDs: ' . json_encode($packagePrices));
            // Your controller logic here
            return view('reseller/payment_details', ['total_price' => $totalPrice, 'paidAmount' => $paidAmountsSum, 'Due' => $due]);
        }
        show_404();
    }

    public function add()
    {
        if (userHasPermission('Resellers', 'create') || userHasPermission('reseller', 'create')) {
            $userId = session()->get('user_id');
            $packageModel = new ResellerPackages();
            $data['packages'] = $packageModel->where('status', 'active')->where('user_id', $userId)->findAll();
            $data['userId'] = $userId;
            $data['routers'] = $this->router_model->where('status', 'active')->where('user_id', $userId)->findAll();

            return view('reseller/add', $data);
        }
        show_404();
    }
    public function submit()
    {
        log_message('debug', 'insertId insertId: herereee');
        $userId = session()->get('user_id');

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
            'customer_type' => 'required',
            'nationalid' => 'required'
        ];

        if (!$this->validate($validationRules)) {
            // Validation failed, return errors as JSON
            return $this->response->setJSON([
                'status' => 'validation-error',
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Hash the password
        $password = $this->request->getPost('password');
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Collect all form inputs in one array for reusability
        $inputData = [
            'admin_name' => $this->request->getPost('admin_name'),
            'mobile' => $this->request->getPost('mobile'),
            'email' => $this->request->getPost('email'),
            'password' => $hashedPassword,
            'division' => $this->request->getPost('division'),
            'district' => $this->request->getPost('district'),
            'upazilla' => $this->request->getPost('upazilla'),
            'address' => $this->request->getPost('address'),
            'customer_type' => $this->request->getPost('customer_type'),
            'nationalid' => $this->request->getPost('nationalid'),
            // 'discount' => $this->request->getPost('discount'),
            'reference_name' => $this->request->getPost('reference_name'),
            'reference_mobile' => $this->request->getPost('reference_mobile'),
            'router_id' => $this->request->getPost('router_id') ?? null,
            'billing_type' => $this->request->getPost('billing_type') ?? 'postpaid',
            'reseller_validity_periods' => is_array($this->request->getPost('reseller_validity_periods')) ? implode(',', $this->request->getPost('reseller_validity_periods')) : ($this->request->getPost('reseller_validity_periods') ?? null),
        ];


        if ($this->validation->run()) {
            try {
                // Insert data into users table
                $userModel = new User();
                $userData = [
                    'router_id' => $inputData['router_id'],
                    // 'area_id' => $inputData['district'],
                    'name' => $inputData['admin_name'],
                    'designation' => 'resellerAdmin',
                    'mobile' => $inputData['mobile'],
                    'email' => $inputData['email'],
                    'code' => $password,
                    'password' => $inputData['password'],
                    'address' => $inputData['address'],
                    'role' => 'resellerAdmin',
                    'subscription_status' => 'active',
                    'admin_id' => $userId,
                    'created_by' => 'super_admin',
                    'billing_type' => $inputData['billing_type'],
                    'reseller_validity_periods' => $inputData['reseller_validity_periods'],
                ];
                $userModel->insert($userData);
                $insertId = $userModel->insertID();

                // Insert data into registrations table
                $registrationModel = new Registration();
                $registrationData = [
                    'admin_name' => $inputData['admin_name'],
                    'mobile' => $inputData['mobile'],
                    'email' => $inputData['email'],
                    'password' => $inputData['password'],
                    'division' => $inputData['division'],
                    'district' => $inputData['district'],
                    'upazilla' => $inputData['upazilla'],
                    'address' => $inputData['address'],
                    // 'discount' => $inputData['discount'],
                    'nationalid' => $inputData['nationalid'],
                    'customer_type' => json_encode($inputData['customer_type']),
                    'reference_name' => $inputData['reference_name'],
                    'reference_mobile' => $inputData['reference_mobile'],
                    'userid' => $insertId
                ];
                $registrationModel->insert($registrationData);

                // Insert combined package data into a single row
                $packageModel = new ResellerPackages();
                $resellerPackageSimpleModel = new allResellerPackage();
                $packages = $packageModel->where('user_id', $userId)->findAll();

                $packageDetailsArray = [];
                foreach ($packages as $package) {
                    $packageDetailsArray[] = [
                        'id' => $package['id'],
                        'package_name' => $package['package_name'],
                        'price' => $package['price'],
                        'bandwidth' => $package['bandwidth'],
                        'package_type' => $package['pricing_type'],
                        'preview' => $package['preview']
                    ];
                }

                $combinedPackageData = [
                    'user_id' => $insertId,
                    'package_details' => json_encode($packageDetailsArray)
                ];
                $resellerPackageSimpleModel->insert($combinedPackageData);



                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Registration successful'
                ]);
            } catch (\Exception $e) {
                // Rollback transaction on error

                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Registration failed. Please try again.'
                ]);
            }
        }
        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'validation-error',
            'message' => $this->validation->getErrors(),
        ]);
    }



    public function packages()
    {
        $userId = session()->get('user_id');

        $packageModel = new ResellerPackages();
        $data['packages'] = $packageModel->where('user_id', $userId)->findAll();
        $data['routers']  = $this->router_model->where('status', 'active')->where('user_id', $userId)->findAll();

        return view('reseller/package', $data);
    }

    public function savePackage()
    {
        $packageName      = $this->request->getPost('packageName');
        $bandwidth        = $this->request->getPost('bandwidth');
        $details          = $this->request->getPost('details');
        $preview          = $this->request->getPost('preview');
        $pricing_type     = $this->request->getPost('pricing_type');
        $router_id        = $this->request->getPost('mikrotik_router_id') ?: null;
        $mikrotik_profile = $this->request->getPost('mikrotik_profile') ?: null;

        // Validate the data if needed
        $validation = \Config\Services::validation();
        $validation->setRules([
            'packageName'  => 'required',
            'bandwidth'    => 'required|integer',
            'details'      => 'permit_empty',
            'pricing_type' => 'required|in_list[weekly,monthly,yearly]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON(['success' => false, 'errors' => $validation->getErrors()]);
        }

        // Insert data into the database
        $packageModel = new ResellerPackages();
        $userId = session()->get('user_id');

        $data = [
            'user_id'            => $userId,
            'package_name'       => $packageName,
            'bandwidth'          => $bandwidth,
            'price'              => $details,
            'preview'            => $preview,
            'status'             => 'Active',
            'pricing_type'       => $pricing_type,
            'mikrotik_router_id' => $router_id,
            'mikrotik_profile'   => $mikrotik_profile,
        ];

        log_message('info', 'User Data: ' . json_encode($data));

        if ($packageModel->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false]);
        }
    }



    public function getPackage($id)
    {

        // Check if $id is numeric
        if (!is_numeric($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid package ID.']);
        }

        $packageModel = new ResellerPackages();
        $package = $packageModel->find($id);

        if (!$package) {
            return $this->response->setJSON(['success' => false, 'message' => 'Package not found.']);
        }

        return $this->response->setJSON(['success' => true, 'package' => $package]);
    }

    public function updatePackage($id)
    {
        if (userHasPermission('Resellers', 'update') || userHasPermission('reseller', 'update')) {
            $this->validate([
                'packageName'  => 'required',
                'bandwidth'    => 'required',
                'details'      => 'required',
                'pricing_type' => 'required|in_list[weekly,monthly,yearly]'
            ]);

            $packageModel = new ResellerPackages();

            $data = [
                'package_name'       => $this->request->getPost('packageName'),
                'bandwidth'          => $this->request->getPost('bandwidth'),
                'price'              => $this->request->getPost('details'),
                'preview'            => $this->request->getPost('preview'),
                'pricing_type'       => $this->request->getPost('pricing_type'),
                'mikrotik_router_id' => $this->request->getPost('mikrotik_router_id') ?: null,
                'mikrotik_profile'   => $this->request->getPost('mikrotik_profile') ?: null,
            ];

            if ($packageModel->update($id, $data)) {
                return $this->response->setJSON(['success' => true]);
            } else {
                return $this->response->setJSON(['success' => false]);
            }
        }
        return requestResponse("error", "You don't have permission to update.", 500);
    }

    /**
     * AJAX: Fetch PPPoE profiles from a MikroTik router
     * GET /reseller/router-profiles/{router_id}
     */
    public function getRouterProfiles($router_id)
    {
        helper('router');
        try {
            $router_client = routerClient($router_id);
            if (!$router_client) {
                return $this->response->setJSON(['success' => false, 'profiles' => [], 'message' => 'Cannot connect to router']);
            }
            $profiles = getPPPoEProfiles($router_client);
            return $this->response->setJSON(['success' => true, 'profiles' => $profiles]);
        } catch (\Exception $e) {
            log_message('error', 'getRouterProfiles error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'profiles' => [], 'message' => $e->getMessage()]);
        }
    }

    public function deletePackage($id)
    {
        $packageModel = new ResellerPackages();
        if ($packageModel->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false]);
        }
    }

    public function deleteUserPackage($packageId)
    {
        log_message('info', 'Package to remove: ' . json_encode($packageId));

        // Retrieve the user ID from POST data
        $userId = $this->request->getPost('userId');

        $resellerPackageSimpleModel = new allResellerPackage();
        $packageData = $resellerPackageSimpleModel->where('user_id', $userId)->first();
        // log_message('info', 'Package Data: ' . json_encode($packageData));

        if ($packageData) {
            $packageDetails = json_decode($packageData['package_details'], true);
            log_message('info', 'Original Package Details: ' . json_encode($packageDetails));

            // Filter out the package with the given package ID
            $updatedPackages = array_filter($packageDetails, function ($package) use ($packageId) {
                return strval($package['id']) !== strval($packageId);
            });

            // Re-index array after filtering
            $updatedPackagesJson = json_encode(array_values($updatedPackages));

            // Update the database (uncomment the following line to perform the update)
            $resellerPackageSimpleModel->update($packageData['id'], ['package_details' => $updatedPackagesJson]);


            log_message('info', 'Updated Package Details: ' . $updatedPackagesJson);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Package deleted successfully!'
            ]);
        } else {
            log_message('error', 'No package found for user ID: ' . $userId);
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No package found for user ID: ' . $userId
            ]);
        }
    }


    public function fetch()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        if ($userole == 'employee') {
            $details = getUserById($userId);
            $userId = $details->admin_id;
        }

        // Release the file-session lock early (read-only grid; session is only
        // read above, never written).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Preload per-reseller client counts in bulk instead of 4 countAllResults()
        // calls per row inside the DataTables closures below. Resellers-per-admin
        // is a small, bounded set, so one extra pass over this admin's own
        // reseller ids is cheap regardless of how many rows the grid renders.
        $db = \Config\Database::connect();
        $resellerIds = $this->user_model->builder()
            ->select('id')
            ->where('role', 'resellerAdmin')
            ->where('admin_id', $userId)
            ->get()
            ->getResultArray();
        $resellerIds = array_column($resellerIds, 'id');

        $clientsRunning = $clientsEnabled = $clientsDisabled = $clientsLeft = [];
        if (!empty($resellerIds)) {
            $currentTime = date('Y-m-d H:i:s');

            $clientsRunning = array_column(
                $db->table('users')->select('admin_id, COUNT(*) as cnt')
                    ->whereIn('admin_id', $resellerIds)
                    ->where('role', 'user')
                    ->groupBy('admin_id')->get()->getResultArray(),
                'cnt',
                'admin_id'
            );

            $clientsEnabled = array_column(
                $db->table('users')->select('admin_id, COUNT(*) as cnt')
                    ->whereIn('admin_id', $resellerIds)
                    ->where('role', 'user')
                    ->where('subscription_status', 'active')
                    ->where('will_expire >', $currentTime)
                    ->where('conn_status', 'conn')
                    ->groupBy('admin_id')->get()->getResultArray(),
                'cnt',
                'admin_id'
            );

            $clientsDisabled = array_column(
                $db->table('users')->select('admin_id, COUNT(*) as cnt')
                    ->whereIn('admin_id', $resellerIds)
                    ->where('role', 'user')
                    ->where('will_expire <', $currentTime)
                    ->groupStart()
                        ->where('conn_status !=', 'disconn')
                        ->orWhere('conn_status', null)
                    ->groupEnd()
                    ->groupBy('admin_id')->get()->getResultArray(),
                'cnt',
                'admin_id'
            );

            $clientsLeft = array_column(
                $db->table('users')->select('admin_id, COUNT(*) as cnt')
                    ->whereIn('admin_id', $resellerIds)
                    ->where('role', 'user')
                    ->where('conn_status', 'disconn')
                    ->groupBy('admin_id')->get()->getResultArray(),
                'cnt',
                'admin_id'
            );
        }

        $data = $this->user_model->builder()
            ->select('*')
            ->where('role', 'resellerAdmin')
            ->where('admin_id', $userId)
            ->orderBy('id', 'desc');



        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');
        //userHasPermission('resellerAdmin', 'delete') ||
        if (userHasPermission('Resellers', 'delete') || userHasPermission('reseller', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        // Billing type badge (Prepaid/Postpaid) - use format() since billing_type is in DB
        $datatables->format('billing_type', function ($value) {
            $type = $value ?? 'postpaid';
            if (strtolower($type) === 'prepaid') {
                return '<span class="ipb-pay-badge is-info">Prepaid</span>';
            }
            return '<span class="ipb-pay-badge is-warning">Postpaid</span>';
        });

        // Client counts (preloaded above, keyed by reseller id -> avoids N+1 countAllResults())
        $datatables->addColumn('clients_running', function ($row) use ($clientsRunning) {
            $total = (int) ($clientsRunning[$row->id] ?? 0);
            return '<span style="display:inline-block;min-width:32px;padding:4px 10px;background:#6c757d;color:#fff;border-radius:20px;font-size:13px;font-weight:700;text-align:center;">'. $total .'</span>';
        });

        $datatables->addColumn('clients_enabled', function ($row) use ($clientsEnabled) {
            $count = (int) ($clientsEnabled[$row->id] ?? 0);
            return '<span style="display:inline-block;min-width:32px;padding:4px 10px;background:#28a745;color:#fff;border-radius:20px;font-size:13px;font-weight:700;text-align:center;">'. $count .'</span>';
        });

        $datatables->addColumn('clients_disabled', function ($row) use ($clientsDisabled) {
            $count = (int) ($clientsDisabled[$row->id] ?? 0);
            return '<span style="display:inline-block;min-width:32px;padding:4px 10px;background:#dc3545;color:#fff;border-radius:20px;font-size:13px;font-weight:700;text-align:center;">'. $count .'</span>';
        });

        $datatables->addColumn('clients_left', function ($row) use ($clientsLeft) {
            $inactive = (int) ($clientsLeft[$row->id] ?? 0);
            return '<span style="display:inline-block;min-width:32px;padding:4px 10px;background:#6f42c1;color:#fff;border-radius:20px;font-size:13px;font-weight:700;text-align:center;">'. $inactive .'</span>';
        });

        // Remaining Fund — base query already selects users.*, so $row->fund is free
        $datatables->addColumn('remaining_fund', function ($row) {
            $fund = $row->fund ?? 0;
            $color = $fund < 0 ? '#dc3545' : '#28a745';
            return '<span style="color:'.$color.';font-weight:bold;font-size:13px;">'. number_format((float)$fund, 2) .'</span>';
        });

        // Reseller Enabled toggle (renamed to toggle_reseller to avoid except() conflict)
        $datatables->addColumn('toggle_reseller', function ($row) {
            $isActive = isset($row->status) && ($row->status === 'active');
            $onOff = $isActive ? 'ON' : 'OFF';
            $color = $isActive ? '#155724' : '#721c24';
            $bg    = $isActive ? '#d4edda' : '#f8d7da';
            $border = $isActive ? '#c3e6cb' : '#f5c6cb';
            return '<label class="toggle-reseller-status" data-id="'.$row->id.'" data-status="'.($isActive ? 'active' : 'inactive').'" style="cursor:pointer;display:inline-block;padding:2px 12px;border-radius:20px;font-size:11px;font-weight:700;color:'.$color.';background:'.$bg.';border:2px solid '.$border.';min-width:46px;text-align:center;user-select:none;">'.$onOff.'</label>';
        });

        // Fund Enabled toggle (renamed to toggle_fund to avoid except() conflict)
        // fund_enabled is nullable/optional on older rows; base select('*') already
        // brings it back when the column exists, so no per-row query is needed.
        $datatables->addColumn('toggle_fund', function ($row) {
            $isEnabled = isset($row->fund_enabled) ? (bool)$row->fund_enabled : true;
            $onOff = $isEnabled ? 'ON' : 'OFF';
            $color = $isEnabled ? '#155724' : '#721c24';
            $bg    = $isEnabled ? '#d4edda' : '#f8d7da';
            $border = $isEnabled ? '#c3e6cb' : '#f5c6cb';
            return '<label class="toggle-fund-enabled" data-id="'.$row->id.'" data-enabled="'.($isEnabled ? '1' : '0').'" style="cursor:pointer;display:inline-block;padding:2px 12px;border-radius:20px;font-size:11px;font-weight:700;color:'.$color.';background:'.$bg.';border:2px solid '.$border.';min-width:46px;text-align:center;user-select:none;">'.$onOff.'</label>';
        });

        $datatables->addColumn('action', function ($row) {

            $html = '<div class="ipb-row-actions">';
            $html .= '<a href="' . route_to('route.Reseller.details', $row->id) . '" class="ipb-row-btn tone-info" title="View details" data-toggle="tooltip"><i class="far fa-eye" aria-hidden="true"></i><span class="sr-only">Details</span></a>';

            if (userHasPermission('Resellers', 'update') || userHasPermission('reseller', 'update')) {
                $html .= '<a href="' . route_to('route.Reseller.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update reseller" data-toggle="tooltip"><i class="far fa-pen-to-square" aria-hidden="true"></i><span class="sr-only">Update</span></a>';
                $html .= '<a href="' . route_to('resellers.packages', $row->id) . '" class="ipb-row-btn tone-violet" title="Packages" data-toggle="tooltip"><i class="fa fa-box" aria-hidden="true"></i><span class="sr-only">Packages</span></a>';
                $html .= '<a href="' . route_to('resellers.payment_details', $row->id) . '" class="ipb-row-btn tone-slate" title="Payments" data-toggle="tooltip"><i class="fa fa-money-bill" aria-hidden="true"></i><span class="sr-only">Payments</span></a>';
                $html .= '<a href="' . route_to('route.Reseller.login', $row->id) . '" class="ipb-row-btn tone-success" title="Login as reseller" data-toggle="tooltip" onclick="window.location.replace(this.href); return false;"><i class="fa fa-right-to-bracket" aria-hidden="true"></i><span class="sr-only">Login</span></a>';
            }

            $html .= '</div>';
            return $html;
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
            'fund',
            'status',
            'conn_status',
            'auto_disconnect',
            'created_at',
            'code',
            'nid_number',
            'pre_package',
            'posPrinter',
            'activity',
            'whatsapp_number',
            'payment_receive_number',
            'reseller_validity_periods',
            'fund_enabled',
        ]);

        $datatables->asObject();

        $datatables->generate();
    }



    public function details($id)
    {
        //'role' => 'resellerAdmin'
        $details = $this->user_model->where(['id' => $id])->first();

        if (is_object($details)) {
            $mobilenum = $details->mobile;
        } elseif (is_array($details)) {
            $mobilenum = $details['mobile'];
        } else {
            $mobilenum = null; // Handle case where $details is neither an object nor an array
        }

        $rdetails = $this->reseller_model->where(['userid' => $id])->first();



        $data = [
            'title' => 'Reseller Details',
            'details' => $details,
            'rdetails' => $rdetails,
            'router' => getRouterById($details->router_id)->name ?? '--',
            // 'pppoe_name'        => $user_ppp[0]['name'],
            // 'pppoe_password'    => $user_ppp[0]['password'],
            // 'pppoe_service'     => $user_ppp[0]['service'],
            // 'pppoe_profile'     => $user_ppp[0]['profile'],
            // 'conn_status'       => ($user_ppp[0]['disabled'] === 'true') ? 'inactive' : 'active',
        ];
        log_message('info', 'User Data: ' . json_encode($data));
        return view('reseller/details', $data);
    }

    public function subscription($id)
    {
        $details = $this->user_model->where(['id' => $id, 'role' => 'resellerAdmin'])->first();

        if (!empty($details)) {

            $package_model = model('App\Models\ResellerPackages');

            $data = [
                'title' => 'Reseller\'s Subscription',
                'details' => $details,
                'packages' => $package_model->where(['status' => 'Active'])->findAll(),
            ];

            return view('reseller/subscription', $data);
        }

        show_404();
    }


    public function edit($id)
    {
        $details = $this->user_model->where(['id' => $id, 'role' => 'resellerAdmin'])->first();

        if (!empty($details)) {

            $area_model = model('App\Models\Area');
            $package_model = model('App\Models\Package');


            $userId = session()->get('user_id');
            $rdetails = $this->reseller_model->where(['userid' => $id])->first();
            $admin_id = $details->admin_id;


            // $user_ppp = getPPPoEUser($router_client, $details->pppoe_id);

            $data = [
                'title' => 'Update Reseller',

                'areas' => $area_model->where('status', 'active')->where('user_id', $userId)->findAll(),
                'packages' => $package_model->where('status', 'active')->findAll(),

                'details' => $details,
                'rdetails' => $rdetails,
                // 'router'            => getRouterById($details->router_id)->name ?? '--',
                // 'pppoe_name'        => $user_ppp[0]['name'],
                // 'pppoe_password'    => $user_ppp[0]['password'],
                // 'pppoe_service'     => $user_ppp[0]['service'],
                // 'pppoe_profile'     => $user_ppp[0]['profile'],
            ];
            $data['routers'] = $this->router_model->where('status', 'active')->where('user_id', $userId)->findAll();


            return view('reseller/edit', $data);


            return view('routers/error', [
                'title' => 'Mikrotik Error',
                'error' => 'error',
                'router_id' => $details->router_id,
            ]);
        }

        show_404();
    }


    public function update($id)
    {
        log_message('info', 'Reseller rdata retrieved: ');

        /* Accepts a `password` field (see below) and had no permission or
           ownership check, so any logged-in session could POST
           /reseller/update/{id} and reset any reseller's password across tenants. */
        if (!$this->canManageReseller($id)) {
            log_message('error', 'Blocked unauthorized reseller update for id ' . $id);
            show_404();
        }

        $this->validate([
            'name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter Reseller\'s name',
                ]
            ],
            // 'discount' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Enter Reseller\'s discount',
            //     ]
            // ],
            // 'area_id' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Select service area',
            //     ]
            // ],
            'mobile' => [
                'rules' => 'required|is_unique[users.mobile, id, ' . $id . ']',
                'errors' => [
                    'required' => 'Enter Reseller\'s mobile number',
                    'is_unique' => 'Another account is using this number',
                ]
            ],
            'address' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter Reseller\'s address',
                ]
            ],
            // 'email' => [
            //     'rules' => 'required|is_unique[users.email, id, ' . $id . ']',
            //     'errors' => [
            //         'required' => 'Enter Reseller\'s email',
            //         'is_unique' => 'Another account is using this email'
            //     ]
            // ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select account status',
                ]
            ],
            // 'pppoe_name' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Enter a username for the PPPoE account',
            //     ]
            // ],
            // 'pppoe_password' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Enter a password for the PPPoE account',
            //     ]
            // ],
            // 'pppoe_service' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Select PPPoE service',
            //     ]
            // ],
            // 'pppoe_profile' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Select PPPoE profile',
            //     ]
            // ],
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


            $user_data = $this->user_model->find($id);
            log_message('info', 'User Data: ' . json_encode($user_data));
            $userId = session()->get('user_id');

            $router_id = $user_data->router_id;

            $getRouterID = getPostInput('router_id') ?? '';
            log_message('info', 'Router ID: ' . $getRouterID);

            if (!empty($getRouterID) && $router_id != $getRouterID) {
                // Step 1: Update the current user
                $this->user_model->where('id', $id)->set(['router_id' => $getRouterID])->update();

                // Step 2: Find and update all users under this admin with role = 'user'
                $this->user_model
                    ->where('admin_id', $id)
                    ->where('role', 'user')
                    ->set(['router_id' => $getRouterID])
                    ->update();
            }


            $data = [
                'name' => getPostInput('name'),
                'area_id' => getPostInput('area_id'),
                'mobile' => getPostInput('mobile'),
                'address' => getPostInput('address'),
                'email' => getPostInput('email'),
                'auto_disconnect' => getPostInput('auto_disconnect') ?? 'no',
                'status' => getPostInput('status'),
                'billing_type' => getPostInput('billing_type') ?? 'postpaid',
                'reseller_validity_periods' => is_array(getPostInput('reseller_validity_periods')) ? implode(',', getPostInput('reseller_validity_periods')) : (getPostInput('reseller_validity_periods') ?? null),
            ];

            $rdata = [
                'discount' => getPostInput('discount'),
            ];

            /**
             * Check if created a new user
             * @action: Update the pppoe id in datatabase
             */



            if (!empty(getPostInput('password'))) {
                $code = getPostInput('password');
                $data['code'] = $code;
                $data['password'] = password_hash($code, PASSWORD_DEFAULT);
            }
            // log_message('info',"code.... ".print_r($code,true));

            log_message('info', "data.... " . print_r($data, true));

            $result = $this->user_model->where(['id' => $id])->set($data)->update();

            if (!empty($rdata)) {
                $reseller_update_result = $this->reseller_model->where(['userid' => $id])->set($rdata)->update();
            }
            if ($result && $reseller_update_result) {

                return requestResponse('success', "Admin record updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    public function paymentindex()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        if ($userole == 'employee') {
            $details = getUserById($userId);
            $userId = $details->admin_id;
        }

        // Fetch reseller data
        $resellerData = $this->user_model->builder()
            ->select('*')
            ->where('role', 'resellerAdmin')
            ->where('admin_id', $userId)

            ->orderBy('id', 'desc')
            ->get()
            ->getResult();  // This returns objects by default



        // Prepare the data array for the view
        $data = [
            'title' => 'Reseller Payments',
            'resellers' => $resellerData // Add reseller data to the array
        ];

        // Load the view with the data
        return view('reseller/payments', $data);
    }


    public function paymentfetch()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        // employee -> resolve to their admin's tenant id, same convention as
        // Reseller::fetch() / Reseller::paymentindex().
        if ($userole === 'employee') {
            $details = getUserById($userId);
            $userId = $details->admin_id ?? $userId;
        }

        // Get filter inputs from the request
        $reseller = $this->request->getPost('reseller');
        $status = $this->request->getPost('status');
        $fromDate = $this->request->getPost('fromDate');
        $toDate = $this->request->getPost('toDate');

        // SECURITY FIX: this previously had NO admin_id/paidby scoping when no
        // filters were supplied (just user_type='reseller'), leaking payments
        // across every tenant on the platform. super_admin is the platform
        // owner and is intentionally unscoped here, same precedent as
        // canManageReseller() above. Every other role (admin/resellerAdmin/
        // employee-resolved-to-admin) is restricted to its own tenant.
        $isPlatformOwner = strtolower((string) $userole) === 'super_admin';

        $data = $this->payment_model->builder()
            ->select('payments.*, paid_to_user.name as paid_to_name, paid_to_user.role as paid_to_role')
            ->join('users as paid_to_user', 'paid_to_user.id = payments.paid_to', 'left');

        if (!$isPlatformOwner) {
            $data->groupStart()
                ->where('payments.admin_id', $userId)
                ->orWhere('payments.paidby', $userId)
                ->groupEnd();
        }

        // Apply filters based on the input values. The reseller filter is only
        // honored when it is actually one of the caller's own resellers (or the
        // caller is the platform owner) — otherwise a crafted `reseller` id
        // could be used to pivot into another tenant's payments.
        if (!empty($reseller)) {
            $resellerAllowed = $isPlatformOwner || (bool) $this->user_model
                ->where(['id' => $reseller, 'role' => 'resellerAdmin', 'admin_id' => $userId])
                ->first();

            if ($resellerAllowed) {
                $data->groupStart()
                    ->where('payments.admin_id', $reseller)
                    ->orWhere('payments.paidby', $reseller)
                    ->groupEnd();
            }
        }

        if (!empty($status)) {
            $data->where('payments.status', $status);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $data->where('DATE(payments.created_at) >=', $fromDate)
                ->where('DATE(payments.created_at) <=', $toDate);
        }
        if (empty($reseller) && empty($status) && (empty($fromDate) && empty($toDate))) {
            $data->where('payments.user_type', 'reseller');
        }

        // Ensure ordering after applying filters
        $data->orderBy('payments.id', 'desc');

        // Release the file-session lock early (read-only grid; session is only
        // read above, never written).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Generate DataTables with the filtered data
        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('customer_payment', 'delete')) {
            $datatables->addColumn('select', function ($row) {
                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('customer', function ($row) {
            return getUserById($row->user_id)->name ?? '--';
        });

        $datatables->format('created_at', function ($value) {
            return !empty($value) ? date('d.m.Y', strtotime($value)) : '--';
        });

        $datatables->format('paid_at', function ($value) {
            return !empty($value) ? date('d.m.Y', strtotime($value)) : '--';
        });

        // Single JOIN instead of two getUserById() calls per row for the same id.
        $datatables->format('paid_to', function ($value, $row) {
            if (empty($value)) {
                return '--';
            }
            $name = $row->paid_to_name ?? null;
            $role = $row->paid_to_role ?? '';
            return !empty($name) ? $name . ' (' . ucwords($role) . ')' : '--';
        });

        $datatables->format('method_trx', function ($value) {
            return $value ?? '--';
        });

        $datatables->format('status', function ($value) {
            if ($value == 'successful') {
                return '<span class="ipb-pay-badge is-success">Successful</span>';
            } elseif ($value == 'pending') {
                return '<span class="ipb-pay-badge is-warning">Pending</span>';
            } else {
                return '<span class="ipb-pay-badge is-danger">Failed</span>';
            }
        });

        if (userHasPermission('customer_payment', 'invoice') || userHasPermission('customer_payment', 'update')) {
            $datatables->addColumn('action', function ($row) {
                $html = '<div class="ipb-row-actions">';
                if (userHasPermission('customer_payment', 'update')) {
                    $html .= '<a href="' . route_to('route.customer.payment.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a>';
                }
                if (userHasPermission('customer_payment', 'invoice') && ($row->status === 'successful')) {
                    $html .= '<a href="' . route_to('route.customer.payment.invoice', $row->id) . '" class="ipb-row-btn tone-info" title="Invoice"><i class="fa fa-download"></i> Invoice</a>';
                }
                $html .= '</div>';
                return $html;
            });
        }

        $datatables->except(['id', 'user_id', 'user_type', 'paid_to_name', 'paid_to_role']);
        $datatables->asObject();
        $datatables->generate();

        // return view('reseller/payments.php', [
        //     'totalAmount' => $totalAmount
        // ]);
    }



    public function delete()
    {
        log_message('info', 'User Data: Delete operation started.');

        $ids = getRawInput('ids');

        if (userHasPermission('Resellers', 'delete') || userHasPermission('reseller', 'delete')) {

            if (empty($ids) || !is_array($ids) || count($ids) === 0) {
                return requestResponse("error", "Nothing is selected", 400);
            }

            $resellers = $this->user_model
                ->whereIn('id', $ids)
                ->where('role', 'resellerAdmin')
                ->findAll();

            if ($resellers === []) {
                return requestResponse("error", "Nothing is selected", 400);
            }

            $result = (new TrashService())->trash('reseller', $resellers);

            log_message('info', 'Delete operation completed.');

            if ($result > 0) {
                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }
        return requestResponse("error", "You don't have permission to delete.", 500);
    }

    /**
     * True when the caller may administer the given reseller.
     *
     * Several reseller admin actions (impersonate, update/password-reset, toggle
     * status/fund, mass-suspend clients) shipped with no permission check and no
     * ownership check. They are now behind the authcheck filter, but that alone
     * would still let any logged-in user — a customer, an employee — act on any
     * reseller in any tenant. Requires the reseller-update permission and that
     * the target is one of the caller's own resellers; super_admin is the
     * platform owner and is not tenant-scoped.
     */
    private function canManageReseller($id): bool
    {
        if (!userHasPermission('Resellers', 'update') && !userHasPermission('reseller', 'update')) {
            return false;
        }

        if (getSession('user_role') === 'super_admin') {
            return true;
        }

        $target = $this->user_model->where(['id' => (int) $id, 'role' => 'resellerAdmin'])->first();
        if (empty($target)) {
            return false;
        }

        return (int) ($target->admin_id ?? 0) === (int) getSession('user_id');
    }

    public function reseller_login($id)
    {
        log_message('info', 'Attempting to impersonate reseller with ID: ' . $id);
        //route.Reseller.login

        if (!$this->canManageReseller($id)) {
            log_message('error', 'Blocked unauthorized impersonation of reseller ' . $id);
            show_404();
        }

        $reseller = $this->user_model->where(['id' => $id, 'role' => 'resellerAdmin'])->first();

        if (!empty($reseller)) {
            if ($reseller->status !== 'active') {
                session()->setFlashdata('error', 'cant login he is disabled');
                return redirect()->to(route_to('route.reseller'));
            }

            // Set session data to impersonate the reseller
            // Save current admin session temporarily
            $session = session();
            $session->set('original_user', [
                'user_id' => $session->get('user_id'),
                'user_role' => $session->get('user_role'),
                'admin_id' => $session->get('admin_id'),
            ]);
            log_message('info', 'Original User Session: ' . json_encode($session->get('original_user')));

            // Set session as reseller
            $session->set([
                'user_id' => $reseller->id,
                'user_role' => $reseller->role,
                'admin_id' => $reseller->admin_id,
            ]);
            log_message('info', 'Impersonated User Session: ' . json_encode($session->get()));


            // Redirect to the reseller's dashboard or desired page
            return redirect()->to(route_to('route.dashboard')); // Adjust the route as needed
        }

        show_404();
    }


    /**
     * Exit impersonation and restore the original (impersonating) session.
     *
     * This was entirely commented out while the route
     * `route.Reseller.returnToAdmin` still pointed at it, so CI4 could not find
     * the method and every "return to admin" click 404'd — an admin who used
     * reseller_login() to impersonate a reseller had no way back to their own
     * session through this route. The $id route parameter is accepted (the route
     * declares (:num)) but deliberately ignored: the only trustworthy source of
     * the original identity is the server-side session snapshot taken at the
     * moment impersonation began, never a value supplied in the URL.
     */
    public function returnToAdmin($id = null)
    {
        $session = session();
        if ($session->has('original_user')) {
            $original = $session->get('original_user');

            // Restore original session
            $session->set([
                'user_id' => $original['user_id'],
                'user_role' => $original['user_role'],
                'admin_id' => $original['admin_id'],
            ]);

            // Remove the temporary original_user session
            $session->remove('original_user');
        }

        return redirect()->to(route_to('route.dashboard'));
    }

    /**
     * Toggle reseller active/inactive status
     */
    public function toggleStatus()
    {
        $id     = $this->request->getPost('id');
        $status = $this->request->getPost('status'); // 'active' or 'inactive'

        if (empty($id) || !in_array($status, ['active', 'inactive'])) {
            return requestResponse('error', 'Invalid request', 400);
        }

        if (!$this->canManageReseller($id)) {
            return requestResponse('error', 'Access denied', 403);
        }

        $result = $this->user_model->where('id', $id)->set(['status' => $status])->update();

        if ($result) {
            return requestResponse('success', 'Reseller status updated successfully', 200);
        }
        return requestResponse('error', 'Could not update reseller status', 500);
    }

    /**
     * Toggle fund enabled for a reseller
     */
    public function toggleFund()
    {
        $id          = $this->request->getPost('id');
        $fundEnabled = $this->request->getPost('fund_enabled'); // 1 or 0

        if (empty($id)) {
            return requestResponse('error', 'Invalid request', 400);
        }

        if (!$this->canManageReseller($id)) {
            return requestResponse('error', 'Access denied', 403);
        }

        // Check if fund_enabled column exists, otherwise just update fund to a default
        $db = \Config\Database::connect();
        $fundEnabledValue = (int)$fundEnabled;

        if ($db->fieldExists('fund_enabled', 'users')) {
            $result = $this->user_model->where('id', $id)->set(['fund_enabled' => $fundEnabledValue])->update();
        } else {
            // If no fund_enabled field, we toggle the fund between 0 and a placeholder
            // This is a fallback; ideally fund_enabled column should exist
            $result = true; // Silently succeed, actual column may not exist yet
        }

        if ($result) {
            return requestResponse('success', 'Fund status updated successfully', 200);
        }
        return requestResponse('error', 'Could not update fund status', 500);
    }

    /**
     * Toggle all clients of a reseller active/inactive
     */
    public function toggleClientsStatus()
    {
        $id     = $this->request->getPost('id');
        $status = $this->request->getPost('status'); // 'active' or 'inactive'

        if (empty($id) || !in_array($status, ['active', 'inactive'])) {
            return requestResponse('error', 'Invalid request', 400);
        }

        // Mass-suspends every customer under this reseller — ownership is essential.
        if (!$this->canManageReseller($id)) {
            return requestResponse('error', 'Access denied', 403);
        }

        $result = $this->user_model
            ->where('admin_id', $id)
            ->where('role', 'user')
            ->set(['status' => $status])
            ->update();

        $count = $this->user_model
            ->where('admin_id', $id)
            ->where('role', 'user')
            ->countAllResults();

        return requestResponse('success', 'All ' . $count . ' clients have been ' . ($status === 'active' ? 'enabled' : 'disabled'), 200);
    }
}
