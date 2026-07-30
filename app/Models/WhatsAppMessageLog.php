<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppMessageLog extends Model
{
    protected $table         = 'whatsapp_message_log';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $allowedFields = [
        'admin_user_id',
        'customer_user_id',
        'wa_phone',
        'category',
        'direction',
        'template_name',
        'wamid',
        'status',
        'error_code',
        'billable',
        'payload_redacted',
    ];
}
