<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeLocation extends Model
{
    protected $table            = 'employee_locations';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'employee_id',
        'latitude',
        'longitude',
        'address',
        'created_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->createTableIfNotExists();
    }

    public function createTableIfNotExists()
    {
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
                    'employee_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => false,
                    ],
                    'latitude' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                    ],
                    'longitude' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                    ],
                    'address' => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->createTable($this->table);
            } catch (\Exception $e) {
                log_message('error', 'Error creating table ' . $this->table . ': ' . $e->getMessage());
            }
        }
    }
}
