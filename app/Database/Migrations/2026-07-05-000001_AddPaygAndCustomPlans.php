<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pay-As-You-Go + custom plan support.
 *
 * NOTE: the AdminPackage / TenantWallet / WalletTransaction / Registration
 * models also apply this DDL at runtime (repo convention), so live databases
 * that never run `spark migrate` still converge. Every step here is additive
 * and guarded, so running both paths is safe.
 */
class AddPaygAndCustomPlans extends Migration
{
    public function up()
    {
        // --- admin_packages: plan-type discriminator + PAYG rate card ---
        $packageColumns = [
            'plan_type'        => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => false, 'default' => 'fixed', 'after' => 'pricing_type'],
            'base_fee'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => 0, 'after' => 'price'],
            'per_user_rate'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => 0, 'after' => 'base_fee'],
            'min_topup'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => 0, 'after' => 'per_user_rate'],
            'trial_days'       => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => 0, 'after' => 'min_topup'],
            'addons'           => ['type' => 'TEXT', 'null' => true, 'after' => 'features'],
            'assigned_user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'addons'],
            'is_public'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => 1, 'after' => 'assigned_user_id'],
            'sort_order'       => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => 0, 'after' => 'is_public'],
        ];

        foreach ($packageColumns as $name => $definition) {
            if (!$this->db->fieldExists($name, 'admin_packages')) {
                $this->forge->addColumn('admin_packages', [$name => $definition]);
            }
        }

        // --- registrations: what plan the registrant asked for ---
        if ($this->db->tableExists('registrations')) {
            if (!$this->db->fieldExists('requested_plan', 'registrations')) {
                $this->forge->addColumn('registrations', [
                    'requested_plan' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
                ]);
            }
            if (!$this->db->fieldExists('plan_note', 'registrations')) {
                $this->forge->addColumn('registrations', [
                    'plan_note' => ['type' => 'TEXT', 'null' => true],
                ]);
            }
        }

        // --- tenant_wallets ---
        if (!$this->db->tableExists('tenant_wallets')) {
            $this->forge->addField([
                'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'                 => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'balance'                 => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
                'addons'                  => ['type' => 'TEXT', 'null' => true],
                'grace_until'             => ['type' => 'DATETIME', 'null' => true],
                'low_balance_notified_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at'              => ['type' => 'DATETIME', 'null' => true],
                'updated_at'              => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('user_id');
            $this->forge->createTable('tenant_wallets', true);
        }

        // --- wallet_transactions (ledger) ---
        if (!$this->db->tableExists('wallet_transactions')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'wallet_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'user_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'type'          => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => false],
                'amount'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
                'balance_after' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
                'reference'     => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
                'description'   => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
                'created_by'    => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->addKey('reference');
            $this->forge->createTable('wallet_transactions', true);
        }
    }

    public function down()
    {
        // Additive-only migration: leave data in place on rollback.
    }
}
