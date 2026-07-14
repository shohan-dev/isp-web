<?php

namespace App\Models;

use CodeIgniter\Model;

class Ticket extends Model
{
    protected $table            = 'tickets';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'admin_ids',
        'transfer',
        'subject',
        'category',
        'priority',
        'details',
        'datetime',
        'remarks',
        'viewed',
        'status',
    ];
}
