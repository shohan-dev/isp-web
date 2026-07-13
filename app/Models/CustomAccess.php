<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomAccess extends Model
{
    protected $table            = 'custom_access';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'admin_id',
        'user_id',
        'permissions',
        'status'
    ];

    // Phase 2 (C1): a custom-access change invalidates the L2 permission cache so
    // the affected user's access updates immediately (not after the 30s TTL).
    protected $allowCallbacks = true;
    protected $afterInsert    = ['bustPermissionCache'];
    protected $afterUpdate    = ['bustPermissionCache'];
    protected $afterDelete    = ['bustPermissionCache'];

    protected function bustPermissionCache(array $eventData): array
    {
        helper('user');
        if (function_exists('bumpPermissionCacheVersion')) {
            bumpPermissionCacheVersion();
        }

        return $eventData;
    }
}
