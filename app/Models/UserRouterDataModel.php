<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRouterDataModel extends Model
{
    protected $table = 'user_router_data';
    protected $primaryKey = 'id';

    // Allowed fields for mass assignment
    protected $allowedFields = [
        'user_id',
        'router_id',

        'router_password',
        'pppoe_secret',
        'pppoe_profile',
        'last_updated',
    ];

    // Return results as objects
    protected $returnType = 'object';

    // Automatically handle timestamps
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureColumnExists();
    }

    /**
     * Ensures that the pppoe_profile column exists in the table.
     * This is useful for seamless updates across different environments.
     */
    private function ensureColumnExists()
    {
        // Phase-E1: once per FPM worker process; skip on subsequent instantiations.
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        if ($db->tableExists($this->table) && !$db->fieldExists('pppoe_profile', $this->table)) {
            $forge = \Config\Database::forge();
            $forge->addColumn($this->table, [
                'pppoe_profile' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                    'after' => 'pppoe_secret'
                ]
            ]);
        }
    }
}
