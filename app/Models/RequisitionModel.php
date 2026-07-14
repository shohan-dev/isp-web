<?php namespace App\Models;

use CodeIgniter\Model;

class RequisitionModel extends Model
{
    protected $table      = 'requisitions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'admin_id',
        'requisition_id',
        'item_count',
        'vendor_suggestion',
        'unit_id',
        'total_amount',
        'requisition_date',
        'requisition_by',
        'deadline',
        'approved_by',
        'approved_date',
        // Item details fields added here
        'item_name',
        'category',
        'subcategory',
        'item_id',
        'description',
        'qty',
        'rate',
        'total',
        'remarks'
    ];

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

        if (! $this->db->tableExists($this->table)) {
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
                    'unsigned'   => true,
                    'null'       => false
                ],
                'requisition_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100
                ],
                'item_count' => [
                    'type'       => 'INT',
                    'null'       => true
                ],
                'unit_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true
                ],
                'vendor_suggestion' => [
                    'type'       => 'TEXT',
                    'null'       => true
                ],
                'total_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'null'       => true
                ],
                'requisition_date' => [
                    'type' => 'DATE',
                    'null' => true
                ],
                'requisition_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true
                ],
                'deadline' => [
                    'type' => 'DATE',
                    'null' => true
                ],
                'approved_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true
                ],
                'approved_date' => [
                    'type' => 'DATE',
                    'null' => true
                ],
                // Item detail fields added here
                'item_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true
                ],
                'subcategory' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true
                ],
                'item_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true
                ],
                'description' => [
                    'type'       => 'TEXT',
                    'null'       => true
                ],
                'qty' => [
                    'type'       => 'FLOAT',
                    'null'       => true
                ],
                'rate' => [
                    'type'       => 'FLOAT',
                    'null'       => true
                ],
                'total' => [
                    'type'       => 'FLOAT',
                    'null'       => true
                ],
                'remarks' =>[
                    'type'       => 'VARCHAR',
                    'constraint' => 2000,
                    'null'       => true
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
            $forge->createTable($this->table, true); // true = if not exists
        }
    }
}
