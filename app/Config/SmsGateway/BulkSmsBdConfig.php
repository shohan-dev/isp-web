<?php
 
/**
 * BulkSmsBd sms sending library config file
 * @author Pranay Chakraborty
 * @link https://github.com/pranaycb
 */
 
namespace Config\SmsGateway;
 
use CodeIgniter\Config\BaseConfig;
 
class BulkSmsBdConfig extends BaseConfig
{
 
    public $api_key, $senderid;
 
    public function __construct($id = null)
    {
        $userId = $id ?? session()->get('user_id');
        $prefix = getSettingPrefixForUser($userId);
 
        $this->api_key = service('settings')->get($prefix . '.bulksmsbd_api_key');
        $this->senderid = service('settings')->get($prefix . '.bulksmsbd_sender_id');
    }
}
