<?php

namespace Zapi\Modules\Customer\Core\Services\CustomerService;

use App\Models\User;

trait CustomerServicePart01Segment
{
        public function profile(int $userId): ?array
        {
            $userModel = model(User::class);
            $record = $userModel->where('id', $userId)->first();
    
            if (!is_array($record)) {
                return null;
            }
    
            return [
                'id' => (int) $record['id'],
                'name' => $record['name'] ?? '',
                'email' => $record['email'] ?? '',
                'mobile' => $record['mobile'] ?? '',
                'status' => $record['status'] ?? null,
            ];
        }
    
}
