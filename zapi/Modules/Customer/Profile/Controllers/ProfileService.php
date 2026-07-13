<?php

namespace Zapi\Modules\Customer\Profile\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class ProfileService extends CustomerBaseService
{
    public function update()
    {
        $userId = $this->getInputValue('user_id');
        $data = [
            'name' => $this->getInputValue('name'),
            'mobile' => $this->getInputValue('mobile'),
            'email' => $this->getInputValue('email'),
            'address' => $this->getInputValue('address'),
        ];

        if ($this->user_model->update($userId, $data)) {
            return $this->respondSuccess(['updated' => true]);
        }

        return $this->respondError('Something went wrong! Please try again', 500, 'REQUEST_FAILED');
    }
}

