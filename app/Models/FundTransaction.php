<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Immutable ledger of users.fund mutations. `reference` doubles as an
 * idempotency key (e.g. "payment:123", "bkashsms:ABC123") — FundService
 * refuses to apply the same reference twice.
 */
class FundTransaction extends Model
{
    protected $table = 'fund_transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public const TYPE_CREDIT     = 'credit';
    public const TYPE_DEBIT      = 'debit';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected function initialize()
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        if ($db->tableExists($this->table)) {
            return;
        }

        $forge = \Config\Database::forge();
        $forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INTEGER',
                'null' => false,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => false,
            ],
            'balance_after' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => false,
            ],
            'reference' => [
                'type' => 'VARCHAR',
                'constraint' => '191',
                'null' => true,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'INTEGER',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('user_id');
        $forge->addUniqueKey('reference');
        $forge->createTable($this->table, true);
    }

    protected $allowedFields = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'description',
        'created_by',
        'created_at',
    ];
}
