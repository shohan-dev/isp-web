<?php

namespace Zapi\Modules\Reseller\Sms\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Sms\Services\SmsService\SmsServicePart01Segment;

class SmsService extends BaseApiController
{

    use SmsServicePart01Segment;

    protected $sms_model;
    protected $user_model;

    public function __construct()
    {
        $this->sms_model = model('App\Models\Sms');
        $this->user_model = model('App\Models\User');
        helper(['sms', 'user']);
    }


}

