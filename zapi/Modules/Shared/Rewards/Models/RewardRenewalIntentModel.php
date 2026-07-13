<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * Snapshot captured at renew() time so the (post-hoc) reconciler can correctly
 * award early-renewal (+2) and package-upgrade (+5) rewards even after the
 * gateway overwrites users.will_expire / users.package_id on success.
 */
class RewardRenewalIntentModel extends Model
{
    protected $table         = 'reward_renewal_intent';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'payment_id',
        'old_package_id',
        'new_package_id',
        'old_will_expire',
        'days_before_expiry',
        'is_upgrade',
        'new_package_price',
        'captured_at',
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
            'id'                 => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'            => ['type' => 'INT', 'constraint' => 11],
            'payment_id'         => ['type' => 'BIGINT', 'constraint' => 20],
            'old_package_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'new_package_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'old_will_expire'    => ['type' => 'DATETIME', 'null' => true],
            'days_before_expiry' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'is_upgrade'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'new_package_price'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'captured_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('payment_id');
        $forge->addKey('user_id');
        $forge->createTable($this->table, true);
    }

    public function captureOnce(array $data): void
    {
        $paymentId = (int) ($data['payment_id'] ?? 0);
        if ($paymentId <= 0) {
            return;
        }
        if ($this->where('payment_id', $paymentId)->first()) {
            return; // idempotent capture
        }
        $data['captured_at'] = $data['captured_at'] ?? date('Y-m-d H:i:s');
        $this->insert($data);
    }
}
