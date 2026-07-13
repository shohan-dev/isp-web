<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Immutable ledger of tenant wallet mutations. `reference` doubles as an
 * idempotency key (e.g. "payment:123", "cycle:55:2026-07-05") — WalletService
 * refuses to apply the same reference twice.
 */
class WalletTransaction extends Model
{
    protected $table = 'wallet_transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public const TYPE_CREDIT     = 'credit';
    public const TYPE_DEBIT      = 'debit';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected function initialize()
    {
        // Phase-E1: once per FPM worker process
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
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'wallet_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
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
                'constraint' => '100',
                'null' => true,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('user_id');
        $forge->addKey('reference');
        $forge->createTable($this->table, true);
    }

    protected $allowedFields = [
        'wallet_id',
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
