<?php

namespace Zapi\Modules\Reseller\CustomerPayment\Services;

use Zapi\Modules\Reseller\Core\Services\ResellerBaseService;
use Zapi\Modules\Reseller\CustomerPayment\Services\CustomerPaymentService\CustomerPaymentServicePart01Segment;

/* Extends ResellerBaseService (itself a BaseApiController) purely to reach
   canAccessReseller(): RoleAuthFilter only checks that the JWT role is in the
   allowed set, never that the route's {resellerId} belongs to the caller. */
class CustomerPaymentService extends ResellerBaseService
{

    use CustomerPaymentServicePart01Segment;

    protected $payment_model;
    protected $user_model;

    public function __construct()
    {
        $this->payment_model = model('App\Models\Payment');
        $this->user_model = model('App\Models\User');
        helper(['url', 'user']);
    }


}

