<?php

namespace App\Models;

use CodeIgniter\Model;

class Router extends Model
{
    protected $table            = 'routers';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'name',
        'host',
        'username',
        'password',
        'port',
        'status',
        'hotspot_name',
        'dns_name',
        'currency',
    ];
}
