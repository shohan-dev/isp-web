<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppOptIn extends Model
{
    protected $table         = 'whatsapp_opt_ins';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $allowedFields = [
        'admin_user_id',
        'wa_phone',
        'marketing_opt_in',
        'opted_in_at',
        'opted_out_at',
        'source',
    ];

    public function isOptedIn(int $adminUserId, string $waPhone): bool
    {
        $row = $this->where([
            'admin_user_id' => $adminUserId,
            'wa_phone'      => $waPhone,
            'marketing_opt_in' => 1,
        ])->first();

        return $row !== null;
    }
}
