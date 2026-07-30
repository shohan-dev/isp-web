<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppTemplate extends Model
{
    protected $table         = 'whatsapp_templates';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $allowedFields = [
        'admin_user_id',
        'meta_template_id',
        'name',
        'language',
        'category',
        'status',
        'event_key',
        'body_preview',
        'components_json',
        'is_enabled',
    ];

    public function findEnabledForEvent(int $adminUserId, string $eventKey): ?object
    {
        return $this->where([
            'admin_user_id' => $adminUserId,
            'event_key'     => $eventKey,
            'is_enabled'    => 1,
            'status'        => 'APPROVED',
        ])->first();
    }
}
