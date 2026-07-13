<?php

namespace App\Models;

use CodeIgniter\Model;

class ExpenseModel extends Model
{
    protected $table            = 'expenses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'user_id',
        'name',
        'expense_head',
        'employee',
        'invoice_no',
        'date',
        'amount',
        'bank_account',
        'document',
        'description',
        'status',
        'created_by',
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

            $fields = [

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
                ],

                'expense_head' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],

                'employee' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],

                'invoice_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],

                'date' => [
                    'type' => 'DATE',
                ],

                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                ],

                'bank_account' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],

                'document' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],

                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],

                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'approved',
                ],

                'created_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],

                'created_at DATETIME default current_timestamp',
                'updated_at DATETIME default current_timestamp on update current_timestamp'
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->createTable($this->table, true);
        }
    }
}
