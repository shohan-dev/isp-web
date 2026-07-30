<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppCampaignRecipient extends Model
{
    protected $table         = 'whatsapp_campaign_recipients';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $allowedFields = [
        'campaign_id',
        'wa_phone',
        'status',
        'wamid',
        'error',
    ];
}
