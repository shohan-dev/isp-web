<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * In-app notification inbox (Bangla). Backs the customer Notification module,
 * which previously returned mock data.
 */
class AppNotificationModel extends Model
{
    protected $table         = 'app_notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'owner_id',
        'type',
        'title',
        'body',
        'ref_type',
        'ref_id',
        'action_url',
        'is_read',
        'created_at',
        'read_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    public function ensureTableExists(): void
    {
        $db = \Config\Database::connect();
        if ($db->tableExists($this->table)) {
            return;
        }

        $forge = \Config\Database::forge();
        $forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'owner_id'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'system'],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'body'       => ['type' => 'TEXT', 'null' => true],
            'ref_type'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'ref_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'action_url' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'is_read'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'read_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addKey(['user_id', 'is_read', 'created_at']);
        $forge->createTable($this->table, true);
    }

    public function add(int $userId, string $type, string $title, ?string $body, array $extra = []): int
    {
        $this->insert(array_merge([
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ], $extra));
        return (int) $this->getInsertID();
    }
}
