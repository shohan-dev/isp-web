<?php

namespace Zapi\Modules\Reseller\Profile\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Profile\Services\ProfileService\ProfileServicePart01Segment;

class ProfileService extends BaseApiController
{

    use ProfileServicePart01Segment;

    protected $user_model;
    protected $registration_model;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
        $this->registration_model = model('App\Models\Registration');
        helper(['url', 'user']);
    }


}

