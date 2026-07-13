<?php

namespace Zapi\Modules\Reseller\Payment\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Payment\Services\PaymentService\PaymentServicePart01Segment;

class PaymentService extends BaseApiController
{

    use PaymentServicePart01Segment;

    protected $payment_model;

    public function __construct()
    {
        $this->payment_model = model('App\Models\Payment');
        helper(['url', 'user']);
    }

}

