<?php

namespace Zapi\Modules\Reseller\Router\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Router\Services\RouterService\RouterServicePart01Segment;

class RouterService extends BaseApiController
{

    use RouterServicePart01Segment;

    protected $user_model;
    protected $router_model;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
        $this->router_model = model('App\Models\Router');
        helper(['url', 'user', 'router']);
    }


}

