<?php

namespace App\Models;

use CodeIgniter\Model;

class UserDataUsageModel extends Model
{
    protected $table = 'user_data_usage';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'admin_id',
        'user_name',
        'interface',
        'date',
        'rx_mb',
        'tx_mb',
        'rx_today',
        'tx_today',
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
                'interface' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                ],
                'date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'rx_mb' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                ],
                'tx_mb' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                ],
                'rx_today' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                ],
                'tx_today' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                ],
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->addUniqueKey(['user_name', 'date']); // Enforces daily unique entry per user
            $forge->createTable($this->table, true);
        }
    }

    // Insert or update usage data for a user
    public function saveUsage(int $adminId, string $userName, string $interface, string $date, float $rxMb, float $txMb): bool
    {
        // Get previous cumulative data usage
        $prev = $this->where('admin_id', $adminId)
            ->where('date <', $date)
            ->orderBy('date', 'DESC')
            ->first();
        // If no previous data, initialize today's usage to cumulative
        

        $rxToday = $txToday = 0;
        if ($prev) {
            if ($rxMb < $prev['rx_mb'] || $txMb < $prev['tx_mb']) {
                // Router likely rebooted — don't calculate delta
                $rxToday = $rxMb;
                $txToday = $txMb;

            } else {
                $rxToday = $rxMb - $prev['rx_mb'];
                $txToday = $txMb - $prev['tx_mb'];
                
            }
        } else {
            $rxToday = $rxMb;
            $txToday = $txMb;
        }

        $existing = $this->where('user_name', $userName)
            ->where('date', $date)
            ->first();

        $data = [
            'admin_id' => $adminId,
            'user_name' => $userName,
            'interface' => $interface,
            'date' => $date,
            'rx_mb' => $rxMb,
            'tx_mb' => $txMb,
            'rx_today' => $rxToday,
            'tx_today' => $txToday,
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        return $this->insert($data) !== false;
    }

}
