<?php

namespace App\Models;

use CodeIgniter\Model;

class User extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';

    protected function initialize()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        
        $fieldsToAdd = [];
        if (!$db->fieldExists('whatsapp_number', $this->table)) {
            $fieldsToAdd['whatsapp_number'] = [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
                'after' => 'mobile'
            ];
        }
        if (!$db->fieldExists('payment_receive_number', $this->table)) {
            $fieldsToAdd['payment_receive_number'] = [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
                'after' => 'whatsapp_number'
            ];
        }
        if (!$db->fieldExists('billing_type', $this->table)) {
            $fieldsToAdd['billing_type'] = [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
                'default' => 'postpaid',
                'after' => 'payment_receive_number'
            ];
        }
        if (!$db->fieldExists('reseller_validity_periods', $this->table)) {
            $fieldsToAdd['reseller_validity_periods'] = [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'billing_type'
            ];
        }
        if (!$db->fieldExists('fund_enabled', $this->table)) {
            $fieldsToAdd['fund_enabled'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'default' => 1,
                'after' => 'reseller_validity_periods'
            ];
        }
        if (!$db->fieldExists('tenant_id', $this->table)) {
            $fieldsToAdd['tenant_id'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'admin_id',
            ];
        }
        if (!$db->fieldExists('pending_package_id', $this->table)) {
            $fieldsToAdd['pending_package_id'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'pre_package',
            ];
        }
        if (!$db->fieldExists('trial_ends_at', $this->table)) {
            $fieldsToAdd['trial_ends_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'pending_package_id',
            ];
        }

        if (!empty($fieldsToAdd)) {
            $forge->addColumn($this->table, $fieldsToAdd);
        }
    }

    protected $allowedFields    = [
        'package_id',
        'pre_package',
        'pending_package_id',
        'trial_ends_at',
        'area_id',
        'router_id',
        'name',
        'designation',
        'mobile',
        'nid_number',
        'email',
        'password',
        'code',
        'address',
        'pppoe_id',
        'conn_status',
        'last_renewed',
        'will_expire',
        'subscription_status',
        'auto_disconnect',
        'role',
        'created_at',
        'updated_at',
        'status',
        'admin_id',
        'tenant_id',
        'created_by',
        'posPrinter',
        'activity',
        'fund',
        'whatsapp_number',
        'payment_receive_number',
        'billing_type',
        'reseller_validity_periods',
        'fund_enabled',
    ];

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data)
    {

        $data['data']['created_at'] = date('Y-m-d H:i:s');
        $data['data']['last_renewed'] = date('Y-m-d H:i:s');

        return $data;
    }

    protected function beforeUpdate(array $data)
    {

        $data['data']['updated_at'] = date('Y-m-d H:i:s');

        return $data;
    }
}
