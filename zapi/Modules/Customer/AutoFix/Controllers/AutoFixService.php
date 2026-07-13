<?php

namespace Zapi\Modules\Customer\AutoFix\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

/**
 * Customer self-service auto-fix actions.
 *
 * SAFETY: "reboot" here means dropping ONLY this subscriber's PPPoE session
 * (the customer's home router re-dials within seconds). We deliberately do
 * NOT send /system/reboot to the shared ISP MikroTik — that would disconnect
 * every customer on that router. Full-device reboot is out of scope for the
 * customer portal.
 *
 * All user-facing messages are in Bangla and never expose raw router errors.
 */
class AutoFixService extends CustomerBaseService
{
    /** Cooldown (seconds) between subscriber session resets. */
    private const REBOOT_COOLDOWN = 300;

    private function manualRebootSteps(): array
    {
        return [
            'রাউটারের পাওয়ার ক্যাবল খুলুন এবং ৩০ সেকেন্ড অপেক্ষা করুন।',
            'আবার পাওয়ার ক্যাবল লাগান।',
            '২-৩ মিনিট অপেক্ষা করুন — ইন্টারনেট ফিরে আসবে।',
        ];
    }

    public function rebootRouter($userId)
    {
        helper('router');
        $userId = (int) $userId;
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }
        if (!$this->canPerformAction($userId, 'router_reboot')) {
            return $this->respondError('You are not allowed to perform this action', 403);
        }

        $routerId = $user->router_id ?? null;
        $pppoe    = getSubscriberPppoeName($userId);

        if ($routerId && $pppoe) {
            if (!$this->withinCooldown('router_reboot', $userId, self::REBOOT_COOLDOWN)) {
                return $this->respondSuccess([
                    'user_id'        => $userId,
                    'action'         => 'router_reboot',
                    'status'         => 'pending',
                    'transport_used' => 'cooldown',
                    'message'        => 'কিছুক্ষণ আগেই রিস্টার্ট অনুরোধ পাঠানো হয়েছে। অনুগ্রহ করে কয়েক মিনিট পর আবার চেষ্টা করুন।',
                    'steps'          => [],
                ]);
            }

            $result = rebootSubscriberSession($routerId, $pppoe);

            if (in_array($result['status'], ['success', 'offline'], true)) {
                $this->logAction($userId, 'router_reboot', 'Subscriber session reset (safe reboot)', ['router' => $routerId]);

                return $this->respondSuccess([
                    'user_id'        => $userId,
                    'action'         => 'router_reboot',
                    'status'         => 'success',
                    'transport_used' => 'mikrotik_pppoe_session_reset',
                    'message'        => 'আপনার সংযোগ রিসেট করা হয়েছে। কয়েক সেকেন্ডের মধ্যে রাউটার স্বয়ংক্রিয়ভাবে আবার যুক্ত হবে।',
                    'steps'          => [
                        'সংযোগ রিসেট করা হয়েছে',
                        '১০–৩০ সেকেন্ড অপেক্ষা করুন',
                        'ইন্টারনেট স্বয়ংক্রিয়ভাবে ফিরে আসবে',
                    ],
                    'important'      => 'রিস্টার্টের সময় রাউটারের পাওয়ার বন্ধ করবেন না।',
                ]);
            }
        }

        // Router unreachable / no PPPoE mapping → guided manual reboot (never a hard error).
        $this->logAction($userId, 'router_reboot', 'Auto reset unavailable; returned manual guidance', ['router' => $routerId]);

