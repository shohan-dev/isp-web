<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * product_showcase_images — screenshots belonging to a product_showcase_categories
 * row. category_id is a soft reference only (no hard FK constraint), matching
 * how sidebar_pins.user_id and similar lookup relationships are modeled in
 * this codebase. Guarded so it is safe to (re-)run.
 */
class CreateProductShowcaseImagesTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('product_showcase_images')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'category_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'image_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'caption' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('category_id');

        $this->forge->createTable('product_showcase_images', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('product_showcase_images', true);
    }
}
