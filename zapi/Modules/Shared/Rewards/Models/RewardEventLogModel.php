<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * Dedupe / cap guard for non-payment-derived reward events that can't be
 * inferred from the payments table alone:
 *   - feedback   (monthly cap)  period_key = 'YYYY-MM'
 *   - autopay    (one-time)     period_key = 'once'
 *   - birthday   (annual)       period_key = 'YYYY'
 *   - ticket_rating             period_key = 'ticket:{id}'
 *
 * The UNIQUE (user_id, event_type, period_key) is the cap enforcement.
 */
class RewardEventLogModel extends Model
{
    protected $table         = 'reward_event_log';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'event_type',
        'period_key',
        'ref_id',
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
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'period_key' => ['type' => 'VARCHAR', 'constraint' => 30],
            'ref_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey(['user_id', 'event_type', 'period_key']);
        $forge->createTable($this->table, true);
    }

    /**
     * Atomically claim an event slot. Returns true if THIS call won the slot
     * (caller should award), false if it was already claimed (cap reached).
     */
    public function claim(int $userId, string $eventType, string $periodKey, ?int $refId = null): bool
    {
        $builder = $this->db->table($this->table);
        // INSERT IGNORE — relies on the unique key. affectedRows==1 means we won.
        $builder->ignore(true)->insert([
            'user_id'    => $userId,
            'event_type' => $eventType,
            'period_key' => $periodKey,
            'ref_id'     => $refId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affectedRows() > 0;
    }
}
