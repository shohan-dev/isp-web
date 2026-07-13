<?php

namespace Zapi\Modules\Reseller\Customer\DTOs;

class CustomerFilters
{
    public ?int $resellerId = null;
    public ?string $search = null;
    public ?string $status = null;
    public int $page = 1;
    public int $perPage = 10;
}
