<?php

namespace App\Models;

use CodeIgniter\Model;

class ConnectionData extends Model
{
    protected $table = 'connection_details';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'sub_area_id',
        'connection_type',
        'cable_requirement',
        'fiber_code',
        'number_of_core',
        'core_color',
        'client_type',
        'billing_status',
        'otc',
        'otc_status',
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
                    'sub_area_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => 20,
                        'null' => false,
                    ],
                    'connection_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => false,
                    ],
                    'cable_requirement' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => true,
                    ],
                    'fiber_code' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                    ],
                    'number_of_core' => [
                        'type' => 'INT',
                        'constraint' => 3,
                        'null' => true,
                    ],
                    'core_color' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                    ],
                    'client_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                    ],
                    'billing_status' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                    ],
                    'otc' => [
                        'type' => 'VARCHAR',
                        'constraint' => 10,
                        'null' => true,
                    ],
                    'otc_status' => [
                        'type' => 'VARCHAR',
                        'constraint' => 20,
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
                $forge->createTable($this->table, true); // Create table if not exists
            } catch (\Exception $e) {
                log_message('error', 'Failed to create table: ' . $e->getMessage());
            }
        }
    }
}
