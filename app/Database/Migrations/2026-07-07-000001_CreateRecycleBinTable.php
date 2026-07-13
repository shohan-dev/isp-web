<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Central recycle_bin table for soft-delete snapshots (Item 6).
 * Guarded so it is safe to (re-)run.
 */
class CreateRecycleBinTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('recycle_bin')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'entity' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'entity_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'source_table' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'source_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'payload' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'deleted_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'deleted_by_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'restored_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('entity');
        $this->forge->addKey('expires_at');
        $this->forge->addKey(['source_table', 'source_id']);

        $this->forge->createTable('recycle_bin', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('recycle_bin', true);
    }
}
