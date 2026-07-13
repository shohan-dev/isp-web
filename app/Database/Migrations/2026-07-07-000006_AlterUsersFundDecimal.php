<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Widen users.fund to DECIMAL(12,2) so paise are not truncated (Item 7).
 * Guarded: skips when the column is already DECIMAL or the table is missing.
 */
class AlterUsersFundDecimal extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('users')) {
            return;
        }

        $row = $this->db->query("SHOW COLUMNS FROM `users` LIKE 'fund'")->getRow();
        if ($row !== null && stripos((string) ($row->Type ?? ''), 'decimal') !== false) {
            return;
        }

        $this->db->query(
            'ALTER TABLE `users` MODIFY COLUMN `fund` DECIMAL(12,2) NOT NULL DEFAULT 0.00'
        );
    }

    public function down(): void
    {
        if (! $this->db->tableExists('users')) {
            return;
        }

        $row = $this->db->query("SHOW COLUMNS FROM `users` LIKE 'fund'")->getRow();
        if ($row === null) {
            return;
        }

        // Best-effort revert; fractional balances would be truncated.
        $this->db->query(
            'ALTER TABLE `users` MODIFY COLUMN `fund` INT NOT NULL DEFAULT 0'
        );
    }
}
