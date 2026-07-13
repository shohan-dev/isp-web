<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\ConnectionInterface;

class BandwidthModel extends Model
{
    protected $table = 'bandwidth'; // Table name
    protected $primaryKey = 'id';
    protected $allowedFields = ['package_name', 'price', 'category', 'provider', 'purchase_bill'];

    // Database connection (optional, if you want to handle DB connection manually)
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect(); // Connect to the database
        $this->checkTable(); // Ensure table exists when model is loaded
    }

    // Method to check if the table exists and create it if not
    private function checkTable()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        // Query to check if the table exists
        if (!$this->db->tableExists($this->table)) {
            // If the table doesn't exist, create it
            $forge = \Config\Database::forge();
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'package_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255
                ],
                'price' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                ],
                'category' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255
                ],
                'provider' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255
                ],
                'purchase_bill' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255
                ]
            ];
            
            // Add the fields and create the table
            $forge->addField($fields);
            $forge->addPrimaryKey('id');
            $forge->createTable($this->table, true); // 'true' ensures it doesn't overwrite an existing table
        }
    }

    // Method to get all bandwidth packages
    public function getAllBandwidthPackages()
    {
        return $this->findAll();
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
