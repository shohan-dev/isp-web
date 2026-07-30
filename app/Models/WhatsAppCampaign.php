<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppCampaign extends Model
{
    protected $table         = 'whatsapp_campaigns';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $allowedFields = [
        'admin_user_id',
        'template_id',
        'name',
        'status',
        'scheduled_at',
        'stats_json',
        'audience_json',
    ];
}
