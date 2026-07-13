<?php

namespace App\Models;

use CodeIgniter\Model;

class Sms extends Model
{
    protected $table            = 'sms_messages';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'datetime',
        'content',
        'send_to',
        'sender_number',
        'message_id',
        'logs',
        'gateway',
        'status',
    ];
 
    public function __construct()
    {
        parent::__construct();
        $this->ensureColumnsExist();
    }
 
    private function ensureColumnsExist()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        if ($db->tableExists($this->table)) {
            $forge = \Config\Database::forge();
            $fields = [];
            
            if (!$db->fieldExists('sender_number', $this->table)) {
                $fields['sender_number'] = ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true, 'after' => 'send_to'];
            }
            if (!$db->fieldExists('message_id', $this->table)) {
                $fields['message_id'] = ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'sender_number'];
            }
 
            if (!empty($fields)) {
                $forge->addColumn($this->table, $fields);
            }
        }
    }
}
