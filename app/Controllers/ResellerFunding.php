<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ResellerFundingModel;
use App\Models\ResellerTransactions;

use App\Models\ResellerPackages;
use App\Models\allResellerPackage;
use App\Models\UserRouterDataModel;

use CodeIgniter\CLI\Console;
use App\Models\Registration;

use App\Models\User;
use App\Libraries\DataTables;


class ResellerFunding extends BaseController
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
    public function Funding_index()
    {
        if (!userHasPermission('Resellers', 'read') && !userHasPermission('reseller', 'read')) {
            return requestResponse("error", "You don't have permission to view.", 500);
        }
        $userId = session()->get('user_id');

        // Your controller logic here
        return view('reseller/index', ['userId' => $userId]);
    }


    public function paymentindex()
    {
        $userId = session()->get('user_id');

        $role = session()->get('user_role');

        if ($role != 'resellerAdmin') {


            // Fetch reseller data
            $resellerData = $this->user_model->builder()
                ->select('*')
                ->where('role', 'resellerAdmin')
                ->where('admin_id', $userId)

                ->orderBy('id', 'desc')
                ->get()
                ->getResult();  // This returns objects by default
        } else {
            $resellerData = [];
            $resellerData = $this->user_model->builder()
                ->select('*')

                ->where('id', $userId)

                ->get()
                ->getResult();
        }

        // Prepare the data array for the view
        $data = [
            'title' => 'Reseller Funding',
            'resellers' => $resellerData // Add reseller data to the array
        ];

        // Load the view with the data
        return view('resellerFunding/index', $data);
    }

    public function transactionindex()
    {
        $userId = session()->get('user_id');
        $role = session()->get('user_role');

        // Reseller may always view own POP transactions; tenant needs POP/payment permission.
        if ($role !== 'resellerAdmin') {
            $canView = userHasPermission('Resellers', 'read')
                || userHasPermission('reseller', 'read')
                || userHasPermission('customer_payment', 'read');
            if (! $canView) {
                return requestResponse('error', "You don't have permission to view.", 403);
            }
        }

        if ($role === 'resellerAdmin') {
            $resellerData = $this->user_model->builder()
                ->select('*')
                ->where('id', $userId)
                ->get()
                ->getResult();
        } else {
            $resellerData = $this->user_model->builder()
                ->select('*')
                ->where('role', 'resellerAdmin')
                ->where('admin_id', $userId)
                ->orderBy('id', 'desc')
                ->get()
                ->getResult();
        }

        $data = [
            'title' => 'Reseller transactions',
            'resellers' => $resellerData,
        ];

        return view('resellerFunding/transactions', $data);
    }

    public function transactionsfetch()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        $canUpdatePayment = userHasPermission('customer_payment', 'update');
        $canInvoicePayment = userHasPermission('customer_payment', 'invoice');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Get filter inputs from the request
        $reseller = $this->request->getPost('reseller');
        $status = $this->request->getPost('status');  // Uncomment if needed
        $fromDate = $this->request->getPost('fromDate');
        $toDate = $this->request->getPost('toDate');
        $today = date('Y-m-d H:i:s');
        $today = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($today)));
        $model = new ResellerTransactions();
        log_message('info', 'Successfully fromDate : ' . print_r($fromDate, true));
        log_message('info', 'Successfully toDate : ' . print_r($toDate, true));
        // Build the initial query based on user role
        // NOTE: users has its own admin_id/status/created_at columns, so every filter below
        // must stay qualified with reseller_transaction. now that the join is in place.
        if ($userole == 'resellerAdmin') {
            $data = $model->builder()
                ->select('reseller_transaction.*, joined_customer.name as joined_customer_name')
                ->join('users as joined_customer', 'joined_customer.id = reseller_transaction.customer', 'left')
                ->where('reseller_transaction.admin_id', $userId)
                ->orderBy('reseller_transaction.id', 'desc');
        } else {
            // Tenant admin: only transactions for their POP resellers (never cross-tenant).
            $popIds = $this->user_model
                ->select('id')
                ->where(['role' => 'resellerAdmin', 'admin_id' => $userId])
                ->findColumn('id');
            if (empty($popIds)) {
                $popIds = [0];
            }
            $data = $model->builder()
                ->select('reseller_transaction.*, joined_customer.name as joined_customer_name')
                ->join('users as joined_customer', 'joined_customer.id = reseller_transaction.customer', 'left')
                ->whereIn('reseller_transaction.admin_id', $popIds)
                ->orderBy('reseller_transaction.id', 'desc');
        }

        // Apply filters based on the input values
        if (!empty($reseller)) {
            $data->where('reseller_transaction.admin_id', $reseller);
        }

        if (!empty($status)) {
            $data->where('reseller_transaction.status', $status);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $data->where('reseller_transaction.created_at >=', $fromDate)
                ->where('reseller_transaction.created_at <=', $toDate);
        } elseif (!empty($fromDate) && empty($toDate)) {
            log_message('info', 'Successfully funding fromDate !empty($fromDate) && empty($toDate): ' . print_r($fromDate, true));
            $data->where('reseller_transaction.created_at >=', $fromDate);
        } elseif (empty($fromDate) && empty($toDate)) {
            log_message('info', 'Successfully fromDate : 5');
            $data->where('reseller_transaction.created_at >=', $today);
        }

        // If all filters are empty (and the user is not resellerAdmin), force no results.
        if ($userole != 'resellerAdmin' && empty($reseller) && empty($status) && empty($fromDate) && empty($toDate)) {
            $data->where('reseller_transaction.admin_id', '-1'); // Assuming no record has admin_id = -1
        }

        // Generate DataTables with the filtered data
        $datatables = new DataTables($data);

        $datatables->addSequenceNumber('serial');

        $datatables->addColumn('customer', function ($row) {
            return $row->joined_customer_name ?? '--';
        });
        $datatables->addColumn('amount', function ($row) {
            return $row->amount ?? '--';
        });
        $datatables->addColumn('package_price', function ($row) {
            return $row->package_price ?? '--';
        });
        $datatables->addColumn('active_for', function ($row) {
            return $row->active_for ?? '--';
        });

        $datatables->format('created_at', function ($value) {
            return !empty($value) ? date('d.m.Y', strtotime($value)) : '--';
        });

        $datatables->addColumn('comments', function ($row) {
            return $row->comments ?? '--';
        });

        if ($canInvoicePayment || $canUpdatePayment) {
            $datatables->addColumn('action', function ($row) use ($canUpdatePayment) {
                $html = '';
                if ($canUpdatePayment) {
                    $html .= '<div class="ipb-row-actions"><a href="' . route_to('route.Reseller.Funding.index', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
                }
                return $html;
            });
        }

        $datatables->except(['id', 'user_id', 'user_type']);
        $datatables->asObject();
        return $datatables->generate();
    }




    public function fundingfetch()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        $canDeletePayment = userHasPermission('customer_payment', 'delete');
        $canUpdatePayment = userHasPermission('customer_payment', 'update');
        $canInvoicePayment = userHasPermission('customer_payment', 'invoice');
        $canSelfRecharge = userHasPermission('Resellers', 'self_recharge');
        $canUpdateReseller = userHasPermission('Resellers', 'update');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Get filter inputs from the request
        $reseller = $this->request->getPost('reseller');
        $status = $this->request->getPost('status');
        $fromDate = $this->request->getPost('fromDate');
        $toDate = $this->request->getPost('toDate');
        $today = date('Y-m-d H:i:s');
        $today = date('Y-m-d H:i:s', strtotime('-1 days', strtotime($today)));
        log_message('info', 'Successfully fromDate : ' . print_r($fromDate, true));
        log_message('info', 'Successfully toDate : ' . print_r($toDate, true));

        $model = new ResellerFundingModel();
        // NOTE: users has its own admin_id/status/created_at/customer-shaped columns, so every
        // filter below must stay qualified with reseller_funding. now that the join is in place.
        if ($userole != 'resellerAdmin') {
            // Build the initial query
            $data = $model->builder()
                ->select('reseller_funding.*, joined_customer.name as joined_customer_name')
                ->join('users as joined_customer', 'joined_customer.id = reseller_funding.customer', 'left')
                // ->where('user_type', 'user')
                ->where('reseller_funding.admin_id', $userId)
                ->orderBy('reseller_funding.id', 'desc');

            // Filter by POP reseller (customer column); admin_id is the tenant owner.
            if (!empty($reseller)) {
                $data->where('reseller_funding.customer', $reseller);
            }
        } else {
            $data = $model->builder()
                ->select('reseller_funding.*, joined_customer.name as joined_customer_name')
                ->join('users as joined_customer', 'joined_customer.id = reseller_funding.customer', 'left')
                // ->where('user_type', 'user')
                ->where('reseller_funding.customer', $userId)
                ->orderBy('reseller_funding.id', 'desc');
        }

        if (!empty($status)) {
            $data->where('reseller_funding.status', $status);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $data->where('reseller_funding.created_at >=', $fromDate)
                ->where('reseller_funding.created_at <=', $toDate);
        } elseif (!empty($fromDate) && empty($toDate)) {
            log_message('info', 'Successfully funding fromDate !empty($fromDate) && empty($toDate): ' . print_r($fromDate, true));
            $data->where('reseller_funding.created_at >=', $fromDate);
        } elseif (empty($fromDate) && empty($toDate)) {
            $data->where('reseller_funding.created_at >=', $today);
        }

        // If all filters are empty (and the user is not resellerAdmin), force no results.
        // if ($userole != 'resellerAdmin' && empty($reseller) && empty($status) && empty($fromDate) && empty($toDate)) {
        //     $data->where('admin_id', '-1'); // Assuming no record has admin_id = -1
        // }


        // Generate DataTables with the filtered data
        $datatables = new DataTables($data);

        $datatables->addSequenceNumber('serial');

        if ($canDeletePayment) {
            $datatables->addColumn('select', function ($row) {
                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('customer', function ($row) {
            return $row->joined_customer_name ?? '--';
        });
        $datatables->addColumn('invoice', function ($row) {
            return $row->invoice_number ?? '--';
        });
        $datatables->addColumn('amount', function ($row) {
            return $row->amount ?? '--';
        });
        $datatables->addColumn('paid', function ($row) {
            return $row->received_amount ?? '--';
        });
        $datatables->addColumn('paid_at', function ($row) {
            return $row->received_date ?? '--';
        });

        $datatables->format('created_at', function ($value) {
            return !empty($value) ? date('d.m.Y', strtotime($value)) : '--';
        });

        $datatables->addColumn('comments', function ($row) {
            return $row->comments ?? '--';
        });

        $datatables->format('paid_via', function ($value) {
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
        if ($userole != 'resellerAdmin') {

            if ($canInvoicePayment || $canUpdatePayment) {
                $datatables->addColumn('action', function ($row) use ($canUpdatePayment) {
                    $html = '';
                    if ($canUpdatePayment) {
                        $html .= '<div class="ipb-row-actions"><a href="' . route_to('route.Reseller.Funding.index', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
                    }

                    return $html;
                });
            }
        } else {
            if ($canSelfRecharge && $canUpdateReseller) {
                $datatables->addColumn('action', function ($row) use ($canSelfRecharge) {
                    $html = '';
                    if ($canSelfRecharge) {
                        $html .= '<div class="ipb-row-actions"><a href="' . route_to('route.Reseller.Funding.index', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
                    }

                    return $html;
                });
            }
        }

        $datatables->except(['id', 'user_id', 'user_type']);
        $datatables->asObject();
        return $datatables->generate();
        // return view('reseller/payments.php', [
        //     'totalAmount' => $totalAmount
        // ]);
    }


    public function new()
    {
        $userId = session()->get('user_id');
        $role = session()->get('user_role');

        if ($role === 'resellerAdmin') {
            $customers = $this->user_model->where(['role' => 'resellerAdmin', 'id' => $userId])->findAll();
        } else {
            $customers = $this->user_model->where(['role' => 'resellerAdmin', 'admin_id' => $userId])->findAll();
        }

        $data = [
            'title' => 'New funding',
            'customers' => $customers,
        ];

        return view('resellerFunding/NewFunding', $data);
    }


    public function index($id = null)
    {
        $model = new ResellerFundingModel();
        $userId = session()->get('user_id');

        // Fetch the payment as an array
        $payment = $model->find($id);

        // Prepare data to pass to the view
        if (getSession('user_role') != 'resellerAdmin') {
            $data = [
                'title' => 'New funding',
                'payment' => $payment,
                'customers' => $this->user_model->where(['role' => 'resellerAdmin'])->where('admin_id', $userId)->findAll()


            ];
        } else {
            $data = [
                'title' => 'New funding',
                'payment' => $payment,
                'customers' => $this->user_model->where(['role' => 'resellerAdmin'])->where('id', $userId)->findAll()


            ];
        }

        return view('resellerFunding/NewFunding', $data);
    }


    public function save()
    {
        $model = new ResellerFundingModel();

        $data = $this->request->getPost();
        $sessionId = (int) session()->get('user_id');
        $role = (string) session()->get('user_role');
        $isReseller = ($role === 'resellerAdmin');

        // reseller_funding.admin_id = tenant owner; .customer = POP reseller
        if ($isReseller) {
            $self = $this->user_model->where(['id' => $sessionId, 'role' => 'resellerAdmin'])->first();
            if (! $self) {
                return redirect()->back()->with('error', 'Reseller account not found.');
            }
            $tenantAdminId = (int) ($self->admin_id ?? 0);
            $customerId = $sessionId;
            $data['admin_id'] = $tenantAdminId;
            $data['customer'] = $customerId;
        } else {
            $tenantAdminId = $sessionId;
            $data['admin_id'] = $tenantAdminId;
            $customerId = (int) ($data['customer'] ?? 0);
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0 || $amount > 500000) {
            return redirect()->back()->with('error', 'Amount must be greater than zero and not exceed 500000.');
        }

        if ($customerId <= 0) {
            return redirect()->back()->with('error', 'Reseller is required.');
        }

        $details = $this->user_model->where(['id' => $customerId, 'role' => 'resellerAdmin'])->first();
        if (! $details) {
            return redirect()->back()->with('error', 'Reseller not found or not owned by your account.');
        }
        // Tenant must own the POP; reseller may only fund themselves under their parent.
        if ($isReseller) {
            if ((int) $details->id !== $sessionId || (int) ($details->admin_id ?? 0) !== $tenantAdminId) {
                return redirect()->back()->with('error', 'Reseller not found or not owned by your account.');
            }
        } elseif ((int) ($details->admin_id ?? 0) !== $tenantAdminId) {
            return redirect()->back()->with('error', 'Reseller not found or not owned by your account.');
        }

        $funding_details = $model->where(['id' => $data['id'] ?? 0])->first();
        $previous_amount = (float) ($funding_details['amount'] ?? 0);

        if (!empty($data['id'])) {
            $existing = $model->where(['id' => $data['id'], 'admin_id' => $tenantAdminId])->first();
            if (! $existing) {
                return redirect()->back()->with('error', 'Funding record not found.');
            }
            // Reseller may only edit their own funding rows
            if ($isReseller && (int) ($existing['customer'] ?? 0) !== $sessionId) {
                return redirect()->back()->with('error', 'Funding record not found.');
            }
            $result = $model->update($data['id'], $data);
        } else {
            $result = $model->insert($data);
            $data['id'] = $result;
        }

        if (!empty($result) && !empty($data['customer'])) {
            $fundService = new \App\Services\FundService();
            $delta = $amount - $previous_amount;
            if ($delta > 0) {
                $fundService->add(
                    $customerId,
                    $delta,
                    'resellerfund:' . (int) $data['id'],
                    'Reseller funding credit',
                    (int) $tenantAdminId
                );
            } elseif ($delta < 0) {
                if (! $fundService->deduct(
                    $customerId,
                    abs($delta),
                    'resellerfund:adj:' . (int) $data['id'] . ':' . round($amount, 2),
                    'Reseller funding adjustment',
                    (int) $tenantAdminId
                )) {
                    return redirect()->back()->with('error', 'Reseller does not have enough fund for this adjustment.');
                }
            }
            if ($delta !== 0.0) {
                (new \App\Services\AuditService())->record(
                    'reseller_funding.save',
                    'reseller_funding',
                    ['funding_id' => (int) $data['id'], 'customer_id' => $customerId, 'delta' => round($delta, 2)]
                );
            }
        }

        return redirect()->route('route.reseller.funding');
    }

    public function delete()
    {
        $ids = getRawInput('ids');
        $model = new ResellerFundingModel();
        $sessionId = (int) session()->get('user_id');
        $role = (string) session()->get('user_role');
        $isReseller = ($role === 'resellerAdmin');
        $tenantAdminId = $sessionId;
        if ($isReseller) {
            $self = $this->user_model->find($sessionId);
            $tenantAdminId = (int) ($self->admin_id ?? 0);
        }
        $fundService = new \App\Services\FundService();
        $userModel = model('App\Models\User');

        if (!empty($ids) && is_array($ids)) {
            $deleted = 0;

            foreach ($ids as $id) {
                $fund = $model->where(['id' => $id, 'admin_id' => $tenantAdminId])->first();
                if (!$fund) {
                    continue;
                }
                if ($isReseller && (int) ($fund['customer'] ?? 0) !== $sessionId) {
                    continue;
                }

                $customerId = (int) ($fund['customer'] ?? 0);
                $details = $userModel->where(['id' => $customerId])->first();
                if (! $details || (int) ($details->admin_id ?? 0) !== $tenantAdminId) {
                    return requestResponse('error', 'Reseller ownership verification failed.', 403);
                }

                $fundAmount = (float) ($fund['amount'] ?? 0);
                if ($fundAmount > 0) {
                    if (! $fundService->deduct(
                        $customerId,
                        $fundAmount,
                        'resellerfund:delete:' . (int) $id,
                        'Reseller funding record deleted',
                        $tenantAdminId
                    )) {
                        return requestResponse('error', "Can't be deleted, Reseller doesn't have that much fund.", 500);
                    }
                }

                if ($model->delete($id)) {
                    $deleted++;
                    if ($fundAmount > 0) {
                        (new \App\Services\AuditService())->record(
                            'reseller_funding.delete',
                            'reseller_funding',
                            ['funding_id' => (int) $id, 'customer_id' => $customerId, 'amount' => $fundAmount]
                        );
                    }
                }
            }

            if ($deleted > 0) {
                return requestResponse('success', 'Selected records deleted successfully', 200);
            }

            return requestResponse('error', 'Nothing is selected!', 400);
        }
        return requestResponse('error', 'Nothing is selected!', 400);
    }

    public function transactiondelete()
    {
        $ids = getRawInput('ids');
        $model = new ResellerTransactions();
        $sessionId = (int) session()->get('user_id');
        $role = (string) session()->get('user_role');

        if (empty($ids) || ! is_array($ids) || count($ids) === 0) {
            return requestResponse('error', 'Nothing is selected!', 400);
        }

        $allowedIds = [];
        if ($role === 'resellerAdmin') {
            $rows = $model->whereIn('id', $ids)->where('admin_id', $sessionId)->findAll();
            $allowedIds = array_map(static fn ($r) => (int) (is_array($r) ? $r['id'] : $r->id), $rows);
        } else {
            $popIds = $this->user_model
                ->select('id')
                ->where(['role' => 'resellerAdmin', 'admin_id' => $sessionId])
                ->findColumn('id') ?: [0];
            $rows = $model->whereIn('id', $ids)->whereIn('admin_id', $popIds)->findAll();
            $allowedIds = array_map(static fn ($r) => (int) (is_array($r) ? $r['id'] : $r->id), $rows);
        }

        if (empty($allowedIds)) {
            return requestResponse('error', 'Nothing is selected!', 400);
        }

        $result = $model->whereIn('id', $allowedIds)->delete();
        if ($result) {
            return requestResponse('success', 'Selected records deleted successfully', 200);
        }

        return requestResponse('error', 'Something went wrong! Please try again', 500);
    }
}
