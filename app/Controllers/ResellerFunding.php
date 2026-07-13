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
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;


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
        if (!userHasPermission('Resellers', 'read') && userHasPermission('reseller', 'read')) {
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
            'title' => 'Reseller transactions',
            'resellers' => $resellerData // Add reseller data to the array
        ];

        // Load the view with the data
        return view('resellerFunding/transactions', $data);
    }

    public function transactionsfetch()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

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
        if ($userole == 'resellerAdmin') {
            $data = $model->builder()
                ->select('*')
                ->where('admin_id', $userId)
                ->orderBy('id', 'desc');
        } else {
            $data = $model->builder()
                ->select('*')
                ->orderBy('id', 'desc');
        }

        // Apply filters based on the input values
        if (!empty($reseller)) {
            $data->where('admin_id', $reseller);
        }

        if (!empty($status)) {
            $data->where('status', $status);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $data->where('created_at >=', $fromDate)
                ->where('created_at <=', $toDate);
        } elseif (!empty($fromDate) && empty($toDate)) {
            log_message('info', 'Successfully funding fromDate !empty($fromDate) && empty($toDate): ' . print_r($fromDate, true));
            $data->where('created_at >=', $fromDate);
        } elseif (empty($fromDate) && empty($toDate)) {
            log_message('info', 'Successfully fromDate : 5');
            $data->where('created_at >=', $today);
        }

        // If all filters are empty (and the user is not resellerAdmin), force no results.
        if ($userole != 'resellerAdmin' && empty($reseller) && empty($status) && empty($fromDate) && empty($toDate)) {
            $data->where('admin_id', '-1'); // Assuming no record has admin_id = -1
        }

        // Generate DataTables with the filtered data
        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        $datatables->addColumn('customer', function ($row) {
            return getUserById($row->customer)->name ?? '--';
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

        if (userHasPermission('customer_payment', 'invoice') || userHasPermission('customer_payment', 'update')) {
            $datatables->addColumn('action', function ($row) {
                $html = '';
                if (userHasPermission('customer_payment', 'update')) {
                    $html .= '<div class="ipb-row-actions"><a href="' . route_to('route.Reseller.Funding.index', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
                }
                return $html;
            });
        }

        $datatables->except(['id', 'user_id', 'user_type']);
        $datatables->asObject();
        $datatables->generate();
    }




    public function fundingfetch()
    {
        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

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
        if ($userole != 'resellerAdmin') {
            // Build the initial query
            $data = $model->builder()
                ->select('*')
                // ->where('user_type', 'user')
                ->where('admin_id', $userId)
                ->orderBy('id', 'desc');

            // Apply filters based on the input values
            // Apply filters based on the input values
            if (!empty($reseller)) {
                $data->where('admin_id', $reseller);
            }
        } else {
            $data = $model->builder()
                ->select('*')
                // ->where('user_type', 'user')
                ->where('customer', $userId)
                ->orderBy('id', 'desc');
        }

        if (!empty($status)) {
            $data->where('status', $status);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $data->where('created_at >=', $fromDate)
                ->where('created_at <=', $toDate);
        } elseif (!empty($fromDate) && empty($toDate)) {
            log_message('info', 'Successfully funding fromDate !empty($fromDate) && empty($toDate): ' . print_r($fromDate, true));
            $data->where('created_at >=', $fromDate);
        } elseif (empty($fromDate) && empty($toDate)) {
            $data->where('created_at >=', $today);
        }

        // If all filters are empty (and the user is not resellerAdmin), force no results.
        // if ($userole != 'resellerAdmin' && empty($reseller) && empty($status) && empty($fromDate) && empty($toDate)) {
        //     $data->where('admin_id', '-1'); // Assuming no record has admin_id = -1
        // }


        // Generate DataTables with the filtered data
        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('customer_payment', 'delete')) {
            $datatables->addColumn('select', function ($row) {
                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('customer', function ($row) {
            $userId = session()->get('user_id');
            return getUserById($row->customer)->name ?? '--';
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

            if (userHasPermission('customer_payment', 'invoice') || userHasPermission('customer_payment', 'update')) {
                $datatables->addColumn('action', function ($row) {
                    $html = '';
                    if (userHasPermission('customer_payment', 'update')) {
                        $html .= '<div class="ipb-row-actions"><a href="' . route_to('route.Reseller.Funding.index', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
                    }

                    return $html;
                });
            }
        } else {
            if (userHasPermission('Resellers', 'self_recharge') && userHasPermission('Resellers', 'update')) {
                $datatables->addColumn('action', function ($row) {
                    $html = '';
                    if (userHasPermission('Resellers', 'self_recharge')) {
                        $html .= '<div class="ipb-row-actions"><a href="' . route_to('route.Reseller.Funding.index', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
                    }

                    return $html;
                });
            }
        }

        $datatables->except(['id', 'user_id', 'user_type']);
        $datatables->asObject();
        $datatables->generate();

        // return view('reseller/payments.php', [
        //     'totalAmount' => $totalAmount
        // ]);
    }


    public function new()
    {
        $userId = session()->get('user_id');

        $data = [
            'title' => 'New funding',
            'customers' => $this->user_model->where(['role' => 'resellerAdmin'])->where('admin_id', $userId)->findAll()
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

        $adminId = session()->get('user_id');

        $data['admin_id'] = $adminId;

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0 || $amount > 500000) {
            return redirect()->back()->with('error', 'Amount must be greater than zero and not exceed 500000.');
        }

        $customerId = (int) ($data['customer'] ?? 0);
        if ($customerId <= 0) {
            return redirect()->back()->with('error', 'Reseller is required.');
        }

        $userModel = model('App\Models\User');
        $details = $userModel->where(['id' => $customerId, 'role' => 'resellerAdmin'])->first();
        if (! $details || (int) ($details->admin_id ?? 0) !== (int) $adminId) {
            return redirect()->back()->with('error', 'Reseller not found or not owned by your account.');
        }

        $funding_details = $model->where(['id' => $data['id'] ?? 0])->first();
        $previous_amount = (float) ($funding_details['amount'] ?? 0);

        if (!empty($data['id'])) {
            $existing = $model->where(['id' => $data['id'], 'admin_id' => $adminId])->first();
            if (! $existing) {
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
                    (int) $adminId
                );
            } elseif ($delta < 0) {
                if (! $fundService->deduct(
                    $customerId,
                    abs($delta),
                    'resellerfund:adj:' . (int) $data['id'] . ':' . round($amount, 2),
                    'Reseller funding adjustment',
                    (int) $adminId
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
        $adminId = (int) session()->get('user_id');
        $fundService = new \App\Services\FundService();
        $userModel = model('App\Models\User');

        if (!empty($ids) && is_array($ids)) {
            $deleted = 0;

            foreach ($ids as $id) {
                $fund = $model->where(['id' => $id, 'admin_id' => $adminId])->first();
                if (!$fund) {
                    continue;
                }

                $customerId = (int) ($fund['customer'] ?? 0);
                $details = $userModel->where(['id' => $customerId])->first();
                if (! $details || (int) ($details->admin_id ?? 0) !== $adminId) {
                    return requestResponse('error', 'Reseller ownership verification failed.', 403);
                }

                $fundAmount = (float) ($fund['amount'] ?? 0);
                if ($fundAmount > 0) {
                    if (! $fundService->deduct(
                        $customerId,
                        $fundAmount,
                        'resellerfund:delete:' . (int) $id,
                        'Reseller funding record deleted',
                        $adminId
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


        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            $result = $model->whereIn('id', $ids)->delete();

            if ($result) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected!", 400);
    }
}
