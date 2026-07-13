<?php

namespace Zapi\Modules\Reseller\Funding\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Funding\Services\FundingService\FundingServicePart01Segment;
use Zapi\Modules\Reseller\Funding\Services\FundingService\FundingServicePart02Segment;

class FundingService extends BaseApiController
{

    use FundingServicePart01Segment;
    use FundingServicePart02Segment;

    protected $funding_model;
    protected $user_model;
    protected $payment_model;

    public function __construct()
    {
        $this->funding_model = model('App\Models\ResellerFundingModel');
        $this->user_model = model('App\Models\User');
        $this->payment_model = model('App\Models\Payment');
        helper(['url', 'user']);
    }


}

