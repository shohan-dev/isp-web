<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table         = 'tenants';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'slug',
        'name',
        'owner_user_id',
        'status',
        'plan',
        'primary_color',
        'logo',
        'favicon',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected function initialize()
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db    = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'slug' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 63,
                    'null'       => false,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'owner_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'active',
                ],
                'plan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'primary_color' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'logo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'favicon' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
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
            $forge->addUniqueKey('slug');
            $forge->addKey('owner_user_id');
            $forge->addKey('status');
            $forge->createTable($this->table, true);
        }
    }

    public function findBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }

        return $this->where('slug', $slug)->first();
    }

    public function findByOwner(int $ownerUserId)
    {
        if ($ownerUserId <= 0) {
            return null;
        }

        return $this->where('owner_user_id', $ownerUserId)->first();
    }

    /**
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return [
            'www', 'admin', 'api', 'mail', 'app', 'ftp', 'localhost', 'static',
            'assets', 'cdn', 'platform', 'master', 'root', 'support', 'help',
            'status', 'health', 'healthz', 'metrics', 'cron', 'zapi', 'docs',
            'blog', 'shop', 'store', 'portal', 'dashboard', 'login', 'auth',
            'register', 'signup', 'billing', 'pay', 'payment', 'webhook',
            'ns1', 'ns2', 'mx', 'smtp', 'imap', 'pop', 'vpn', 'dev', 'test',
            'staging', 'demo', 'beta', 'isppaybd',
        ];
    }

    public static function isValidSlug(string $slug): bool
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || strlen($slug) < 2 || strlen($slug) > 63) {
            return false;
        }
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $slug)) {
            return false;
        }
        if (strpos($slug, '--') !== false) {
            return false;
        }
        if (in_array($slug, self::reservedSlugs(), true)) {
            return false;
        }

        return true;
    }
}
