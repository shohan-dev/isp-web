<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ResellerPackages;
use App\Models\AdminPackage;
use App\Models\Registration;
use App\Models\UserRouterDataModel;
use App\Models\ContactModel;

use CodeIgniter\CLI\Console;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;


class Admin extends BaseController
{
    protected $router_model, $user_model, $reseller_model;

    public function __construct()
    {

        /**
         * Router Model
         */
        $this->router_model = model('App\Models\Router');

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
        $userId = session()->get('user_id');
        $status = $this->request->getGet('status') ?? null;

        // Your controller logic here
        return view('SecondAdmin/index', ['userId' => $userId, 'status' => $status]);
    }

    public function contactfetch()
    {

        return view('auth/contact_fetch');
    }

    public function contactfetchall()
    {
        $contactModel = new ContactModel();

        $data = $contactModel->builder()
            ->select('*')
            ->orderBy('id', 'desc');

        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        $datatables->addColumn('select', function ($row) {
            return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
        });

        $datatables->addColumn('Name', function ($row) {
            log_message('error', 'Row name: ' . print_r($row->name, true));
            return $row->name ?? $row['name'] ?? '--';
        });

        $datatables->addColumn('Phone', function ($row) {
            log_message('error', 'Row phone: ' . print_r($row->phone, true));
            return $row->phone ?? $row['phone'] ?? '--';
        });

        $datatables->addColumn('Email', function ($row) {
            log_message('error', 'Row email: ' . print_r($row->email, true));
            return $row->email ?? $row['email'] ?? '--';
        });

        $datatables->addColumn('Message', function ($row) {
            log_message('error', 'Row message: ' . print_r($row->message, true));
            return $row->message ?? $row['message'] ?? '--';
        });



        $datatables->format('created_at', function ($value) {
            return !empty($value) ? date('d M Y, h:i a', strtotime($value)) : '--';
        });

        $datatables->format('updated_at', function ($value) {
            return !empty($value) ? date('d M Y, h:i a', strtotime($value)) : '--';
        });

        $datatables->format('inquiry_type', function ($value) {
            return '<span class="ipb-pay-badge is-info">' . esc(ucfirst((string) $value)) . '</span>';
        });

        $datatables->addColumn('action', function ($row) {
            return '<div class="ipb-row-actions"><button type="button" class="ipb-row-btn tone-info view-inquiry" data-id="' . $row->id . '" title="View" data-toggle="tooltip"><i class="fa fa-eye" aria-hidden="true"></i><span class="sr-only">View</span></button></div>';
        });

        // $datatables->except([
        //     'id',
        //     'phone',
        //     'email',
        //     'message',

        //     'inquiry_type',
        //     'created_at',
        //     'updated_at',
        // ]);

        $datatables->asObject();
        $datatables->generate();
    }


    public function contactdelete()
    {
        // Get the IDs of the inquiries to delete
        $ids = getRawInput('ids');
        log_message('info', 'Fetched select ids: ' . json_encode($ids));

        if (empty($ids)) {
            return $this->response->setJSON(['error' => 'No id is selected.']);
        }

        // Load the ContactModel
        $contactModel = new ContactModel();

        // Delete the inquiries
        $deleted = $contactModel->delete($ids);

        if ($deleted) {
            return $this->response->setJSON(['success' => 'Selected inquiries have been deleted successfully.']);
        } else {
            return $this->response->setJSON(['error' => 'An error occurred while deleting the inquiries.']);
        }
    }

    public function add()
    {
        $userId = session()->get('user_id');
        $packageModel = new ResellerPackages();
        $data['packages'] = $packageModel->findAll();
        $data['userId'] = $userId;
        // Your controller logic here
        return view('reseller/add', $data);
    }


    public function packages()
    {
        $isExpiredSession = (getSession('status') === 'inactive');
        if (!$isExpiredSession && !userHasPermission('profile_update', 'update')) {
            show_404();
        }

        $packageModel = new AdminPackage();
        $userId = session()->get('user_id');
        $details = $this->user_model->where(['id' => $userId])->first();
        $package_id = $details->package_id ?? '--';
        $pre_package = $details->pre_package ?? '--';
        $pending_package_id = $details->pending_package_id ?? null;
        $role = $details->role ?? '--';
        $admin_id = $details->admin_id ?? '--';
        $created_by = $details->created_by ?? '--';
        log_message('info', 'User Data: created_by ' . json_encode($created_by));
        $data['package_id'] = $package_id;
        $data['pre_package'] = $pre_package;
        $data['pending_package_id'] = $pending_package_id;
        $data['trialUser'] = $details;



        if (session()->get('user_role') === 'admin') {
            // Tenants see: public fixed plans + custom plans assigned to THEM.
            // PAYG is switched from the wallet page, never picked here.
            $data['packages'] = $packageModel->where(['Activity' => 'active'])
                ->groupStart()
                    ->groupStart()
                        ->groupStart()
                            ->where('plan_type', AdminPackage::TYPE_FIXED)
                            ->orWhere('plan_type IS NULL', null, false)
                            ->orWhere('plan_type', '')
                        ->groupEnd()
                        ->groupStart()
                            ->where('is_public', 1)
                            ->orWhere('is_public IS NULL', null, false)
                        ->groupEnd()
                    ->groupEnd()
                    ->orGroupStart()
                        ->where('plan_type', AdminPackage::TYPE_CUSTOM)
                        ->where('assigned_user_id', (int) $userId)
                    ->groupEnd()
                ->groupEnd()
                ->findAll();
        } elseif (session()->get('user_role') === 'user') {
            if ($created_by === 'resellerAdmin') {
                log_message('info', 'User Data: admin_id ' . json_encode($admin_id));


                $packageModel = model('App\Models\allResellerPackage');
                $rawPackages = $packageModel->where('user_id', $admin_id)->findAll();

                // Decode the package_details JSON field
                $packages = [];
                foreach ($rawPackages as $package) {
                    $package['package_details'] = json_decode($package['package_details'], true);
                    foreach ($package['package_details'] as $details) {
                        $packages[] = $details;
                    }
                }
                $data['packages'] = $packages;
                // log_message('info', 'User Data: packages ' . json_encode($packages));
            } else {
                // log_message('info', 'User Data: admin_id ' . json_encode($admin_id));
                $package_model = model('App\Models\Package');
                // log_message('info', 'User Data: admin_id 1' . json_encode($admin_id));
                // log_message('info', 'User Data: user 1' . json_encode($data));

                $data['packages'] = $package_model->where(['user_id' => $admin_id])->findAll();
            }
        } else {
            $data['packages'] = $packageModel->findAll();
            // Tenant list for pinning custom plans to a specific ISP admin.
            $data['tenants'] = $this->user_model->builder()
                ->select('id, name, mobile')
                ->where('role', 'admin')
                ->orderBy('name', 'asc')
                ->get()->getResult();
        }
        // log_message('info', 'User Data: ' . json_encode($data));

        return view('SecondAdmin/package', $data);
    }

