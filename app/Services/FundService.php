<?php

namespace App\Services;

use App\Models\FundTransaction;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * FundService — atomic, race-safe wallet (`users.fund`) mutations with a
 * mandatory ledger trail (`fund_transactions`).
 *
 * Every balance change is a single conditional UPDATE executed by the database.
 * Mutations carrying a `reference` are idempotent — replaying a gateway callback
 * or webhook cannot double-apply.
 */
class FundService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
        new FundTransaction();
    }

    /**
     * Atomically subtract $amount from users.fund IFF the balance covers it.
     *
     * @return bool true if deducted (or already applied via reference); false when
     *              insufficient funds or on error.
     */
    public function deduct(
        int $userId,
        float $amount,
        ?string $reference = null,
        string $description = '',
        ?int $createdBy = null,
        bool $allowNegative = false
    ): bool {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return true;
        }

        if ($reference !== null && $this->referenceExists($reference)) {
            log_message('info', "FundService::deduct skipped duplicate reference {$reference} for user {$userId}");

            return true;
        }

        $this->db->transBegin();

        try {
            if (! $this->applyDeduct($userId, $amount, $reference, $description, $createdBy, $allowNegative)) {
                $this->db->transRollback();

                return false;
            }

            $this->db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'FundService::deduct failed for user ' . $userId . ': ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Atomically add $amount to users.fund.
     *
     * @return bool true if credited (or already applied via reference); false on error.
     */
    public function add(
        int $userId,
        float $amount,
        ?string $reference = null,
        string $description = '',
        ?int $createdBy = null
    ): bool {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return true;
        }

        if ($reference !== null && $this->referenceExists($reference)) {
            log_message('info', "FundService::add skipped duplicate reference {$reference} for user {$userId}");

            return true;
        }

        $this->db->transBegin();

        try {
            if (! $this->applyAdd($userId, $amount, $reference, $description, $createdBy)) {
                $this->db->transRollback();

                return false;
            }

            $this->db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'FundService::add failed for user ' . $userId . ': ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Move $amount from one user's fund to another, atomically (transaction).
     * Idempotent when $reference is provided (already-applied references return true).
     */
    public function transfer(
        int $fromUserId,
        int $toUserId,
        float $amount,
        ?string $reference = null,
        string $description = '',
        ?int $createdBy = null
    ): bool {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return true;
        }

        $deductRef = $reference !== null ? $reference . ':out' : null;
        $addRef    = $reference !== null ? $reference . ':in' : null;

        if ($reference !== null && $this->referenceExists($deductRef)) {
            return true;
        }

        $this->db->transBegin();

        try {
            if (! $this->applyDeduct($fromUserId, $amount, $deductRef, $description, $createdBy)
                || ! $this->applyAdd($toUserId, $amount, $addRef, $description, $createdBy)) {
                $this->db->transRollback();

                return false;
            }

            $this->db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'FundService::transfer failed: ' . $e->getMessage());

            return false;
        }
    }

    public function referenceExists(string $reference): bool
    {
        return (new FundTransaction())->where('reference', $reference)->countAllResults() > 0;
    }

    /**
     * @return list<object>
     */
    public function history(int $userId, int $limit = 50): array
    {
        return (new FundTransaction())
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->findAll($limit);
    }

    public function balance(int $userId): float
    {
        $row = $this->db->query('SELECT `fund` FROM ' . $this->usersTable() . ' WHERE `id` = ?', [$userId])->getRow();

        return $row ? round((float) $row->fund, 2) : 0.0;
    }

    /**
     * Prefix-aware `users` table name. The 'default' DBGroup has an empty
     * DBPrefix so this is a no-op in production, but the 'tests' DBGroup
     * deliberately sets DBPrefix='db_' to catch exactly this class of
     * hardcoded-unprefixed-raw-SQL bug (its own config comment says so) —
     * raw query() calls, unlike Model/query-builder access, are never
     * auto-prefixed by CI4.
     */
    protected function usersTable(): string
    {
        return '`' . $this->db->DBPrefix . 'users`';
    }

    protected function applyDeduct(
        int $userId,
        float $amount,
        ?string $reference,
        string $description,
        ?int $createdBy,
        bool $allowNegative = false
    ): bool {
        if ($allowNegative) {
            // Postpaid billing debt: deliberately allowed to drive fund negative
            // (an "owed" balance), unlike every other deduct() caller.
            $this->db->query(
                'UPDATE ' . $this->usersTable() . ' SET `fund` = `fund` - ? WHERE `id` = ?',
                [$amount, $userId]
            );
        } else {
            $this->db->query(
                'UPDATE ' . $this->usersTable() . ' SET `fund` = `fund` - ? WHERE `id` = ? AND `fund` >= ?',
                [$amount, $userId, $amount]
            );
        }

        if ($this->db->affectedRows() < 1) {
            return false;
        }

        $this->writeLedger($userId, FundTransaction::TYPE_DEBIT, -$amount, $reference, $description, $createdBy);

        return true;
    }

    protected function applyAdd(
        int $userId,
        float $amount,
        ?string $reference,
        string $description,
        ?int $createdBy
    ): bool {
        $this->db->query(
            'UPDATE ' . $this->usersTable() . ' SET `fund` = `fund` + ? WHERE `id` = ?',
            [$amount, $userId]
        );

        if ($this->db->affectedRows() < 1) {
            return false;
        }

        $this->writeLedger($userId, FundTransaction::TYPE_CREDIT, $amount, $reference, $description, $createdBy);

        return true;
    }

    protected function writeLedger(
        int $userId,
        string $type,
        float $signedAmount,
        ?string $reference,
        string $description,
        ?int $createdBy
    ): void {
        $row = $this->db->query('SELECT `fund` FROM ' . $this->usersTable() . ' WHERE `id` = ?', [$userId])->getRow();

        (new FundTransaction())->insert([
            'user_id'       => $userId,
            'type'          => $type,
            'amount'        => round($signedAmount, 2),
            'balance_after' => $row ? round((float) $row->fund, 2) : 0.0,
            'reference'     => $reference,
            'description'   => $description,
            'created_by'    => $createdBy,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
