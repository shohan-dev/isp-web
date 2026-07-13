<?php

namespace Zapi\Modules\Shared\Rewards\Services;

use Zapi\Modules\Shared\Rewards\Models\ReferralModel;

/**
 * Detects abusive referrals: self-referral, duplicate identity (phone/NID/email
 * already a customer), and duplicate referral attempts. Returns a flag reason
 * rather than hard-blocking, so a human (reseller/sAdmin) can override.
 */
class ReferralFraudGuard
{
    private ReferralModel $referrals;

    public function __construct()
    {
        $this->referrals = new ReferralModel();
    }

    /**
     * @return array{ok:bool, reason:?string}
     */
    public function check(int $referrerId, ?string $mobile, ?string $email, ?string $nid): array
    {
        $mobile = $mobile !== null ? trim($mobile) : '';
        $email  = $email !== null ? strtolower(trim($email)) : '';
        $nid    = $nid !== null ? trim($nid) : '';

        $userModel = model('App\Models\User');

        // 1) Self-referral — referee identity matches the referrer's own record.
        $referrer = $userModel->find($referrerId);
        if ($referrer) {
            $rMobile = (string) (is_object($referrer) ? ($referrer->mobile ?? '') : ($referrer['mobile'] ?? ''));
            $rEmail  = strtolower((string) (is_object($referrer) ? ($referrer->email ?? '') : ($referrer['email'] ?? '')));
            $rNid    = (string) (is_object($referrer) ? ($referrer->nid_number ?? '') : ($referrer['nid_number'] ?? ''));
            if (($mobile !== '' && $mobile === $rMobile)
                || ($email !== '' && $email === $rEmail)
                || ($nid !== '' && $nid === $rNid)) {
                return ['ok' => false, 'reason' => 'self_referral'];
            }
        }

        // 2) Duplicate identity — already an existing customer.
        if ($mobile !== '' && $userModel->where('mobile', $mobile)->first()) {
            return ['ok' => false, 'reason' => 'duplicate_mobile'];
        }
        if ($email !== '' && $userModel->where('email', $email)->first()) {
            return ['ok' => false, 'reason' => 'duplicate_email'];
        }
        if ($nid !== '' && $userModel->where('nid_number', $nid)->first()) {
            return ['ok' => false, 'reason' => 'duplicate_nid'];
        }

        // 3) Duplicate referral — same identity already pending/verified.
        if ($mobile !== '') {
            $dup = $this->referrals
                ->where('referee_mobile', $mobile)
                ->whereIn('status', ['pending', 'verified'])
                ->first();
            if ($dup) {
                return ['ok' => false, 'reason' => 'duplicate_referral'];
            }
        }
        if ($email !== '') {
            $dup = $this->referrals
                ->where('referee_email', $email)
                ->whereIn('status', ['pending', 'verified'])
                ->first();
            if ($dup) {
                return ['ok' => false, 'reason' => 'duplicate_referral'];
            }
        }
        if ($nid !== '') {
            $dup = $this->referrals
                ->where('referee_nid', $nid)
                ->whereIn('status', ['pending', 'verified'])
                ->first();
            if ($dup) {
                return ['ok' => false, 'reason' => 'duplicate_referral'];
            }
        }

        return ['ok' => true, 'reason' => null];
    }
}
