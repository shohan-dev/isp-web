<?php

namespace Zapi\Modules\Reseller\Area\Repositories;

use App\Models\Area as AreaModel;
use App\Models\AreaSub as AreaSubModel;

class ServiceAreaRepository
{
    protected AreaModel $areaModel;
    protected AreaSubModel $subAreaModel;

    public function __construct()
    {
        $this->areaModel = model(AreaModel::class);
        $this->subAreaModel = model(AreaSubModel::class);
    }

    public function areaModel(): AreaModel
    {
        return $this->areaModel;
    }

    public function subAreaModel(): AreaSubModel
    {
        return $this->subAreaModel;
    }
}
