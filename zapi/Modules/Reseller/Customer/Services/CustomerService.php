<?php

namespace Zapi\Modules\Reseller\Customer\Services;

use Zapi\Core\Base\BaseApiController;
use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Zapi\Modules\Reseller\Customer\Services\CustomerService\CustomerServicePart01Segment;
use Zapi\Modules\Reseller\Customer\Services\CustomerService\CustomerServicePart02Segment;
use Zapi\Modules\Reseller\Customer\Services\CustomerService\CustomerServicePart03Segment;
use Zapi\Modules\Reseller\Customer\Services\CustomerService\CustomerServicePart04Segment;
use Zapi\Modules\Reseller\Customer\Services\CustomerService\CustomerServicePart05Segment;
use Zapi\Modules\Reseller\Customer\Services\CustomerService\CustomerServicePart06Segment;
use Zapi\Modules\Reseller\Customer\Services\CustomerService\CustomerServicePart07Segment;
use Zapi\Modules\Reseller\Customer\Services\CustomerService\CustomerServicePart08Segment;

/* Extends ResellerBaseService to reach canAccessReseller() — RoleAuthFilter only
   checks the JWT role, never that the route's {resellerId} is the caller's. */
class CustomerService extends \Zapi\Modules\Reseller\Core\Services\ResellerBaseService
{

    use CustomerServicePart01Segment;
    use CustomerServicePart02Segment;
    use CustomerServicePart03Segment;
    use CustomerServicePart04Segment;
    use CustomerServicePart05Segment;
    use CustomerServicePart06Segment;
    use CustomerServicePart07Segment;
    use CustomerServicePart08Segment;

    protected $user_model;
    protected $userRouterDataModel;
    protected $db;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
        $this->userRouterDataModel = model('App\Models\UserRouterDataModel');
        $this->db = Database::connect();
        helper(['url', 'user', 'router']);
    }


}

