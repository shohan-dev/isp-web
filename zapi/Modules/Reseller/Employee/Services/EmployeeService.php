<?php

namespace Zapi\Modules\Reseller\Employee\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Employee\Services\EmployeeService\EmployeeServicePart01Segment;

class EmployeeService extends BaseApiController
{

    use EmployeeServicePart01Segment;

    protected $user_model;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
        helper(['url', 'user']);
    }


}

