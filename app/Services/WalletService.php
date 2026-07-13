<?php

namespace App\Services;

use App\Models\TenantWallet;
use App\Models\WalletTransaction;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * WalletService — atomic, race-safe tenant wallet (tenant_wallets.balance)
 * mutations with a mandatory ledger trail (wallet_transactions).
 *
 * Same discipline as FundService: every balance change is a single conditional
 * UPDATE executed by the database, so check-and-decrement is atomic and a
 * balance can never be overdrawn. Additionally every mutation records a ledger
 * row, and mutations carrying a `reference` are idempotent — replaying a
 * gateway callback or cron cycle cannot double-apply.
 */
class WalletService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
        // Instantiating the models guarantees the tables exist (runtime DDL).
        new TenantWallet();
        new WalletTransaction();
    }

    /**
     * Fetch (or create) the wallet row for a tenant sAdmin user.
     */
    public function ensureWallet(int $userId): object
    {
        $model = new TenantWallet();
        $wallet = $model->where('user_id', $userId)->first();

        if (!$wallet) {
            try {
                $model->insert([
                    'user_id' => $userId,
                    'balance' => 0,
                ]);
            } catch (\Throwable $e) {
                // Lost a create race — the unique key on user_id kept us safe.
                log_message('debug', 'WalletService::ensureWallet race for user ' . $userId . ': ' . $e->getMessage());
            }
            $wallet = $model->where('user_id', $userId)->first();
        }

        return $wallet;
    }

    public function balance(int $userId): float
    {
        $wallet = (new TenantWallet())->where('user_id', $userId)->first();

        return $wallet ? (float) $wallet->balance : 0.0;
    }

    /**
     * Add funds. Idempotent when $reference is provided: if a ledger row with
     * that reference already exists the credit is skipped (returns true).
     */
    public function credit(int $userId, float $amount, ?string $reference = null, string $description = '', ?int $createdBy = null): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $wallet = $this->ensureWallet($userId);

        if ($reference !== null && $this->referenceExists($reference)) {
            log_message('info', "WalletService::credit skipped duplicate reference {$reference} for user {$userId}");

            return true;
        }

        $this->db->transBegin();

        try {
            $this->db->query(
                'UPDATE `tenant_wallets` SET `balance` = `balance` + ?, `updated_at` = ? WHERE `id` = ?',
                [$amount, date('Y-m-d H:i:s'), $wallet->id]
            );

            if ($this->db->affectedRows() < 1) {
                $this->db->transRollback();

                return false;
            }

            $this->writeLedger($wallet->id, $userId, WalletTransaction::TYPE_CREDIT, $amount, $reference, $description, $createdBy);
            $this->db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'WalletService::credit failed for user ' . $userId . ': ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Atomically subtract $amount IFF the balance covers it. Idempotent when
     * $reference is provided (already-applied references return true).
     *
     * @return bool true if deducted (or already applied); false when the
     *              balance is insufficient or on error.
     */
    public function debit(int $userId, float $amount, ?string $reference = null, string $description = '', ?int $createdBy = null): bool
    {
        if ($amount <= 0) {
            return true; // nothing to deduct
        }

        $wallet = $this->ensureWallet($userId);

        if ($reference !== null && $this->referenceExists($reference)) {
            log_message('info', "WalletService::debit skipped duplicate reference {$reference} for user {$userId}");

            return true;
        }

        $this->db->transBegin();

        try {
            $this->db->query(
                'UPDATE `tenant_wallets` SET `balance` = `balance` - ?, `updated_at` = ? WHERE `id` = ? AND `balance` >= ?',
                [$amount, date('Y-m-d H:i:s'), $wallet->id, $amount]
            );

            if ($this->db->affectedRows() < 1) {
                $this->db->transRollback();

                return false; // insufficient balance
            }

            $this->writeLedger($wallet->id, $userId, WalletTransaction::TYPE_DEBIT, -$amount, $reference, $description, $createdBy);
            $this->db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'WalletService::debit failed for user ' . $userId . ': ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Signed manual adjustment by the platform super-admin. Negative amounts
     * may NOT overdraw the wallet.
     */
    public function adjust(int $userId, float $amount, string $description, int $createdBy, ?string $reference = null): bool
    {
        if ($amount == 0.0) {
            return false;
        }

        $wallet = $this->ensureWallet($userId);

        if ($reference !== null && $this->referenceExists($reference)) {
            return true;
        }

        $this->db->transBegin();

        try {
            if ($amount > 0) {
                $this->db->query(
                    'UPDATE `tenant_wallets` SET `balance` = `balance` + ?, `updated_at` = ? WHERE `id` = ?',
                    [$amount, date('Y-m-d H:i:s'), $wallet->id]
                );
            } else {
                $this->db->query(
                    'UPDATE `tenant_wallets` SET `balance` = `balance` - ?, `updated_at` = ? WHERE `id` = ? AND `balance` >= ?',
                    [abs($amount), date('Y-m-d H:i:s'), $wallet->id, abs($amount)]
                );
            }

            if ($this->db->affectedRows() < 1) {
                $this->db->transRollback();

                return false;
            }

            $this->writeLedger($wallet->id, $userId, WalletTransaction::TYPE_ADJUSTMENT, $amount, $reference, $description, $createdBy);
            $this->db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'WalletService::adjust failed for user ' . $userId . ': ' . $e->getMessage());

            return false;
        }
    }

    public function referenceExists(string $reference): bool
    {
        return (new WalletTransaction())->where('reference', $reference)->countAllResults() > 0;
    }

    /**
     * Ledger history for a tenant (newest first).
     *
     * @return list<object>
     */
    public function history(int $userId, int $limit = 50): array
    {
        return (new WalletTransaction())
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->findAll($limit);
    }

    protected function writeLedger(int $walletId, int $userId, string $type, float $signedAmount, ?string $reference, string $description, ?int $createdBy): void
    {
        $row = $this->db->query('SELECT `balance` FROM `tenant_wallets` WHERE `id` = ?', [$walletId])->getRow();

        (new WalletTransaction())->insert([
            'wallet_id'     => $walletId,
            'user_id'       => $userId,
            'type'          => $type,
            'amount'        => $signedAmount,
            'balance_after' => $row ? (float) $row->balance : 0,
            'reference'     => $reference,
            'description'   => $description,
            'created_by'    => $createdBy,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
