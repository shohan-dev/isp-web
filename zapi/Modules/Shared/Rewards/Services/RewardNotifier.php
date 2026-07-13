<?php

namespace Zapi\Modules\Shared\Rewards\Services;

use Zapi\Modules\Shared\Rewards\Models\AppNotificationModel;

/**
 * Fan-out notifications for referral/reward events across in-app + SMS + email.
 * Every channel is best-effort: a failure in one never throws or blocks a
 * points award (mirrors the audit-log best-effort pattern in CustomerBaseService).
 */
class RewardNotifier
{
    private AppNotificationModel $inApp;

    public function __construct()
    {
        $this->inApp = new AppNotificationModel();
    }

    /**
     * @param array $opts  ['sms'=>bool, 'email'=>bool, 'ref_type','ref_id','action_url']
     */
    public function notify(int $userId, string $type, string $title, string $body, array $opts = []): void
    {
        $ownerId = 0;
        $user = null;
        try {
            $user = model('App\Models\User')->find($userId);
            if ($user) {
                $ownerId = (int) (is_object($user) ? ($user->admin_id ?? 0) : ($user['admin_id'] ?? 0));
            }
        } catch (\Throwable $e) {
            // ignore — notification is best-effort
        }

        // 1) In-app (always)
        try {
            $this->inApp->add($userId, $type, $title, $body, [
                'owner_id'   => $ownerId,
                'ref_type'   => $opts['ref_type'] ?? null,
                'ref_id'     => $opts['ref_id'] ?? null,
                'action_url' => $opts['action_url'] ?? null,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'RewardNotifier in-app failed: ' . $e->getMessage());
        }

        if (!$user) {
            return;
        }

        $mobile = (string) (is_object($user) ? ($user->mobile ?? '') : ($user['mobile'] ?? ''));
        $email  = (string) (is_object($user) ? ($user->email ?? '')  : ($user['email'] ?? ''));

        // 2) SMS (opt-in, best-effort)
        if (($opts['sms'] ?? true) && $mobile !== '' && function_exists('sendotrSms')) {
            try {
                sendotrSms($mobile, $title . "\n" . $body, $userId);
            } catch (\Throwable $e) {
                log_message('error', 'RewardNotifier sms failed: ' . $e->getMessage());
            }
        }

        // 3) Email (opt-in, best-effort)
        if (($opts['email'] ?? true) && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && function_exists('sendMail')) {
            try {
                sendMail($email, $title, '<p>' . esc($body) . '</p>');
            } catch (\Throwable $e) {
                log_message('error', 'RewardNotifier email failed: ' . $e->getMessage());
            }
        }
    }
}
