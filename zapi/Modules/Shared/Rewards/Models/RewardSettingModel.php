<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * Key/value reward configuration with two scopes:
 *   owner_id = 0          -> SaaS-wide super-admin default
 *   owner_id = <reseller> -> per-reseller override
 *
 * Resolution (reseller override -> global default -> hardcoded spec default)
 * lives in RewardConfigService; this model is just storage.
 */
class RewardSettingModel extends Model
{
    protected $table         = 'reward_settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'owner_id',
        'key_name',
        'value',
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
            'id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'owner_id'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'key_name'  => ['type' => 'VARCHAR', 'constraint' => 64],
            'value'     => ['type' => 'VARCHAR', 'constraint' => 190],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey(['owner_id', 'key_name']);
        $forge->createTable($this->table, true);
    }

    /**
     * Read a single value for an owner scope, or null if not set.
     */
    public function getValue(int $ownerId, string $key): ?string
    {
        $row = $this->where('owner_id', $ownerId)->where('key_name', $key)->first();
        if (!$row) {
            return null;
        }
        return is_object($row) ? (string) $row->value : (string) $row['value'];
    }

    /**
     * Upsert a single value for an owner scope.
     */
    public function setValue(int $ownerId, string $key, string $value): bool
    {
        $existing = $this->where('owner_id', $ownerId)->where('key_name', $key)->first();
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $id = is_object($existing) ? $existing->id : $existing['id'];
            return (bool) $this->update($id, ['value' => $value, 'updated_at' => $now]);
        }
        return (bool) $this->insert([
            'owner_id'   => $ownerId,
            'key_name'   => $key,
            'value'      => $value,
            'updated_at' => $now,
        ]);
    }
}
