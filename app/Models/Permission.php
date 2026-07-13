<?php

namespace App\Models;

use CodeIgniter\Model;

class Permission extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'user_type',
        'permissions'
    ];

    // Phase 2 (C1): any permission write invalidates the L2 permission cache so a
    // grant/revoke takes effect immediately (rather than after the 30s TTL).
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
