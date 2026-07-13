<?php

namespace App\Models;

use CodeIgniter\Model;

class UserBindingModel extends Model
{
    protected $table = 'user_bindings';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'admin_id',
        'user_name',
        'mac_address',
        'ip_address',
        'binding_type',
    ];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    // Auto-create table if not exists
    public function ensureTableExists()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            $forge = \Config\Database::forge();

            $fields = [
                'id' => [
                    'type' => 'INT',
                    'auto_increment' => true,
                ],
                'admin_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'user_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                ],
                'mac_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'binding_type' => [
                    'type'       => 'ENUM',
                    'constraint' => ['regular', 'blocked', 'bypassed'],
                    'default'    => 'regular',
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                ],
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->addUniqueKey(['user_name', 'mac_address']); // Enforces unique MAC per user
            $forge->createTable($this->table, true);
        }
    }

    // Insert or update binding data
    public function saveBinding(int $adminId, string $userName, string $mac, ?string $ip = null, string $type = 'regular'): bool
    {
        $existing = $this->where('user_name', $userName)
            ->where('mac_address', $mac)
            ->first();

        $data = [
            'admin_id'     => $adminId,
            'user_name'    => $userName,
            'mac_address'  => $mac,
            'ip_address'   => $ip,
            'binding_type' => $type,
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        return $this->insert($data) !== false;
    }

    // Delete binding by MAC or ID
    public function deleteBinding($idOrMac): bool
    {
        if (is_numeric($idOrMac)) {
            return $this->delete($idOrMac);
        }
        return $this->where('mac_address', $idOrMac)->delete();
    }

    // Get bindings for a user
    public function getUserBindings(string $userName): array
    {
        return $this->where('user_name', $userName)
            ->findAll();
    }
}