    /**
     * Extra plan fields (plan type, PAYG rate card, custom-plan assignment,
     * visibility) shared by savePackage() / updatePackage(). Only the
     * platform super-admin may set them.
     */
    private function collectPlanTypeFields(): array
    {
        if (getSession('user_role') !== 'super_admin') {
            return [];
        }

        $planType = $this->request->getPost('plan_type');
        if ($planType === null) {
            return []; // caller didn't send plan fields — leave them untouched
        }
        if (!in_array($planType, [AdminPackage::TYPE_FIXED, AdminPackage::TYPE_PAYG, AdminPackage::TYPE_CUSTOM], true)) {
            $planType = AdminPackage::TYPE_FIXED;
        }

        $fields = [
            'plan_type' => $planType,
            'is_public' => $this->request->getPost('is_public') !== null ? (int) (bool) $this->request->getPost('is_public') : 1,
            'sort_order' => (int) ($this->request->getPost('sort_order') ?? 0),
            'trial_days' => (int) ($this->request->getPost('trial_days') ?? 0),
            'assigned_user_id' => null,
            'base_fee' => 0,
            'per_user_rate' => 0,
            'min_topup' => 0,
        ];

        if ($planType === AdminPackage::TYPE_PAYG) {
            $fields['base_fee'] = (float) ($this->request->getPost('base_fee') ?? 0);
            $fields['per_user_rate'] = (float) ($this->request->getPost('per_user_rate') ?? 0);
            $fields['min_topup'] = (float) ($this->request->getPost('min_topup') ?? 0);

            $addonsRaw = (string) $this->request->getPost('addons');
            $decoded = json_decode($addonsRaw, true);
            if (is_array($decoded)) {
                $fields['addons'] = json_encode(array_values($decoded));
            }
        } elseif ($planType === AdminPackage::TYPE_CUSTOM) {
            $assigned = (int) ($this->request->getPost('assigned_user_id') ?? 0);
            $fields['assigned_user_id'] = $assigned > 0 ? $assigned : null;
            $fields['is_public'] = 0; // custom plans are never publicly listed
        }

        return $fields;
    }

    public function savePackage()
    {
        $packageName = $this->request->getPost('packageName');
        $duration = $this->request->getPost('duration');
        $price = $this->request->getPost('price');
        $preview = $this->request->getPost('preview');
        $pricing_type = $this->request->getPost('pricing_type');

        // PAYG plans bill monthly from the wallet — pricing_type is implicit.
        $isPayg = $this->request->getPost('plan_type') === AdminPackage::TYPE_PAYG;

        // Validate the data if needed
        $validation = \Config\Services::validation();
        $validation->setRules([
            'packageName' => 'required',
            'duration' => 'required|integer',
            'price' => 'permit_empty',
            'pricing_type' => $isPayg ? 'permit_empty|in_list[weekly,monthly,yearly]' : 'required|in_list[weekly,monthly,yearly]',
            'features' => 'permit_empty'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON(['success' => false, 'errors' => $validation->getErrors()]);
        }

        // Insert data into the database
        $packageModel = new AdminPackage();
        $packageModel->checkFeaturesColumn();
        $data = [
            'package_name' => $packageName,
            'duration' => $duration,
            'price' => $price,
            'preview' => $preview,
            'Activity' => 'Active',
            'pricing_type' => $pricing_type ?: 'monthly',
            'features' => $this->request->getPost('features'),
        ] + $this->collectPlanTypeFields();

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

        $packageModel = new AdminPackage();
        $package = $packageModel->find($id);

        if (!$package) {
            return $this->response->setJSON(['success' => false, 'message' => 'Package not found.']);
        }

        return $this->response->setJSON(['success' => true, 'package' => $package]);
    }

    public function updatePackage($id)
    {
        $isPayg = $this->request->getPost('plan_type') === AdminPackage::TYPE_PAYG;

        $this->validate([
            'packageName' => 'required',
            'duration' => 'required',
            'price' => $isPayg ? 'permit_empty' : 'required',
            'pricing_type' => $isPayg ? 'permit_empty|in_list[weekly,monthly,yearly]' : 'required|in_list[weekly,monthly,yearly]',
            'features' => 'permit_empty'
        ]);

        $packageModel = new AdminPackage();
        $packageModel->checkFeaturesColumn();

        $data = [
            'package_name' => $this->request->getPost('packageName'),
            'duration' => $this->request->getPost('duration'),
            'price' => $this->request->getPost('price') ?? 0,
            'preview' => $this->request->getPost('preview'),
            'Activity' => $this->request->getPost('Activity'),
            'pricing_type' => $this->request->getPost('pricing_type') ?: 'monthly',
            'features' => $this->request->getPost('features'),
        ] + $this->collectPlanTypeFields();

        if ($packageModel->update($id, $data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false]);
        }
    }

