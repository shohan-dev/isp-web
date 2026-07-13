<?php

namespace Zapi\Modules\Reseller\Area\Services;

use Zapi\Core\Base\BaseApiController;
use App\Models\Area as AreaModel;
use App\Models\AreaSub as AreaSubModel;
use Zapi\Modules\Reseller\Area\Services\ServiceAreaService\ServiceAreaServicePart01Segment;
use Zapi\Modules\Reseller\Area\Services\ServiceAreaService\ServiceAreaServicePart02Segment;

/**
 * Reseller service areas — mirrors web {@see \App\Controllers\Area} (fetch/create/sub/update/delete).
 */
class ServiceAreaService extends BaseApiController
{

    use ServiceAreaServicePart01Segment;
    use ServiceAreaServicePart02Segment;

    protected $area_model;
    protected $subarea_model;

    public function __construct()
    {
        $this->area_model = model(AreaModel::class);
        $this->subarea_model = model(AreaSubModel::class);
        helper(['url', 'user']);
    }


}

