<?php

namespace App\Models;

use CodeIgniter\Model;

class OltSynchronizedOnuModel extends Model
{
    protected $table            = 'olt_synchronized_onus';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'olt_id',
        'pon_port',
        'onu_index',
        'mac_address',
        'status',
        'rx_power',
        'distance',
        'description',
        'splitter_name',
        'customer_name',
        'company_name',
        'address',
        'mobile',
        'pppoe_id',
        'voltage',
        'temp',
        'bias',
        'tx_power',
        'vendor',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {

            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'olt_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'pon_port' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'onu_index' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'mac_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'rx_power' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'distance' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                ],
                'description' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'splitter_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'customer_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'company_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'mobile' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'pppoe_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'voltage' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'temp' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'bias' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'tx_power' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'vendor' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
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
            $forge->addKey('olt_id');
            $forge->addKey('mac_address');
            $forge->createTable($this->table, true);
        } else {
            $fieldsToAdd = [];
            if (!$db->fieldExists('customer_name', $this->table)) {
                $fieldsToAdd['customer_name'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'splitter_name'
                ];
            }
            if (!$db->fieldExists('company_name', $this->table)) {
                $fieldsToAdd['company_name'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'customer_name'
                ];
            }
            if (!$db->fieldExists('address', $this->table)) {
                $fieldsToAdd['address'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'company_name'
                ];
            }
            if (!$db->fieldExists('mobile', $this->table)) {
                $fieldsToAdd['mobile'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'address'
                ];
            }
            if (!$db->fieldExists('pppoe_id', $this->table)) {
                $fieldsToAdd['pppoe_id'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'mobile'
                ];
            }
            if (!$db->fieldExists('voltage', $this->table)) {
                $fieldsToAdd['voltage'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'pppoe_id'
                ];
            }
            if (!$db->fieldExists('temp', $this->table)) {
                $fieldsToAdd['temp'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'voltage'
                ];
            }
            if (!$db->fieldExists('bias', $this->table)) {
                $fieldsToAdd['bias'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'temp'
                ];
            }
            if (!$db->fieldExists('tx_power', $this->table)) {
                $fieldsToAdd['tx_power'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'bias'
                ];
            }
            if (!$db->fieldExists('vendor', $this->table)) {
                $fieldsToAdd['vendor'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'tx_power'
                ];
            }
            if (!empty($fieldsToAdd)) {
                $forge->addColumn($this->table, $fieldsToAdd);
            }
        }
    }
}