    public function deletePackage($id)
    {
        $packageModel = new AdminPackage();
        if ($packageModel->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false]);
        }
    }

    public function activatePackage($id)
    {
        helper('subscription');
        $userId = (int) session()->get('user_id');
        log_message('info', 'User Data: here id:' . json_encode($id));

        $userModel = model('App\Models\User');
        $details = $userModel->where(['id' => $userId])->first();

        if (empty($details)) {
            return requestResponse('error', 'User not found.', 404);
        }

        if ((string) ($details->package_id ?? '') === (string) $id
            && (string) ($details->pending_package_id ?? '') !== (string) $id
            && !hasPendingPackageChange($details)
        ) {
            return requestResponse('error', 'You are already on this package.', 400);
        }

        if ((string) ($details->pending_package_id ?? '') === (string) $id) {
            $paymentModel = model('App\Models\Payment');
            $pendingPay = $paymentModel->where('user_id', $userId)
                ->where('status', 'pending')
                ->orderBy('id', 'DESC')
                ->first();
            if ($pendingPay) {
                return $this->response->setJSON([
                    'success' => true,
                    'payment_id' => (int) $pendingPay->id,
                    'payment_url' => route_to('route.payment.pay', $pendingPay->id),
                    'amount' => (float) ($pendingPay->amount ?? 0),
                    'invoice' => $pendingPay->invoice ?? '',
                    'message' => 'Payment is already pending for this package. Complete it from My Payment or pay now.',
                    'current_package_id' => $details->package_id,
                    'pending_package_id' => $details->pending_package_id,
                ]);
            }
        }

        // Plan-type guards: PAYG is switched from the wallet page, and custom
        // plans can only be taken by the tenant they were made for.
        if (getSession('user_role') === 'admin') {
            $targetPackage = (new AdminPackage())->find($id);
            $targetType = is_object($targetPackage) ? ($targetPackage->plan_type ?? 'fixed') : ($targetPackage['plan_type'] ?? 'fixed');
            $targetAssigned = is_object($targetPackage) ? ($targetPackage->assigned_user_id ?? null) : ($targetPackage['assigned_user_id'] ?? null);
            $targetPublic = is_object($targetPackage) ? ($targetPackage->is_public ?? 1) : ($targetPackage['is_public'] ?? 1);

            if (empty($targetPackage)) {
                return requestResponse('error', 'Package not found.', 404);
            }
            if ($targetType === AdminPackage::TYPE_PAYG) {
                return requestResponse('error', 'Switch to Pay-As-You-Go from your Wallet page instead.', 400);
            }
            if ($targetType === AdminPackage::TYPE_CUSTOM && (int) $targetAssigned !== (int) $userId) {
                return requestResponse('error', 'This plan is not available for your account.', 403);
            }
            if ($targetType !== AdminPackage::TYPE_CUSTOM && (int) ($targetPublic ?? 1) !== 1) {
                return requestResponse('error', 'This plan is not available for your account.', 403);
            }
        }

        $result = requestPackageChange($userId, (int) $id);
        if (!$result['ok']) {
            return requestResponse('error', $result['message'] ?? 'Could not request package change.', 400);
        }

        return $this->response->setJSON([
            'success' => true,
            'payment_id' => $result['payment_id'],
            'payment_url' => $result['payment_url'],
            'amount' => $result['amount'],
            'invoice' => $result['invoice'] ?? '',
            'message' => $result['message'],
            'package_name' => $result['package_name'] ?? '',
            'current_package_id' => $result['current_package_id'],
            'pending_package_id' => $result['pending_package_id'],
        ]);
    }


    public function fetch()
    {
        $userId = session()->get('user_id');
        $status = $this->request->getPost('status');


        if ($status === 'inactive') {
            $data = $this->user_model->builder()
                ->select('*')
                ->where('role', 'admin')
                ->where('subscription_status', 'inactive')
                ->orderBy('id', 'desc');
        } elseif ($status === 'active') {
            $data = $this->user_model->builder()
                ->select('*')
                ->where('role', 'admin')
                ->where('subscription_status', 'active')
                ->orderBy('id', 'desc');
        } else {
            $data = $this->user_model->builder()
                ->select('*')
                ->where('role', 'admin')

                ->orderBy('id', 'desc');
        }

        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('resellerAdmin', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('package', function ($row) {

            return getAdminPackage($row->id)['package_name'] ?? '--';
        });


        $datatables->format('created_at', function ($value) {

            return date("d-m-Y, h:i a", strtotime($value));
        });

        $datatables->addColumn('subscription_status', function ($row) {

            return ($row->subscription_status === 'inactive')
                ? '<span class="ipb-pay-badge is-danger">Pending</span>'
                : '<span class="ipb-pay-badge is-success">Paid</span>';
        });

        $datatables->format('auto_disconnect', function ($value) {

            return ($value === 'yes')
                ? '<span class="ipb-pay-badge is-success">Yes</span>'
                : '<span class="ipb-pay-badge is-danger">No</span>';
        });

        $datatables->format('conn_status', function ($value) {

            return ($value === 'conn')
                ? '<span class="ipb-pay-badge is-success">Connected</span>'
                : '<span class="ipb-pay-badge is-danger">Disconnected</span>';
        });



        $datatables->format('status', function ($value) {

            return ($value === 'active')
                ? '<span class="ipb-pay-badge is-success">Active</span>'
                : '<span class="ipb-pay-badge is-danger">Inactive</span>';
        });

        $datatables->addColumn('action', function ($row) {

            $html = '<div class="ipb-row-actions">';
            $html .= '<a href="' . route_to('route.Admin.details', $row->id) . '" class="ipb-row-btn tone-info" title="View details" data-toggle="tooltip"><i class="far fa-eye" aria-hidden="true"></i><span class="sr-only">Details</span></a>';
            $html .= '<button type="button" class="ipb-row-btn tone-violet" title="Copy subscription link" data-toggle="tooltip" onclick="navigator.clipboard.writeText(\'' . base_url('subscription/' . $row->id) . '\'); alert(\'Subscription link copied!\');"><i class="fas fa-link" aria-hidden="true"></i><span class="sr-only">Link</span></button>';

            if (getSession('user_role') === 'super_admin') {
                $html .= '<a href="' . route_to('route.Admin.adminsubscription', $row->id) . '" class="ipb-row-btn tone-success" title="Update subscription" data-toggle="tooltip"><i class="fa fa-bolt" aria-hidden="true"></i><span class="sr-only">Subscription</span></a>';
            }
            if (userHasPermission('admin', 'update_subscription') && getSession('user_role') === 'admin') {
                $html .= '<a href="' . route_to('route.Admin.subscription', $row->id) . '" class="ipb-row-btn tone-success" title="Update subscription" data-toggle="tooltip"><i class="fa fa-bolt" aria-hidden="true"></i><span class="sr-only">Subscription</span></a>';
            }

            if (userHasPermission('admin', 'update')) {
                $html .= '<a href="' . route_to('route.Admin.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update" data-toggle="tooltip"><i class="far fa-pen-to-square" aria-hidden="true"></i><span class="sr-only">Update</span></a>';
            }

            $html .= '<a href="' . route_to('route.routers') . '?id=' . $row->id . '" class="ipb-row-btn tone-slate" title="Routers" data-toggle="tooltip"><i class="fa fa-network-wired" aria-hidden="true"></i><span class="sr-only">Routers</span></a>';
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
            'role',
            'password',
            'updated_at',
            'admin_id',
        ]);

        $datatables->asObject();

        $datatables->generate();
    }



    public function details($id)
    {
        $details = $this->user_model->where(['id' => $id, 'role' => 'admin'])->first();

        if (is_object($details)) {
            $mobilenum = $details->mobile;
            $uid = $details->id;
        } elseif (is_array($details)) {
            $mobilenum = $details['mobile'];
            $uid = $details['id'];
        } else {
            $mobilenum = null; // Handle case where $details is neither an object nor an array
        }

        $rdetails = $this->reseller_model->where(['userid' => $uid])->first();



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
        return view('SecondAdmin/details', $data);
    }

    public function adminsubscription($id)
    {
        $details = $this->user_model->where(['id' => $id])->first();

        if (!empty($details)) {

            $package_model = new AdminPackage();

            $data = [
                'title' => 'Customer\'s Subscription',
                'details' => $details,
                'packages' => $package_model->findAll(),
            ];

            // Platform admin gets the tenant's wallet + PAYG state and any
            // custom plan request from registration.
            if (getSession('user_role') === 'super_admin' && ($details->role ?? '') === 'admin') {
                try {
                    $billing = new \App\Services\PaygBillingService();
                    $walletService = new \App\Services\WalletService();
                    $data['wallet'] = $walletService->ensureWallet((int) $details->id);
                    $data['walletEstimate'] = $billing->estimate((int) $details->id);
                    $data['isPaygTenant'] = $billing->isPaygUser($details);
                    $data['paygPlan'] = $billing->paygPlan();
                    $data['registration'] = model('App\Models\Registration')->where('userid', $details->id)->first();
                    $data['walletLedger'] = model('App\Models\WalletTransaction')
                        ->where('user_id', $details->id)
                        ->orderBy('id', 'desc')
                        ->findAll(15);
                } catch (\Throwable $e) {
                    log_message('error', 'adminsubscription wallet context failed: ' . $e->getMessage());
                }
            }

            return view('SecondAdmin/admin_subscription', $data);
        }

        show_404();
    }

    /**
     * Platform admin: manual wallet credit/debit for a tenant (ledgered).
     */
    public function walletAdjust($id)
    {
        if (getSession('user_role') !== 'super_admin') {
            return requestResponse('error', 'Only the platform admin can adjust wallets.', 403);
        }

        $tenant = $this->user_model->where(['id' => $id, 'role' => 'admin'])->first();
        if (empty($tenant)) {
            return requestResponse('error', 'Tenant not found.', 404);
        }

        $amount = (float) $this->request->getPost('amount');
        $description = trim((string) $this->request->getPost('description'));

        if ($amount == 0.0) {
            return requestResponse('error', 'Enter a non-zero amount (negative to deduct).', 400);
        }
        if ($description === '') {
            return requestResponse('error', 'A reason is required for manual wallet adjustments.', 400);
        }

        $ok = (new \App\Services\WalletService())->adjust(
            (int) $tenant->id,
            $amount,
            $description,
            (int) getSession('user_id')
        );

        if (!$ok) {
            return requestResponse('error', 'Adjustment failed — a deduction cannot exceed the current balance.', 400);
        }

        // A credit can instantly reactivate a suspended PAYG tenant.
        if ($amount > 0) {
            try {
                $billing = new \App\Services\PaygBillingService();
                $fresh = $this->user_model->find($tenant->id);
                if ($billing->isPaygUser($fresh)
                    && (($fresh->subscription_status ?? '') === 'inactive'
                        || empty($fresh->will_expire) || strtotime($fresh->will_expire) <= time())
                ) {
                    $billing->runCycle($fresh, true);
                }
            } catch (\Throwable $e) {
                log_message('error', 'walletAdjust reactivation failed: ' . $e->getMessage());
            }
        }

        return requestResponse('success', 'Wallet adjusted successfully.', 200);
    }

    /**
     * Platform admin: switch a tenant between fixed and Pay-As-You-Go billing.
     * Remaining paid days carry over in both directions.
     */
    public function switchBillingMode($id)
    {
        if (getSession('user_role') !== 'super_admin') {
            return requestResponse('error', 'Only the platform admin can switch billing modes.', 403);
        }

        $tenant = $this->user_model->where(['id' => $id, 'role' => 'admin'])->first();
        if (empty($tenant)) {
            return requestResponse('error', 'Tenant not found.', 404);
        }

        $mode = $this->request->getPost('mode');

        if ($mode === 'payg') {
            $billing = new \App\Services\PaygBillingService();
            $plan = $billing->paygPlan();
            if (empty($plan)) {
                return requestResponse('error', 'No active PAYG plan configured.', 400);
            }
            $planId = (int) (is_object($plan) ? $plan->id : $plan['id']);

            (new \App\Services\WalletService())->ensureWallet((int) $tenant->id);
            $this->user_model->update($tenant->id, [
                'package_id' => $planId,
                'pre_package' => $tenant->package_id,
                'billing_type' => 'prepaid',
                'pending_package_id' => null,
            ]);

            // If they're already expired, bill the first cycle immediately.
            $fresh = $this->user_model->find($tenant->id);
            if (empty($fresh->will_expire) || strtotime($fresh->will_expire) <= time()) {
                $billing->runCycle($fresh, true);
            }

            return requestResponse('success', 'Tenant switched to Pay-As-You-Go.', 200);
        }

        if ($mode === 'fixed') {
            $packageId = (int) $this->request->getPost('package_id');
            $package = (new AdminPackage())->find($packageId);
            $pkgType = is_object($package) ? ($package->plan_type ?? 'fixed') : ($package['plan_type'] ?? 'fixed');
            if (empty($package) || $pkgType === AdminPackage::TYPE_PAYG) {
                return requestResponse('error', 'Pick a valid fixed or custom plan.', 400);
            }

            $this->user_model->update($tenant->id, [
                'package_id' => $packageId,
                'pre_package' => $tenant->package_id,
                'billing_type' => 'postpaid',
                'pending_package_id' => null,
            ]);

            $fresh = $this->user_model->find($tenant->id);
            $expireTs = !empty($fresh->will_expire) ? strtotime($fresh->will_expire) : 0;
            if ($expireTs > time()) {
                $this->user_model->update($tenant->id, [
                    'subscription_status' => 'active',
                    'conn_status' => 'conn',
                ]);
            }

            return requestResponse('success', 'Tenant switched to fixed-plan billing. Their current expiry date is unchanged.', 200);
        }

        return requestResponse('error', 'Unknown billing mode.', 400);
    }



    /**
     * Customers
     * @action: Update Customer Subscription
     */
    public function updateSubscription($id)
    {
        $this->validate([
            'package_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select a package',
                ]
            ],
            'last_renewed' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select renewal date',
                ]
            ],
            'will_expire' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select expire date',
                ]
            ],
        ]);

        if ($this->validation->run()) {
            $lastRenewed = $this->normalizeAdminDatetimeInput(getPostInput('last_renewed'));
            $willExpire = $this->normalizeAdminDatetimeInput(getPostInput('will_expire'));

            if ($lastRenewed === null || $willExpire === null) {
                return requestResponse('validation-error', [
                    'last_renewed' => 'Enter a valid renewal date',
                    'will_expire' => 'Enter a valid expire date',
                ], 400);
            }

            $now = time();
            $expireTs = strtotime($willExpire);
            $packageId = (int) getPostInput('package_id');
            $package = (new AdminPackage())->find($packageId);
            $pkgType = is_object($package) ? ($package->plan_type ?? 'fixed') : ($package['plan_type'] ?? 'fixed');

            $data = [
                'package_id' => $packageId,
                'last_renewed' => $lastRenewed,
                'will_expire' => $willExpire,
                'pending_package_id' => null,
                'subscription_status' => $expireTs > $now ? 'active' : 'inactive',
                'conn_status' => $expireTs > $now ? 'conn' : 'disconn',
                'billing_type' => $pkgType === AdminPackage::TYPE_PAYG ? 'prepaid' : 'postpaid',
            ];

            $result = $this->user_model->where(['id' => $id])->set($data)->update();

            if ($result) {
                return requestResponse('success', 'Subscription updated successfully', 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Convert HTML datetime-local values to MySQL datetime strings.
     */
    protected function normalizeAdminDatetimeInput(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = str_replace('T', ' ', trim($value));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
            $normalized .= ':00';
        }

        $ts = strtotime($normalized);

        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }
    public function subscription($id)
    {
        $role = getSession('user_role');
        $callerId = (int) getSession('user_id');

        // Self-service subscription page: only the account owner may view it.
        if ($callerId !== (int) $id) {
            show_404();
        }

        if ($role === 'admin') {
            $details = $this->user_model->where(['id' => $id, 'role' => 'admin'])->first();
            $rdetails = $this->reseller_model->where(['userid' => $id])->first();

            if (!empty($details)) {

                $package_model = model('App\Models\AdminPackage');

                $data = [
                    'title' => 'Admin\'s Subscription',
                    'rdetails' => $rdetails,
                    'details' => $details,
                    'packages' => $package_model->findAll(),
                    'trialUser' => $details,
                ];

                return view('SecondAdmin/subscription', $data);
            }
        }elseif($role === 'resellerAdmin'){
            $details = $this->user_model->where(['id' => $id])->first();
            log_message('info', 'User Data: ' . json_encode($details));

            if (!empty($details)) {

                // $package_model = model('App\Models\AdminPackage');

                $data = [
                    'title' => 'Reseller\'s Subscription',
                    'details' => $details,
                    // 'packages' => $package_model->findAll(),
                ];

                return view('SecondAdmin/Resellersubscription', $data);
            }
        }

        show_404();
    }

    public function delete()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            // Delete users with matching IDs
            log_message('info', 'Fetched delete data: ' . json_encode($ids));

            // Initialize Registration model
            $registrationModel = new Registration();


            // Get all resellers with the provided admin IDs
            $resellers = $this->user_model->whereIn('admin_id', $ids)
                ->where('role', 'resellerAdmin')
                ->get()->getResultArray();
            log_message('info', 'Fetched admin_id delete data: ' . json_encode($resellers));

            if (!empty($resellers)) {
                $resellerIds = array_column($resellers, 'id');
                log_message('info', 'Fetched resellerIds delete data: ' . json_encode($resellerIds));



                $users = $this->user_model->whereIn('admin_id', $resellerIds)
                    ->where('role', 'user')
                    ->get()->getResultArray();

                if (!empty($users)) {

                    $user_router_model = new UserRouterDataModel();
                    $userIds = array_column($users, 'id');
                    // Check if user_id exists in user_router_model before deleting
                    $userRouterData = $user_router_model->whereIn('user_id', $userIds)->delete();

                    if (!empty($userRouterData)) {
                        // If user_router_model has records, delete them
                        log_message('info', 'deleted customer delete data: ');
                    }
                }
                // Delete all customers associated with each reseller
                $this->user_model->whereIn('admin_id', $resellerIds)->delete();

                $registrationModel->whereIn('userid', $resellerIds)->delete();
                $this->user_model->whereIn('id', $resellerIds)->delete();
            }

            // Delete the admins' own customers and employees,resellers

            $mainusers = $this->user_model->whereIn('admin_id', $ids)
                ->where('role', 'user')
                ->get()->getResultArray();

            if (!empty($mainusers)) {

                $user_router_model = new UserRouterDataModel();
                $mainuserIds = array_column($mainusers, 'id');
                // Check if user_id exists in user_router_model before deleting
                $userRouterData = $user_router_model->whereIn('user_id', $mainuserIds)->delete();

                if (!empty($userRouterData)) {
                    // If user_router_model has records, delete them
                    log_message('info', 'deleted customer delete data: ');
                }
            }
            $this->user_model->whereIn('id', $ids)->delete();
            $registrationModel->whereIn('userid', $ids)->delete();

            $result = $this->user_model->whereIn('admin_id', $ids)->delete();
            $registrationModel->whereIn('userid', $ids)->delete();

            if ($result) {
                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected", 400);
    }


    public function edit($id)
    {
        $details = $this->user_model->where(['id' => $id, 'role' => 'admin'])->first();

        if (!empty($details)) {

            $area_model = model('App\Models\Area');
            $package_model = model('App\Models\Package');
            $rdetails = $this->reseller_model->where(['userid' => $id])->first();




            // $user_ppp = getPPPoEUser($router_client, $details->pppoe_id);

            $data = [
                'title' => 'Update Admin',
                'rdetails' => $rdetails,
                'areas' => $area_model->where('status', 'active')->findAll(),
                'packages' => $package_model->where('status', 'active')->findAll(),
                'details' => $details,
                // 'router'            => getRouterById($details->router_id)->name ?? '--',
                // 'pppoe_name'        => $user_ppp[0]['name'],
                // 'pppoe_password'    => $user_ppp[0]['password'],
                // 'pppoe_service'     => $user_ppp[0]['service'],
                // 'pppoe_profile'     => $user_ppp[0]['profile'],
            ];

            return view('SecondAdmin/edit', $data);


            // return view('routers/error', [
            //     'title' => 'Mikrotik Error',
            //     'error' => 'error',
            //     'router_id' => $details->router_id,
            // ]);
        }

        show_404();
    }


    public function update($id)
    {
        $this->validate([
            'name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter Admin\'s name',
                ]
            ],
            // 'area_id' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Select service area',
            //     ]
            // ],
            'mobile' => [
                'rules' => 'required|is_unique[users.mobile, id, ' . $id . ']',
                'errors' => [
                    'required' => 'Enter Admin\'s mobile number',
                    'is_unique' => 'Another account is using this number',
                ]
            ],
            'address' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter Admin\'s address',
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




            $data = [
                'name' => getPostInput('name'),
                'area_id' => getPostInput('area_id'),
                'mobile' => getPostInput('mobile'),
                'address' => getPostInput('address'),
                'email' => getPostInput('email'),
                'auto_disconnect' => getPostInput('auto_disconnect') ?? 'no',
                'status' => getPostInput('status'),
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
            log_message('info', "data.... " . print_r($data));
            $result = $this->user_model->where(['id' => $id, 'role' => 'admin'])->set($data)->update();

            if ($result) {

                return requestResponse('success', "Admin record updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Platform admin revenue report (sAdmin subscription payments).
     */
    public function revenue()
    {
        if (session()->get('user_role') !== 'super_admin') {
            show_404();
        }

        $packageModel = new AdminPackage();
        $packages = $packageModel->orderBy('package_name', 'ASC')->findAll();
        $sAdmins = $this->user_model
            ->select('id, name, email')
            ->where('role', 'admin')
            ->orderBy('name', 'ASC')
            ->findAll();

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = date('F', mktime(0, 0, 0, $m, 1));
        }

        $years = [];
        $currentYear = (int) date('Y');
        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
            $years[] = $y;
        }

        $summary = $this->getPlatformRevenueSummary();
        $packageStats = $this->getPackageStats($this->defaultPackagePeriodFilters());

        return view('SecondAdmin/revenue', [
            'title' => 'Platform Revenue',
            'packages' => $packages,
            'sAdmins' => $sAdmins,
            'months' => $months,
            'years' => $years,
            'summary' => $summary,
            'packageStats' => $packageStats,
        ]);
    }

    /**
     * AJAX: Admins by package — occupied now + payments in selected month/range.
     */
    public function revenuePackageStats()
    {
        if (session()->get('user_role') !== 'super_admin') {
            return requestResponse('error', 'Unauthorized', 403);
        }

        $stats = $this->getPackageStats($this->parsePackagePeriodFilters());

        return $this->response->setJSON([
            'status' => 'success',
            'package_stats' => $stats,
        ]);
    }

    private function defaultPackagePeriodFilters(): array
    {
        return [
            'period_type' => 'single',
            'month' => date('F'),
            'year' => (int) date('Y'),
            'from_month' => '',
            'from_year' => '',
            'to_month' => '',
            'to_year' => '',
            'status' => 'successful',
        ];
    }

    private function parsePackagePeriodFilters(): array
    {
        return [
            'period_type' => (string) ($this->request->getPost('period_type') ?? 'single'),
            'month' => trim((string) ($this->request->getPost('month') ?? '')),
            'year' => $this->request->getPost('year') ?? '',
            'from_month' => trim((string) ($this->request->getPost('from_month') ?? '')),
            'from_year' => $this->request->getPost('from_year') ?? '',
            'to_month' => trim((string) ($this->request->getPost('to_month') ?? '')),
            'to_year' => $this->request->getPost('to_year') ?? '',
            'status' => trim((string) ($this->request->getPost('status') ?? 'successful')),
        ];
    }

    /**
     * Occupied now (current package_id on sAdmin users) + subscription payments in period.
     */
    private function getPackageStats(array $filters): array
    {
        $db = db_connect();

        $occupiedRows = $db->table('users')
            ->select('users.package_id, COALESCE(admin_packages.package_name, "No package") as package_name, COUNT(users.id) as occupied_count')
            ->join('admin_packages', 'admin_packages.id = users.package_id', 'left')
            ->where('users.role', 'admin')
            ->groupBy('users.package_id')
            ->orderBy('occupied_count', 'DESC')
            ->get()
            ->getResultArray();

        $periodBuilder = $db->table('payments')
            ->select('users.package_id, COALESCE(admin_packages.package_name, "No package") as package_name, COUNT(payments.id) as payment_count, COUNT(DISTINCT payments.user_id) as admin_count, COALESCE(SUM(payments.amount), 0) as total_amount')
            ->join('users', 'users.id = payments.user_id', 'inner')
            ->join('admin_packages', 'admin_packages.id = users.package_id', 'left')
            ->where('users.role', 'admin');

        $status = $filters['status'] ?? 'successful';
        if ($status !== '' && $status !== 'all') {
            $periodBuilder->where('payments.status', $status);
        }

        $periodLabel = $this->applyPackagePeriodFilter($periodBuilder, $filters);

        $periodRows = $periodBuilder
            ->groupBy('users.package_id')
            ->orderBy('payment_count', 'DESC')
            ->get()
            ->getResultArray();

        $merged = [];
        foreach ($occupiedRows as $row) {
            $pid = (string) ($row['package_id'] ?? '0');
            $merged[$pid] = [
                'package_id' => $row['package_id'],
                'package_name' => $row['package_name'] ?? 'No package',
                'occupied_count' => (int) ($row['occupied_count'] ?? 0),
                'payment_count' => 0,
                'admin_count' => 0,
                'total_amount' => 0.0,
            ];
        }
        foreach ($periodRows as $row) {
            $pid = (string) ($row['package_id'] ?? '0');
            if (!isset($merged[$pid])) {
                $merged[$pid] = [
                    'package_id' => $row['package_id'],
                    'package_name' => $row['package_name'] ?? 'No package',
                    'occupied_count' => 0,
                    'payment_count' => 0,
                    'admin_count' => 0,
                    'total_amount' => 0.0,
                ];
            }
            $merged[$pid]['payment_count'] = (int) ($row['payment_count'] ?? 0);
            $merged[$pid]['admin_count'] = (int) ($row['admin_count'] ?? 0);
            $merged[$pid]['total_amount'] = (float) ($row['total_amount'] ?? 0);
        }

        usort($merged, static function ($a, $b) {
            return ($b['occupied_count'] + $b['payment_count']) <=> ($a['occupied_count'] + $a['payment_count']);
        });

        $rows = array_values($merged);
        $totals = [
            'occupied' => array_sum(array_column($rows, 'occupied_count')),
            'payment_count' => array_sum(array_column($rows, 'payment_count')),
            'admin_count' => array_sum(array_column($rows, 'admin_count')),
            'total_amount' => array_sum(array_column($rows, 'total_amount')),
        ];

        return [
            'period_label' => $periodLabel,
            'period_type' => $filters['period_type'] ?? 'single',
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    private function applyPackagePeriodFilter($builder, array $filters): string
    {
        $type = $filters['period_type'] ?? 'single';

        if ($type === 'all') {
            return 'All time';
        }

        if ($type === 'range') {
            $start = $this->monthYearToStart($filters['from_month'] ?? '', $filters['from_year'] ?? '');
            $end = $this->monthYearToEnd($filters['to_month'] ?? '', $filters['to_year'] ?? '');
            if ($start && $end) {
                $builder->where('COALESCE(payments.paid_at, payments.created_at) >=', $start);
                $builder->where('COALESCE(payments.paid_at, payments.created_at) <=', $end);

                return trim(($filters['from_month'] ?? '') . ' ' . ($filters['from_year'] ?? ''))
                    . ' – '
                    . trim(($filters['to_month'] ?? '') . ' ' . ($filters['to_year'] ?? ''));
            }

            return 'Invalid range';
        }

        // single month (default)
        $month = $filters['month'] ?? '';
        $year = (int) ($filters['year'] ?? 0);
        if ($month !== '' && $year > 0) {
            $builder->where('payments.month', $month);
            $builder->groupStart()
                ->where('YEAR(COALESCE(payments.paid_at, payments.created_at)) =', $year, false)
                ->orLike('payments.period', $year . '-', 'after')
                ->groupEnd();

            return $month . ' ' . $year;
        }

        return 'All time';
    }

    private function monthYearToStart(string $month, $year): ?string
    {
        $m = $this->monthNameToNumber($month);
        $y = (int) $year;
        if ($m < 1 || $m > 12 || $y < 1970) {
            return null;
        }

        return sprintf('%04d-%02d-01 00:00:00', $y, $m);
    }

    private function monthYearToEnd(string $month, $year): ?string
    {
        $start = $this->monthYearToStart($month, $year);
        if (!$start) {
            return null;
        }

        return date('Y-m-t 23:59:59', strtotime($start));
    }

    private function monthNameToNumber(string $month): int
    {
        $month = trim($month);
        if ($month === '') {
            return 0;
        }
        $ts = strtotime('1 ' . $month . ' 2000');

        return $ts ? (int) date('n', $ts) : 0;
    }

    /**
     * Filtered revenue rows + totals for platform admin (DataTables server-side).
     */
    public function revenueFetch()
    {
        if (session()->get('user_role') !== 'super_admin') {
            return requestResponse('error', 'Unauthorized', 403);
        }

        $name = trim((string) ($this->request->getPost('name') ?? $this->request->getGet('name') ?? ''));
        $packageId = $this->request->getPost('package_id') ?? $this->request->getGet('package_id') ?? '';
        $month = trim((string) ($this->request->getPost('month') ?? $this->request->getGet('month') ?? ''));
        $year = $this->request->getPost('year') ?? $this->request->getGet('year') ?? '';
        $status = trim((string) ($this->request->getPost('status') ?? $this->request->getGet('status') ?? 'successful'));
        $adminId = $this->request->getPost('admin_id') ?? $this->request->getGet('admin_id') ?? '';

        $filters = [
            'name' => $name,
            'package_id' => $packageId,
            'month' => $month,
            'year' => $year,
            'status' => $status,
            'admin_id' => $adminId,
        ];

        $draw = (int) ($this->request->getPost('draw') ?? 1);
        $start = max(0, (int) ($this->request->getPost('start') ?? 0));
        $length = (int) ($this->request->getPost('length') ?? 25);
        if ($length === -1) {
            $length = 500;
        }
        if ($length <= 0 || $length > 500) {
            $length = 25;
        }

        [$orderCol, $orderDir] = $this->resolveRevenueOrder();

        $filteredCount = $this->buildPlatformRevenueQuery($filters)->countAllResults(false);

        $rows = $this->buildPlatformRevenueQuery($filters)
            ->orderBy($orderCol, $orderDir)
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $totals = $this->getPlatformRevenueTotals($filters);
        $summary = $this->getPlatformRevenueSummary();

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $filteredCount,
            'recordsFiltered' => $filteredCount,
            'data' => $rows,
            'totals' => $totals,
            'summary' => $summary,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveRevenueOrder(): array
    {
        $columns = [
            0 => 'payments.id',
            1 => 'payments.invoice',
            2 => 'users.name',
            3 => 'users.email',
            4 => 'admin_packages.package_name',
            5 => 'payments.month',
            6 => 'payments.amount',
            7 => 'payments.paid_via',
            8 => 'payments.status',
            9 => 'payments.paid_at',
        ];

        $col = 'payments.id';
        $dir = 'DESC';
        $orders = $this->request->getPost('order');

        if (is_array($orders) && isset($orders[0])) {
            $idx = (int) ($orders[0]['column'] ?? 0);
            $col = $columns[$idx] ?? 'payments.id';
            $dir = strtoupper((string) ($orders[0]['dir'] ?? 'desc')) === 'ASC' ? 'ASC' : 'DESC';
        }

        return [$col, $dir];
    }

    private function buildPlatformRevenueQuery(array $filters = [])
    {
        $db = db_connect();
        $builder = $db->table('payments')
            ->select('payments.id, payments.invoice, payments.amount, payments.pay_amount, payments.month, payments.period, payments.status, payments.paid_via, payments.paid_at, payments.created_at, payments.user_id, users.name as admin_name, users.email as admin_email, users.package_id, COALESCE(admin_packages.package_name, "No package") as package_name')
            ->join('users', 'users.id = payments.user_id', 'inner')
            ->join('admin_packages', 'admin_packages.id = users.package_id', 'left')
            ->where('users.role', 'admin');

        $status = $filters['status'] ?? 'successful';
        if ($status !== '' && $status !== 'all') {
            $builder->where('payments.status', $status);
        }

        if (!empty($filters['name'])) {
            $builder->groupStart()
                ->like('users.name', $filters['name'])
                ->orLike('users.email', $filters['name'])
                ->orLike('payments.invoice', $filters['name'])
                ->groupEnd();
        }

        if (!empty($filters['package_id'])) {
            $builder->where('users.package_id', (int) $filters['package_id']);
        }

        if (!empty($filters['admin_id'])) {
            $builder->where('payments.user_id', (int) $filters['admin_id']);
        }

        if (!empty($filters['month'])) {
            $builder->where('payments.month', $filters['month']);
        }

        if (!empty($filters['year'])) {
            $year = (int) $filters['year'];
            $builder->groupStart()
                ->where('YEAR(COALESCE(payments.paid_at, payments.created_at)) =', $year, false)
                ->orLike('payments.period', $year . '-', 'after')
                ->groupEnd();
        }

        return $builder;
    }

    private function getPlatformRevenueTotals(array $filters = []): array
    {
        $db = db_connect();
        $builder = $db->table('payments')
            ->select('COALESCE(SUM(payments.amount), 0) as total_amount, COUNT(payments.id) as total_count, COUNT(DISTINCT payments.user_id) as admin_count')
            ->join('users', 'users.id = payments.user_id', 'inner')
            ->where('users.role', 'admin');

        $status = $filters['status'] ?? 'successful';
        if ($status !== '' && $status !== 'all') {
            $builder->where('payments.status', $status);
        }
        if (!empty($filters['name'])) {
            $builder->groupStart()
                ->like('users.name', $filters['name'])
                ->orLike('users.email', $filters['name'])
                ->orLike('payments.invoice', $filters['name'])
                ->groupEnd();
        }
        if (!empty($filters['package_id'])) {
            $builder->where('users.package_id', (int) $filters['package_id']);
        }
        if (!empty($filters['admin_id'])) {
            $builder->where('payments.user_id', (int) $filters['admin_id']);
        }
        if (!empty($filters['month'])) {
            $builder->where('payments.month', $filters['month']);
        }
        if (!empty($filters['year'])) {
            $year = (int) $filters['year'];
            $builder->groupStart()
                ->where('YEAR(COALESCE(payments.paid_at, payments.created_at)) =', $year, false)
                ->orLike('payments.period', $year . '-', 'after')
                ->groupEnd();
        }

        $row = $builder->get()->getRowArray();

        return [
            'total_amount' => (float) ($row['total_amount'] ?? 0),
            'total_count' => (int) ($row['total_count'] ?? 0),
            'admin_count' => (int) ($row['admin_count'] ?? 0),
        ];
    }

    private function getPlatformRevenueSummary(): array
    {
        $db = db_connect();
        $thisMonth = date('F');
        $thisYear = (int) date('Y');
        $lastMonthTs = strtotime('first day of last month');
        $lastMonth = date('F', $lastMonthTs);
        $lastMonthYear = (int) date('Y', $lastMonthTs);

        $sumFor = function (string $month, int $year, string $status = 'successful') use ($db) {
            $row = $db->table('payments')
                ->select('COALESCE(SUM(payments.amount), 0) as total, COUNT(payments.id) as cnt')
                ->join('users', 'users.id = payments.user_id', 'inner')
                ->where('users.role', 'admin')
                ->where('payments.status', $status)
                ->where('payments.month', $month)
                ->groupStart()
                    ->where('YEAR(COALESCE(payments.paid_at, payments.created_at)) =', $year, false)
                    ->orLike('payments.period', $year . '-', 'after')
                ->groupEnd()
                ->get()
                ->getRowArray();

            return [
                'amount' => (float) ($row['total'] ?? 0),
                'count' => (int) ($row['cnt'] ?? 0),
            ];
        };

        $allTime = $db->table('payments')
            ->select('COALESCE(SUM(payments.amount), 0) as total, COUNT(payments.id) as cnt')
            ->join('users', 'users.id = payments.user_id', 'inner')
            ->where('users.role', 'admin')
            ->where('payments.status', 'successful')
            ->get()
            ->getRowArray();

        $pending = $db->table('payments')
            ->select('COALESCE(SUM(payments.amount), 0) as total, COUNT(payments.id) as cnt')
            ->join('users', 'users.id = payments.user_id', 'inner')
            ->where('users.role', 'admin')
            ->where('payments.status', 'pending')
            ->get()
            ->getRowArray();

        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = strtotime("-{$i} months");
            $m = date('F', $ts);
            $y = (int) date('Y', $ts);
            $data = $sumFor($m, $y);
            $monthly[] = [
                'label' => date('M Y', $ts),
                'month' => $m,
                'year' => $y,
                'amount' => $data['amount'],
                'count' => $data['count'],
            ];
        }

        $packageUsers = $db->table('users')
            ->select('COALESCE(admin_packages.package_name, "No package") as package_name, COUNT(users.id) as user_count')
            ->join('admin_packages', 'admin_packages.id = users.package_id', 'left')
            ->where('users.role', 'admin')
            ->groupBy('users.package_id')
            ->orderBy('user_count', 'DESC')
            ->get()
            ->getResultArray();

        $thisMonthData = $sumFor($thisMonth, $thisYear);
        $lastMonthData = $sumFor($lastMonth, $lastMonthYear);

        return [
            'total_all' => (float) ($allTime['total'] ?? 0),
            'total_all_count' => (int) ($allTime['cnt'] ?? 0),
            'this_month' => $thisMonthData['amount'],
            'this_month_count' => $thisMonthData['count'],
            'last_month' => $lastMonthData['amount'],
            'last_month_count' => $lastMonthData['count'],
            'pending' => (float) ($pending['total'] ?? 0),
            'pending_count' => (int) ($pending['cnt'] ?? 0),
            'monthly' => $monthly,
            'package_users' => $packageUsers,
            'this_month_label' => date('F Y'),
            'last_month_label' => date('F Y', $lastMonthTs),
        ];
    }
}
