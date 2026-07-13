<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Extention;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class EmployeePayment extends BaseController
{

    protected $payment_model, $user_model;

    public function __construct()
    {
        /**
         * Payment Model
         */
        $this->payment_model = model('App\Models\Payment');

        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
    }

    /**
     * Employee Payment
     * @action: Employee Payment View
     */
    public function index($user_type = null)
    {
        $status = $this->request->getGet('status');
        $data = [
            'title' => 'Employees Payment',
            'status' => $status,
        ];

        return view('payments/employee/all', $data);
    }


    /**
     * Employee Payment
     * @action: Fetch Employee Payments
     */
    public function fetch()
    {
        $userId = session()->get('user_id');

        $userRole = session()->get('user_role');
        $status = $this->request->getPost('status');
        log_message('info', 'Successfully called userRole : ' . print_r($userRole, true));
        // log_message('info', 'Successfully called userId : ' . print_r($userId, true));
        $details = $this->user_model->where(['id' => $userId])->first();

        if ($userRole === 'employee') {
            $admin_id = $details->admin_id;
            // $Pre_created_by = $details->created_by;
            // if ($Pre_created_by === 'admin') {
            //     $userId = $details->admin_id;
            //     // $details = $this->user_model->where(['id' => $userId])->first();
            // } else {
            //     $userId = $details->admin_id;
            //     $details = $this->user_model->where(['id' => $userId])->first();
            //     $userId = $details->admin_id;
            // }


            $data = $this->payment_model->builder()
                ->select('*')
                ->where('user_type', 'employee')
                ->where('user_id ', $userId)
                ->where('admin_id', $admin_id)
                ->orderBy('id', 'desc');
        } else {
            $data = $this->payment_model->builder()
                ->select('*')
                ->where(['user_type' => 'employee'])
                ->where('admin_id', $userId)
                ->orderBy('id', 'desc');
        }

        if (!empty($status)) {
            $data->where('payments.status', $status);
        }

        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('employee_payment', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('employee', function ($row) {

            return getUserById($row->user_id)->name ?? '--';
        });

        $datatables->addColumn('area', function ($row) {

            return getUserArea($row->user_id) ? getUserArea($row->user_id)->area_name . ' (' . getUserArea($row->user_id)->area_code . ')' : '--';
        });


        $datatables->format('paid_at', function ($value) {

            return !empty($value) ? date('d.m.Y', strtotime($value)) : '--';
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

        if (userHasPermission('employee_payment', 'update')) {
            $datatables->addColumn('action', function ($row) {
                return '<div class="ipb-row-actions"><a href="' . route_to('route.employee.payment.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
            });
        }

        $datatables->except(['id', 'user_id', 'created_at', 'user_type', 'method_trx']);

        $datatables->asObject();

        $datatables->generate();
    }


    /**
     * Employee Payment
     * @action: New Payment View
     */
    public function new()
    {
        $userId = session()->get('user_id');
        $details = $this->user_model->where(['id' => $userId])->first();

        $userRole = session()->get('user_role');

        if ($userRole === 'employee') {
            $userId = $details->admin_id;

            $data = [
                'title' => 'New Payment',
                'employees' => $this->user_model->where(['role' => 'employee'])->where('admin_id', $userId)->findAll()
            ];
        } else {
            $data = [
                'title' => 'New Payment',
                'employees' => $this->user_model->where(['role' => 'employee'])->where('admin_id', $userId)->findAll()
            ];
        }

        return view('payments/employee/new', $data);
    }


    /**
     * Employee Payment
     * @action: New Payment Create
     */
    public function create()
    {
        $this->validate([
            'employee' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select employee',
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

        if (getPostInput('status') === 'successful') {

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

            $id = getPostInput('employee');

            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $id])->first();
            // $admin_id = $details->admin_id;
            $admin_id = null;

            if (is_object($details)) {
                $admin_id = $details->admin_id ?? '--';
            } elseif (is_array($details)) {
                $admin_id = $details['admin_id'] ?? '--';
            }

            $data = [
                'user_id' => $id,
                'user_type' => 'employee',
                'admin_id' => $admin_id,
                'paidby' => session()->get('user_id'),
                'invoice' => 'INV-' . random_int(1000, 9999),
                'amount' => getPostInput('amount'),
                'month' => getPostInput('month'),
                'created_at' => date('Y-m-d H:i:s'),
                'paid_via' => getPostInput('paid_via'),
                'status' => getPostInput('status'),
            ];

            if (getPostInput('status') === 'successful') {

                $data['paid_at'] = date('Y-m-d H:i:s');
            }

            $result = $this->payment_model->insert($data, false);

            if ($result) {
                try {
                    // event: employee_pay | default template: 1 (Employee Salary Payment Template)
                    // Pass $data (payment record) so amount+month are resolved; Send_SMs fetches employee name/mobile via user_id
                    sendEventSms('employee_pay', $data, (int)($admin_id ?? 0), 1);
                } catch (\Throwable $e) {
                    // Handle the exception  
                    log_message('debug', 'Send_SMs Error: ' . $e->getMessage());
                    // You can also log the error or take other actions as needed  
                }
                return requestResponse('success', "Payment record added successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Employee Payment
     * @action: Delete Employee Payment
     */
    public function delete()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            $result = $this->payment_model->where('user_type', 'employee')->whereIn('id', $ids)->delete();

            if ($result) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected!", 400);
    }


    /**
     * Employee Payment
     * @action: Edit Employee Payment
     */
    public function edit($id)
    {
        $details = $this->payment_model->where(['id' => $id, 'user_type' => 'employee'])->first();

        if (!empty($details)) {

            $data = [
                'title' => 'Update Payment',
                'details' => $details,
            ];

            return view('payments/employee/edit', $data);
        }

        show_404();
    }



    /**
     * Employee Payment
     * @action: Update Employee Payment
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

        if (getPostInput('status') === 'successful') {

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

            if (getPostInput('status') === 'successful') {

                $data['paid_at'] = date('Y-m-d H:i:s');
            }

            $result = $this->payment_model->where(['id' => $id, 'user_type' => 'employee'])->set($data)->update();

            if ($result) {

                return requestResponse('success', "Payment record updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }
}
