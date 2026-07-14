<?php

namespace App\Models;

use CodeIgniter\Model;

class Package extends Model
{
    protected $table            = 'packages';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'package_name',
        'bandwidth',
        'price',
        'pricing_type',
        'status',
        'visibility',
    ];
}
