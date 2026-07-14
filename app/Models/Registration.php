<?php

namespace App\Models;

use CodeIgniter\Model;

class Registration extends Model
{
    protected $table = 'registrations';
    protected $primaryKey = 'id';

    protected function initialize()
    {
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
        if (!$db->fieldExists('requested_plan', $this->table)) {
            $fieldsToAdd['requested_plan'] = [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'after' => 'package'
            ];
        }
        if (!$db->fieldExists('plan_note', $this->table)) {
            $fieldsToAdd['plan_note'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'requested_plan'
            ];
        }

        if (!empty($fieldsToAdd)) {
            $forge->addColumn($this->table, $fieldsToAdd);
        }
    }

    protected $allowedFields = [
        'userid',
        'organization_name',
        'discount',
        'admin_name',
        'mobile',
        'email',
        'nationalid',
        'password',
        'division',
        'district',
        'upazilla',
        'address',
        'package',
        'requested_plan',
        'plan_note',
        'customer_type',
        'reference_name',
        'will_expire',
        'reference_mobile',
        'whatsapp_number',
        'payment_receive_number',
    ];
}
