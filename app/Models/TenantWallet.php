<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Platform wallet for tenant ISP owners (role sAdmin) on the Pay-As-You-Go
 * plan. Distinct from users.fund, which belongs to the reseller fund feature.
 * All balance mutations must go through App\Services\WalletService so every
 * change lands in the wallet_transactions ledger.
 */
class TenantWallet extends Model
{
    protected $table = 'tenant_wallets';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

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
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'balance' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => false,
                'default' => 0,
            ],
            'addons' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'grace_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'low_balance_notified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('user_id');
        $forge->createTable($this->table, true);
    }

    protected $allowedFields = [
        'user_id',
        'balance',
        'addons',
        'grace_until',
        'low_balance_notified_at',
    ];

    protected $useTimestamps = true;

    /**
     * Addon keys the tenant has enabled, e.g. ['sms', 'whitelabel'].
     *
     * @param object|array|null $wallet
     */
    public static function chosenAddons($wallet): array
    {
        if (empty($wallet)) {
            return [];
        }
        $raw = is_object($wallet) ? ($wallet->addons ?? null) : ($wallet['addons'] ?? null);
        $keys = json_decode((string) $raw, true);

        return is_array($keys) ? array_values(array_filter(array_map('strval', $keys))) : [];
    }
}
