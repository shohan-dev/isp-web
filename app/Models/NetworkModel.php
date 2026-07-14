<?php

namespace App\Models;

use CodeIgniter\Model;

class NetworkModel extends Model
{
    protected $table      = 'network_diagrams';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'parent_id',
        'label',
        'color',
        'admin_id',
        'latitude',
        'longitude'
    ];

    protected $useTimestamps = false;

    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
        $this->checkTable();
    }

    private function checkTable()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        if (!$this->db->tableExists($this->table)) {
            $forge = \Config\Database::forge();

            $fields = [
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true
                ],
                'parent_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true
                ],
                'label' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false
                ],
                'color' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => false
                ],
                'admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false
                ],
                'latitude' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,6',
                    'null'       => true
                ],
                'longitude' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,6',
                    'null'       => true
                ]
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);

            // Optional: Add the foreign key
            $forge->addForeignKey('parent_id', $this->table, 'id', 'SET NULL', 'CASCADE');

            $forge->createTable($this->table, true); // true = if not exists
        }
    }

    // Create table if it doesn't exist
    public function ensureTableExists()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'auto_increment' => true
                ],
                'parent_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true
                ],
                'label' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100
                ],
                'color' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50
                ],
                'admin_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'latitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,6',
                    'null' => true
                ],
                'longitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,6',
                    'null' => true
                ]
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->addForeignKey('parent_id', $this->table, 'id', 'SET NULL', 'CASCADE');
            $forge->createTable($this->table, true);
        }
    }
}
