<?php

namespace Config\PaymentGateway;

use CodeIgniter\Config\BaseConfig;

class NagadConfig extends BaseConfig
{

    public $sandbox_mode, $merchant_account, $merchant_id, $merchant_private_key, $merchant_public_key, $timezone, $invoice;

    public function __construct()
    {
        helper('text');

        // $this->sandbox_mode          = service('settings')->get('BaseController.nagadpg_sandbox_mode');
        // $this->merchant_account      = service('settings')->get('BaseController.nagadpg_merchant_account');
        // $this->merchant_id           = service('settings')->get('BaseController.nagadpg_merchant_id');
        // $this->merchant_private_key  = service('settings')->get('BaseController.nagadpg_merchant_private_key');
        // $this->merchant_public_key   = service('settings')->get('BaseController.nagadpg_merchant_public_key');
        // $this->timezone              = app_timezone();
        // $this->invoice               = strtoupper(random_string('alnum', 8));




        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        if ($userRole === 'super_admin' || $userRole === 'admin') {
            // return setting()->get('BaseController' . $userId . '.' . $key);
            // $userId=$userId ?? 2;
            $this->sandbox_mode          = service('settings')->get('BaseController2.nagadpg_sandbox_mode');
            $this->merchant_account      = service('settings')->get('BaseController2.nagadpg_merchant_account');
            $this->merchant_id           = service('settings')->get('BaseController2.nagadpg_merchant_id');
            $this->merchant_private_key  = service('settings')->get('BaseController2.nagadpg_merchant_private_key');
            $this->merchant_public_key   = service('settings')->get('BaseController2.nagadpg_merchant_public_key');
            $this->timezone              = app_timezone();
            $this->invoice               = strtoupper(random_string('alnum', 8));
    
        
        }
        // elseif ($userRole === 'admin') {
        //     // return setting()->get('BaseController' . $userId . '.' . $key);
        //     $userId=$userId ?? 2;
        //     $this->sandbox_mode          = service('settings')->get('BaseController' . $userId . '.nagadpg_sandbox_mode');
        //     $this->merchant_account      = service('settings')->get('BaseController' . $userId . '.nagadpg_merchant_account');
        //     $this->merchant_id           = service('settings')->get('BaseController' . $userId . '.nagadpg_merchant_id');
        //     $this->merchant_private_key  = service('settings')->get('BaseController' . $userId . '.nagadpg_merchant_private_key');
        //     $this->merchant_public_key   = service('settings')->get('BaseController' . $userId . '.nagadpg_merchant_public_key');
        //     $this->timezone              = app_timezone();
        //     $this->invoice               = strtoupper(random_string('alnum', 8));
    
        
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


            $this->sandbox_mode          = service('settings')->get('BaseController' . $admin . '.nagadpg_sandbox_mode');
            $this->merchant_account      = service('settings')->get('BaseController' . $admin . '.nagadpg_merchant_account');
            $this->merchant_id           = service('settings')->get('BaseController' . $admin . '.nagadpg_merchant_id');
            $this->merchant_private_key  = service('settings')->get('BaseController' . $admin . '.nagadpg_merchant_private_key');
            $this->merchant_public_key   = service('settings')->get('BaseController' . $admin . '.nagadpg_merchant_public_key');
            $this->timezone              = app_timezone();
            $this->invoice               = strtoupper(random_string('alnum', 8));

        }else{

            $userModel = model('App\Models\User');
            $details = !empty($userId) ? $userModel->where(['id' => $userId])->first() : null;
            
            $admin = 2;
            $created_by = 'super_admin';
            if ($details) {
                $admin = is_object($details) ? ($details->admin_id ?? 2) : ($details['admin_id'] ?? 2);
                $created_by = is_object($details) ? ($details->created_by ?? 'super_admin') : ($details['created_by'] ?? 'super_admin');
            }
            // log_message('info', 'Successfully called the $details->admin_id: ' . $details->admin_id);


            if($created_by==='resellerAdmin'){
                $details = $userModel->where(['id' => $admin])->first();
                $admins = 2;
                if ($details) {
                    $admins = is_object($details) ? ($details->admin_id ?? 2) : ($details['admin_id'] ?? 2);
                }
                // log_message('info', 'Successfully called the reseller admin: ' . $admins);

                $this->sandbox_mode          = service('settings')->get('BaseController' . $admins . '.nagadpg_sandbox_mode');
                $this->merchant_account      = service('settings')->get('BaseController' . $admins . '.nagadpg_merchant_account');
                $this->merchant_id           = service('settings')->get('BaseController' . $admins . '.nagadpg_merchant_id');
                $this->merchant_private_key  = service('settings')->get('BaseController' . $admins . '.nagadpg_merchant_private_key');
                $this->merchant_public_key   = service('settings')->get('BaseController' . $admins . '.nagadpg_merchant_public_key');
                $this->timezone              = app_timezone();
                $this->invoice               = strtoupper(random_string('alnum', 8));

            }
            else{
            // log_message('info', 'Successfully called the user admin: ' . $admin);

            $this->sandbox_mode          = service('settings')->get('BaseController' . $admin . '.nagadpg_sandbox_mode');
            $this->merchant_account      = service('settings')->get('BaseController' . $admin . '.nagadpg_merchant_account');
            $this->merchant_id           = service('settings')->get('BaseController' . $admin . '.nagadpg_merchant_id');
            $this->merchant_private_key  = service('settings')->get('BaseController' . $admin . '.nagadpg_merchant_private_key');
            $this->merchant_public_key   = service('settings')->get('BaseController' . $admin . '.nagadpg_merchant_public_key');
            $this->timezone              = app_timezone();
            $this->invoice               = strtoupper(random_string('alnum', 8));

            }
        }

    }
}
