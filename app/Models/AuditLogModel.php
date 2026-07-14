<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\User;

class AuditLogModel extends Model
{
    protected $table         = 'audit_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'action',
        'entity',
        'client',
        'router',
        'details',
        'actor',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    // Auto-create table if not exists
    public function ensureTableExists()
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
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'action' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'entity' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'client' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'router' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'details' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'actor' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'ip_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 145,
                    'null'       => true,
                ],
                'user_agent' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->addKey('created_at');
            $forge->addKey('action');
            $forge->addKey('ip_address');

            $forge->createTable($this->table, true);
        }
    }

    // Insert audit log (helper method)
    public function log($data)
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        return $this->insert($data);
    }

    public function getFiltered($from, $to, $perPage, $id, $pppoeFilter = null)
    {
        $builder = $this->orderBy('created_at', 'DESC');
        $builder->where('user_id =', $id);
        if ($from) {
            $builder->where('DATE(created_at) >=', $from);
        }

        if ($to) {
            $builder->where('DATE(created_at) <=', $to);
        }

        if ($pppoeFilter !== null && $pppoeFilter !== '') {
            $builder->like('details', $pppoeFilter);
        }

        return $builder->paginate($perPage);
    }
}
