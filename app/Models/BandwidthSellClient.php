<?php

namespace App\Models;

use CodeIgniter\Model;

class BandwidthSellClient extends Model
{
    protected $table      = 'bandwidth_sell_client';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'customer_name',
        'admin_id',
        'customer_code',
        'contact_person',
        'email',
        'mobile_number',
        'phone_number',
        'pop_status',
        'reference_by',
        'address',

        // Step 2
        'nttn_info',
        'vlan_info',       // NEW field for multiple VLANs
        'scr_id',
        'activation_date',
        'ip_addresses',    // NEW field for multiple IPs
        'pop_name',

        // Step 3
        'username',
        'password',
        'activity_status'
    ];


    protected $useTimestamps = true;

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

        if (! $this->db->tableExists($this->table)) {
            $forge = \Config\Database::forge();

            $fields = [
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true
                ],
                'admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'customer_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255
                ],
                'customer_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true
                ],
                'contact_person' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true
                ],
                'mobile_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20
                ],
                'phone_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true
                ],
                'pop_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50
                ],
                'reference_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true
                ],
                'address' => [
                    'type' => 'TEXT',
                    'null' => true
                ],

                // Step 2
                'nttn_info' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true
                ],
                'vlan_info' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'scr_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true
                ],
                'activation_date' => [
                    'type' => 'DATE'
                ],
                'ip_addresses' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'pop_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true
                ],

                // Step 3
                'username' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100
                ],
                'password' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255
                ],
                'activity_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
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
            $forge->createTable($this->table, true); // true = if not exists
        }
    }
}
