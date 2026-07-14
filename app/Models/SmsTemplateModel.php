<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsTemplateModel extends Model
{
    protected $table = 'sms_templates';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id','template_name', 'message_body', 'template_type', 'created_at'];
}
