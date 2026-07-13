<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Settings extends BaseController
{

    public function __construct()
    {
        /**
         * Sms Helper
         */
        helper('sms');
    }


    /**
     * Theme Studio — client-side brand preferences (localStorage).
     * Presentation only; no server persistence.
     */
    public function themeStudio()
    {
        return view('settings/theme-studio', [
            'title' => 'Theme Studio',
        ]);
    }

    /**
     * Settings
     * @action: Software Settings
     */
    public function index()
    {
        if (getInputMethod() === 'get') {

            $data = [
                'title' => 'Software Settings',
            ];

            return view('settings/software-settings', $data);
        }

        if (getInputMethod() === 'post') {


            if (!empty($_FILES['app_logo']['name'])) {
                $this->validate([
                    'app_logo' => [
                        'rules' => 'uploaded[app_logo]|max_size[app_logo,2048]|is_image[app_logo]|ext_in[app_logo,png,jpg,jpeg,gif]',
                        'errors' => [
                            'uploaded' => 'Please upload a logo',
                            'max_size' => 'Logo size is too large (max 2MB)',
                            'is_image' => 'The file must be an image',
                            'ext_in'   => 'Invalid extension. Allowed: png, jpg, jpeg, gif'
                        ]
                    ]
                ]);
            }

            if (!empty($_FILES['app_icon']['name'])) {
                $this->validate([
                    'app_icon' => [
                        'rules' => 'uploaded[app_icon]|max_size[app_icon,1024]|is_image[app_icon]|ext_in[app_icon,png,jpg,jpeg,gif,ico]',
                        'errors' => [
                            'uploaded' => 'Please upload an icon',
                            'max_size' => 'Icon size is too large (max 1MB)',
                            'is_image' => 'The file must be an image',
                            'ext_in'   => 'Invalid extension. Allowed: png, jpg, jpeg, gif, ico'
                        ]
                    ]
                ]);
            }

            // if (getPostInput('default_sms_gateway') === 'bulksmsbd') {

            //     $this->validate([
            //         'bulksmsbd_api_key' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'bulksmsbd_sender_id' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //     ]);
            // }

            // if (getPostInput('default_sms_gateway') === 'greenwebsms') {

            //     $this->validate([
            //         'greenwebsms_token' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //     ]);
            // }

            // if (getPostInput('default_sms_gateway') === 'smsq') {

            //     $this->validate([
            //         'smsq_api_key' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'smsq_client_id' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'smsq_sender_id' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //     ]);
            // }

            // if (getPostInput('enable_bkashpg') === 'yes') {

            //     $this->validate([
            //         'bkashpg_app_key' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'bkashpg_app_secret' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'bkashpg_username' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'bkashpg_password' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'bkashpg_sandbox_mode' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'bkashpg_charge' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //     ]);
            // }

            // if (getPostInput('enable_nagadpg') === 'yes') {

            //     $this->validate([
            //         'nagadpg_merchant_account' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'nagadpg_merchant_id' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'nagadpg_merchant_private_key' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'nagadpg_merchant_public_key' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'nagadpg_sandbox_mode' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'nagadpg_charge' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //     ]);
            // }

            // if (getPostInput('enable_sslcommerz') === 'yes') {

            //     $this->validate([
            //         'sslcommerz_store_id' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'sslcommerz_store_passwd' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'sslcommerz_sandbox_mode' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //         'sslcommerz_charge' => [
            //             'rules' => 'required',
            //             'errors' => [
            //                 'required' => 'This field is required',
            //             ],
            //         ],
            //     ]);
            // }

            $validation_rules = [];
            if (!empty($_FILES['app_logo']['name'])) {
                $validation_rules['app_logo'] = [
                    'rules' => 'uploaded[app_logo]|max_size[app_logo,4096]|is_image[app_logo]|ext_in[app_logo,png,jpg,jpeg,gif,ico,svg]',
                    'errors' => [
                        'uploaded' => 'Please upload a valid logo file',
                        'max_size' => 'Logo size is too large (max 4MB)',
                        'is_image' => 'The logo must be an image file',
                        'ext_in'   => 'Invalid extension for logo. Allowed: png, jpg, jpeg, gif, ico, svg'
                    ]
                ];
            }
            if (!empty($_FILES['app_icon']['name'])) {
                $validation_rules['app_icon'] = [
                    'rules' => 'uploaded[app_icon]|max_size[app_icon,2048]|is_image[app_icon]|ext_in[app_icon,png,jpg,jpeg,gif,ico,svg]',
                    'errors' => [
                        'uploaded' => 'Please upload a valid icon file',
                        'max_size' => 'Icon size is too large (max 2MB)',
                        'is_image' => 'The icon must be an image file',
                        'ext_in'   => 'Invalid extension for icon. Allowed: png, jpg, jpeg, gif, ico, svg'
                    ]
                ];
            }

            if (!empty($validation_rules)) {
                if (!$this->validate($validation_rules)) {
                    return requestResponse('error', implode(', ', $this->validation->getErrors()), 400);
                }
            }

            if (empty($validation_rules) || $this->validation->run()) {

            $data = getPostInput();

            if (!empty($_FILES['app_logo']['name'])) {

                $logo = getFileInput('app_logo');

                $updatedLogo = $logo->getRandomName();

                $logo->move('assets/img/logo/', $updatedLogo);

                if ($logo->hasMoved()) {

                    $data['app_logo'] = $updatedLogo;

                    //remove previously uploaded logo
                    if (!empty(getSetting("app_logo"))) {

                        $current_logo = 'assets/img/logo/' . getSetting("app_logo");

                        if (file_exists($current_logo)) {

                            unlink($current_logo);
                        }
                    }
                }
                else {
                    return requestResponse('error', 'Something went wrong while trying to upload the logo', 500);
                }
            }
            else {

                $data['app_logo'] = getSetting("app_logo");
            }

            if (!empty($_FILES['app_icon']['name'])) {

                $icon = getFileInput('app_icon');

                $updatedIcon = $icon->getRandomName();

                $icon->move('assets/img/logo/', $updatedIcon);

                if ($icon->hasMoved()) {

                    $data['app_icon'] = $updatedIcon;

                    //remove previously uploaded icon
                    if (!empty(getSetting("app_icon"))) {

                        $current_icon = 'assets/img/logo/' . getSetting("app_icon");

                        if (file_exists($current_icon)) {

                            unlink($current_icon);
                        }
                    }
                }
                else {

                    return requestResponse('error', 'Something went wrong while trying to upload the icon', 500);
                }
            }
            else {

                $data['app_icon'] = getSetting("app_icon");
            }

            $result = setSetting($data);

            if ($result) {

                return requestResponse('succes', "Settings updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
            }

            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }

        show_404();
    }


    /**
     * Settings
     * @action: Check Sms Balance
     */
    public function checkBalance()
    {
        $this->validate(['gateway' => ['rules' => 'required|in_list[bulksmsbd, greenwebsms, smsq, telnet, bulksmsdhaka, awajdigital]']]);

        if ($this->validation->run()) {

            $gateway = getPostInput('gateway');

            return requestResponse('success', checkBalance($gateway), 200);
        }

        return requestResponse('error', $this->validation->getErrors(), 400);
    }


    /**
     * Settings
     * @action: Change Password
     */
    public function changePassword()
    {
        if (!userHasPermission('password_change', 'update'))
            show_404();

        if (getInputMethod() === 'get') {

            $data = [
                'title' => 'Change Password',
            ];

            return view('settings/change-password', $data);
        }

        if (getInputMethod() === 'post') {

            $this->validate([
                'old_password' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Enter your current password',
                    ]
                ],
                'new_password' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Enter a new password',
                    ]
                ],
                'retyped_new_password' => [
                    'rules' => 'required|matches[new_password]',
                    'errors' => [
                        'required' => 'Rewrite new password',
                        'matches' => 'Passwords doesn\'t matched'
                    ]
                ],
            ]);

            if ($this->validation->run()) {

                $userModel = model('App\Models\User');

                $user = $userModel->find(getSession('user_id'));

                if (password_verify(getPostInput('old_password'), $user->password)) {

                    $newpass = getPostInput('new_password');
                    $result = $userModel->update(getSession('user_id'), [
                        'code' => $newpass,
                        'password' => password_hash($newpass, PASSWORD_DEFAULT)
                    ]);

                    if ($result) {

                        // Phase 2: a password change kills outstanding JWT access tokens.
                        helper('token');
                        revokeUserTokens(getSession('user_id'));

                        return requestResponse('succes', "Password changed successfully", 200);
                    }

                    return requestResponse('error', "Something went wrong! Please try again", 500);
                }

                return requestResponse('validation-error', ['old_password' => 'Your current password is wrong'], 400);
            }

            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }
    }
}
