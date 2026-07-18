<?php

namespace App\Controllers;

use Exception;
use Mpdf\Mpdf;
use App\Controllers\BaseController;


use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

// require 'vendor/autoload.php';


class CustomerPayment extends BaseController
{

    protected $payment_model, $user_model, $reseller_model;

    public function __construct()
    {

        /**
         * Payment Model
         */
        $this->payment_model = model('App\Models\Payment');
        $this->reseller_model = model('App\Models\Registration');
        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
    }

    /**
     * Customer Payment
     * @action: Customer Payment View
     */
    public function index()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');


        $today = date('Y-m-d H:i:s');
        $today = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($today)));

        if ($userole === 'super_admin') {
            $data = $this->payment_model->builder()
                ->select('*')
                ->where('user_type', 'user')
                ->groupStart()
                ->where('admin_id', $userId)
                ->orWhere('paid_to', $userId)
                ->groupEnd()
                ->where('created_at >=', $today)
                ->orderBy('id', 'desc');
        } elseif ($userole === 'admin') {
            $data = $this->payment_model->builder()
                ->select('*')
                ->where('user_type', 'user')
                ->where('created_at >=', $today)
                ->orderBy('id', 'desc');
        } else {
            $data = $this->payment_model->builder()
                ->select('*')
                ->where('user_type', 'user')
                ->groupStart()
                ->where('admin_id', $userId)
                ->orWhere('paid_to', $userId)
                ->groupEnd()
                ->where('created_at >=', $today)
                ->orderBy('id', 'desc');
        }

        $actualToday = date('Y-m-d 00:00:00');
        $todayAmountQuery = $this->payment_model->builder()
            ->selectSum('amount')
            ->where('user_type', 'user')
            ->groupStart()
            ->where('admin_id', $userId)
            ->orWhere('paid_to', $userId)
            ->groupEnd()
            ->where('created_at >=', $actualToday)
            ->where('status', 'successful');

        $todayAmount = $todayAmountQuery->get()->getRow()->amount ?? 0;

        $status = $this->request->getGet('status');

        $usersQuery = $this->user_model->where(['role' => 'user', 'status' => 'active']);

        $usersQuery->where('admin_id', $userId);

        $users = $usersQuery->findAll();

        $data = [
            'title' => 'Customers Payment',
            'todayAmount' => $todayAmount,
            'status' => $status,
            'users' => $users,
        ];

        return view('payments/customer/all', $data);
    }

    public function user_index($id)
    {
        $userId = session()->get('user_id');

        log_message('debug', 'Loading payment view for user ID: ' . $id);
        log_message('debug', 'Session user ID: ' . $userId);

        // $userole = session()->get('user_role');

        $today = date('Y-m-d H:i:s');
        $today = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($today)));

        if (!empty($id)) {
            // Shared base criteria to avoid repetition
            $baseCriteria = [
                'user_type' => 'user',
                'admin_id' => $userId
            ];

            // Calculate Successful Amount
            $successfulAmount = $this->payment_model->builder()
                ->where($baseCriteria)
                ->where('status', 'successful')
                ->groupStart()
                ->where('user_id', $id)
                ->orWhere('paidby', $id)
                ->groupEnd()
                ->selectSum('amount')
                ->get()
                ->getRow()->amount ?? 0;

            // Calculate Pending Amount
            $pendingAmount = $this->payment_model->builder()
                ->where($baseCriteria)
                ->where('status', 'pending')
                ->groupStart()
                ->where('user_id', $id)
                ->orWhere('paidby', $id)
                ->groupEnd()
                ->selectSum('amount')
                ->get()
                ->getRow()->amount ?? 0;

            // Round the results
            $successfulAmount = round((float) $successfulAmount, 2);
            $pendingAmount = round((float) $pendingAmount, 2);

            log_message('debug', "Calculated successful amount: $successfulAmount");
            log_message('debug', "Calculated pending amount: $pendingAmount");
        }


        $data = [
            'title' => 'Customers Payment',
            'successfulAmount' => $successfulAmount,
            'pendingAmount' => $pendingAmount,
        ];
        $data['user_id'] = $id;

        return view('payments/customer/user', $data);
    }

    public function newPrint()
    {
        $data = [
            'title' => 'Customers Payment',
        ];

        return view('customers/printer_view');
    }
    /**
     * Customer Payment
     * @action: Fetch Customer Payments
     */
    public function fetch()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        $paid_via = $this->request->getPost('paid_via');
        $status = $this->request->getPost('status');
        $fromDate = $this->request->getPost('fromDate');
        $toDate = $this->request->getPost('toDate');
        // log_message('debug', 'Fetching payments with filters - Paid Via: ' . $paid_via . ', Status: ' . $status . ', From Date: ' . $fromDate . ', To Date: ' . $toDate);
        $today = date('Y-m-d H:i:s');
        $today = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($today)));
        // log_message('info', 'Successfully fromDate : ' . print_r($fromDate, true));
        // log_message('info', 'Successfully toDate : ' . print_r($toDate, true));

        $builder = $this->payment_model->builder()
            ->select('payments.*, customer.name as customer_name, admin.name as paid_to_name, admin.role as paid_to_role')
            ->join('users as customer', 'customer.id = payments.user_id', 'left')
            ->join('users as admin', 'admin.id = payments.paid_to', 'left');

        if ($userole === 'super_admin') {
            $builder->groupStart()
                ->where(['payments.user_type' => 'user', 'payments.admin_id' => $userId])
                ->orWhere('payments.paid_to', $userId) // Include if paid to this admin
                ->orWhere('payments.status', 'failed')
                ->groupEnd();
        } elseif ($userole === 'admin') {
            $builder->where('payments.user_type', 'user')
                ->groupStart()
                ->where('payments.admin_id', $userId)
                ->orWhere('payments.paid_to', $userId) // Include if handled by this sAdmin
                ->orGroupStart()
                ->where('payments.user_id', $userId)
                ->where('payments.admin_id !=', 0)
                ->groupEnd()
                ->groupEnd();
        } elseif ($userole === 'user') {
            $builder->where('payments.user_type', 'user')
                ->groupStart()
                ->where('payments.user_id', $userId)
                ->orWhere('payments.paidby', $userId)
                ->groupEnd();
        } else {
            $builder->groupStart()
                ->where(['payments.user_type' => 'user', 'payments.admin_id' => $userId])
                ->orWhere('payments.paid_to', $userId) // Include if paid to this reseller
                ->groupEnd();
        }
        $builder->orderBy('COALESCE(payments.paid_at, payments.created_at)', 'DESC', false);
        $data = $builder;
        if (!empty($status)) {
            $data->where('payments.status', $status);
        }

        if (!empty($paid_via)) {
            $data->where('paid_via', $paid_via);
        }

        $today = date('Y-m-d H:i:s');
        $today = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($today)));

        if (!empty($fromDate) && !empty($toDate)) {
            // log_message('info', 'Successfully fromDate : ' . print_r($fromDate, true));

            $data->where('payments.created_at >=', $fromDate)
                ->where('payments.created_at <=', $toDate);
        } elseif (!empty($fromDate) && empty($toDate)) {
            // log_message('info', 'Successfully fromDate !empty($fromDate) && empty($toDate): ' . print_r($fromDate, true));

            $data->where('payments.created_at >=', $fromDate);
        } elseif (empty($fromDate) && empty($toDate)) {
            $data->where('payments.created_at >=', $today);
        }

        // $totalQuery = clone $data;
        // $totalAmount = $totalQuery->selectSum('amount')->get()->getRow()->amount ?? 0;
        // log_message('info', 'Successfully totalAmount : ' . print_r($totalAmount, true));
        // session()->set('totalAmount', $totalAmount);


        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('customer_payment', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('customer', function ($row) {
            return $row->customer_name ?? '--';
        });
        $datatables->format('created_at', function ($value) {

            return !empty($value) ? date('d.m.Y H:i:s', strtotime($value)) : '--';
        });

        $datatables->format('amount', function ($value) {

            return $value ?? '--';
        });

        $datatables->format('paid_at', function ($value) {

            return !empty($value) ? date('d.m.Y H:i:s', strtotime($value)) : '--';
        });

        $datatables->format('paid_to', function ($value, $row) {
            if (empty($row->paid_to_name)) {
                return '--';
            }
            return $row->paid_to_name . ' (' . ucwords($row->paid_to_role ?? '') . ')';
        });

        $datatables->format('method_trx', function ($value) {

            return $value ?? '--';
        });

        $datatables->format('status', function ($value) {
            if ($value == 'successful') {
                return '<span class="ipb-pay-badge is-success">Successful</span>';
            }
            if ($value == 'pending') {
                return '<span class="ipb-pay-badge is-warning">Pending</span>';
            }
            return '<span class="ipb-pay-badge is-danger">Failed</span>';
        });

        if (userHasPermission('customer_payment', 'invoice') || userHasPermission('customer_payment', 'update')) {
            $datatables->addColumn('action', function ($row) {
                $html = '<div class="ipb-row-actions">';

                if (userHasPermission('customer_payment', 'update')) {
                    $html .= '<a href="' . route_to('route.customer.payment.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a>';
                }

                if (userHasPermission('customer_payment', 'invoice') && $row->status === 'successful') {
                    $html .= '<a href="' . route_to('route.customer.payment.invoice', $row->id) . '" target="_blank" rel="noopener noreferrer" class="ipb-row-btn tone-info" title="Invoice"><i class="far fa-file-pdf"></i> Invoice</a>';
                    $html .= '<a href="' . route_to('route.customer.payment.receiptPrint', $row->id) . '" target="_blank" class="ipb-row-btn tone-slate" title="POS"><i class="fa fa-print"></i> POS</a>';
                }

                $html .= '</div>';
                return $html;
            });
        }

        $datatables->except(['id', 'user_id', 'user_type']);

        $datatables->asObject();


        $datatables->generate();
    }

    public function user_fetch()
    {
        $Id = $this->request->getPost('user_id');

        log_message('debug', 'Fetching payments for user ID: ' . $Id);

        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        $fromDate = $this->request->getPost('fromDate');
        $toDate = $this->request->getPost('toDate');
        $today = date('Y-m-d H:i:s');
        $today = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($today)));
        // log_message('info', 'Successfully fromDate : ' . print_r($fromDate, true));
        // log_message('info', 'Successfully toDate : ' . print_r($toDate, true));
        if (!empty($Id)) {

            $data = $this->payment_model->builder()

                ->where('user_type', 'user')
                ->groupStart()
                ->where('admin_id', $userId)
                ->orWhere('paid_to', $userId)
                ->groupEnd()
                ->groupStart() // Starts a parenthesis (
                ->where('user_id', $Id)
                ->orWhere('paidby', $Id)
                ->groupEnd(); // Ends the parenthesis )
        } else {

            $data = $this->payment_model->builder()

                ->where('user_type', 'user')
                ->groupStart()
                ->where('admin_id', $userId)
                ->orWhere('paid_to', $userId)
                ->groupEnd();
        }
        $data->orderBy('COALESCE(payments.paid_at, payments.created_at)', 'DESC', false);


        if (!empty($fromDate) && !empty($toDate)) {
            // log_message('info', 'Successfully fromDate : ' . print_r($fromDate, true));

            $data->where('payments.created_at >=', $fromDate)
                ->where('payments.created_at <=', $toDate);
        } elseif (!empty($fromDate) && empty($toDate)) {
            // log_message('info', 'Successfully fromDate !empty($fromDate) && empty($toDate): ' . print_r($fromDate, true));

            $data->where('payments.created_at >=', $fromDate);
        } elseif (empty($fromDate) && empty($toDate)) {
            // log_message('info', 'Successfully fromDate empty: ' . print_r($fromDate, true));

            // $data->where('created_at >=', $today);
        }

        // $totalQuery = clone $data;
        // $totalAmount = $totalQuery->selectSum('amount')->get()->getRow()->amount ?? 0;
        // log_message('info', 'Successfully totalAmount : ' . print_r($totalAmount, true));
        // session()->set('totalAmount', $totalAmount);


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

            return !empty($value) ? date('d.m.Y H:i:s', strtotime($value)) : '--';
        });

        $datatables->format('amount', function ($value) {

            return $value ?? '--';
        });

        $datatables->format('paid_at', function ($value) {

            return !empty($value) ? date('d.m.Y H:i:s', strtotime($value)) : '--';
        });

        $datatables->format('paid_to', function ($value) {

            return !empty($value) ?
                getUserById($value)->name . ' (' . ucwords(getUserById($value)->role ?? '') . ')' ?? '--' :
                '--';
        });

        $datatables->format('method_trx', function ($value) {

            return $value ?? '--';
        });

        $datatables->format('status', function ($value) {
            if ($value == 'successful') {
                return '<span class="ipb-pay-badge is-success">Successful</span>';
            }
            if ($value == 'pending') {
                return '<span class="ipb-pay-badge is-warning">Pending</span>';
            }
            return '<span class="ipb-pay-badge is-danger">Failed</span>';
        });

        if (userHasPermission('customer_payment', 'invoice') || userHasPermission('customer_payment', 'update')) {
            $datatables->addColumn('action', function ($row) {
                $html = '<div class="ipb-row-actions">';

                if (userHasPermission('customer_payment', 'update')) {
                    if (!empty($row->custom_data) && $row->status === 'successful') {
                        $html .= '<a href="javascript:void(0)" onclick="editManualInvoice(' . $row->id . ')" class="ipb-row-btn tone-brand" title="Update"><i class="fa fa-edit"></i> Update</a>';
                    } else {
                        $html .= '<a href="' . route_to('route.customer.payment.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a>';
                    }
                }

                if (userHasPermission('customer_payment', 'invoice') && $row->status === 'successful') {
                    $html .= '<a href="' . route_to('route.customer.payment.invoice', $row->id) . '" target="_blank" class="ipb-row-btn tone-info" title="Invoice"><i class="far fa-file-pdf"></i> Invoice</a>';
                    $html .= '<a href="' . route_to('route.customer.payment.receiptPrint', $row->id) . '" target="_blank" class="ipb-row-btn tone-slate" title="POS"><i class="fa fa-print"></i> POS</a>';
                }

                $html .= '</div>';
                return $html;
            });
        }

        $datatables->except(['id', 'user_id', 'user_type']);

        $datatables->asObject();


        $datatables->generate();
    }


    /**
     * Customer Payment
     * @action: New Payment View
     */
    public function new()
    {
        $userId = session()->get('user_id');

        $userole = session()->get('user_role');

        $details = $this->user_model->where(['id' => $userId])->first();
        // $emp_admin_id = $details->admin_id;

        if ($userole === 'employee') {
            if (empty($details)) {
                return redirect()->to('login');
            }
            $Pre_created_by = $details->created_by;
            if ($Pre_created_by === 'admin') {
                $userId = $details->admin_id;
                // $details = $this->user_model->where(['id' => $userId])->first();
            } else {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
                if (empty($details)) {
                    return redirect()->to('login');
                }
                $userId = $details->admin_id;
            }
            $area_id = $details->area_id;
            $data = [
                'title' => 'New Payment',
                'customers' => $this->user_model->where(['role' => 'user'])->where('area_id', $area_id)->where('admin_id', $userId)->findAll()
            ];

            return view('payments/customer/new', $data);
        }

        $data = [
            'title' => 'New Payment',
            'customers' => $this->user_model->where(['role' => 'user'])->where('admin_id', $userId)->findAll()
        ];

        return view('payments/customer/new', $data);
    }


    /**
     * Customer Payment
     * @action: New Payment Create
     */
    public function create()
    {
        $this->validate([
            'customer' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select customer',
                ]
            ],
            'amount' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter payment amount',
                ]
            ],
            'month' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select payment month',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select payment status',
                ]
            ],
        ]);

        if (getPostInput('status') === 'successful' && (session()->get('user_role') === 'super_admin' || session()->get('user_role') === 'admin')) {

            $this->validate([
                'paid_via' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Select payment method',
                    ]
                ],
                'method_trx' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Select method transaction id',
                    ]
                ],
            ]);
        }

        if ($this->validation->run()) {

            $id = getPostInput('customer');

            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $id])->first();
            // $admin_id = $details->admin_id;
            $admin_id = null;

            if (is_object($details)) {
                $admin_id = $details->admin_id ?? '--';
            } elseif (is_array($details)) {
                $admin_id = $details['admin_id'] ?? '--';
            }

            if (!is_object($details) && !is_array($details)) {
                return requestResponse('error', 'Customer not found', 404);
            }



            if ($details->created_by == 'admin') {
                $package_model = model('App\Models\Package');
                $package = $package_model->find($details->package_id);
            } else {
                $package = ResellerPackagePrice($details->package_id, true);
            }
            log_message('info', 'Fetched package details: ' . json_encode($package));

            $price = is_object($package)
                ? ($package->price ?? 0)
                : (is_array($package)
                    ? ($package['price'] ?? 0)
                    : (is_numeric($package) ? $package : 0)
                );


            $data = [
                'user_id' => $id,
                'user_type' => 'user',
                'admin_id' => $admin_id,
                'paidby' => session()->get('user_id'),
                'invoice' => 'INV-' . random_int(100000, 999999),
                'amount' => getPostInput('amount'),
                'pay_amount' => $price ?? 0,
                'month' => getPostInput('month'),
                'paid_via' => getPostInput('paid_via'),
                'paid_to' => getSession('user_id'),
                'status' => getPostInput('status'),
            ];

            if (session()->get('user_role') === 'super_admin' || session()->get('user_role') === 'admin') {
                if (!empty(getPostInput('user_id_override'))) {
                    $overrideUserId = (int) getPostInput('user_id_override');
                    /* Both role tests below were inverted relative to their own
                       comments. 'super_admin' is the platform owner; 'admin' is a
                       tenant admin. The ownership check must run for the TENANT
                       admin (it was running for super_admin and being skipped for
                       admin), otherwise a tenant admin could post a
                       user_id_override naming another tenant's customer and attach
                       the payment to them. */
                    if (session()->get('user_role') !== 'super_admin') {
                        // Regular admin: target user must belong to this admin's scope.
                        $targetUser = $userModel->where(['id' => $overrideUserId, 'admin_id' => (int) session()->get('user_id')])->first();
                        if (!$targetUser) {
                            return requestResponse('error', 'Access denied: user does not belong to your account', 403);
                        }
                    }
                    $data['user_id'] = $overrideUserId;
                }
                // Only sAdmin may reassign admin ownership (was === 'admin', which
                // let a tenant admin reassign a payment to any other tenant while
                // blocking the platform owner who is meant to have this power).
                if (!empty(getPostInput('admin_id_override')) && session()->get('user_role') === 'super_admin') {
                    $data['admin_id'] = getPostInput('admin_id_override');
                }
                if (!empty(getPostInput('paidby_override')))
                    $data['paidby'] = getPostInput('paidby_override');
                $data['method_trx'] = getPostInput('method_trx');
                $data['comment'] = getPostInput('comment');
            }
            log_message('info', 'Fetched existing data 1: ' . json_encode($data));

            if (!empty(getPostInput('paid_at'))) {
                $data['paid_at'] = date('Y-m-d H:i:s', strtotime(getPostInput('paid_at')));
            } elseif (getPostInput('status') === 'successful') {
                $data['paid_at'] = date('Y-m-d H:i:s');
            }

            if (
                !empty(getPostInput('renew')) &&
                (getPostInput('renew') == 'yes') &&
                (getPostInput('will_expire') === "")
            ) {

                return requestResponse('validation-error', ['will_expire' => 'Select expire date'], 400);
            }
            $month = getPostInput('month');
            $existing = $this->payment_model->where([
                'user_id' => $id,
                'month' => $month
            ])->first();

            log_message('info', 'Fetched existing data: ' . json_encode($data));
            try {
                if ($existing && $existing->status != 'successful') {
                    // Update the existing record
                    $result = $this->payment_model->update($existing->id, $data);
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s');
                    // Insert new payment
                    $result = $this->payment_model->insert($data, false);
                }
            } catch (Exception $e) {

                log_message('error', 'SMS Sending Failed: ' . $e->getMessage());
            }

            // $result = $this->payment_model->insert($data, false);

            if ($result) {
                $will_expire = getPostInput('will_expire');
                if (!empty(getPostInput('renew')) && (getPostInput('renew') == 'yes') && (getPostInput('status') === 'successful')) {
                    $now = date('Y-m-d H:i:s');

                    log_message('info', 'Successfully called details : ' . print_r($will_expire, true));

                    $id = $data['user_id'];
                    $user = getUserById($id);
                    $user_details = $user;
                    $userId = session()->get('user_id');

                    $userModel = model('App\Models\User');
                    $details = $userModel->where(['id' => $userId])->first();
                    $reseller = null;
                    if ($user && $user->created_by === 'resellerAdmin') {
                        $reseller = $userModel->where(['id' => $user->admin_id])->first();
                    }

                    if ($reseller && $reseller->role === 'resellerAdmin') {
                        $fund = $reseller->fund ?? 0;

                        $tprice = ResellerPackagePrice($user->package_id);
                        log_message('info', 'Fetched tprice ResellerPackagePrice($package_id): ' . json_encode($tprice));

                        $now = time(); // Correct way to get current timestamp
                        $now_ts = $now;
                        $will_expire_ts = strtotime($will_expire);

                        if ($user_details->subscription_status === 'active') {
                            $prewill_expire = $user_details->will_expire;
                            $prewill_expire = strtotime($user_details->will_expire); // Convert to timestamp

                            if ($prewill_expire === false || $will_expire_ts === false) {
                                log_message('error', 'Invalid date format encountered');
                            } else {
                                $difference = ceil(($will_expire_ts - $prewill_expire) / (60 * 60 * 24));
                                log_message('info', 'Fetched difference data: ' . json_encode($difference));
                            }
                        } else {
                            if ($will_expire_ts === false) {
                                log_message('error', 'Invalid date format received for will_expire.');
                            }

                            if (is_numeric($will_expire_ts) && is_numeric($now_ts) && $will_expire_ts > $now_ts) {
                                $difference = ceil(($will_expire_ts - $now_ts) / (60 * 60 * 24));
                                log_message('info', 'Fetched difference data: ' . json_encode($difference));
                            }
                        }
                        log_message('info', 'Fetched will_expire: ' . json_encode($will_expire));

                        $price_per_day = $tprice / 30;
                        $price = $price_per_day * ($difference ?? 0);
                        $billingType = $reseller->billing_type ?? 'postpaid';
                        $fundEnabled = isset($reseller->fund_enabled) ? (bool) $reseller->fund_enabled : true;
                        if (!$fundEnabled) {
                            return requestResponse("error", "Reseller funding is disabled. Please contact admin.", 500);
                        }
                        if ($billingType === 'prepaid' && $fund < $price) {
                            return requestResponse("error", "Reseller does not have enough fund. Please recharge.", 500);
                        }

                        if ($billingType === 'prepaid') {
                            $fundService = new \App\Services\FundService();
                            if (! $fundService->deduct((int) $reseller->id, (float) $price)) {
                                return requestResponse("error", "Reseller does not have enough fund. Please recharge.", 500);
                            }
                        }
                        if ($will_expire_ts > $now_ts) {

                            // 👉 Do something here
                            log_message('info', 'The new will_expire is in the future.');

                            $id = $data['user_id'];
                            $user = getUserById($id);
                            if ($user->role === 'user') {
                                $router_client = routerClient($user->router_id);

                                // BUG-11: !is_array() is TRUE for null too, so a breaker-open client
                                // silently passed through and called router functions with null.
                                if (is_object($router_client)) {

                                    $pppoe = getPPPoEUserUserId($router_client, $user->id);
                                    $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                                    log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

                                    $result = enablePPPoEUser($router_client, $pppoe_id);

                                    if (!$result) {
                                        log_message('error', "Failed to enable PPPoE user for User ID {$user->id}");
                                        $pppoe_secret = resolvePppoeSecret((int) $user->id);
                                        $res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret, $pppoe_id);
                                        if ($res) {
                                            log_message('info', "Successfully enabled PPPoE user for User ID {$user->id}");
                                        }
                                    }
                                } else {
                                    log_message('warning', "CustomerPayment: router unavailable for user {$user->id} (router_id={$user->router_id}); payment recorded, provisioning skipped — worker will retry when router recovers.");
                                }
                            }
                        }
                        // log_message('info', 'The new will_expire is in the past.');
                        $this->user_model->update($data['user_id'], [
                            'last_renewed' => date('Y-m-d H:i:s'),
                            'will_expire' => $will_expire,
                            'subscription_status' => ($will_expire_ts > $now_ts) ? 'active' : 'inactive',
                        ]);
                        $transationdata = [
                            'customer' => $id,
                            'admin_id' => $reseller->id,
                            'amount' => $price,
                            'package_price' => $tprice,
                            'active_for' => $difference ?? 0,
                            'comments' => 'Single Customer Created'
                        ];
                        $transationModel = model('App\Models\ResellerTransactions');
                        $result = $transationModel->insert($transationdata);

                        try {
                            // event: payment_done | default template: 13 (customer Bill payment)
                            // Build a merged payload: user fields + payment fields so all placeholders resolve
                            $smsUser = getUserById($transationdata['customer'] ?? $data['user_id']);
                            $smsMerged = [
                                'user_id' => $transationdata['customer'] ?? $data['user_id'],
                                'admin_id' => $transationdata['admin_id'] ?? $userId,
                                'amount' => $transationdata['amount'] ?? '--',
                                'month' => $transationdata['month'] ?? date('F Y'),
                                'will_expire' => $will_expire,
                                'name' => $smsUser ? ($smsUser->name ?? '--') : '--',
                                'mobile' => $smsUser ? ($smsUser->mobile ?? '--') : '--',
                                'email' => $smsUser ? ($smsUser->email ?? '--') : '--',
                                'package_id' => $smsUser ? ($smsUser->package_id ?? '--') : '--',
                            ];
                            try {
                                sendEventSms('payment_done', $smsMerged, (int) ($transationdata['admin_id'] ?? $userId), 13);
                            } catch (\Throwable $e) {
                                log_message('error', 'SMS Sending Failed: ' . $e->getMessage());
                            }
                        } catch (Exception $e) {
                            log_message('error', 'SMS Sending Failed: ' . $e->getMessage());
                        }
                        return requestResponse('success', "New customer record added successfully", 200);
                    } else {
                        // Check if the will_expire is greater than current time
                        $now_ts = time();
                        $will_expire_ts = strtotime($will_expire);
                        if ($will_expire_ts > $now_ts) {

                            // 👉 Do something here
                            log_message('info', 'The new will_expire is in the future.');

                            $id = $data['user_id'];
                            $user = getUserById($id);
                            if ($user->role === 'user') {


                                $router_client = routerClient($user->router_id);

                                $pppoe = getPPPoEUserUserId($router_client, $user->id);
                                $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

                                log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");

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
                                        $res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret, $pppoe_id);
                                        if ($res) {
                                            log_message('info', "Successfully enabled PPPoE user for User ID {$user->id}");
                                            // $user_model->update($user->id, ['activity' => 'active']);
                                        }
                                    }
                                }
                            }
                        }
                        // log_message('info', 'The new will_expire is in the past.');
                        $this->user_model->update($data['user_id'], [
                            'last_renewed' => date('Y-m-d H:i:s'),
                            'will_expire' => $will_expire,
                            'subscription_status' => ($will_expire_ts > $now_ts) ? 'active' : 'inactive',
                        ]);
                        // event: payment_done | default template: 13 (customer Bill payment)
                        // Merge user fields into $data so name/mobile are available
                        $smsUser = getUserById($data['user_id']);
                        $smsMerged = array_merge($data, [
                            'name' => $smsUser ? ($smsUser->name ?? '--') : '--',
                            'mobile' => $smsUser ? ($smsUser->mobile ?? '--') : '--',
                            'email' => $smsUser ? ($smsUser->email ?? '--') : '--',
                            'package_id' => $smsUser ? ($smsUser->package_id ?? '--') : '--',
                            'will_expire' => $will_expire,
                        ]);
                        try {
                            sendEventSms('payment_done', $smsMerged, $smsMerged['admin_id'] ?? null, 13);
                        } catch (\Throwable $e) {
                            log_message('error', 'SMS Sending Failed: ' . $e->getMessage());
                        }
                    }
                }
                return requestResponse('success', "Payment record added successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Customer Payment
     * @action: Delete Customer Payment
     */
    public function delete()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            $result = $this->payment_model->where('user_type', 'user')->whereIn('id', $ids)->delete();

            if ($result) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected!", 400);
    }


    /**
     * Customer Payment
     * @action: Edit Customer Payment
     */
    public function edit($id)
    {
        $details = $this->payment_model->where(['id' => $id, 'user_type' => 'user'])->first();

        if (!empty($details)) {

            $data = [
                'title' => 'Update Payment',
                'details' => $details,
            ];

            return view('payments/customer/edit', $data);
        }

        show_404();
    }



    /**
     * Customer Payment
     * @action: Update Customer Payment
     */
    public function update($id)
    {
        $this->validate([
            'amount' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter payment amount',
                ]
            ],
            'month' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select payment month',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select payment status',
                ]
            ],
        ]);

        if (getPostInput('status') === 'successful' && (session()->get('user_role') === 'super_admin' || session()->get('user_role') === 'admin')) {

            $this->validate([
                'paid_via' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Select payment method',
                    ]
                ],

            ]);
        }

        if ($this->validation->run()) {

            $data = [
                'amount' => getPostInput('amount'),
                'month' => getPostInput('month'),
                'paid_via' => getPostInput('paid_via'),
                'status' => getPostInput('status'),
            ];

            if (session()->get('user_role') === 'super_admin' || session()->get('user_role') === 'admin') {
                if ($this->request->getPost('user_id') !== null) {
                    $data['user_id'] = getPostInput('user_id');
                }
                if ($this->request->getPost('admin_id') !== null) {
                    $data['admin_id'] = getPostInput('admin_id');
                }
                if ($this->request->getPost('paidby') !== null) {
                    $data['paidby'] = getPostInput('paidby');
                }
                if ($this->request->getPost('method_trx') !== null) {
                    $data['method_trx'] = getPostInput('method_trx');
                }
                if ($this->request->getPost('comment') !== null) {
                    $data['comment'] = getPostInput('comment');
                }
            }

            if (!empty(getPostInput('paid_at'))) {
                $data['paid_at'] = date('Y-m-d H:i:s', strtotime(getPostInput('paid_at')));
            } elseif (getPostInput('status') === 'successful') {
                // Only set to current time if paid_at is not already set
                $existing = $this->payment_model->find($id);
                if (empty($existing->paid_at)) {
                    $data['paid_at'] = date('Y-m-d H:i:s');
                }
            }

            if (
                !empty(getPostInput('renew')) &&
                (getPostInput('renew') == 'yes') &&
                (getPostInput('will_expire') === "")
            ) {

                return requestResponse('validation-error', ['will_expire' => 'Select expire date'], 400);
            }
            $old_payment = $this->payment_model->find($id);
            $already_paid = ($old_payment && $old_payment->status === 'successful');

            log_message('info', 'Successfully called details : ' . print_r($data, true));
            $result = $this->payment_model->where(['id' => $id, 'user_type' => 'user'])->set($data)->update();


            if ($result) {
                $will_expire = getPostInput('will_expire');
                $now = date('Y-m-d H:i:s');
                if (!empty(getPostInput('renew')) && (getPostInput('renew') == 'yes') && (getPostInput('status') === 'successful')) {

                    $payment = $this->payment_model->find($id);
                    $user = getUserById($payment->user_id);

                    if (!empty($user)) {
                        $now_ts = time();
                        $will_expire_ts = strtotime($will_expire);

                        $userModel = model('App\Models\User');
                        $reseller = null;
                        if ($user->created_by === 'resellerAdmin') {
                            $reseller = $userModel->where(['id' => $user->admin_id])->first();
                        }

                        if ($reseller && $reseller->role === 'resellerAdmin' && !$already_paid) {
                            $fund = $reseller->fund ?? 0;
                            $tprice = ResellerPackagePrice($user->package_id);
                            log_message('info', 'Fetched tprice ResellerPackagePrice($package_id): ' . json_encode($tprice));

                            if ($user->subscription_status === 'active') {
                                $prewill_expire = $user->will_expire;
                                $prewill_expire = strtotime($user->will_expire);

                                if ($prewill_expire === false || $will_expire_ts === false) {
                                    log_message('error', 'Invalid date format encountered');
                                } else {
                                    $difference = ceil(($will_expire_ts - $prewill_expire) / (60 * 60 * 24));
                                    log_message('info', 'Fetched difference data: ' . json_encode($difference));
                                }
                            } else {
                                if ($will_expire_ts === false) {
                                    log_message('error', 'Invalid date format received for will_expire.');
                                }

                                if (is_numeric($will_expire_ts) && is_numeric($now_ts) && $will_expire_ts > $now_ts) {
                                    $difference = ceil(($will_expire_ts - $now_ts) / (60 * 60 * 24));
                                    log_message('info', 'Fetched difference data: ' . json_encode($difference));
                                }
                            }

                            $price_per_day = $tprice / 30;
                            $price = $price_per_day * ($difference ?? 0);
                            $billingType = $reseller->billing_type ?? 'postpaid';
                            $fundEnabled = isset($reseller->fund_enabled) ? (bool) $reseller->fund_enabled : true;

                            if (!$fundEnabled) {
                                return requestResponse("error", "Reseller funding is disabled. Please contact admin.", 500);
                            }
                            if ($billingType === 'prepaid' && $fund < $price) {
                                return requestResponse("error", "Reseller does not have enough fund. Please recharge.", 500);
                            }

                            // Atomic, idempotent, ledgered deduction via FundService — replaces
                            // a raw read-modify-write that lost concurrent updates (two
                            // simultaneous renewals could each overdraw) and left no audit
                            // trail. Semantics preserved: prepaid is already fund-guarded
                            // above; postpaid may still go negative (owed), via the explicit
                            // $allowNegative opt-in (deduct() otherwise refuses below zero).
                            (new \App\Services\FundService())->deduct(
                                (int) $reseller->id,
                                (float) $price,
                                'reseller-renew:' . $user->id . ':' . $will_expire_ts,
                                'Reseller-funded renewal for customer #' . $user->id,
                                null,
                                $billingType !== 'prepaid'
                            );

                            $transationdata = [
                                'customer' => $user->id,
                                'admin_id' => $reseller->id,
                                'amount' => $price,
                                'package_price' => $tprice,
                                'active_for' => $difference ?? 0,
                                'comments' => 'Single Customer Renewed'
                            ];
                            $transationModel = model('App\Models\ResellerTransactions');
                            $transationModel->insert($transationdata);
                        }

                        if ($will_expire_ts > $now_ts) {
                            // 👉 Do something here
                            log_message('info', 'The new will_expire is in the future.');
                            $id = $payment->user_id;
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
                                        $res = enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret, $pppoe_id);
                                        if ($res) {
                                            log_message('info', "Successfully enabled PPPoE user for User ID {$user->id}");
                                            // $user_model->update($user->id, ['activity' => 'active']);
                                        }
                                    }
                                }
                            }
                        }

                        $this->user_model->update($payment->user_id, [
                            'last_renewed' => date('Y-m-d H:i:s'),
                            'will_expire' => $will_expire,
                            'subscription_status' => ($will_expire_ts > $now_ts) ? 'active' : 'inactive',
                        ]);
                    }
                }

                return requestResponse('success', "Payment record updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }



    /**
     * Customer Payment
     * @action: Get Subscription Expired Date
     */
    public function getExpiryDate()
    {
        $this->validate([
            'customer' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select customer',
                ]
            ],
        ]);

        if ($this->validation->run()) {

            $user_id = getPostInput('customer');

            $expiry = calcUserSubsRenewDate($user_id);
            log_message('info', 'Fetched expiry date: ' . json_encode($expiry));

            $details = getuserbyId($user_id);

            if ($details->created_by == 'admin') {
                $package_model = model('App\Models\Package');
                $package = $package_model->find($details->package_id);
            } else {
                $packages = ResellerPackagePrice($details->package_id, true);
                $package = [
                    'price' => $packages['price'] ?? (is_numeric($packages) ? $packages : 0)
                ];
            }
            log_message('info', 'Fetched package details: ' . json_encode($package));
            $data = [
                'expiry' => $expiry,
                'package' => $package
            ];
            return requestResponse('success', $data, 200);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Customer Payment
     * @action: Print Payment Invoice
     */
    public function invoicePrint($id)
    {

        $details = $this->payment_model
            ->where(['id' => $id, 'status' => 'successful'])
            ->first();

        log_message('debug', 'invoicePrint print function working : ' . json_encode($details));

        if (!empty($details)) {

            $customData = json_decode($details->custom_data ?? '{}', true);
            $user = getUserById($details->user_id);

            $data = [
                'details' => $details,
                'user' => $user,
                'customData' => $customData,
                'companyName' => $customData['company_name'] ?? getSetting('app_name', 'ISP', $details->admin_id),
                'companyMobile' => $customData['company_mobile'] ?? getSetting('company_mobile', '', $details->admin_id),
                'companyAddress' => $customData['company_address'] ?? getSetting('company_address', '', $details->admin_id),
                'customerName' => $customData['customer_name'] ?? ($user->name ?? '--'),
                'customerMobile' => $customData['customer_mobile'] ?? ($user->mobile ?? '--'),
                'customerEmail' => $customData['customer_email'] ?? ($user->email ?? '--'),
                'customerAddress' => $customData['customer_address'] ?? ($user->address ?? '--'),
            ];

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'orientation' => 'P',
                'default_font' => 'hindsiliguri',
            ]);

            $logo = getSetting('app_logo');
            $logoPath = FCPATH . 'assets/img/logo/' . $logo;

            if (!empty($logo) && file_exists($logoPath)) {
                $mpdf->SetWatermarkImage($logoPath);
                $mpdf->showWatermarkImage = true;
                $mpdf->watermarkImageAlpha = 0.15; // Subtle watermark
                $mpdf->watermark_size = 60;
                $mpdf->watermarkAngle = 33;
            }

            $html = view('payments/invoice', $data);
            $mpdf->WriteHTML($html);

            // Bypass CI4 response to force inline behavior
            if (ob_get_length())
                ob_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="invoice.pdf"');
            echo $mpdf->Output('', 'I');
            exit;

        }

        show_404();
    }
    // public $load;




    public function directPrintReceipt($orderId)
    {

        log_message('debug', 'directPrintReceipt print function working : ');

        try {
            // Connect to the printer
            $connector = new FilePrintConnector("COM3");

            $printer = new Printer($connector);

            // Print a sample text
            $printer->text("Hello from Bluetooth!\n");
            $printer->cut();
            $printer->close();
        } catch (Exception $e) {
            echo "Failed to print: " . $e->getMessage() . "\n";
        }
    }



    public function receiptPrint($orderId)
    {
        // Increase execution time and memory limit
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        // Fetch order details
        $orderDetails = $this->payment_model
            ->where(['id' => $orderId, 'status' => 'successful'])
            ->first();

        if (!empty($orderDetails)) {
            // Fetch user details
            $user = $this->user_model->find($orderDetails->user_id);

            log_message('debug', 'receiptPrint print function working : ' . json_encode($user));
            $adminid = getSession('user_id');
            $admindata = $this->user_model->find($adminid);

            $rdetails = $this->reseller_model->where(['userid' => $adminid])->first();

            $customData = json_decode($orderDetails->custom_data ?? '{}', true);
            $user = $this->user_model->find($orderDetails->user_id);

            // Prepare data for the view with custom overrides
            $data = [
                'details' => $orderDetails,
                'rdetails' => $rdetails,
                'user' => $user,
                'admin' => $admindata,
                'customData' => $customData,
                'companyName' => $customData['company_name'] ?? ($rdetails['organization_name'] ?? getSetting('app_name', 'ISP', $orderDetails->admin_id)),
                'companyMobile' => $customData['company_mobile'] ?? ($admindata->mobile ?? getSetting('company_mobile', '', $orderDetails->admin_id)),
                'companyAddress' => $customData['company_address'] ?? ($admindata->address ?? getSetting('company_address', '', $orderDetails->admin_id)),
                'customerName' => $customData['customer_name'] ?? ($user->name ?? '--'),
                'customerMobile' => $customData['customer_mobile'] ?? ($user->mobile ?? '--'),
                'customerAddress' => $customData['customer_address'] ?? ($user->address ?? '--'),
            ];

            // Fix customerAddress to use customData if present
            $data['customerAddress'] = $customData['customer_address'] ?? ($user->address ?? '--');

            log_message('debug', 'Receipt Data: ' . json_encode($data));
            // Generate PDF
            // $mpdf = new \Mpdf\Mpdf([
            //     'mode' => 'utf-8',
            //     'format' => [58, 150], // 58mm x 100mm
            //     'orientation' => 'P',
            //     'default_font' => 'hindsiliguri',
            // ]);


            $mpdf = new \Mpdf\Mpdf([
                'format' => [58, 150],
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'default_font' => 'freeSerif',
            ]);

            $html = view('payments/pos', $data);
            $mpdf->WriteHTML($html);

            // Bypass CI4 response to force inline behavior
            if (ob_get_length())
                ob_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="receipt.pdf"');
            echo $mpdf->Output('', 'I');
            exit;
        }

        show_404();
    }





    // Function to send the PDF to the POS printer
    private function printPOS($filePath)
    {
        $printerName = "POS_PRINTER";
        log_message('info', 'its here.....');

        // Check the operating system and execute the appropriate print command
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            log_message('info', 'its here.. win...');

            $result = shell_exec("print /d:\"$printerName\" \"$filePath\"");
        } else {
            log_message('info', 'its here.. else...');

            $result = shell_exec("lp -d $printerName \"$filePath\"");
        }

        // Check for errors in printing
        if ($result === null) {
            log_message('error', "Failed to send print job to $printerName for file $filePath");
            throw new \RuntimeException('Unable to send print job to the printer.');
        }
    }
    /**
     * Customer Payment
     * @action: Bulk Generate Invoices
     */
    public function generateInvoices()
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');

        // Get customers based on the logged-in user's role
        $query = $this->user_model->where('role', 'user')->where('status', 'active');

        if ($userRole === 'resellerAdmin') {
            $query->where('admin_id', $userId);
        } elseif ($userRole === 'employee') {
            $employee = $this->user_model->find($userId);
            $query->where('admin_id', $employee->admin_id);
        } elseif ($userRole === 'admin') {
            // sAdmin can generate for everyone or just their own? 
            // Usually sAdmin sees everything, but let's limit to their own or all depending on typical usage.
            // For now, let's allow all active users if sAdmin.
        } else {
            return requestResponse('error', "Unauthorized access", 403);
        }

        $users = $query->findAll();
        $currentMonth = date('F');
        $count = 0;

        foreach ($users as $user) {
            // Check if a payment record already exists for this user and month
            $existing = $this->payment_model->where([
                'user_id' => $user->id,
                'month' => $currentMonth,
                'user_type' => 'user'
            ])->first();

            if (!$existing) {
                // Get package price
                $package = getUserPackage($user->id);
                $price = 0;
                if ($user->created_by === 'resellerAdmin') {
                    $price = ResellerPackagePrice($user->package_id, null, $user->admin_id, 'resellerAdmin');
                } else {
                    $price = is_array($package) ? ($package['price'] ?? 0) : ($package->price ?? 0);
                }

                $paydata = [
                    'user_id' => $user->id,
                    'user_type' => 'user',
                    'admin_id' => $user->admin_id ?? "",
                    'invoice' => 'INV-' . random_int(100000, 999999),
                    'amount' => $price ?? 0,
                    'month' => $currentMonth,
                    'created_at' => date('Y-m-d H:i:s'),
                    'status' => 'pending',
                ];

                $this->payment_model->insert($paydata);
                $count++;
            }
        }

        return requestResponse('success', "$count invoices generated successfully for $currentMonth", 200);
    }

    /**
     * Customer Payment
     * @action: Get Payment Details for Modal
     */
    public function getPaymentDetails($id)
    {
        $details = $this->payment_model->find($id);
        if (!$details) {
            return requestResponse('error', 'Payment record not found', 404);
        }

        $user = getUserById($details->user_id);
        $customData = json_decode($details->custom_data ?? '{}', true);

        $response = [
            'id' => $details->id,
            'user_id' => $details->user_id,
            'company_name' => $customData['company_name'] ?? getSetting('app_name', 'ISP', $details->admin_id),
            'company_mobile' => $customData['company_mobile'] ?? getSetting('company_mobile', '', $details->admin_id),
            'company_address' => $customData['company_address'] ?? getSetting('company_address', '', $details->admin_id),
            'customer_name' => $customData['customer_name'] ?? ($user->name ?? ''),
            'customer_mobile' => $customData['customer_mobile'] ?? ($user->mobile ?? ''),
            'customer_email' => $customData['customer_email'] ?? ($user->email ?? ''),
            'customer_address' => $customData['customer_address'] ?? ($user->address ?? ''),
            'invoice' => $details->invoice,
            'amount' => $details->amount,
            'pay_amount' => $details->pay_amount,
            'month' => $details->month,
            'company_address' => $customData['company_address'] ?? getSetting('company_address', '', $details->admin_id),
            'status' => $details->status,
        ];

        return requestResponse('success', $response, 200);
    }

    /**
     * Customer Payment
     * @action: Save Manual Invoice
     */
    public function saveManualInvoice()
    {
        $id = $this->request->getPost('id');
        $userId = $this->request->getPost('user_id');

        $rules = [
            'amount' => 'required|numeric',
            'month' => 'required',
        ];

        if (!$this->validate($rules)) {
            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }

        $customData = [
            'company_name' => $this->request->getPost('company_name'),
            'company_mobile' => $this->request->getPost('company_mobile'),
            'company_address' => $this->request->getPost('company_address'),
            'customer_name' => $this->request->getPost('customer_name'),
            'customer_mobile' => $this->request->getPost('customer_mobile'),
            'customer_email' => $this->request->getPost('customer_email'),
        ];

        $data = [
            'amount' => $this->request->getPost('amount'),
            'pay_amount' => $this->request->getPost('amount'), // Full payment
            'month' => $this->request->getPost('month'),
            'invoice' => $this->request->getPost('invoice') ?: 'INV-' . random_int(100000, 999999),
            'custom_data' => json_encode($customData),
            'status' => 'successful',
            'paid_at' => date('Y-m-d H:i:s'),
            'paid_via' => 'Cash',
            'paid_to' => session()->get('user_id'),
        ];

        log_message('debug', 'Saving Manual Invoice Data: ' . json_encode($data));

        if (!empty($id)) {
            // Update existing
            if (!empty($userId)) {
                $user = getUserById($userId);
                $data['user_id'] = $userId;
                $data['paidby'] = $userId;
                $data['package_id'] = $user->package_id ?? 0;
            }

            $this->payment_model->update($id, $data);
            return requestResponse('success', 'Invoice updated and marked as paid successfully', 200);
        } else {
            // Create new
            if (empty($userId)) {
                return requestResponse('error', 'Customer selection is required for new invoice', 400);
            }
            $user = getUserById($userId);
            $currentAdminId = session()->get('user_id'); // The admin/reseller creating this

            $data['user_id'] = $userId;
            $data['user_type'] = 'user';
            $data['admin_id'] = $currentAdminId; // Associate with the creator so they can see it
            $data['paidby'] = $userId;
            $data['package_id'] = $user->package_id ?? 0;
            $data['created_at'] = date('Y-m-d H:i:s');

            if ($this->payment_model->insert($data)) {
                return requestResponse('success', 'Manual invoice created and marked as paid successfully', 200);
            } else {
                return requestResponse('error', 'Failed to create manual invoice: ' . json_encode($this->payment_model->errors()), 500);
            }
        }
    }
}
