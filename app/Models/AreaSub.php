<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class AreaSub extends Model
{
    protected $table            = 'sub_areas';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'area_name',
        'area_code',
        'status',
    ];
    protected $useTimestamps    = true;

    public function __construct()
    {
        parent::__construct();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = Database::connect();
        $forge = Database::forge($db);

        if (!$db->tableExists($this->table)) {
            try {
                $forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'auto_increment' => true,
                        'unsigned'       => true,
                    ],
                    'user_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'null'       => false,
                    ],
                    'area_name' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => false,
                    ],
                    'area_code' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 50,
                        'null'       => false,
                    ],
                    'status' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 10,
                        'default'    => 'inactive',
                    ],
                    'created_at' => [
                        'type' => 'TIMESTAMP',
                        'null' => false // Set as NOT NULL
                    ],
                    'updated_at' => [
                        'type'    => 'DATETIME',
                        'null'    => true,
                        'default' => null,
                        'on_update' => 'CURRENT_TIMESTAMP',
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
