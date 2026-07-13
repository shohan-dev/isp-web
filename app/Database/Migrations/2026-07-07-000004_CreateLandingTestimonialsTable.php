<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * landing_testimonials — admin-editable quotes for the marketing homepage.
 * Seeded with zero rows; the section auto-hides until content is added.
 */
class CreateLandingTestimonialsTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('landing_testimonials')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'company' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'quote' => [
                'type' => 'TEXT',
            ],
            'avatar_initials' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'null'       => true,
            ],
            'rating' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 5,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'sort_order']);
        $this->forge->createTable('landing_testimonials', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('landing_testimonials', true);
    }
}
