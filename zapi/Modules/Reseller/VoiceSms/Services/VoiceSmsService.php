<?php

namespace Zapi\Modules\Reseller\VoiceSms\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\VoiceSms\Services\VoiceSmsService\VoiceSmsServicePart01Segment;

class VoiceSmsService extends BaseApiController
{

    use VoiceSmsServicePart01Segment;

    protected $user_model;
    protected $voice_model;
    protected $event_config_model;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
        $this->voice_model = model('App\Models\VoiceSmsModel');
        $this->event_config_model = model('App\Models\VoiceEventConfig');
        helper(['user']);
    }


}

