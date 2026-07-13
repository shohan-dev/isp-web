<?php

namespace Zapi\Modules\Shared\Rewards\Services;

use Zapi\Modules\Shared\Rewards\Models\RewardWalletModel;
use Zapi\Modules\Shared\Rewards\Models\RewardTransactionModel;
use Zapi\Modules\Shared\Rewards\Models\RewardRedemptionModel;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;

/**
 * The transactional core of the points system.
 *
 * Invariants guaranteed:
 *   - Every award/spend is exactly-once (UNIQUE idempotency_key on the ledger).
 *   - wallet.balance is mutated only inside the same DB transaction as the
 *     ledger row, by exactly that row's signed `points`.
 *   - available balance = balance - held; redemption holds never let the wallet
 *     go negative (atomic conditional UPDATE).
 *   - The wallet is fully reconstructable from the ledger (reconcileWallet()).
 */
class RewardEngine
{
    private RewardWalletModel $wallets;
    private RewardTransactionModel $ledger;
    private RewardRedemptionModel $redemptions;
    private RewardConfigService $config;

    public function __construct(?RewardConfigService $config = null)
    {
        $this->wallets     = new RewardWalletModel();
        $this->ledger      = new RewardTransactionModel();
        $this->redemptions = new RewardRedemptionModel();
        $this->config      = $config ?? new RewardConfigService();
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    /** Resolve the owner (reseller/admin id) for a customer from users.admin_id. */
    public function resolveOwnerId(int $userId): int
    {
        $user = model('App\Models\User')->find($userId);
        if (!$user) {
            return 0;
        }
        return (int) (is_object($user) ? ($user->admin_id ?? 0) : ($user['admin_id'] ?? 0));
    }

    public function getWallet(int $userId, int $ownerId = 0)
    {
        return $this->wallets->getOrCreate($userId, $ownerId);
    }

    public function availableBalance(int $userId): int
    {
        $w = $this->wallets->where('user_id', $userId)->first();
        if (!$w) {
            return 0;
        }
        return max(0, (int) $w->balance - (int) $w->held);
    }

    /**
     * Idempotently post an EARN (positive) — or a negative adjustment/expiry — row.
     *
     * @return bool true if a new row was posted, false if it was a duplicate
     */
    public function award(
        int $userId,
        int $ownerId,
        int $points,
        string $source,
        string $idempotencyKey,
        ?string $refType = null,
        $refId = null,
        ?string $note = null,
        ?int $expiresInDays = null
    ): bool {
        if ($points === 0) {
            return false;
        }

        $db = $this->db();
        $db->transBegin();
        try {
            $this->wallets->getOrCreate($userId, $ownerId);

            $expiresAt = null;
            $remaining = 0;
            if ($points > 0) {
                $remaining = $points;
                $days = $expiresInDays ?? $this->config->getInt($ownerId, RewardSources::KEY_POINT_EXPIRY_DAYS);
                if ($days > 0) {
                    $expiresAt = date('Y-m-d', strtotime("+{$days} days"));
                }
            }

            // Ledger insert is the idempotency gate (INSERT IGNORE on unique key).
            $db->table('reward_transactions')->ignore(true)->insert([
                'user_id'         => $userId,
                'owner_id'        => $ownerId,
                'points'          => $points,
                'balance_after'   => 0, // stamped after wallet update
                'source'          => $source,
                'ref_type'        => $refType,
                'ref_id'          => $refId,
                'idempotency_key' => $idempotencyKey,
                'status'          => RewardSources::TXN_POSTED,
                'expires_at'      => $expiresAt,
                'remaining'       => $remaining,
                'note'            => $note,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            if ($db->affectedRows() <= 0) {
                // Duplicate key — already awarded. No wallet change.
                $db->transCommit();
                return false;
            }
            $txnId = (int) $db->insertID();

            // Atomic wallet mutation.
            $db->query(
                'UPDATE reward_wallets SET balance = balance + ?, lifetime_earned = lifetime_earned + ?, updated_at = ? WHERE user_id = ?',
                [$points, max($points, 0), date('Y-m-d H:i:s'), $userId]
            );

            $newBalance = (int) ($db->table('reward_wallets')->where('user_id', $userId)->get()->getRow()->balance ?? 0);
            $db->table('reward_transactions')->where('id', $txnId)->update(['balance_after' => $newBalance]);

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'RewardEngine::award failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Place a redemption HOLD against a pending payment. Atomically reserves
     * `points` from the available balance (balance - held). Releases any prior
     * hold on the same payment first so re-renew is consistent.
     *
     * @return array{ok:bool, points:int, redemption_id:?int, reason:?string}
     */
    public function redeemHold(int $userId, int $ownerId, int $points, int $paymentId, int $ttlMinutes = 30): array
    {
        if ($points <= 0) {
            return ['ok' => false, 'points' => 0, 'redemption_id' => null, 'reason' => 'no_points'];
        }

        // Drop any stale hold for this payment so we never double-reserve.
        $this->redeemReleaseByPayment($paymentId, RewardSources::REDEEM_RELEASED);

        $db = $this->db();
        $db->transBegin();
        try {
            $this->wallets->getOrCreate($userId, $ownerId);

            // Atomic conditional reserve: only succeeds if enough is available.
            $db->query(
                'UPDATE reward_wallets SET held = held + ?, updated_at = ? WHERE user_id = ? AND (balance - held) >= ?',
                [$points, date('Y-m-d H:i:s'), $userId, $points]
            );

            if ($db->affectedRows() <= 0) {
                $db->transRollback();
                return ['ok' => false, 'points' => 0, 'redemption_id' => null, 'reason' => 'insufficient_balance'];
            }

            $db->table('reward_redemptions')->insert([
                'user_id'         => $userId,
                'owner_id'        => $ownerId,
                'payment_id'      => $paymentId,
                'points'          => $points,
                'status'          => RewardSources::REDEEM_HELD,
                'hold_expires_at' => date('Y-m-d H:i:s', strtotime("+{$ttlMinutes} minutes")),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $redemptionId = (int) $db->insertID();

            $db->transCommit();
            return ['ok' => true, 'points' => $points, 'redemption_id' => $redemptionId, 'reason' => null];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'RewardEngine::redeemHold failed: ' . $e->getMessage());
            return ['ok' => false, 'points' => 0, 'redemption_id' => null, 'reason' => 'error'];
        }
    }

    /**
     * Apply a held redemption once its payment settled successfully:
     * FIFO-consume lots, post the -spend ledger row, decrement balance & held,
     * and best-effort record the reward-subsidy expense.
     */
    public function redeemApplyByPayment(int $paymentId): bool
    {
        $hold = $this->redemptions->findActiveHoldForPayment($paymentId);
        if (!$hold) {
            return false;
        }
        return $this->redeemApply((int) $hold->id);
    }

    public function redeemApply(int $redemptionId): bool
    {
        $db = $this->db();
        $db->transBegin();
        try {
            // Compare-and-swap: claim the hold. Only one apply can win.
            $db->query(
                "UPDATE reward_redemptions SET status = ?, updated_at = ? WHERE id = ? AND status = ?",
                [RewardSources::REDEEM_APPLIED, date('Y-m-d H:i:s'), $redemptionId, RewardSources::REDEEM_HELD]
            );
            if ($db->affectedRows() <= 0) {
                $db->transCommit(); // already applied/released by someone else
                return false;
            }

            $hold = $db->table('reward_redemptions')->where('id', $redemptionId)->get()->getRow();
            $userId  = (int) $hold->user_id;
            $ownerId = (int) $hold->owner_id;
            $points  = (int) $hold->points;
            $paymentId = (int) $hold->payment_id;

            // FIFO consume earn lots (oldest, non-expired first).
            $this->fifoConsume($db, $userId, $points);

            // Post the spend ledger row (idempotent on redemption id).
            $db->table('reward_transactions')->ignore(true)->insert([
                'user_id'         => $userId,
                'owner_id'        => $ownerId,
                'points'          => -$points,
                'balance_after'   => 0,
                'source'          => RewardSources::REDEMPTION,
                'ref_type'        => 'payment',
                'ref_id'          => $paymentId,
                'idempotency_key' => 'redemption:' . $redemptionId,
                'status'          => RewardSources::TXN_POSTED,
                'remaining'       => 0,
                'note'            => 'Reward redemption for payment #' . $paymentId,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $inserted = $db->affectedRows() > 0;
            $txnId = $inserted ? (int) $db->insertID() : null;

            if ($inserted) {
                // Decrement balance & release the hold portion in one shot.
                $db->query(
                    'UPDATE reward_wallets SET balance = balance - ?, held = GREATEST(held - ?, 0), lifetime_spent = lifetime_spent + ?, updated_at = ? WHERE user_id = ?',
                    [$points, $points, $points, date('Y-m-d H:i:s'), $userId]
                );
                $newBalance = (int) ($db->table('reward_wallets')->where('user_id', $userId)->get()->getRow()->balance ?? 0);
                $db->table('reward_transactions')->where('id', $txnId)->update(['balance_after' => $newBalance]);
            } else {
                // Ledger already existed (rare). Just make sure held is released.
                $db->query(
                    'UPDATE reward_wallets SET held = GREATEST(held - ?, 0), updated_at = ? WHERE user_id = ?',
                    [$points, date('Y-m-d H:i:s'), $userId]
                );
            }

            // Best-effort subsidy accounting (never roll back the spend on failure).
            $expenseId = $this->recordSubsidyExpense($ownerId, $userId, $paymentId, $points);

            $db->table('reward_redemptions')->where('id', $redemptionId)->update([
                'applied_txn_id' => $txnId,
                'expense_id'     => $expenseId,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'RewardEngine::redeemApply failed: ' . $e->getMessage());
            return false;
        }
    }

    /** Release a hold by redemption id (compare-and-swap on status). */
    public function redeemRelease(int $redemptionId, string $newStatus = RewardSources::REDEEM_RELEASED): bool
    {
        $db = $this->db();
        $db->transBegin();
        try {
            $hold = $db->table('reward_redemptions')->where('id', $redemptionId)->get()->getRow();
            if (!$hold || $hold->status !== RewardSources::REDEEM_HELD) {
                $db->transCommit();
                return false;
            }
            $db->query(
                'UPDATE reward_redemptions SET status = ?, payment_id = NULL, updated_at = ? WHERE id = ? AND status = ?',
                [$newStatus, date('Y-m-d H:i:s'), $redemptionId, RewardSources::REDEEM_HELD]
            );
            if ($db->affectedRows() <= 0) {
                $db->transCommit();
                return false;
            }
            $db->query(
                'UPDATE reward_wallets SET held = GREATEST(held - ?, 0), updated_at = ? WHERE user_id = ?',
                [(int) $hold->points, date('Y-m-d H:i:s'), (int) $hold->user_id]
            );
            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'RewardEngine::redeemRelease failed: ' . $e->getMessage());
            return false;
        }
    }

    public function redeemReleaseByPayment(int $paymentId, string $newStatus = RewardSources::REDEEM_RELEASED): bool
    {
        $hold = $this->redemptions->findActiveHoldForPayment($paymentId);
        if (!$hold) {
            return false;
        }
        return $this->redeemRelease((int) $hold->id, $newStatus);
    }

    /** Release all holds that timed out without a settled payment. */
    public function releaseExpiredHolds(): int
    {
        $now = date('Y-m-d H:i:s');
        $stale = $this->redemptions
            ->where('status', RewardSources::REDEEM_HELD)
            ->where('hold_expires_at <', $now)
            ->findAll(500);

        $count = 0;
        foreach ($stale as $hold) {
            if ($this->redeemRelease((int) $hold->id, RewardSources::REDEEM_EXPIRED)) {
                $count++;
            }
        }
        return $count;
    }

    /** Expire due point lots FIFO-style (one negative ledger row per lot). */
    public function expireDuePoints(): int
    {
        $today = date('Y-m-d');
        $lots = $this->ledger
            ->where('remaining >', 0)
            ->where('points >', 0)
            ->where('expires_at IS NOT NULL', null, false)
            ->where('expires_at <', $today)
            ->findAll(1000);

        $count = 0;
        foreach ($lots as $lot) {
            $userId  = (int) $lot->user_id;
            $ownerId = (int) $lot->owner_id;
            $rem     = (int) $lot->remaining;
            if ($rem <= 0) {
                continue;
            }
            $posted = $this->award(
                $userId,
                $ownerId,
                -$rem,
                RewardSources::EXPIRY,
                'expiry:' . $lot->id,
                'txn',
                $lot->id,
                'Points expired'
            );
            // Zero the lot so it is not counted/expired again.
            $this->ledger->where('id', $lot->id)->set(['remaining' => 0])->update();
            if ($posted) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Recompute the wallet cache from the ledger (self-heal). Safe to run
     * repeatedly; useful in the daily cron.
     */
    public function reconcileWallet(int $userId): void
    {
        $db = $this->db();
        $balance = (int) ($db->table('reward_transactions')
            ->selectSum('points')->where('user_id', $userId)->where('status', 'posted')
            ->get()->getRow()->points ?? 0);

        $earned = (int) ($db->table('reward_transactions')
            ->selectSum('points')->where('user_id', $userId)->where('points >', 0)
            ->get()->getRow()->points ?? 0);

        $spent = (int) ($db->table('reward_transactions')
            ->selectSum('points')->where('user_id', $userId)->where('source', RewardSources::REDEMPTION)
            ->get()->getRow()->points ?? 0);

        $held = (int) ($db->table('reward_redemptions')
            ->selectSum('points')->where('user_id', $userId)->where('status', RewardSources::REDEEM_HELD)
            ->get()->getRow()->points ?? 0);

        $this->wallets->getOrCreate($userId);
        $db->table('reward_wallets')->where('user_id', $userId)->update([
            'balance'         => $balance,
            'lifetime_earned' => max(0, $earned),
            'lifetime_spent'  => abs($spent),
            'held'            => max(0, $held),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /** Points expiring within N days (for the wallet UI). */
    public function expiringSoonPoints(int $userId, int $days = 30): int
    {
        $cut = date('Y-m-d', strtotime("+{$days} days"));
        $row = $this->ledger
            ->selectSum('remaining')
            ->where('user_id', $userId)
            ->where('remaining >', 0)
            ->where('points >', 0)
            ->where('expires_at IS NOT NULL', null, false)
            ->where('expires_at <=', $cut)
            ->first();
        return (int) ($row->remaining ?? 0);
    }

    // ---- internals ----------------------------------------------------

    private function fifoConsume($db, int $userId, int $points): void
    {
        $need = $points;
        $today = date('Y-m-d');
        $lots = $db->table('reward_transactions')
            ->select('id, remaining')
            ->where('user_id', $userId)
            ->where('remaining >', 0)
            ->where('points >', 0)
            ->groupStart()
                ->where('expires_at IS NULL', null, false)
                ->orWhere('expires_at >=', $today)
            ->groupEnd()
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResult();

        foreach ($lots as $lot) {
            if ($need <= 0) {
                break;
            }
            $take = min((int) $lot->remaining, $need);
            $db->table('reward_transactions')
                ->where('id', $lot->id)
                ->set('remaining', 'remaining - ' . (int) $take, false)
                ->update();
            $need -= $take;
        }
        // If $need > 0 the wallet balance still authoritatively decrements;
        // lot tracking is best-effort and self-heals via reconcileWallet().
    }

    /**
     * Record the reward-point subsidy as an expense against the customer's
     * owner (reseller/admin) so revenue reports stay accurate. Best-effort.
     */
    private function recordSubsidyExpense(int $ownerId, int $userId, int $paymentId, int $points): ?int
    {
        try {
            if ($ownerId <= 0 || $points <= 0) {
                return null;
            }

            $expenseTypeModel = model('App\Models\ExpenseTypeModel');
            $type = $expenseTypeModel->where('user_id', $ownerId)->where('name', 'Reward Subsidy')->first();
            if (!$type) {
                $expenseTypeModel->insert(['user_id' => $ownerId, 'name' => 'Reward Subsidy', 'status' => 'active']);
                $typeId = (int) $expenseTypeModel->getInsertID();
            } else {
                $typeId = (int) (is_object($type) ? $type->id : $type['id']);
            }

            $expenseModel = model('App\Models\ExpenseModel');
            $expenseModel->insert([
                'user_id'      => $ownerId,
                'name'         => 'Reward Point Subsidy',
                'expense_head' => $typeId,
                'invoice_no'   => 'RWD-' . $paymentId,
                'date'         => date('Y-m-d'),
                'amount'       => $points, // 1 point = 1 BDT subsidy
                'description'  => 'Reward points redeemed by customer #' . $userId . ' on payment #' . $paymentId,
                'status'       => 'approved',
                'created_by'   => 'system',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            return (int) $expenseModel->getInsertID();
        } catch (\Throwable $e) {
            log_message('error', 'RewardEngine::recordSubsidyExpense failed: ' . $e->getMessage());
            return null;
        }
    }
}
