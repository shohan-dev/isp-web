<?php

namespace App\Models;

use CodeIgniter\Model;

class Payment extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';

    public function __construct()
    {
        parent::__construct();
        $this->ensureCommentColumn();
    }

    private function ensureCommentColumn()
    {
        // Phase-E1: run the DDL probe only once per PHP process (FPM worker).
        // Previously this fired on every model instantiation (~90+ metadata
        // queries/request). After the first successful check, columns exist
        // and the guard skips all SHOW COLUMNS / ALTER TABLE work.
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $forge = \Config\Database::forge();
        if ($this->db->tableExists($this->table)) {
            if (!$this->db->fieldExists('comment', $this->table)) {
                $forge->addColumn($this->table, [
                    'comment' => [
                        'type' => 'TEXT',
                        'null' => true,
                        'after' => 'method_trx'
                    ]
                ]);
            }
            if (!$this->db->fieldExists('custom_data', $this->table)) {
                $forge->addColumn($this->table, [
                    'custom_data' => [
                        'type' => 'TEXT',
                        'null' => true,
                        'after' => 'comment'
                    ]
                ]);
            }

            // Ensure created_at and paid_at are DATETIME to store time
            $fields = $this->db->getFieldData($this->table);
            foreach ($fields as $field) {
                if (($field->name === 'created_at' || $field->name === 'paid_at') && strtoupper($field->type) === 'DATE') {
                    $forge->modifyColumn($this->table, [
                        $field->name => [
                            'type' => 'DATETIME',
                            'null' => true,
                        ]
                    ]);
                }
            }
        }
    }
    protected $allowedFields    = [
        'user_id',
        'user_type',
        'admin_id',
        'paidby',
        'invoice',
        'amount',
        'pay_amount',
        'month',
        'period',        // year-aware billing month (Phase 4) — auto-filled on insert below
        'gateway_trx',   // dedicated successful-gateway idempotency key (Phase 4)
        'created_at',
        'paid_at',
        'paid_via',
        'paid_to',
        'method_trx',
        'comment',
        'custom_data',
        'status',
    ];

    // Keep the year-aware `period` column populated on every new payment (Phase 4/5).
    protected $beforeInsert = ['fillPeriod'];

    /**
     * First day of the billing month for a payment, derived from its created_at
     * (or now) — the same shape the Phase 4 backfill used
     * (DATE_FORMAT(created_at, '%Y-%m-01')). Pure + DB-free so it is unit-testable.
     */
    public static function periodFor(?string $createdAt): string
    {
        $ts = ! empty($createdAt) ? (strtotime($createdAt) ?: time()) : time();

        return date('Y-m-01', $ts);
    }

    /**
     * beforeInsert: fill `period` when the caller didn't set it, so new rows get
     * the year-aware billing month without anyone having to remember to pass it.
     * No-ops safely if the column hasn't been migrated yet (a DB managed outside
     * `php spark db:optimize`/`migrate`).
     */
    protected function fillPeriod(array $data)
    {
        $row = $data['data'] ?? [];
        if (! empty($row['period'])) {
            return $data; // caller set it explicitly — respect it
        }

        try {
            if (! $this->db->fieldExists('period', $this->table)) {
                return $data; // column not present — don't write an unknown column
            }
        } catch (\Throwable $e) {
            return $data;
        }

        $data['data']['period'] = self::periodFor($row['created_at'] ?? null);

        return $data;
    }
}
