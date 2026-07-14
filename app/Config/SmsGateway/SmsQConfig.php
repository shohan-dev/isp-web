<?php
 
namespace Config\SmsGateway;
 
use CodeIgniter\Config\BaseConfig;
 
class SmsQConfig extends BaseConfig
{
    public $api_key, $senderid, $clientid;
 
    public function __construct($id = null)
    {
        $userId = $id ?? session()->get('user_id');
        $prefix = getSettingPrefixForUser($userId);
 
        $this->api_key = service('settings')->get($prefix . '.smsq_api_key');
        $this->senderid = service('settings')->get($prefix . '.smsq_sender_id');
        $this->clientid = service('settings')->get($prefix . '.smsq_client_id');
    }
}
