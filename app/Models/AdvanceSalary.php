<?php

namespace App\Models;

use CodeIgniter\Model;

class AdvanceSalary extends Model
{
    protected $table            = 'advance_salary';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'employee_id',
        'amount',
        'reason',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
