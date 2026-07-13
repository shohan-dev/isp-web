<?php

namespace Zapi\Modules\Shared\Rewards\Services;

use Zapi\Modules\Shared\Rewards\Models\ReferralModel;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;
use Zapi\Modules\Shared\Rewards\Support\RewardMessages;

/**
 * Auth-agnostic core of the referral verification lifecycle. Both the JWT
 * reseller API service and the session-based website controller call this so
 * "Approve & Activate" / "Reject" behave identically everywhere (no drift).
 *
 * Callers are responsible for AUTHORIZATION (ownership) before invoking.
 */
class ReferralWorkflow
{
    /**
     * Approve & activate: verify the referral, activate the lead account +
     * package, award the referrer, audit-log and notify.
     *
     * @return array{ok:bool, message:string, points:int, referee_id:int, status:string}
     */
    public function approve(int $referralId, int $actorId): array
    {
        $refModel = new ReferralModel();
        $referral = $refModel->find($referralId);
        if (!$referral) {
            return ['ok' => false, 'message' => 'Referral not found', 'points' => 0, 'referee_id' => 0, 'status' => ''];
        }
        if (in_array($referral->status, [RewardSources::REFERRAL_VERIFIED, RewardSources::REFERRAL_REJECTED], true)) {
            return ['ok' => false, 'message' => 'This referral is already ' . $referral->status, 'points' => 0, 'referee_id' => 0, 'status' => $referral->status];
        }

        $refereeId  = (int) ($referral->referee_id ?? 0);
        $referrerId = (int) ($referral->referrer_id ?? 0);
        if ($refereeId <= 0) {
            return ['ok' => false, 'message' => 'Referred customer record is missing', 'points' => 0, 'referee_id' => 0, 'status' => $referral->status];
        }

        $userModel = model('App\Models\User');
        $referee = $userModel->find($refereeId);
        if (!$referee) {
            return ['ok' => false, 'message' => 'Referred customer not found', 'points' => 0, 'referee_id' => $refereeId, 'status' => $referral->status];
        }

        // 1) Activate the lead account + package.
        $userModel->update($refereeId, [
            'status'              => 'active',
            'subscription_status' => 'active',
            'will_expire'         => $this->resolveWillExpire($refereeId),
            'last_renewed'        => date('Y-m-d H:i:s'),
        ]);

        // 2) Award the referrer.
        $config = new RewardConfigService();
        $referrer = $userModel->find($referrerId);
        $referrerOwner = $referrer ? (int) ($referrer->admin_id ?? 0) : 0;
        $points = $config->isEnabled($referrerOwner, RewardSources::KEY_REFERRAL_ENABLED)
            ? $config->getInt($referrerOwner, RewardSources::KEY_REFERRAL_POINTS)
            : 0;
        if ($points > 0) {
            (new RewardEngine())->award(
                $referrerId,
                $referrerOwner,
                $points,
                RewardSources::REFERRAL,
                'referral:' . $referralId,
                'referral',
                $referralId,
                'Referral reward'
            );
        }

        // 3) Mark verified.
        $refModel->update($referralId, [
            'status'         => RewardSources::REFERRAL_VERIFIED,
            'points_awarded' => $points,
            'verified_at'    => date('Y-m-d H:i:s'),
            'verified_by'    => $actorId,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        // 4) Audit + notify (best-effort).
        $this->audit($actorId, 'referral_approved', 'Referral approved & customer activated', $refereeId);
        $refereeName = (string) ($referral->referee_name ?? ($referee->name ?? ''));
        $msg = RewardMessages::referralVerified($refereeName, $points);
        (new RewardNotifier())->notify($referrerId, RewardSources::NOTIFY_REFERRAL, $msg['title'], $msg['body'], [
            'ref_type'   => 'referral',
            'ref_id'     => $referralId,
            'action_url' => '/referral',
        ]);

        return ['ok' => true, 'message' => 'Referral approved and customer activated.', 'points' => $points, 'referee_id' => $refereeId, 'status' => RewardSources::REFERRAL_VERIFIED];
    }

    /**
     * @return array{ok:bool, message:string, status:string}
     */
    public function reject(int $referralId, int $actorId, ?string $reason = null): array
    {
        $refModel = new ReferralModel();
        $referral = $refModel->find($referralId);
        if (!$referral) {
            return ['ok' => false, 'message' => 'Referral not found', 'status' => ''];
        }
        if ($referral->status === RewardSources::REFERRAL_VERIFIED) {
            return ['ok' => false, 'message' => 'A verified referral cannot be rejected', 'status' => $referral->status];
        }

        $refModel->update($referralId, [
            'status'        => RewardSources::REFERRAL_REJECTED,
            'reject_reason' => ($reason !== null && $reason !== '') ? $reason : null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->audit($actorId, 'referral_rejected', 'Referral rejected: ' . (string) $reason, (int) ($referral->referee_id ?? 0));

        $msg = RewardMessages::referralRejected((string) ($referral->referee_name ?? ''));
        (new RewardNotifier())->notify((int) $referral->referrer_id, RewardSources::NOTIFY_REFERRAL, $msg['title'], $msg['body'], [
            'ref_type' => 'referral',
            'ref_id'   => $referralId,
        ]);

        return ['ok' => true, 'message' => 'Referral rejected.', 'status' => RewardSources::REFERRAL_REJECTED];
    }

    private function resolveWillExpire(int $userId): string
    {
        if (function_exists('calcUserSubsRenewDate')) {
            try {
                $d = calcUserSubsRenewDate($userId);
                if (!empty($d)) {
                    return (string) $d;
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }
        return date('Y-m-d H:i:s', strtotime('+30 days'));
    }

    private function audit(int $actorId, string $action, string $details, int $userId): void
    {
        try {
            $ip = '';
            $ua = '';
            try {
                $req = service('request');
                $ip = $req->getIPAddress();
                $ua = (string) $req->getUserAgent();
            } catch (\Throwable $e) {
                // CLI / no request context
            }
            model('App\Models\AuditLogModel')->log([
                'user_id'    => $userId,
                'action'     => $action,
                'entity'     => 'referral',
                'details'    => $details,
                'actor'      => (string) ($actorId ?: 'system'),
                'ip_address' => $ip,
                'user_agent' => $ua,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'referral audit failed: ' . $e->getMessage());
        }
    }
}
