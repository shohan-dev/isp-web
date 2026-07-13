<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Profile extends BaseController
{

    protected $user_model, $reseller_model;

    public function __construct()
    {
        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
        $this->reseller_model = model('App\Models\Registration');
    }


    /**
     * Profile
     * @action: Profile View
     */
    public function index()
    {
        if (
            !userHasPermission('profile_update', 'read') &&
            !userHasPermission('profile_update', 'update')
        ) show_404();

        $id = getSession('user_id');
        $details = $this->user_model->where(['id' => $id])->first();

        if (is_object($details)) {
            $mobilenum = $details->mobile;
        } elseif (is_array($details)) {
            $mobilenum = $details['mobile'];
        } else {
            $mobilenum = null;
        }

        if ($mobilenum === null) {
            return show_404(); // Or redirect to a different page or show a custom message
        }


        $rdetails = $this->reseller_model->where(['userid' => $id])->first();

        $data = [
            'title' => 'My Profile',
            'details' => $details,
            'rdetails' => $rdetails,

        ];

        return view('profile/profile', $data);
    }


    /**
     * Profile
     * @action: Update Profile
     */
    public function update()
    {
        if (!userHasPermission('profile_update', 'update')) show_404();

        $this->validate([
            'name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter your name',
                ]
            ],
            'mobile' => [
                'rules' => 'required|is_unique[users.mobile, id, ' . getSession('user_id') . ']',
                'errors' => [
                    'required' => 'Enter your mobile number',
                    'is_unique' => 'Another account is using this number'
                ]
            ],
            'email' => [
                'rules' => 'required|is_unique[users.email, id, ' . getSession('user_id') . ']',
                'errors' => [
                    'required' => 'Enter your email id',
                    'is_unique' => 'Another account is using this email'
                ]
            ],
            'address' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter your address',
                ]
            ],
            'whatsapp_number' => [
                'rules' => 'permit_empty',
            ],
            'payment_receive_number' => [
                'rules' => 'permit_empty',
            ],
        ]);

        if ($this->validation->run()) {

            $data = [
                'name'     => getPostInput('name'),
                'mobile'   => getPostInput('mobile'),
                'email'    => getPostInput('email'),
                'address'  => getPostInput('address'),
                'whatsapp_number'  => getPostInput('whatsapp_number'),
                'payment_receive_number'  => getPostInput('payment_receive_number'),
                // 'posPrinter' => getPostInput('posPrinter'),
            ];

            $result = $this->user_model->update(getSession('user_id'), $data);

            if ($result) {

                return requestResponse('success', "Profile updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    public function Orgupdate()
    {
        if (!userHasPermission('profile_update', 'update')) show_404();


        $data = [
            'organization_name'     => getPostInput('organization_name'),
            'nationalid'   => getPostInput('nationalid'),
            'customer_type'     => json_encode(getPostInput('customer_type')),

        ];

        log_message('debug', 'insertId insertId: ' . print_r($data, true));
        // or
        $id = getSession('user_id');
        $rdetails = $this->reseller_model->where(['userid' => $id])->first();
        log_message('debug', 'rdetails rdetails: ' . print_r($rdetails, true));


        if (!empty($rdetails)) {
            log_message('debug', 'Update operation for user ID: ' . $rdetails['id']??null);
            $userId = $rdetails['id']??null;

            $result = $this->reseller_model->update($userId, $data);
        } else {
            $data['userid'] = $id;

            $result = $this->reseller_model->insert($data);

            log_message('debug', 'Insert performed with new ID: ' . $result);
        }

        if ($result) {

            return requestResponse('success', "Profile updated successfully", 200);
        }

        return requestResponse('error', "Something went wrong! Please try again", 500);
    }

    //    public function userProfile()
    // {
    //     // log_message(`debug`, 'userProfile called');
    //     // Get username from POST request
    //     $username = $this->request->getPost('name');

    //     $model = model('App\Models\UserRouterDataModel');
    //     $data = $model->where('pppoe_secret', $username)->first();

    //     log_message('debug', 'User Router Data: ' . print_r($data, true));

    //     $id = $data ? $data->user_id : null;

    //     $user_model = model('App\Models\User');
    //     $user = $user_model->where('id', $id)->first();


    //     if (!$user) {
    //         return $this->response->setJSON([
    //             'status'   => 'error',
    //             'response' => 'No username provided.'
    //         ]);
    //     }

    //     log_message('debug', 'User Profile Data: ' . print_r($user, true));

    //     // 🔹 Example: Fetch from DB or MikroTik
    //     // Replace this with your actual query/service
    //     // $userDetails = [
    //     //     'details'     => $user, 

    //     // ];

    //     return $this->response->setJSON([
    //         'status'   => 'success',
    //         'response' => $user
    //     ]);
    // }


    public function userProfile()
    {
        $usernames = $this->request->getPost('names');

        if (empty($usernames)) {
            return $this->response->setJSON([
                'status'   => 'error',
                'response' => 'No usernames provided.'
            ]);
        }

        if (!is_array($usernames)) {
            $usernames = [$usernames];
        }

        $model = model('App\Models\UserRouterDataModel');
        $routerData = $model->whereIn('pppoe_secret', $usernames)->findAll();

        if (!$routerData) {
            return $this->response->setJSON([
                'status'   => 'error',
                'response' => 'No user router data found.'
            ]);
        }

        $userIds = array_column($routerData, 'user_id');
        $user_model = model('App\Models\User');
        $users = $user_model->whereIn('id', $userIds)->findAll();

        // Merge PPPoE secret with user data
        $profiles = [];
        foreach ($routerData as $r) {
            $user = array_filter($users, fn($u) => $u->id == $r->user_id);
            $user = reset($user);
            if ($user) {
                $profiles[] = [
                    'pppoe_secret'  => $r->pppoe_secret,
                    'customer_name' => $user->name ?? 'N/A',
                ];
            }
        }


        return $this->response->setJSON([
            'status'   => 'success',
            'response' => $profiles
        ]);
    }
}
