<?php

namespace Zapi\Modules\Customer\User\DTOs;

class UserFilters
{
    public ?int $customerId = null;
    public int $page = 1;
    public int $perPage = 10;
}
