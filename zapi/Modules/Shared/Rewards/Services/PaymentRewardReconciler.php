<?php

namespace Zapi\Modules\Shared\Rewards\Services;

use Zapi\Modules\Shared\Rewards\Models\RewardSettingModel;
use Zapi\Modules\Shared\Rewards\Models\RewardRenewalIntentModel;
use Zapi\Modules\Shared\Rewards\Models\RewardRedemptionModel;
use Zapi\Modules\Shared\Rewards\Models\RewardEventLogModel;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;
use Zapi\Modules\Shared\Rewards\Support\RewardMessages;

/**
 * Cron-driven reconciliation. Because the payment SUCCESS status is flipped in
 * legacy gateway controllers (outside zapi), we never hook them directly:
 * instead we scan the authoritative `payments` table and award/finalise
 * idempotently. Safe to run as often as every few minutes.
 */
class PaymentRewardReconciler
{
    private const CURSOR_KEY = 'reconciler_cursor_payment_id';

    private RewardEngine $engine;
    private RewardConfigService $config;
    private RewardNotifier $notifier;
    private RewardSettingModel $settings;
    private RewardRenewalIntentModel $intents;
    private RewardRedemptionModel $redemptions;
    private RewardEventLogModel $eventLog;

    public function __construct()
    {
        $this->engine      = new RewardEngine();
        $this->config      = new RewardConfigService();
        $this->notifier    = new RewardNotifier();
        $this->settings    = new RewardSettingModel();
        $this->intents     = new RewardRenewalIntentModel();
        $this->redemptions = new RewardRedemptionModel();
        $this->eventLog    = new RewardEventLogModel();
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    /**
     * Main reconcile pass: payment-derived rewards + redemption apply/release.
     *
     * @return array summary counters
     */
    public function reconcile(int $batch = 1000): array
    {
        $db = $this->db();
        $cursor = (int) ($this->settings->getValue(0, self::CURSOR_KEY) ?? 0);

        $rows = $db->table('payments')
            ->where('status', 'successful')
            ->where('id >', $cursor)
            ->orderBy('id', 'ASC')
            ->limit($batch)
            ->get()
            ->getResult();

        $summary = ['scanned' => 0, 'online' => 0, 'early' => 0, 'upgrade' => 0, 'streak' => 0, 'redeemed' => 0, 'max_id' => $cursor];

        foreach ($rows as $p) {
            $summary['scanned']++;
            $paymentId = (int) $p->id;
            $userId    = (int) $p->user_id;
            $userType  = strtolower((string) ($p->user_type ?? ''));

            // Only end-customer self-payments earn customer reward points.
            if ($userType !== 'user' || $userId <= 0) {
                $summary['max_id'] = max($summary['max_id'], $paymentId);
                continue;
            }
            $ownerId = (int) ($p->admin_id ?? $this->engine->resolveOwnerId($userId));

            // (a) Online payment bonus.
            $paidVia = strtolower((string) ($p->paid_via ?? ''));
            if ($paidVia !== '' && in_array($paidVia, RewardSources::ONLINE_GATEWAYS, true)) {
                $pts = $this->config->getInt($ownerId, RewardSources::KEY_ONLINE_PAYMENT_POINTS);
                if ($pts > 0 && $this->engine->award($userId, $ownerId, $pts, RewardSources::ONLINE_PAYMENT, 'online_payment:' . $paymentId, 'payment', $paymentId, 'Online payment bonus')) {
                    $summary['online']++;
                    $this->notifyEarned($userId, $pts, RewardSources::ONLINE_PAYMENT);
                }
            }

            // (b)/(c) Early-renewal & upgrade — from the intent snapshot.
            $intent = $this->intents->where('payment_id', $paymentId)->first();
            $isOnTime = false;
            if ($intent) {
                if ((int) $intent->days_before_expiry > 0) {
                    $isOnTime = true;
                    $pts = $this->config->getInt($ownerId, RewardSources::KEY_EARLY_RENEWAL_POINTS);
                    if ($pts > 0 && $this->engine->award($userId, $ownerId, $pts, RewardSources::EARLY_RENEWAL, 'early_renewal:' . $paymentId, 'payment', $paymentId, 'Early renewal bonus')) {
                        $summary['early']++;
                        $this->notifyEarned($userId, $pts, RewardSources::EARLY_RENEWAL);
                    }
                }
                if ((int) $intent->is_upgrade === 1) {
                    $pts = $this->config->getInt($ownerId, RewardSources::KEY_UPGRADE_POINTS);
                    if ($pts > 0 && $this->engine->award($userId, $ownerId, $pts, RewardSources::UPGRADE, 'upgrade:' . $paymentId, 'payment', $paymentId, 'Package upgrade bonus')) {
                        $summary['upgrade']++;
                        $this->notifyEarned($userId, $pts, RewardSources::UPGRADE);
                    }
                }
            }

            // (d) On-time streak: 3 consecutive on-time renewals (conservative —
            // only counts payments we captured intent for, so no false awards).
            if ($isOnTime && $this->qualifiesForStreak($userId, $paymentId)) {
                $pts = $this->config->getInt($ownerId, RewardSources::KEY_STREAK_POINTS);
                if ($pts > 0 && $this->engine->award($userId, $ownerId, $pts, RewardSources::STREAK, 'streak:' . $userId . ':' . $paymentId, 'payment', $paymentId, 'On-time streak bonus')) {
                    $summary['streak']++;
                    $this->notifyEarned($userId, $pts, RewardSources::STREAK);
                }
            }

            // (e) Apply any held redemption tied to this now-successful payment.
            if ($this->engine->redeemApplyByPayment($paymentId)) {
                $summary['redeemed']++;
            }

            $summary['max_id'] = max($summary['max_id'], $paymentId);
        }

        // Advance cursor only after the batch is processed (idempotency makes
        // re-processing safe regardless).
        if ($summary['max_id'] > $cursor) {
            $this->settings->setValue(0, self::CURSOR_KEY, (string) $summary['max_id']);
        }

        // Release holds whose payment ended up failed.
        $summary['released_failed'] = $this->releaseFailedHolds();

        return $summary;
    }

    /**
     * True when this payment and the previous two successful self-payments were
     * all on-time (renewed before expiry per their intent snapshots).
     */
    private function qualifiesForStreak(int $userId, int $paymentId): bool
    {
        $db = $this->db();
        $recent = $db->table('payments')
            ->select('id')
            ->where('user_id', $userId)
            ->where('paidby', $userId)
            ->where('user_type', 'user')
            ->where('status', 'successful')
            ->where('id <=', $paymentId)
            ->orderBy('id', 'DESC')
            ->limit(3)
            ->get()
            ->getResult();

        if (count($recent) < 3) {
            return false;
        }

        foreach ($recent as $r) {
            $intent = $this->intents->where('payment_id', (int) $r->id)->first();
            if (!$intent || (int) $intent->days_before_expiry <= 0) {
                return false;
            }
        }
        return true;
    }

    /** Release holds whose tied payment is recorded as failed. */
    public function releaseFailedHolds(): int
    {
        $db = $this->db();
        $held = $this->redemptions->where('status', RewardSources::REDEEM_HELD)->findAll(500);
        $count = 0;
        foreach ($held as $hold) {
            $paymentId = (int) ($hold->payment_id ?? 0);
            if ($paymentId <= 0) {
                continue;
            }
            $pay = $db->table('payments')->where('id', $paymentId)->get()->getRow();
            if ($pay && strtolower((string) $pay->status) === 'failed') {
                if ($this->engine->redeemRelease((int) $hold->id, RewardSources::REDEEM_RELEASED)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * 6-month / 12-month loyalty rewards for active customers (one-time each,
     * idempotency key guards repeats). Run daily.
     */
    public function runLoyalty(int $batch = 2000): array
    {
        $db = $this->db();
        $summary = ['loyalty_6m' => 0, 'loyalty_12m' => 0];

        $users = $db->table('users')
            ->select('id, admin_id, created_at, status')
            ->where('role', 'user')
            ->where('status', 'active')
            ->limit($batch)
            ->get()
            ->getResult();

        $now = time();
        foreach ($users as $u) {
            $userId = (int) $u->id;
            $ownerId = (int) ($u->admin_id ?? 0);
            $start = strtotime((string) ($u->created_at ?? ''));
            if (!$start) {
                continue;
            }
            $months = ($now - $start) / (30 * 24 * 3600);

            if ($months >= 12) {
                $pts = $this->config->getInt($ownerId, RewardSources::KEY_LOYALTY_12M_POINTS);
                if ($pts > 0 && $this->engine->award($userId, $ownerId, $pts, RewardSources::LOYALTY_12M, 'loyalty_12m:' . $userId, 'user', $userId, '12-month loyalty')) {
                    $summary['loyalty_12m']++;
                    $this->notifyEarned($userId, $pts, RewardSources::LOYALTY_12M);
                }
            }
            if ($months >= 6) {
                $pts = $this->config->getInt($ownerId, RewardSources::KEY_LOYALTY_6M_POINTS);
                if ($pts > 0 && $this->engine->award($userId, $ownerId, $pts, RewardSources::LOYALTY_6M, 'loyalty_6m:' . $userId, 'user', $userId, '6-month loyalty')) {
                    $summary['loyalty_6m']++;
                    $this->notifyEarned($userId, $pts, RewardSources::LOYALTY_6M);
                }
            }
        }
        return $summary;
    }

    /**
     * Birthday rewards. Requires a DOB column on `users` (dob/birthday/date_of_birth).
     * No-op (logged) if no such column exists, so it is safe to schedule now.
     */
    public function runBirthday(int $batch = 5000): array
    {
        $db = $this->db();
        $dobCol = null;
        foreach (['dob', 'birthday', 'date_of_birth', 'birth_date'] as $c) {
            if ($db->fieldExists($c, 'users')) {
                $dobCol = $c;
                break;
            }
        }
        if ($dobCol === null) {
            log_message('info', 'RewardReconciler::runBirthday — no DOB column on users; skipped.');
            return ['birthday' => 0, 'skipped' => true];
        }

        $today = date('m-d');
        $year = date('Y');
        $users = $db->table('users')
            ->select("id, admin_id, {$dobCol} as dob")
            ->where('role', 'user')
            ->where('status', 'active')
            ->where("DATE_FORMAT({$dobCol}, '%m-%d') =", $today)
            ->limit($batch)
            ->get()
            ->getResult();

        $count = 0;
        foreach ($users as $u) {
            $userId = (int) $u->id;
            $ownerId = (int) ($u->admin_id ?? 0);
            $pts = $this->config->getInt($ownerId, RewardSources::KEY_BIRTHDAY_POINTS);
            if ($pts > 0 && $this->engine->award($userId, $ownerId, $pts, RewardSources::BIRTHDAY, 'birthday:' . $userId . ':' . $year, 'user', $userId, 'Birthday gift')) {
                $count++;
                $this->notifyEarned($userId, $pts, RewardSources::BIRTHDAY);
            }
        }
        return ['birthday' => $count, 'skipped' => false];
    }

    private function notifyEarned(int $userId, int $points, string $source): void
    {
        $reason = RewardMessages::reasonForSource($source);
        $msg = RewardMessages::pointsEarned($points, $reason);
        $this->notifier->notify($userId, RewardSources::NOTIFY_REWARD, $msg['title'], $msg['body'], [
            'ref_type'   => 'reward',
            'action_url' => '/reward',
        ]);
    }
}
