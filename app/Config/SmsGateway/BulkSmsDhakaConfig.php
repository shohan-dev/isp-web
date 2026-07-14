<?php
 
namespace Config\SmsGateway;
 
use CodeIgniter\Config\BaseConfig;
 
class BulkSmsDhakaConfig extends BaseConfig
{
    public $api_key, $senderid;
 
    public function __construct($id = null)
    {
        $userId = $id ?? session()->get('user_id');
        $prefix = getSettingPrefixForUser($userId);
 
        $this->api_key = service('settings')->get($prefix . '.bulksmsdhaka_api_key');
        $this->senderid = service('settings')->get($prefix . '.bulksmsdhaka_sender_id');
    }
}
