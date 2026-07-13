<?php

namespace App\Models;

use CodeIgniter\Model;

class ResellerTransactions extends Model
{
    protected $table = 'reseller_transaction';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'customer',
        'admin_id',
        'amount',
        'package_price',
        'active_for',
        'comments',
        
    ];
    protected $useTimestamps = true;

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
        $forge = \Config\Database::forge($db);


        if (!$db->tableExists($this->table)) {
            try {
                $forge->addField([
                    'id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'auto_increment' => true
                    ],
                    'customer' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => false
                    ],
                    'admin_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => 11,
                        'null' => false
                    ],
                    'amount' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                        'null' => false
                    ],
                    'package_price' => [
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => false
                    ],
                    'active_for' => [
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => false
                    ],
                    'comments' => [
                        'type' => 'TEXT',
                        'null' => true
                    ],
                    
                    'created_at' => [
                        'type' => 'TIMESTAMP',
                        'null' => false // Set as NOT NULL
                    ],
                    'updated_at' => [
                        'type' => 'TIMESTAMP',
                        'on_update' => 'CURRENT_TIMESTAMP', // Automatically set on update
                        'null' => true // Allow NULL if the record is not updated yet
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
