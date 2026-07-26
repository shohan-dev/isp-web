<?php

namespace App\Models;

use CodeIgniter\Model;

class Attendance extends Model
{
    protected $table            = 'attendances';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'created_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'employee_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'date' => [
                    'type'       => 'DATE',
                ],
                'check_in' => [
                    'type'       => 'DATETIME',
                    'null'       => true,
                ],
                'check_out' => [
                    'type'       => 'DATETIME',
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'present',
                ],
                'created_at' => [
                    'type'       => 'DATETIME',
                    'null'       => true,
                ],
            ]);

            $forge->addKey('id', true);
            $forge->addKey('employee_id');
            $forge->addKey('date');
            $forge->createTable($this->table, true);
        }
    }
}
