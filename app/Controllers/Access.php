<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class Access extends BaseController
{
    protected $permission_model, $custom_access_model;

    public function __construct()
    {
        /**
         * Only admin can access this page
         */
        // if (getSession('user_role') != 'super_admin') show_404();

        /**
         * Permission Model
         */
        $this->permission_model = model('App\Models\Permission');

        /**
         * Cuastom Access Model
         */
        $this->custom_access_model = model('App\Models\CustomAccess');
    }

    /**
     * Access
     * @action: Default Access View
     */
    public function index()
    {
        $data = [
            'title' => 'Default Access',
        ];




        return view('access/default-access', $data);
    }

    /**
     * Access
     * @action: Fetch Access
     */
    public function fetch()
    {
        $userId = session()->get('user_id');
        $userRole = model('App\Models\User')->where('id', $userId)->first()->role;


        // dd($userRole);
        // $userId = 216;
        log_message('debug', 'User Details:asdasd ' . print_r($userId, true));

        $data = $this->permission_model->builder()
            ->select('*')
            ->where('user_id', $userId);

        if ($userRole === 'admin') {
            $data->where('user_type !=', 'admin');
        }

        // fetch the data

        log_message('debug', 'asdwdasd' . print_r($data, true));

        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');



        $datatables->addColumn('role', function ($row) {

            // log_message('debug', 'asdasdwwwdasdasddatatables' . print_r($row, true));
            if ($row->user_type === 'admin') {
                return 'Second Admin'; // Handle 'admin' specifically
            }
            if ($row->user_type === 'resellerAdmin') {
                return 'Reseller'; // Handle 'admin' specifically
            }
            return $row->user_type === 'user' ? 'Customer' : ucwords($row->user_type);
        });

        // log_message('debug', 'asdasdwwwdasdasddatatables' . print_r($row, true));





        $datatables->addColumn('action', function ($row) {
            // log_message('debug', 'asdasdwwwdasdasddatatables' . print_r($row->id , true));


            return '<div class="ipb-row-actions"><button type="button" class="ipb-row-btn tone-brand access-btn" data-access_id="' . $row->id . '" title="Update"><i class="far fa-pen-to-square"></i> Update</button></div>';
        });

        $datatables->except(['id', 'user_type', 'permissions']);

        $datatables->asObject();

        $datatables->generate();
    }

    /**
     * Access
     * @action: Get Access Record
     */
    public function getAccess()
    {
        $this->validate(['access_id' => ['rules' => 'required']]);

        if ($this->validation->run()) {
            $access_id = getPostInput('access_id');

            log_message('debug', 'access id Details: ' . print_r($access_id, true));

            $data = $this->permission_model->find($access_id);

            log_message('debug', 'Fetched Data: ' . print_r($data, true));

            if (!empty($data)) {
                // Ensure permissions are correctly fetched
                $data->permissions = json_decode($data->permissions, true) ?? [];

                log_message('debug', 'Decoded Permissions:asdasdasdaww ' . print_r($data->permissions, true));

                $html = view('access/partial/default-access-fields', [
                    'user_type' => $data->user_type, // Ensure correct user type is used
                    'permissions' => $data->permissions, // Pass the decoded permissions
                    'form_submit_link' => route_to('route.useraccess.update_access', $data->id),
                    'heading' => ($data->user_type == 'user') ? "Customer's Access" : ucwords($data->user_type) . "'s Access",
                    'btn_text' => 'Update'
                ]);

                // log_message('debug', 'Decoded Permissions:asdad ' . print_r($html, true));


                return requestResponse('success', $html, 200);
            }

            return requestResponse('error', 'Invalid access id', 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }



    /**
     * Access
     * @action: Update Employee Access
     */
    public function updateAccess($user_type)
    {
        $data = [
            'permissions' => json_encode(getPostInput()),
        ];

        log_message('debug', 'Input Data data: ' . print_r($data, true));
        log_message('debug', 'User Type: ' . print_r($user_type, true));

        $userId = session()->get('user_id');

        log_message('debug', 'asdadadasdasdwdasdasd ' . print_r($userId, true));


        // Ensure the user has permission to update
        $existingPermission = $this->permission_model
            ->where('id', $user_type)
            ->where('user_id', $userId) // Ensure the current user has access to update this record
            ->first();

        if (!$existingPermission) {
            return requestResponse('error', "Unauthorized! You don't have permission to update this record", 403);
        }

        // log_message('debug', 'existingPermission asdasda sd ' . print_r($existingPermission, true));


        // Update only if the record exists and the user has permission
        $result = $this->permission_model
            ->set($data)
            ->where(['id' => $user_type, 'user_id' => $userId])
            ->update();



        if ($result) {
            log_message('debug', 'Update Result: ' . print_r($result, true));
            (new \App\Services\AuditService())->record(
                'permission.update',
                'permission',
                ['permission_id' => (int) $user_type, 'user_type' => $existingPermission->user_type ?? null]
            );
            return requestResponse('success', "Records updated successfully", 200);
        }

        return requestResponse('error', "Something went wrong! Please try again", 500);
    }



    /**
     * Access
     * @action: Custom Access
     */
    public function custom()
    {
        $data = [
            'title' => 'Custom Access',
        ];

        return view('access/custom-access', $data);
    }

    /**
     * Access
     * @action: Fetch Custom Access
     */
    public function fetchCustomAccess()
    {
        $userId = session()->get('user_id');

        $data = $this->custom_access_model->builder()
            ->select('*')
            ->where('admin_id', $userId);

        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addColumn('select', function ($row) {

            return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
        });

        $datatables->addSequenceNumber('serial');

        $datatables->addColumn('user', function ($row) {

            return getUserById($row->user_id)->name ?? '--';
        });

        $datatables->addColumn('role', function ($row) {

            $user = getUserById($row->user_id);

            return $user->role === 'user' ? 'Customer' : $user->role;
        });

        $datatables->format('status', function ($value) {

            return ($value === 'active')
                ? '<span class="ipb-pay-badge is-success">Active</span>'
                : '<span class="ipb-pay-badge is-danger">Inactive</span>';
        });

        $datatables->addColumn('action', function ($row) {

            return '<div class="ipb-row-actions"><button type="button" class="ipb-row-btn tone-brand access-btn" data-access_id="' . $row->id . '" title="Update"><i class="far fa-pen-to-square"></i> Update</button></div>';
        });

        $datatables->except(['id', 'user_id', 'permissions']);

        $datatables->asObject();

        $datatables->generate();
    }

    /**
     * Access
     * @action: Default Access View
     */
    public function newCustomAccess()
    {
        $user_model = model('App\Models\User');
        $userId = session()->get('user_id');

        $html = view('access/partial/custom-access-fields', [

            'customers' => $user_model->where(['role' => 'user', 'admin_id' => $userId, 'status' => 'active'])->findAll(),
            'admins' => $user_model->where(['role' => 'admin'])->findAll(),
            'employees' => $user_model->where(['role' => 'employee', 'admin_id' => $userId, 'status' => 'active'])->findAll(),
            'resellers' => $user_model->where(['role' => 'resellerAdmin', 'admin_id' => $userId, 'status' => 'active'])->findAll(),

            'user_type' => '',

            'user_id' => null,

            'form_submit_link' => route_to('route.useraccess.custom.create'),

            'heading' => 'New Access',

            'btn_text' => 'Submit'
        ]);;

        return requestResponse('success', $html, 200);
    }


    /**
     * Access
     * @action: Create Custom Access
     */
    public function createCustomAccess()
    {
        $this->validate([
            'user' => [
                'rules' => 'required|is_unique[custom_access.user_id]',
                'errors' => [
                    'required' => 'Select a user',
                    'is_unique' => 'Custom access for this user is already exists!'
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select access status',
                ]
            ],
        ]);

        if ($this->validation->run()) {

            $_post_data = getPostInput();

            $user_id = $_post_data['user'];
            $status = $_post_data['status'];
            $userId = session()->get('user_id');

            unset($_post_data['user'], $_post_data['status']);

            $permissions = json_encode($_post_data);

            log_message('debug', 'Input Data: ' . print_r($userId, true));
            $result = $this->custom_access_model->insert([
                'user_id' => $user_id,
                'admin_id' => $userId,
                'permissions' => $permissions,
                'status' => $status,
            ], false);

            if ($result) {

                return requestResponse('success', "New access record added successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Access
     * @action: Get Custom Access Record
     */
    public function getCustomAccess()
    {
        $this->validate(['access_id' => ['rules' => 'required']]);
        $userId = session()->get('user_id');

        if ($this->validation->run()) {

            $user_model = model('App\Models\User');

            $access_id = getPostInput('access_id');

            $data = $this->custom_access_model->find($access_id);

            if (!empty($data)) {

                $data_array = [

                    'customers' => $user_model->where(['role' => 'user', 'admin_id' => $userId, 'status' => 'active'])->findAll(),

                    'employees' => $user_model->where(['role' => 'employee', 'admin_id' => $userId, 'status' => 'active'])->findAll(),
                    'admins' => $user_model->where(['role' => 'admin'])->findAll(),
                    'resellers' => $user_model->where(['role' => 'resellerAdmin', 'admin_id' => $userId, 'status' => 'active'])->findAll(),


                    'user_type' => getUserById($data->user_id)->role ?? '',
                    'user_id' => $data->user_id,
                    'permission_status' => $data->status,
                    'heading' => 'Update Access',
                    'btn_text' => 'Update',
                    'form_submit_link' => route_to('route.useraccess.custom.update', $data->id),
                ];

                $html = view('access/partial/custom-access-fields', $data_array);

                return requestResponse('success', $html, 200);
            }

            return requestResponse('error', 'Invalid access id', 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Access
     * @action: Create Custom Access
     */
    public function updateCustomAccess($id)
    {
        log_message('debug', 'Input hereeeee: ');

        $this->validate([
            'user' => [
                'rules' => 'required|is_unique[custom_access.user_id, id, ' . $id . ']',
                'errors' => [
                    'required' => 'Select a user',
                    'is_unique' => 'Custom access for this user has already exists!'
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select access status',
                ]
            ],
        ]);

        if ($this->validation->run()) {

            $_post_data = getPostInput();

            $user_id = $_post_data['user'];
            $status = $_post_data['status'];

            unset($_post_data['user'], $_post_data['status']);

            $permissions = json_encode($_post_data);

            $result = $this->custom_access_model->update($id, [
                'user_id' => $user_id,
                'permissions' => $permissions,
                'status' => $status,
            ]);

            if ($result) {

                (new \App\Services\AuditService())->record(
                    'permission.custom_update',
                    'custom_access',
                    ['custom_access_id' => (int) $id, 'target_user_id' => (int) $user_id, 'status' => $status]
                );

                return requestResponse('success', "Access record has been updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Access
     * @action: Delete Custom Access
     */
    public function deleteCustomAccess()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            $result = $this->custom_access_model->whereIn('id', $ids)->delete();

            if ($result) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected", 400);
    }
}
