<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class News_notice extends Model
{
    protected $table = 'News_notice';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['name', 'url', 'details', 'image', 'admin_id', 'opened', 'created_at'];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * ✅ Automatically create the table if it does not exist
     */
    private function ensureTableExists()
    {
        // Phase-E1: once per FPM worker process, then cross-request cache flag.
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $cacheKey = 'news_notice_table_exists';
        if (cache($cacheKey)) {
            return;
        }

        $db = Database::connect();
        if (!$db->tableExists($this->table)) {
            $forge = Database::forge();

            $fields = [
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                ],
                'image' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'details' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'opened' => [
                    'type'       => 'TEXT',
                    'null'       => true,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->createTable($this->table, true);

            log_message('info', "✅ Table '{$this->table}' created successfully.");
        }

        // Cache for 1 day — table existence rarely changes
        cache()->save($cacheKey, true, 86400);
    }

    /**
     * ➕ Insert new movie server
     */
    public function addServer($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        if ($this->insert($data)) {
            log_message('info', "✅ Movie server '{$data['name']}' added successfully.");
            return true;
        }
        return false;
    }

    /**
     * 🔁 Update movie server by ID
     */
    public function updateServer($id, $data)
    {
        if ($this->update($id, $data)) {
            log_message('info', "🔄 Movie server ID {$id} updated successfully.");
            return true;
        }
        return false;
    }

    /**
     * ❌ Delete movie server by ID
     */
    public function deleteServer($id)
    {
        if ($this->delete($id)) {
            log_message('info', "🗑️ Movie server ID {$id} deleted successfully.");
            return true;
        }
        return false;
    }

    /**
     * 📋 Get all movie servers
     */
    public function getAllServers($admin_id)
    {
        return $this->where('admin_id', $admin_id)->findAll();
    }


    /**
     * 🔍 Get single server by ID
     */
    public function getServerById($id)
    {
        return $this->find($id);
    }
}
