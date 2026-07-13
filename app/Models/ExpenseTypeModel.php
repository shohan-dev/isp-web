<?php

namespace App\Models;

use CodeIgniter\Model;

class ExpenseTypeModel extends Model
{
    protected $table            = 'expense_types';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'user_id',
        'name',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    // 🔥 Auto create table if not exists
    private function ensureTableExists()
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

            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'active',
                ],
                'created_at DATETIME default current_timestamp',
                'updated_at DATETIME default current_timestamp on update current_timestamp'
            ]);

            $forge->addKey('id', true);
            $forge->addKey('user_id');
            $forge->createTable($this->table, true);
        }
    }
}
