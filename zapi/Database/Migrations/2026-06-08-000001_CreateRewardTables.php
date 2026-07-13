<?php

namespace Zapi\Database\Migrations;

use CodeIgniter\Database\Migration;
use Zapi\Modules\Shared\Rewards\Services\RewardConfigService;

/**
 * Referral & Reward Point system schema.
 *
 * NOTE: every reward model also self-creates its table at runtime
 * (ensureTableExists(), matching the AuditLogModel convention), so the system
 * works even if migrations are never run. This migration exists for teams that
 * deploy via `php spark migrate --all` and want a clean, versioned schema.
 */
class CreateRewardTables extends Migration
{
    public function up()
    {
        // referral_codes
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 16],
            'owner_id'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addKey('owner_id');
        $this->forge->createTable('referral_codes', true);

        // referrals
        $this->forge->addField([
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
        $this->forge->addKey('id', true);
        $this->forge->addKey('referrer_id');
        $this->forge->addKey(['owner_id', 'status']);
        $this->forge->addUniqueKey('referee_id');
        $this->forge->addKey('referee_mobile');
        $this->forge->addKey('referee_nid');
        $this->forge->createTable('referrals', true);

        // reward_wallets
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'INT', 'constraint' => 11],
            'owner_id'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'balance'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'lifetime_earned' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'lifetime_spent'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'held'            => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->addKey('owner_id');
        $this->forge->createTable('reward_wallets', true);

        // reward_transactions
        $this->forge->addField([
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
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('idempotency_key');
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addKey(['user_id', 'remaining']);
        $this->forge->addKey(['expires_at', 'remaining']);
        $this->forge->addKey(['ref_type', 'ref_id']);
        $this->forge->createTable('reward_transactions', true);

        // reward_redemptions
        $this->forge->addField([
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
        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_id');
        $this->forge->addKey(['status', 'hold_expires_at']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('reward_redemptions', true);

        // reward_renewal_intent
        $this->forge->addField([
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
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('payment_id');
        $this->forge->addKey('user_id');
        $this->forge->createTable('reward_renewal_intent', true);

        // reward_settings
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'owner_id'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'key_name'   => ['type' => 'VARCHAR', 'constraint' => 64],
            'value'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['owner_id', 'key_name']);
        $this->forge->createTable('reward_settings', true);

        // app_notifications
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'owner_id'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'system'],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'body'       => ['type' => 'TEXT', 'null' => true],
            'ref_type'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'ref_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'action_url' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'is_read'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'read_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'is_read', 'created_at']);
        $this->forge->createTable('app_notifications', true);

        // reward_event_log
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'period_key' => ['type' => 'VARCHAR', 'constraint' => 30],
            'ref_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'event_type', 'period_key']);
        $this->forge->createTable('reward_event_log', true);

        // Seed global (owner_id = 0) defaults.
        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach (RewardConfigService::SPEC_DEFAULTS as $key => $value) {
            $rows[] = ['owner_id' => 0, 'key_name' => $key, 'value' => (string) $value, 'updated_at' => $now];
        }
        if (!empty($rows)) {
            $this->db->table('reward_settings')->ignore(true)->insertBatch($rows);
        }
    }

    public function down()
    {
        foreach ([
            'reward_event_log',
            'app_notifications',
            'reward_settings',
            'reward_renewal_intent',
            'reward_redemptions',
            'reward_transactions',
            'reward_wallets',
            'referrals',
            'referral_codes',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
