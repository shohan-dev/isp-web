<?php

namespace App\Models;

use CodeIgniter\Model;

class VoiceSmsModel extends Model
{
    protected $table            = 'voice_messages';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = ['admin_id', 'name', 'message_id', 'created_at'];

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

        if (!$this->db->tableExists($this->table)) {
            $forge = \Config\Database::forge();

            $fields = [
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                ],
                'message_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ];
            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->createTable($this->table);
        } else {
            // Table exists, check if admin_id exists
            if (!$this->db->fieldExists('admin_id', $this->table)) {
                $forge = \Config\Database::forge();
                // If user_id exists, rename it to admin_id
                if ($this->db->fieldExists('user_id', $this->table)) {
                    $forge->modifyColumn($this->table, [
                        'user_id' => [
                            'name'       => 'admin_id',
                            'type'       => 'INT',
                            'constraint' => 11,
                        ]
                    ]);
                } else {
                    $forge->addColumn($this->table, [
                        'admin_id' => [
                            'type'       => 'INT',
                            'constraint' => 11,
                            'after'      => 'id'
                        ]
                    ]);
                }
            }
        }
    }

    public function getMessagesForAdmin($adminId)
    {
        return $this->where('admin_id', $adminId)->orderBy('id', 'DESC')->findAll();
    }
}
