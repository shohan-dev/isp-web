<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class InventoryCategory extends Model
{
    protected $table = 'inventory_categories';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'admin_id',
        'item_category_name',
        'item_category_status',
        'sub_category_of',
        'short_description',
        'subcategory_items',
        'items',
        'created_at'
    ];

    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();

        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'admin_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,    // or true if it can be nullable
                ],

                'item_category_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255
                ],
                'item_category_status' => [
                    'type' => 'ENUM',
                    'constraint' => ['active', 'inactive'],
                    'default' => 'active'
                ],
                'sub_category_of' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true
                ],
                'subcategory_items' => [
                    'type' => 'LONGTEXT',
                    'null' => true
                ],
                'short_description' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'items' => [
                    'type' => 'VARCHAR',
                    'constraint' => 10000,
                    'null' => true
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->createTable($this->table, true);
        }
    }
    public function getAllBandwidthPackages()
    {
        $admin_id = session()->get('user_id');
        return $this->where('admin_id', $admin_id)->findAll();
        // return $this->findAll();
    }

    // Method to get a specific bandwidth package by ID
    public function getBandwidthPackageById($id)
    {
        return $this->find($id);
    }

    // Method to add a new bandwidth package
    public function addBandwidthPackage($data)
    {
        return $this->insert($data);
    }

    // Method to update an existing bandwidth package
    public function updateBandwidthPackage($id, $data)
    {
        return $this->update($id, $data);
    }

    // Method to delete a bandwidth package
    public function deleteBandwidthPackage($id)
    {
        return $this->delete($id);
    }
}
