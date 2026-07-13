<?php namespace App\Models;

use CodeIgniter\Model;

class UnitModel extends Model
{
    protected $table      = 'units';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'admin_id']; // include admin_id here

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
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false
                ],
                'admin_id' => [ // NEW FIELD
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->createTable($this->table, true);
        }
    }
}
