<?php

namespace App\Models;

use CodeIgniter\Model;

class OltModel extends Model
{
    protected $table            = 'olts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'user_id',
        'olt_name',
        'brand',
        'ip',
        'port',
        'protocol',
        'username',
        'password',
        'login_key',
        'status'
    ];

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
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'olt_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'brand' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'ip' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'port' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                ],
                'protocol' => [
                    'type'       => 'ENUM',
                    'constraint' => ['http', 'https', 'telnet'],
                    'default'    => 'http',
                ],
                'username' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'password' => [
                    'type' => 'TEXT',
                ],
                'login_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'status' => [
                    'type'    => 'TINYINT',
                    'default' => 1,
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

            $forge->addKey('id', true);
            $forge->createTable($this->table, true);
        }
    }
}
