<?php

namespace App\Models;

use CodeIgniter\Model;

class RecycleBinModel extends Model
{
    protected $table         = 'recycle_bin';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'tenant_id',
        'entity',
        'entity_label',
        'source_table',
        'source_id',
        'payload',
        'deleted_by',
        'deleted_by_name',
        'ip_address',
        'created_at',
        'expires_at',
        'restored_at',
    ];

    /**
     * Active (non-restored) bin rows for a tenant, newest first.
     */
    public function getForTenant(int $tenantId, ?string $entity = null, ?string $from = null, ?string $to = null, int $perPage = 25)
    {
        $builder = $this->where('tenant_id', $tenantId)
            ->where('restored_at IS NULL', null, false)
            ->orderBy('created_at', 'DESC');

        if ($entity !== null && $entity !== '') {
            $builder->where('entity', $entity);
        }

        if ($from) {
            $builder->where('DATE(created_at) >=', $from);
        }

        if ($to) {
            $builder->where('DATE(created_at) <=', $to);
        }

        return $builder->paginate($perPage);
    }
}
