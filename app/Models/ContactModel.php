<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactModel extends Model
{
    protected $table = 'contacts'; // Table name
    protected $primaryKey = 'id'; // Primary key
    protected $allowedFields = ['name', 'phone', 'email', 'message', 'inquiry_type']; // Fields allowed for mass assignment
    protected $useTimestamps = true; // Automatically handle created_at and updated_at

    // Ensure table creation if it doesn't exist
    public function createTableIfNotExists()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            $forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'phone' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'message' => [
                    'type' => 'TEXT',
                ],
                'inquiry_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['demo', 'feature', 'other', 'support'],
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $forge->addKey('id', true); // Set 'id' as primary key
            $forge->createTable($this->table, true); // Create the table if it doesn't exist
        }
    }
}
