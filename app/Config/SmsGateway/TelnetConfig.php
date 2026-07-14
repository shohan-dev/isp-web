<?php
 
namespace Config\SmsGateway;
 
use CodeIgniter\Config\BaseConfig;
 
class TelnetConfig extends BaseConfig
{
    public $sid, $user, $password;
 
    public function __construct($id = null)
    {
        $userId = $id ?? session()->get('user_id');
        $prefix = getSettingPrefixForUser($userId);
 
        $this->sid = service('settings')->get($prefix . '.telnet_sms_sender_id');
        $this->user = service('settings')->get($prefix . '.telnet_sms_username');
        $this->password = service('settings')->get($prefix . '.telnet_sms_password');
    }
}