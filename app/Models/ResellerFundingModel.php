<?php

namespace App\Models;

use CodeIgniter\Model;

class ResellerFundingModel extends Model
{
    protected $table = 'reseller_funding';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'customer',
        'admin_id',
        'amount',
        'received_amount',
        'invoice_number',
        'paid_via',
        'received_date',
        'comments',
        'status'
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
        $forge = \Config\Database::forge();

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
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => false
                    ],
                    'amount' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                        'null' => false
                    ],
                    'received_amount' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                        'null' => false
                    ],
                    'paid_via' => [
                        'type' => 'VARCHAR',
                        'constraint' => 32,
                        'null' => false
                    ],
                    'invoice_number' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => false
                    ],
                    'received_date' => [
                        'type' => 'DATE',
                        'null' => false
                    ],
                    'comments' => [
                        'type' => 'TEXT',
                        'null' => true
                    ],
                    'status' => [
                        'type' => 'ENUM',
                        'constraint' => ['successful', 'pending'],
                        'default' => 'pending',
                        'null' => false
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
