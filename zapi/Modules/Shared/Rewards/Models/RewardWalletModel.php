<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * Denormalized per-customer points balance cache.
 *
 *   available balance = balance - held
 *
 * The reward_transactions ledger is the source of truth; this row is kept in
 * sync inside the same DB transaction as every ledger write (see RewardEngine)
 * and is fully reconstructable via RewardEngine::reconcileWallet().
 */
class RewardWalletModel extends Model
{
    protected $table         = 'reward_wallets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'owner_id',
        'balance',
        'lifetime_earned',
        'lifetime_spent',
        'held',
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
            'balance'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'lifetime_earned' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'lifetime_spent'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'held'            => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('user_id');
        $forge->addKey('owner_id');
        $forge->createTable($this->table, true);
    }

    /**
     * Fetch the wallet row, creating an empty one if absent.
     */
    public function getOrCreate(int $userId, int $ownerId = 0)
    {
        $row = $this->where('user_id', $userId)->first();
        if ($row) {
            return $row;
        }
        $this->insert([
            'user_id'    => $userId,
            'owner_id'   => $ownerId,
            'balance'    => 0,
            'held'       => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->where('user_id', $userId)->first();
    }
}
