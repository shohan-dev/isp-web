<?php

namespace Zapi\Modules\Reseller\Subscription\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Subscription\Services\SubscriptionService\SubscriptionServicePart01Segment;
use Zapi\Modules\Reseller\Subscription\Services\SubscriptionService\SubscriptionServicePart02Segment;

class SubscriptionService extends BaseApiController
{

    use SubscriptionServicePart01Segment;
    use SubscriptionServicePart02Segment;

    protected $user_model;
    protected $payment_model;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
        $this->payment_model = model('App\Models\Payment');
        helper(['url', 'user']);
    }


}

