<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Platform Super Admin grants WhatsApp Business to selected Second Admins.
 */
class CreateWhatsAppEntitlements extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('whatsapp_entitlements')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'admin_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'granted_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'granted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('admin_user_id');
        $this->forge->createTable('whatsapp_entitlements', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('whatsapp_entitlements')) {
            $this->forge->dropTable('whatsapp_entitlements', true);
        }
    }
}
