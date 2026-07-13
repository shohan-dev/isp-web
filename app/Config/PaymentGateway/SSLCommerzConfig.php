<?php

namespace Config\PaymentGateway;

use CodeIgniter\Config\BaseConfig;

class SSLCommerzConfig extends BaseConfig
{

    public $base_url, $query_url, $store_id, $store_passwd, $invoice;

    public function __construct()
    {
        helper('text');

        // $this->base_url = 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
        // $this->query_url = 'https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';

        // if (service('settings')->get('BaseController.sslcommerz_sandbox_mode') == 'yes') {

        //     $this->base_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
        //     $this->query_url = 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';
        // }

        // $this->store_id          = service('settings')->get('BaseController.sslcommerz_store_id');
        // $this->store_passwd      = service('settings')->get('BaseController.sslcommerz_store_passwd');
        // $this->invoice           = strtoupper(random_string('alnum', 8));



        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        if ($userRole === 'super_admin' || $userRole === 'admin') {
            $userId= 2;
            // return setting()->get('BaseController' . $userId . '.' . $key);
            if (service('settings')->get('BaseController' . $userId . '.sslcommerz_sandbox_mode') == 'yes') {

                $this->base_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
                $this->query_url = 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';
            }
            
            $this->store_id          = service('settings')->get('BaseController' . $userId . '.sslcommerz_store_id');
            $this->store_passwd      = service('settings')->get('BaseController' . $userId . '.sslcommerz_store_passwd');
            $this->invoice           = strtoupper(random_string('alnum', 8));


        }
        // elseif ($userRole === 'admin') {
        //     $userId=$userId ?? 2;
        //     // return setting()->get('BaseController' . $userId . '.' . $key);
        //     if (service('settings')->get('BaseController' . $userId . '.sslcommerz_sandbox_mode') == 'yes') {

        //         $this->base_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
        //         $this->query_url = 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';
        //     }
            
        //     $this->store_id          = service('settings')->get('BaseController' . $userId . '.sslcommerz_store_id');
        //     $this->store_passwd      = service('settings')->get('BaseController' . $userId . '.sslcommerz_store_passwd');
        //     $this->invoice           = strtoupper(random_string('alnum', 8));


        // }
        elseif ($userRole === 'resellerAdmin'){
            $userId = session()->get('user_id');

            $userModel = model('App\Models\User');
            $details = !empty($userId) ? $userModel->where(['id' => $userId])->first() : null;
            $admin = 2;
            if ($details) {
                $admin = is_object($details) ? ($details->admin_id ?? 2) : ($details['admin_id'] ?? 2);
            }
            log_message('info', 'Successfully called the admin: ' . $admin);

            if (service('settings')->get('BaseController' . $admin . '.sslcommerz_sandbox_mode') == 'yes') {

                $this->base_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
                $this->query_url = 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';
            }
            $this->store_id          = service('settings')->get('BaseController' . $admin . '.sslcommerz_store_id');
            $this->store_passwd      = service('settings')->get('BaseController' . $admin . '.sslcommerz_store_passwd');
            $this->invoice           = strtoupper(random_string('alnum', 8));
    

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

                if (service('settings')->get('BaseController' . $admins . '.sslcommerz_sandbox_mode') == 'yes') {

                    $this->base_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
                    $this->query_url = 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';
                }
                $this->store_id          = service('settings')->get('BaseController' . $admins . '.sslcommerz_store_id');
            $this->store_passwd      = service('settings')->get('BaseController' . $admins . '.sslcommerz_store_passwd');
            $this->invoice           = strtoupper(random_string('alnum', 8));
    

            }
            else{
            // log_message('info', 'Successfully called the user admin: ' . $admin);
            if (service('settings')->get('BaseController' . $admin . '.sslcommerz_sandbox_mode') == 'yes') {

                $this->base_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
                $this->query_url = 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';
            }
            $this->store_id          = service('settings')->get('BaseController' . $admin . '.sslcommerz_store_id');
            $this->store_passwd      = service('settings')->get('BaseController' . $admin . '.sslcommerz_store_passwd');
            $this->invoice           = strtoupper(random_string('alnum', 8));
    

            }
        }

        
    }
}
