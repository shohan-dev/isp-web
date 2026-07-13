<?php

namespace Config\PaymentGateway;

use CodeIgniter\Config\BaseConfig;

class BkashConfig extends BaseConfig
{

    public $environment, $app_key, $app_secret, $username, $password, $invoice;

    public function __construct()
    {
        helper('text');

        $this->environment = 'production';

        



        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        if ($userRole === 'super_admin' || $userRole === 'admin') {
            // return setting()->get('BaseController' . $userId . '.' . $key);
            if (service('settings')->get('BaseController2.bkashpg_sandbox_mode') == 'yes') {

                $this->environment = 'sandbox';
            }
            $userId=2;
            $this->app_key      = service('settings')->get('BaseController' . $userId . '.bkashpg_app_key');
            $this->app_secret   = service('settings')->get('BaseController' . $userId . '.bkashpg_app_secret');
            $this->username     = service('settings')->get('BaseController' . $userId . '.bkashpg_username');
            $this->password     = service('settings')->get('BaseController' . $userId . '.bkashpg_password');

        }
        // elseif ($userRole === 'admin') {
        //     // return setting()->get('BaseController' . $userId . '.' . $key);
        //     if (service('settings')->get('BaseController2.bkashpg_sandbox_mode') == 'yes') {

        //         $this->environment = 'sandbox';
        //     }
            
        //     $this->app_key      = service('settings')->get('BaseController' . $userId . '.bkashpg_app_key');
        //     $this->app_secret   = service('settings')->get('BaseController' . $userId . '.bkashpg_app_secret');
        //     $this->username     = service('settings')->get('BaseController' . $userId . '.bkashpg_username');
        //     $this->password     = service('settings')->get('BaseController' . $userId . '.bkashpg_password');

        // }
        elseif ($userRole === 'resellerAdmin'){
            // $userId = session()->get('user_id');

            $userModel = model('App\Models\User');
            $details = !empty($userId) ? $userModel->where(['id' => $userId])->first() : null;
            $admin = 2;
            if ($details) {
                $admin = is_object($details) ? ($details->admin_id ?? 2) : ($details['admin_id'] ?? 2);
            }
            log_message('info', 'Successfully called the admin: ' . $admin);

            if (service('settings')->get('BaseController' . $admin . '.bkashpg_sandbox_mode') == 'yes') {

                $this->environment = 'sandbox';
            }
            $this->app_key = service('settings')->get('BaseController' . $admin . '.bkashpg_app_key');
            $this->app_secret = service('settings')->get('BaseController' . $admin . '.bkashpg_app_secret');
            $this->username = service('settings')->get('BaseController' . $admin . '.bkashpg_username');
            $this->password = service('settings')->get('BaseController' . $admin . '.bkashpg_password');

        }else{

            $userModel = model('App\Models\User');
            $details = !empty($userId) ? $userModel->where(['id' => $userId])->first() : null;
            
            $admin = 2;
            $created_by = 'super_admin';
            if ($details) {
                $admin = is_object($details) ? ($details->admin_id ?? 2) : ($details['admin_id'] ?? 2);
                $created_by = is_object($details) ? ($details->created_by ?? 'super_admin') : ($details['created_by'] ?? 'super_admin');
            }
            // log_message('info', 'Successfully called the $details->admin_id: ' . $admin);

            if($created_by==='resellerAdmin'){
                $details = $userModel->where(['id' => $admin])->first();
                $admins = 2;
                if ($details) {
                    $admins = is_object($details) ? ($details->admin_id ?? 2) : ($details['admin_id'] ?? 2);
                }
                // log_message('info', 'Successfully called the reseller admin: ' . $admins);

                if (service('settings')->get('BaseController' . $admins . '.bkashpg_sandbox_mode') == 'yes') {

                    $this->environment = 'sandbox';
                }
                $this->app_key = service('settings')->get('BaseController' . $admins . '.bkashpg_app_key');
                $this->app_secret = service('settings')->get('BaseController' . $admins . '.bkashpg_app_secret');
                $this->username = service('settings')->get('BaseController' . $admins . '.bkashpg_username');
                $this->password = service('settings')->get('BaseController' . $admins . '.bkashpg_password');

            }
            else{
            // log_message('info', 'Successfully called the user admin: ' . $admin);
            if (service('settings')->get('BaseController' . $admin . '.bkashpg_sandbox_mode') == 'yes') {

                $this->environment = 'sandbox';
            }
            $this->app_key = service('settings')->get('BaseController' . $admin . '.bkashpg_app_key');
            $this->app_secret = service('settings')->get('BaseController' . $admin . '.bkashpg_app_secret');
            $this->username = service('settings')->get('BaseController' . $admin . '.bkashpg_username');
            $this->password = service('settings')->get('BaseController' . $admin . '.bkashpg_password');

            }
        }


        // $this->app_key      = service('settings')->get('BaseController.bkashpg_app_key');
        // $this->app_secret   = service('settings')->get('BaseController.bkashpg_app_secret');
        // $this->username     = service('settings')->get('BaseController.bkashpg_username');
        // $this->password     = service('settings')->get('BaseController.bkashpg_password');


    }
}
