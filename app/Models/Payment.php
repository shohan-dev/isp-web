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

    // Keep the year-aware `period` column populated on every new/updated payment (Phase 4/5).
    protected $beforeInsert = ['fillPeriod'];
    protected $beforeUpdate = ['fillPeriod'];

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
     * Billing period from the Payment Month dropdown (e.g. "August") + year anchor.
     * Prefer this over created_at so an August bill paid in July does not collide
     * with an existing July row on uniq_pay_user_period_status.
     */
    public static function periodFromMonth(?string $monthName, ?string $anchorDate = null): string
    {
        $anchorTs = ! empty($anchorDate) ? (strtotime($anchorDate) ?: time()) : time();
        $year = (int) date('Y', $anchorTs);
        $monthName = trim((string) $monthName);

        if ($monthName !== '') {
            $monthTs = strtotime('1 ' . $monthName . ' ' . $year);
            if ($monthTs !== false) {
                return date('Y-m-01', $monthTs);
            }
        }

        return self::periodFor($anchorDate);
    }

    /**
     * beforeInsert/beforeUpdate: fill `period` when the caller didn't set it.
     * Prefers the billing `month` name over created_at so inserts match the
     * month the operator selected. No-ops if the column isn't migrated yet.
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

        $data['data']['period'] = self::periodFromMonth(
            $row['month'] ?? null,
            $row['paid_at'] ?? $row['created_at'] ?? null
        );

        return $data;
    }
}
