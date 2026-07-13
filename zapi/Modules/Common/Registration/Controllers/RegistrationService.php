<?php

namespace Zapi\Modules\Common\Registration\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Shared\Rewards\Models\ReferralCodeModel;
use Zapi\Modules\Shared\Rewards\Models\ReferralModel;
use Zapi\Modules\Shared\Rewards\Services\ReferralFraudGuard;
use Zapi\Modules\Shared\Rewards\Services\RewardNotifier;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;
use Zapi\Modules\Shared\Rewards\Support\RewardMessages;

/**
 * Public referral self-registration ("Lead + admin finishes" model).
 *
 * Stores a referral lead only — no customer/user row is created here.
 * An admin completes setup at /customers/new, which creates the customer and
 * awards referral points.
 */
class RegistrationService extends BaseApiController
{
    private function input(string $key, $default = null)
    {
        $raw = $this->request->getJSON(true);
        if (!is_array($raw)) {
            $raw = $this->request->getRawInput();
        }
        if (is_array($raw) && array_key_exists($key, $raw) && $raw[$key] !== '') {
            return $raw[$key];
        }
        $post = $this->request->getPost($key);
        if ($post !== null && $post !== '') {
            return $post;
        }
        $get = $this->request->getGet($key);
        return ($get !== null && $get !== '') ? $get : $default;
    }

    public function validateCode($code = null)
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return $this->respondError('Referral code is required', 400, 'REQUEST_FAILED');
        }
        $row = (new ReferralCodeModel())->findActiveByCode($code);
        if (!$row) {
            return $this->respondSuccess(['valid' => false]);
        }
        $referrer = model('App\Models\User')->find((int) $row->user_id);
        return $this->respondSuccess([
            'valid'         => true,
            'code'          => $code,
            'referrer_name' => (string) ($referrer->name ?? ''),
        ]);
    }

    public function register()
    {
        $name   = trim((string) $this->input('name', ''));
        $mobile = $this->normalizeMobile((string) $this->input('mobile', ''));
        $email  = strtolower(trim((string) $this->input('email', '')));
        $nid    = trim((string) $this->input('nid_number', $this->input('nid', '')));
        $code   = strtoupper(trim((string) $this->input('ref', $this->input('referral_code', ''))));
        $packageId = (int) $this->input('package_id', 0);
        $password  = (string) $this->input('password', '');

        // --- validation ------------------------------------------------
        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required';
        }
        if ($mobile === '' || strlen($mobile) < 11) {
            $errors['mobile'] = 'A valid mobile number is required';
        }
        if ($code === '') {
            $errors['ref'] = 'A referral code is required';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address';
        }
        if (!empty($errors)) {
            return $this->respondError('Please correct the highlighted fields', 422, 'VALIDATION_ERROR', $errors);
        }

        // Web/mobile login uses email field; AuthController falls back to {mobile}@gmail.com.
        if ($email === '' && $mobile !== '') {
            $email = $mobile . '@gmail.com';
        }

        // --- referral code ---------------------------------------------
        $codeModel = new ReferralCodeModel();
        $codeRow = $codeModel->findActiveByCode($code);
        if (!$codeRow) {
            return $this->respondError('Invalid or inactive referral code', 422, 'REQUEST_FAILED', ['ref' => 'Invalid referral code']);
        }
        $referrerId = (int) $codeRow->user_id;
        $userModel  = model('App\Models\User');
        $referrer   = $userModel->find($referrerId);
        if (!$referrer) {
            return $this->respondError('Referrer account not found', 422, 'REQUEST_FAILED');
        }
        $ownerId = (int) ($referrer->admin_id ?? 0);

        // --- fraud guard (hard block) ----------------------------------
        $guard = (new ReferralFraudGuard())->check($referrerId, $mobile, $email, $nid);
        if (!$guard['ok']) {
            return $this->respondError($this->fraudMessage($guard['reason']), 409, 'REFERRAL_REJECTED', ['reason' => $guard['reason']]);
        }

        // --- create the referral lead (no customer row yet) ------------
        $refModel = new ReferralModel();
        if (!$refModel->insert([
            'referrer_id'    => $referrerId,
            'referee_id'     => null,
            'owner_id'       => $ownerId,
            'referral_code'  => $code,
            'status'         => RewardSources::REFERRAL_PENDING,
            'referee_name'   => $name,
            'referee_mobile' => $mobile,
            'referee_email'  => $email !== '' ? $email : null,
            'referee_nid'    => $nid !== '' ? $nid : null,
            'package_id'     => $packageId ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ])) {
            return $this->respondError('Could not complete registration. Please try again.', 500, 'REQUEST_FAILED');
        }
        $referralId = (int) $refModel->getInsertID();

        // --- notify the referrer (best-effort) -------------------------
        $msg = RewardMessages::referralRegistered($name);
        (new RewardNotifier())->notify($referrerId, RewardSources::NOTIFY_REFERRAL, $msg['title'], $msg['body'], [
            'ref_type'   => 'referral',
            'ref_id'     => $referralId,
            'action_url' => '/referral',
        ]);

        return $this->respondSuccess([
            'message'     => 'Registration received. Your ISP will review and complete your account setup.',
            'status'      => 'pending_referral_approval',
            'referral_id' => $referralId,
        ], 201);
    }

    // ---- helpers ------------------------------------------------------

    private function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/[^0-9]/', '', $mobile);
        return (string) $digits;
    }

    private function fraudMessage(?string $reason): string
    {
        switch ($reason) {
            case 'self_referral':
                return 'You cannot refer yourself.';
            case 'duplicate_mobile':
            case 'duplicate_email':
            case 'duplicate_nid':
                return 'An account with these details already exists.';
            case 'duplicate_referral':
                return 'A referral with these details is already pending.';
            default:
                return 'This referral could not be accepted.';
        }
    }
}
