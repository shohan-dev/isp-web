<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppEntitlement extends Model
{
    protected $table         = 'whatsapp_entitlements';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'admin_user_id',
        'enabled',
        'granted_by',
        'granted_at',
        'updated_at',
    ];

    public function findByAdminUserId(int $adminUserId): ?object
    {
        return $this->where('admin_user_id', $adminUserId)->first();
    }

    public function isEnabledFor(int $adminUserId): bool
    {
        $row = $this->findByAdminUserId($adminUserId);

        return $row !== null && (int) ($row->enabled ?? 0) === 1;
    }

    /**
     * Upsert entitlement for a Second Admin.
     */
    public function setEnabled(int $adminUserId, bool $enabled, ?int $grantedBy = null): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->findByAdminUserId($adminUserId);
        $data = [
            'admin_user_id' => $adminUserId,
            'enabled'       => $enabled ? 1 : 0,
            'granted_by'    => $grantedBy,
            'updated_at'    => $now,
        ];
        if ($enabled) {
            $data['granted_at'] = $now;
        }

        if ($existing) {
            $this->update($existing->id, $data);
        } else {
            if (!$enabled) {
                $data['granted_at'] = null;
            }
            $this->insert($data);
        }
    }

    /**
     * @return array<int,object> keyed by admin_user_id
     */
    public function mapByAdminUserId(): array
    {
        $rows = $this->findAll();
        $map  = [];
        foreach ($rows as $row) {
            $map[(int) $row->admin_user_id] = $row;
        }

        return $map;
    }
}
