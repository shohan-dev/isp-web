<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeLocation extends Model
{
    protected $table            = 'employee_locations';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'employee_id',
        'latitude',
        'longitude',
        'address',
        'created_at',
    ];
}
