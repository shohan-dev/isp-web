<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class PurchaseBillModel extends Model
{
    protected $table = 'purchase_bills';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        // Header fields
        'admin_id',
        'provider',
        'payment_status',
        'billing_date',
        'invoice_number',
        'payment_due',
        'attachment',
        'remarks',
        'total',
        // Payment fields
        'discount',
        'payment_method',
        'received_by',
        'paid_by',
        'paid_number',
        // Line-item fields
        'items',
        'image',
    ];

    protected $useTimestamps = true; // auto-manage created_at & updated_at

    public function __construct()
    {
        parent::__construct();

        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'admin_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],

                // — Header —
                'provider' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ],
                'payment_status' => [
                    'type' => 'ENUM',
                    'constraint' => ['Due', 'Paid'],
                    'default' => 'Due',
                ],
                'billing_date' => [
                    'type' => 'DATE',
                ],
                'invoice_number' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'payment_due' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'attachment' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'remarks' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                
                 // Payment-related fields
                'discount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => '0.00',
                ],
                'payment_method' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'received_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'paid_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'paid_number' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],


                // — Line-item —
                'items' => [
                    'type' => 'LONGTEXT',
                    'null' => true, // Optional: allow null values
                ],


                'total' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => '0.00',
                ],
                'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                // — Timestamps —
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->createTable($this->table, true);
        }
    }

    // ────────────────────────────────────────────────────────────
    // Fetch all rows (each row = one line-item with header data)
    public function getAllLines()
    {
        $userId = session()->get('user_id');
        return $this->where('admin_id', $userId)->orderBy('billing_date', 'DESC')->findAll();
        // return $this->orderBy('billing_date', 'DESC')->findAll();
    }

    // Fetch all lines for a given invoice_number
    public function getLinesByInvoice(string $invoiceNumber)
    {
        return $this->where('invoice_number', $invoiceNumber)
            ->orderBy('id')
            ->findAll();
    }

    // Add a new line
    public function addLine(array $data)
    {
        // expects $data to include both header + line fields
        return $this->insert($data);
    }

    // Update a specific line by its PK
    public function updateLine(int $id, array $data)
    {
        return $this->update($id, $data);
    }

    // Delete a line
    public function deleteLine(int $id)
    {
        return $this->delete($id);
    }
}
