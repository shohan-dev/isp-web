<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * product_showcase_categories — super-admin-managed website/mobile screenshot
 * gallery categories for the public landing page product showcase. Guarded so
 * it is safe to (re-)run.
 */
class CreateProductShowcaseCategoriesTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('product_showcase_categories')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'target' => [
                'type'       => 'ENUM',
                'constraint' => ['website', 'mobile'],
            ],
            'bullets' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');

        $this->forge->createTable('product_showcase_categories', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('product_showcase_categories', true);
    }
}
