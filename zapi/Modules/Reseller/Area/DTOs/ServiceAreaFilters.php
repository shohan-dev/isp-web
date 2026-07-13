<?php

namespace Zapi\Modules\Reseller\Area\DTOs;

class ServiceAreaFilters
{
    public ?int $resellerId = null;
    public ?string $search = null;
    public int $page = 1;
    public int $perPage = 10;
}
