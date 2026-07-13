<?php

namespace App\Models;

use CodeIgniter\Model;

class FreeUserRequest extends Model
{
    protected $table = 'free_user_requests';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'reseller_id',
        'admin_id',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
        $this->createTableIfNotExists();
    }

    public function createTableIfNotExists()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            try {
                $forge->addField([
                    'id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'auto_increment' => true,
                    ],
                    'user_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => false,
                    ],
                    'reseller_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => false,
                    ],
                    'admin_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => false,
                    ],
                    'status' => [
                        'type' => 'VARCHAR',
                        'constraint' => 20,
                        'default' => 'pending',
                        'null' => false,
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
                $forge->createTable($this->table, true);
            } catch (\Exception $e) {
                log_message('error', 'Failed to create free_user_requests table: ' . $e->getMessage());
            }
        }
    }
}
