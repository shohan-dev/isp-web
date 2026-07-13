<?php

namespace Zapi\Modules\Reseller\EmployeePayment\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\EmployeePayment\Services\EmployeePaymentService\EmployeePaymentServicePart01Segment;

class EmployeePaymentService extends BaseApiController
{

    use EmployeePaymentServicePart01Segment;

    protected $payment_model;
    protected $user_model;

    public function __construct()
    {
        $this->payment_model = model('App\Models\Payment');
        $this->user_model = model('App\Models\User');
        helper(['url', 'user']);
    }


}

