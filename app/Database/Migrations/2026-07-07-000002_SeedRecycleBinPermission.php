<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Backfill recycle_bin permissions on existing sAdmin and resellerAdmin rows.
 *
 * Runs BEFORE RenameRoleValues in this batch, so permissions.user_type still
 * holds the legacy 'sAdmin' label at this point, not the renamed 'admin'.
 */
class SeedRecycleBinPermission extends Migration
{
    /** @var list<string> */
    private const BACKFILL_ROLES = ['sAdmin', 'resellerAdmin'];

    /** @var list<string> */
    private const PERMS = ['read', 'restore', 'delete_forever', 'empty'];

    public function up(): void
    {
        if (!$this->db->tableExists('permissions')) {
            return;
        }

        $model = model('App\Models\Permission');
        $rows  = $model->whereIn('user_type', self::BACKFILL_ROLES)->findAll();

        foreach ($rows as $row) {
            $perms = json_decode((string) ($row->permissions ?? ''), true);
            if (!is_array($perms)) {
                $perms = [];
            }
            if (isset($perms['recycle_bin'])) {
                continue;
            }

            $perms['recycle_bin'] = self::PERMS;
            $model->update($row->id, ['permissions' => json_encode($perms)]);
        }
    }

    public function down(): void
    {
        if (!$this->db->tableExists('permissions')) {
            return;
        }

        $model = model('App\Models\Permission');
        $rows  = $model->whereIn('user_type', self::BACKFILL_ROLES)->findAll();

        foreach ($rows as $row) {
            $perms = json_decode((string) ($row->permissions ?? ''), true);
            if (!is_array($perms) || !isset($perms['recycle_bin'])) {
                continue;
            }

            unset($perms['recycle_bin']);
            $model->update($row->id, ['permissions' => json_encode($perms)]);
        }
    }
}
