<?php

namespace Config\SmsGateway;

use CodeIgniter\Config\BaseConfig;

class AwajDigitalConfig extends BaseConfig
{
    public $api_token, $sender_number, $default_voice;

    public function __construct($id = null)
    {
        $userId = $id ?? session()->get('user_id');
        $prefix = getSettingPrefixForUser($userId);

        $this->api_token = service('settings')->get($prefix . '.awajdigital_api_token');
        $this->sender_number = service('settings')->get($prefix . '.awajdigital_sender_number');
        $this->default_voice = service('settings')->get($prefix . '.awajdigital_default_voice');
    }
}
