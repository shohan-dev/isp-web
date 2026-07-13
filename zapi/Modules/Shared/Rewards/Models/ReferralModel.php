<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * The referral lead + verification lifecycle.
 * One row per signup attempt that used a referral code.
 */
class ReferralModel extends Model
{
    protected $table         = 'referrals';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'referrer_id',
        'referee_id',
        'owner_id',
        'referral_code',
        'status',
        'fraud_reason',
        'referee_name',
        'referee_mobile',
        'referee_email',
        'referee_nid',
        'package_id',
        'points_awarded',
        'verified_at',
        'verified_by',
        'reject_reason',
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
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'referrer_id'    => ['type' => 'INT', 'constraint' => 11],
            'referee_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'owner_id'       => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'referral_code'  => ['type' => 'VARCHAR', 'constraint' => 16],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'pending'],
            'fraud_reason'   => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'referee_name'   => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'referee_mobile' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'referee_email'  => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'referee_nid'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'package_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'points_awarded' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'verified_at'    => ['type' => 'DATETIME', 'null' => true],
            'verified_by'    => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'reject_reason'  => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('referrer_id');
        $forge->addKey(['owner_id', 'status']);
        $forge->addUniqueKey('referee_id');
        $forge->addKey('referee_mobile');
        $forge->addKey('referee_nid');
        $forge->createTable($this->table, true);
    }

    public function countByStatus(int $referrerId): array
    {
        $rows = $this->select('status, COUNT(*) as cnt')
            ->where('referrer_id', $referrerId)
            ->groupBy('status')
            ->findAll();

        $out = ['total' => 0, 'pending' => 0, 'verified' => 0, 'rejected' => 0, 'flagged' => 0];
        foreach ($rows as $r) {
            $status = is_object($r) ? $r->status : $r['status'];
            $cnt = (int) (is_object($r) ? $r->cnt : $r['cnt']);
            if (isset($out[$status])) {
                $out[$status] += $cnt;
            }
            $out['total'] += $cnt;
        }
        return $out;
    }
}
