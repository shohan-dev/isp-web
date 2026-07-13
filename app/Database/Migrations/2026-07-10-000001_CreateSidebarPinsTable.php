<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-user sidebar quick-access pins. Guarded so it is safe to (re-)run.
 */
class CreateSidebarPinsTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('sidebar_pins')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'pin_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
            ],
            'href' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey(['user_id', 'pin_key']);

        $this->forge->createTable('sidebar_pins', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sidebar_pins', true);
    }
}
