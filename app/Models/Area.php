<?php

namespace App\Models;

use CodeIgniter\Model;

class Area extends Model
{
    protected $table            = 'areas';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'area_name',
        'area_code',
        'status',
    ];
}
