<?php

namespace App\Models;

use CodeIgniter\Model;

class allResellerPackage extends Model
{
    protected $table = 'all_reseller_packages';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'package_details'];

    // Optionally, define getters and setters to handle JSON data
    protected $casts = [
        'package_details' => 'json'
    ];
}
