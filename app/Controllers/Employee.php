<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\TrashService;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class Employee extends BaseController
{
    protected $user_model;

    public function __construct()
    {
        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
    }

    /**
     * Employees
     * @action: All Employee View
     */
    public function index()
    {
        $userId = session()->get('user_id');

        $userModel = model('App\Models\User');
        $details = $userModel->where(['role' => 'user', 'admin_id' => $userId])->first();
        log_message('debug', 'access role => employee rsllerAdmin: ' . print_r($details, true));

        $data = [
            'title' => 'Employees',
        ];

        return view('employee/all', $data);
    }


    /**
     * Employees
     * @action: Fetch Employees
     */
    public function fetch()
    {
        $userId = session()->get('user_id');


        $userole = session()->get('user_role');

        $this->user_model = model('App\Models\User');




        $data = $this->user_model->builder()->select('*')
            ->where([
                'role' => 'employee',
                'admin_id' => $userId
            ])
            ->orderBy('id', 'desc');



        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');


        // Log the processed data
        log_message('info', 'Processed Data: ' . print_r($data, true));



        if (userHasPermission('employee', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->addColumn('area', function ($row) {

            $areas = getEmpArea($row->id);

            if (empty($areas)) {
                return '--';
            }

            $area_names = array_map(function ($area) {
                return $area->area_name . ' (' . $area->area_code . ')';
            }, $areas);

            return implode(', ', $area_names);
        });


        $datatables->format('designation', function ($value) {
            return $value !== null ? ucwords($value) : '';
        });


        $datatables->format('created_at', function ($value) {

            return date("d-m-Y, h:i a", strtotime($value));
        });

        $datatables->format('updated_at', function ($value) {

            return date("d-m-Y, h:i a", strtotime($value));
        });

        $datatables->format('status', function ($value) {
            return ($value === 'active')
                ? '<span class="ipb-pay-badge is-success">Active</span>'
                : '<span class="ipb-pay-badge is-danger">Inactive</span>';
        });

        if (userHasPermission('employee', 'update')) {
            $datatables->addColumn('action', function ($row) {
                return '<div class="ipb-row-actions"><a href="' . route_to('route.employee.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
            });
        }

        $datatables->except([
            'id',
            'area_id',
            'router_id',
            'package_id',
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


    /**
     * Employees
     * @action: New Employee
     */
    public function new()
    {
        $area_model = model('App\Models\Area');
        $userId = session()->get('user_id');

        $data = [
            'title' => 'New Employee',
            'areas'  => $area_model->where('status', 'active')->where('user_id', $userId)->findAll(),
        ];

        return view('employee/new', $data);
    }


    /**
     * Employees
     * @action: New Employee Create
     */
    public function create()
    {
        $this->validate([
            'name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter employee\'s name',
                ]
            ],
            'designation' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select employee\'s designation',
                ]
            ],
            // 'area_id' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Select employee\'s service area',
            //     ]
            // ],
            'mobile' => [
                'rules' => 'required|is_unique[users.mobile]',
                // 'rules' => 'required',
                'errors' => [
                    'required' => 'Enter employee\'s mobile number',
                    // 'is_unique' => 'Another account is using this number',
                    // 'regex_match' => 'Mobile number should contain country code (+880)',
                ]
            ],
            'address' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter employee\'s address',
                ]
            ],
            'email' => [
                'rules' => 'required|is_unique[users.email]',
                'errors' => [
                    'required' => 'Enter employee\'s email id',
                    'is_unique' => 'Another account is using this email'
                ]
            ],
            'password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter a password for the acccount',
                ]
            ],
            're_password' => [
                'rules' => 'required|matches[password]',
                'errors' => [
                    'required' => 'Rewrite the password',
                    'matches'  => 'Passwords doesn\'t matched',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select account status',
                ]
            ],
        ]);

        if ($this->validation->run()) {
            $userId = session()->get('user_id');

            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $userId])->first();

            $created_by = $details->role;

            $area = getPostInput('area_id');

            if (is_array($area)) {
                // Employee → convert array to comma-separated string
                $area_id = implode(',', $area);
            } else {
                // User → keep single ID as string
                $area_id = $area;
            }

            $data = [
                'name'              => getPostInput('name'),
                'designation'       => getPostInput('designation'),
                'area_id'           => $area_id,
                'mobile'            => getPostInput('mobile'),
                'address'           => getPostInput('address'),
                'email'             => getPostInput('email'),
                'password'          => password_hash(getPostInput('password'), PASSWORD_DEFAULT),
                'role'              => 'employee',
                'status'            => getPostInput('status'),
                'admin_id'            => $userId,
                'created_by'          => $created_by,
            ];


            $result = $this->user_model->insert($data, false);
            // event: employee_create | default template: 6 (add_employes)
            try {
                sendEventSms('employee_create', $data, (int) $userId, 6, null, getPostInput('password'));
            } catch (\Throwable $e) {
                log_message('error', 'Employee Creation SMS Failed: ' . $e->getMessage());
            }
            if ($result) {

                return requestResponse('success', "New employee added successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Employees
     * @action: Delete Employees
     */
    public function delete()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            $employees = $this->user_model->whereIn('id', $ids)->where('role', 'employee')->findAll();
            $result = (new TrashService())->trash('employee', $employees);

            if ($result > 0) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected", 400);
    }


    /**
     * Employees
     * @action: Edit Employee View
     */
    public function edit($id)
    {
        $details = $this->user_model->where(['id' => $id, 'role' => 'employee'])->first();

        if (!empty($details)) {

            $area_model = model('App\Models\Area');
            $user_id = session()->get('user_id');


            $data = [
                'title' => 'Update Employee',
                'details' => $details,
                'areas'  => $area_model->where('status', 'active')->where('user_id', $user_id)->findAll(),
            ];

            return view('employee/edit', $data);
        }

        return show_404();
    }

    /**
     * Employees
     * @action: Update Employee
     */
    public function update($id)
    {
        $this->validate([
            'name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter employee\'s name',
                ]
            ],
            'designation' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select employee\'s designation',
                ]
            ],
            'area_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select employee\'s service area',
                ]
            ],
            'mobile' => [
                'rules' => 'required|is_unique[users.mobile, id, ' . $id . ']|regex_match[/^880\d{10}$/]',
                'errors' => [
                    'required' => 'Enter employee\'s mobile number',
                    'is_unique' => 'Another account is using this number',
                    'regex_match' => 'Mobile number should contain country code (+880)'
                ]
            ],
            'address' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter employee\'s address',
                ]
            ],
            'email' => [
                'rules' => 'required|is_unique[users.email, id, ' . $id . ']',
                'errors' => [
                    'required' => 'Enter employee\'s email id',
                    'is_unique' => 'Another account is using this email',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select account status',
                ]
            ],
        ]);

        if (!empty(getPostInput('password')) || !empty(getPostInput('re_password'))) {

            $this->validate([
                're_password' => [
                    'rules' => 'required|matches[password]',
                    'errors' => [
                        'matches'  => 'Passwords doesn\'t matched'
                    ]
                ],

            ]);
        }

        if ($this->validation->run()) {

            $area = getPostInput('area_id');

            if (is_array($area)) {
                // Employee → convert array to comma-separated string
                $area_id = implode(',', $area);
            } else {
                // User → keep single ID as string
                $area_id = $area;
            }

            $data = [
                'name'              => getPostInput('name'),
                'designation'       => getPostInput('designation'),
                'area_id'           => $area_id,
                'mobile'            => getPostInput('mobile'),
                'address'           => getPostInput('address'),
                'email'             => getPostInput('email'),
                'status'            => getPostInput('status'),
            ];
            log_message('debug', 'New Employee Data: ' . print_r($data, true));

            if (!empty(getPostInput('password'))) {

                $data['password'] = password_hash(getPostInput('password'), PASSWORD_DEFAULT);
            }

            $result = $this->user_model->where(['id' => $id, 'role' => 'employee'])->set($data)->update();

            if ($result) {

                return requestResponse('success', "Employee record updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }
}
