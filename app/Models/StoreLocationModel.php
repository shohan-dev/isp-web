<?php namespace App\Models;

use CodeIgniter\Model;

class StoreLocationModel extends Model
{
    protected $table = 'store_locations';
    protected $primaryKey = 'id';
    protected $allowedFields = ['location_name', 'short_value', 'admin_id'];

    protected $useTimestamps = true;

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
                'location_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false
                ],
                'short_value' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false
                ],
                'admin_id' => [
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
