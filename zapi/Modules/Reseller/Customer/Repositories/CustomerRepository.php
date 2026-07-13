<?php

namespace Zapi\Modules\Reseller\Customer\Repositories;

use Config\Database;

class CustomerRepository
{
    public function db()
    {
        return Database::connect();
    }
}
