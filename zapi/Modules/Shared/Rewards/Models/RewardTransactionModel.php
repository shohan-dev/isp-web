<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * Append-only points ledger. The heart of the system.
 *
 *  - points: SIGNED (+earn / -spend / -expire)
 *  - idempotency_key: UNIQUE — makes every award/spend exactly-once
 *  - remaining: for EARN lots, the unconsumed amount (FIFO expiry); 0 for spends
 *  - balance_after: wallet balance immediately after this row (audit trail)
 *
 * Only `remaining` is ever mutated after insert (a consumption pointer);
 * `points` and `balance_after` are immutable.
 */
class RewardTransactionModel extends Model
{
    protected $table         = 'reward_transactions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'owner_id',
        'points',
        'balance_after',
        'source',
        'ref_type',
        'ref_id',
        'idempotency_key',
        'status',
        'expires_at',
        'remaining',
        'note',
        'created_at',
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
            'points'          => ['type' => 'INT', 'constraint' => 11],
            'balance_after'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'source'          => ['type' => 'VARCHAR', 'constraint' => 40],
            'ref_type'        => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'ref_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 120],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'posted'],
            'expires_at'      => ['type' => 'DATE', 'null' => true],
            'remaining'       => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'note'            => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('idempotency_key');
        $forge->addKey(['user_id', 'created_at']);
        $forge->addKey(['user_id', 'remaining']);
        $forge->addKey(['expires_at', 'remaining']);
        $forge->addKey(['ref_type', 'ref_id']);
        $forge->createTable($this->table, true);
    }

    public function sumPosted(int $userId): int
    {
        $row = $this->selectSum('points')
            ->where('user_id', $userId)
            ->where('status', 'posted')
            ->first();
        return (int) ($row->points ?? 0);
    }
}
