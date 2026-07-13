<?php

namespace Zapi\Modules\Customer\User\Repositories;

class UserRepository
{
    public function model()
    {
        return model('App\Models\User');
    }
}
