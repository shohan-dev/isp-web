<?php

namespace App\Models;

use CodeIgniter\Model;

class ResetRequest extends Model
{
    protected $table            = 'password_reset_requests';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'code',
        'valid_till',
        'status'
    ];
}
