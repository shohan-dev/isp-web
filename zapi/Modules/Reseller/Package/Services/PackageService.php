<?php

namespace Zapi\Modules\Reseller\Package\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Package\Services\PackageService\PackageServicePart01Segment;

class PackageService extends BaseApiController
{

    use PackageServicePart01Segment;

    protected $package_model;

    public function __construct()
    {
        $this->package_model = model('App\Models\Package');
        helper(['url', 'user']);
    }


}

