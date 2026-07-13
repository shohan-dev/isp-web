<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * Checkout redemption hold -> apply/release lifecycle, tied to a pending
 * payment id. Holds reserve points (wallet.held) without spending them; the
 * spend ledger row is only written once the tied payment settles successfully.
 */
class RewardRedemptionModel extends Model
{
    protected $table         = 'reward_redemptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'owner_id',
        'payment_id',
        'points',
        'status',
        'hold_expires_at',
        'applied_txn_id',
        'expense_id',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    public function ensureTableExists(): void
    {
        $db = \Config\Database::connect();
        if ($db->tableExists($this->table)) {
            return;
        }

        $forge = \Config\Database::forge();
        $forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'INT', 'constraint' => 11],
            'owner_id'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'payment_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'points'          => ['type' => 'INT', 'constraint' => 11],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'held'],
            'hold_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'applied_txn_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'expense_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('payment_id');
        $forge->addKey(['status', 'hold_expires_at']);
        $forge->addKey('user_id');
        $forge->createTable($this->table, true);
    }

    public function findActiveHoldForPayment(int $paymentId)
    {
        return $this->where('payment_id', $paymentId)
            ->where('status', 'held')
            ->first();
    }
}
