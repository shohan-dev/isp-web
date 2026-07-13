<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Immutable ledger for users.fund mutations (Item 7).
 * Guarded so it is safe to (re-)run.
 */
class CreateFundTransactions extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('fund_transactions')) {
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
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'balance_after' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('reference');

        $this->forge->createTable('fund_transactions', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('fund_transactions', true);
    }
}