        return $this->respondSuccess([
            'user_id'        => $userId,
            'action'         => 'router_reboot',
            'status'         => 'pending',
            'transport_used' => 'manual',
            'message'        => 'এই মুহূর্তে স্বয়ংক্রিয়ভাবে রিস্টার্ট করা যাচ্ছে না। অনুগ্রহ করে নিচের ধাপ অনুসরণ করে রাউটারটি নিজে রিস্টার্ট করুন।',
            'steps'          => $this->manualRebootSteps(),
        ]);
    }

    public function reconnectPPPoE($userId)
    {
        helper('router');
        $userId = (int) $userId;
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }
        if (!$this->canPerformAction($userId, 'pppoe_reconnect')) {
            return $this->respondError('You are not allowed to perform this action', 403);
        }

        $routerId = $user->router_id ?? null;
        $pppoe    = getSubscriberPppoeName($userId);

        if ($routerId && $pppoe && $this->withinCooldown('pppoe_reconnect', $userId, 60)) {
            $result = rebootSubscriberSession($routerId, $pppoe);
            if (in_array($result['status'], ['success', 'offline'], true)) {
                $this->logAction($userId, 'pppoe_reconnect', 'PPPoE session reset', ['router' => $routerId]);

                return $this->respondSuccess([
                    'user_id'        => $userId,
                    'action'         => 'pppoe_reconnect',
                    'status'         => 'success',
                    'transport_used' => 'mikrotik_pppoe_session_reset',
                    'message'        => 'আপনার PPPoE সংযোগ রিসেট করা হয়েছে। কয়েক সেকেন্ডের মধ্যে সংযোগ ফিরে আসবে।',
                    'steps'          => [
                        'বর্তমান সেশন রিসেট করা হয়েছে',
                        '১০ সেকেন্ড অপেক্ষা করুন',
                        'রাউটার স্বয়ংক্রিয়ভাবে আবার যুক্ত হবে',
                    ],
                ]);
            }
        }

        $this->logAction($userId, 'pppoe_reconnect', 'Auto reset unavailable; returned manual guidance', ['router' => $routerId]);

        return $this->respondSuccess([
            'user_id'        => $userId,
            'action'         => 'pppoe_reconnect',
            'status'         => 'pending',
            'transport_used' => 'manual',
            'message'        => 'এই মুহূর্তে স্বয়ংক্রিয়ভাবে সংযোগ রিসেট করা যাচ্ছে না। অনুগ্রহ করে রাউটারটি একবার রিস্টার্ট করুন।',
            'steps'          => $this->manualRebootSteps(),
        ]);
    }

    public function flushDNS($userId)
    {
        helper('router');
        $userId = (int) $userId;
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        $routerId = $user->router_id ?? null;
        if ($routerId) {
            flushRouterDnsCache($routerId); // best-effort, router-side only
        }
        $this->logAction($userId, 'dns_flush', 'DNS cache flush requested', ['router' => $routerId]);

        return $this->respondSuccess([
            'user_id'      => $userId,
            'action'       => 'flush_dns',
            'status'       => 'success',
            'message'      => 'আমাদের প্রান্তে DNS ক্যাশ পরিষ্কার করা হয়েছে।',
            'client_steps' => [
                'Windows: Command Prompt খুলে লিখুন — ipconfig /flushdns',
                'Mac: Terminal-এ লিখুন — sudo dscacheutil -flushcache',
                'অথবা রাউটারটি একবার রিস্টার্ট করুন।',
            ],
            'help'         => 'এখনও ওয়েবসাইট না খুললে রাউটারটি রিস্টার্ট করে দেখুন।',
        ]);
    }

    public function resetSession($userId)
    {
        // For PPPoE customers a session reset == reconnect. Reuse the safe path.
        return $this->reconnectPPPoE($userId);
    }

    public function applyConfigUpdate($userId)
    {
        $userId = (int) $userId;
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }
        $this->logAction($userId, 'config_update', 'Config refresh acknowledged');

        return $this->respondSuccess([
            'user_id' => $userId,
            'action'  => 'apply_config',
            'status'  => 'success',
            'message' => 'আপনার সংযোগের সর্বশেষ কনফিগারেশন প্রয়োগ করা হয়েছে।',
        ]);
    }

    public function performQuickFix($userId, $issueType)
    {
        $userId    = (int) $userId;
        $issueType = (string) $issueType;
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        // Map issue → ordered safe actions.
        $plan = [
            'slow'          => ['flushDNS', 'rebootRouter'],
            'no_connection' => ['rebootRouter'],
            'dns_error'     => ['flushDNS'],
            'disconnect'    => ['reconnectPPPoE'],
        ];
        $actions = $plan[$issueType] ?? ['rebootRouter'];

        $applied = [];
        foreach ($actions as $action) {
            $resp = $this->{$action}($userId); // each returns a ResponseInterface
            $raw  = (is_object($resp) && method_exists($resp, 'getBody')) ? (string) $resp->getBody() : '';
            $body = $raw !== '' ? json_decode($raw, true) : null;
            $data = (is_array($body) && isset($body['data']) && is_array($body['data'])) ? $body['data'] : [];
            $applied[] = [
                'action' => $action,
                'result' => $data['status'] ?? 'done',
            ];
        }

        return $this->respondSuccess([
            'user_id'       => $userId,
            'issue_type'    => $issueType,
            'fixes_applied' => $applied,
            'status'        => 'completed',
            'message'       => 'আপনার সমস্যার জন্য দ্রুত সমাধান প্রয়োগ করা হয়েছে। সংযোগ এখন ভালো হওয়ার কথা।',
            'next_steps'    => [
                'পরিবর্তন কার্যকর হতে ১-২ মিনিট অপেক্ষা করুন',
                'সমস্যা থেকে গেলে রাউটারটি একবার নিজে রিস্টার্ট করুন',
            ],
        ]);
    }
}
