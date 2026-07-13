<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Backfill referral read/update on existing sAdmin and resellerAdmin default
 * permission rows. Employees are intentionally excluded.
 *
 * Runs BEFORE RenameRoleValues in this batch, so permissions.user_type still
 * holds the legacy 'sAdmin' label at this point, not the renamed 'admin'.
 */
class Add_referral_permission_default extends Migration
{
    /** @var list<string> */
    private const BACKFILL_ROLES = ['sAdmin', 'resellerAdmin'];

  public function up()
    {
        if (!$this->db->tableExists('permissions')) {
            return;
        }

        $model = model('App\Models\Permission');
        $rows = $model->whereIn('user_type', self::BACKFILL_ROLES)->findAll();

        foreach ($rows as $row) {
            $perms = json_decode((string) ($row->permissions ?? ''), true);
            if (!is_array($perms)) {
                $perms = [];
            }
            if (isset($perms['referral'])) {
                continue;
            }

            $perms['referral'] = ['read', 'update'];
            $model->update($row->id, ['permissions' => json_encode($perms)]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('permissions')) {
            return;
        }

        $model = model('App\Models\Permission');
        $rows = $model->whereIn('user_type', self::BACKFILL_ROLES)->findAll();

        foreach ($rows as $row) {
            $perms = json_decode((string) ($row->permissions ?? ''), true);
            if (!is_array($perms) || !isset($perms['referral'])) {
                continue;
            }

            unset($perms['referral']);
            $model->update($row->id, ['permissions' => json_encode($perms)]);
        }
    }
}
