<?php
 
namespace Config\SmsGateway;
 
use CodeIgniter\Config\BaseConfig;
 
class GreenWebSmsConfig extends BaseConfig
{
    public $api_key, $senderid;
 
    public function __construct($id = null)
    {
        $userId = $id ?? session()->get('user_id');
        $prefix = getSettingPrefixForUser($userId);
 
        $this->api_key = service('settings')->get($prefix . '.greenwebsms_token');
    }
}
