<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsEventConfig extends Model
{
    protected $table         = 'sms_event_config';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $allowedFields = ['admin_id', 'event', 'template_id', 'is_enabled'];
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

        if (!$this->db->tableExists($this->table)) {
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
                    'null'       => false
                ],
                'event' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false
                ],
                'template_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true
                ],
                'is_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => false,
                    'default'    => 1
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
            $forge->addUniqueKey(['admin_id', 'event']);
            $forge->createTable($this->table, true);
        }
    }

    /**
     * Get all event configs keyed by event name for a given admin.
     */
    public function getConfigsForAdmin(int $adminId): array
    {
        $rows = $this->where('admin_id', $adminId)->findAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->event] = $row;
        }
        return $result;
    }

    /**
     * Upsert (insert or update) a single event config.
     */
    public function upsert(int $adminId, string $event, ?int $templateId, int $isEnabled): void
    {
        $existing = $this->where(['admin_id' => $adminId, 'event' => $event])->first();
        if ($existing) {
            $this->update($existing->id, [
                'template_id' => $templateId,
                'is_enabled'  => $isEnabled,
            ]);
        } else {
            $this->insert([
                'admin_id'    => $adminId,
                'event'       => $event,
                'template_id' => $templateId,
                'is_enabled'  => $isEnabled,
            ]);
        }
    }
}
