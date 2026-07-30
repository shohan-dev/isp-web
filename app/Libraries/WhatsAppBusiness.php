<?php

namespace App\Libraries;

/**
 * WhatsApp Business helpers — tenant account, Graph send via AI Chatbot, logging.
 */
class WhatsAppBusiness
{
    public static function chatbotBaseUrl(): string
    {
        $url = getenv('WHATSAPP_CHATBOT_URL') ?: getenv('AI_CHATBOT_URL') ?: '';
        if ($url === '') {
            // Match AiChatController / AI Chatbot local PORT default when unset.
            $url = 'http://127.0.0.1:8000';
        }

        return rtrim($url, '/');
    }

    public static function internalKey(): string
    {
        return (string) (getenv('ISP_INTERNAL_KEY') ?: getenv('WHATSAPP_INTERNAL_KEY') ?: '');
    }

    public static function webhookPublicUrl(): string
    {
        $override = getenv('WHATSAPP_WEBHOOK_PUBLIC_URL');
        if (!empty($override)) {
            return rtrim((string) $override, '/');
        }

        return self::chatbotBaseUrl() . '/api/whatsapp/webhook';
    }

    public static function resolveTenantAdminId(?int $userId = null, ?string $role = null): int
    {
        $userId = $userId ?? (int) (session()->get('user_id') ?? 0);
        $role   = $role ?? (string) (session()->get('user_role') ?? '');

        if ($role === 'admin' || $role === 'sAdmin' || $role === 'super_admin') {
            return $userId;
        }

        $userModel = model('App\Models\User');
        $user = $userModel->find($userId);
        if (!$user) {
            return $userId;
        }

        $adminId = is_object($user) ? ($user->admin_id ?? null) : ($user['admin_id'] ?? null);
        return (int) ($adminId ?: $userId);
    }

    /**
     * True when $userId is a Second Admin (tenant ISP owner).
     */
    public static function isSecondAdminUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $user = model('App\Models\User')->find($userId);
        if (!$user) {
            return false;
        }
        $role = strtolower((string) (is_object($user) ? ($user->role ?? '') : ($user['role'] ?? '')));

        return $role === 'admin' || $role === 'sadmin';
    }

    /**
     * Platform Super Admin is always entitled for their own account.
     * Second Admins need an enabled row in whatsapp_entitlements.
     */
    public static function isWhatsAppEntitled(int $adminUserId): bool
    {
        if ($adminUserId <= 0) {
            return false;
        }

        $user = model('App\Models\User')->find($adminUserId);
        if (!$user) {
            return false;
        }
        $role = strtolower((string) (is_object($user) ? ($user->role ?? '') : ($user['role'] ?? '')));
        if ($role === 'super_admin') {
            return true;
        }

        return model('App\Models\WhatsAppEntitlement')->isEnabledFor($adminUserId);
    }

    /**
     * ACL is checked by the caller; this gates platform entitlement of the
     * resolved tenant admin (Second Admin) for the current session user.
     */
    public static function currentTenantHasWhatsAppAccess(): bool
    {
        $adminId = self::resolveTenantAdminId();

        return self::isWhatsAppEntitled($adminId);
    }

    /**
     * Inbox / conversation scope. Super Admin may pass ?tenant_admin_id= to
     * oversee an entitled Second Admin's inbox; everyone else uses own tenant.
     */
    public static function resolveInboxAdminId(?int $overrideTenantAdminId = null): int
    {
        $own = self::resolveTenantAdminId();
        $role = (string) (session()->get('user_role') ?? '');
        $isPlatform = function_exists('isPlatformSuperAdmin')
            ? isPlatformSuperAdmin($role)
            : strtolower($role) === 'super_admin';

        if (!$isPlatform) {
            return $own;
        }

        $override = $overrideTenantAdminId;
        if ($override === null) {
            $override = (int) (service('request')->getGet('tenant_admin_id') ?? 0);
        }
        if ($override > 0
            && self::isSecondAdminUser($override)
            && self::isWhatsAppEntitled($override)
        ) {
            return $override;
        }

        return $own;
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        // Bangladesh local 01xxxxxxxxx → 8801xxxxxxxxx
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '88' . $digits;
        }
        return $digits;
    }

    public static function getAccountForAdmin(int $adminUserId): ?object
    {
        return model('App\Models\WhatsAppAccount')->findByAdminUserId($adminUserId);
    }

    /**
     * Send text or template through AI Chatbot Graph client.
     *
     * @param array<string,mixed> $payload
     * @return array{ok:bool,status:int,body:array|string,error?:string}
     */
    public static function sendViaChatbot(array $payload): array
    {
        $url = self::chatbotBaseUrl() . '/api/whatsapp/send';
        $key = self::internalKey();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                $key !== '' ? 'X-ISP-Internal-Key: ' . $key : null,
            ]),
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => $err ?: 'curl_failed'];
        }

        $decoded = json_decode($raw, true);
        return [
            'ok'     => $code >= 200 && $code < 300,
            'status' => $code,
            'body'   => is_array($decoded) ? $decoded : $raw,
            'error'  => $code >= 200 && $code < 300 ? null : ($decoded['detail'] ?? $decoded['message'] ?? 'send_failed'),
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function logMessage(array $data): void
    {
        try {
            model('App\Models\WhatsAppMessageLog')->insert([
                'admin_user_id'    => (int) ($data['admin_user_id'] ?? 0),
                'customer_user_id' => $data['customer_user_id'] ?? null,
                'wa_phone'         => (string) ($data['wa_phone'] ?? ''),
                'category'         => (string) ($data['category'] ?? 'SERVICE'),
                'direction'        => (string) ($data['direction'] ?? 'out'),
                'template_name'    => $data['template_name'] ?? null,
                'wamid'            => $data['wamid'] ?? null,
                'status'           => (string) ($data['status'] ?? 'queued'),
                'error_code'       => $data['error_code'] ?? null,
                'billable'         => (int) ($data['billable'] ?? 0),
                'payload_redacted' => $data['payload_redacted'] ?? null,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'WhatsAppBusiness::logMessage failed: ' . $e->getMessage());
        }
    }

    /**
     * Send a Utility template for a tenant event if configured and enabled.
     *
     * @param array<int,string> $bodyParams Template body variables
     */
    public static function sendUtilityEvent(int $adminUserId, string $eventKey, string $waPhone, array $bodyParams = [], ?int $customerUserId = null): bool
    {
        if (!self::isWhatsAppEntitled($adminUserId)) {
            return false;
        }

        $account = self::getAccountForAdmin($adminUserId);
        if (!$account || !(int) $account->is_active || !(int) $account->utility_enabled) {
            return false;
        }

        $template = model('App\Models\WhatsAppTemplate')->findEnabledForEvent($adminUserId, $eventKey);
        if (!$template || strtoupper((string) $template->category) !== 'UTILITY') {
            return false;
        }

        $to = self::normalizePhone($waPhone);
        if ($to === '') {
            return false;
        }

        $components = [];
        if ($bodyParams !== []) {
            $parameters = [];
            foreach ($bodyParams as $p) {
                $parameters[] = ['type' => 'text', 'text' => (string) $p];
            }
            $components[] = ['type' => 'body', 'parameters' => $parameters];
        }

        $result = self::sendViaChatbot([
            'phone_number_id' => $account->phone_number_id,
            'access_token'    => $account->access_token,
            'to'              => $to,
            'type'            => 'template',
            'template'        => [
                'name'       => $template->name,
                'language'   => ['code' => $template->language ?: 'en'],
                'components' => $components,
            ],
        ]);

        $wamid = null;
        if (is_array($result['body'])) {
            $wamid = $result['body']['messages'][0]['id'] ?? $result['body']['wamid'] ?? null;
        }

        self::logMessage([
            'admin_user_id'    => $adminUserId,
            'customer_user_id' => $customerUserId,
            'wa_phone'         => $to,
            'category'         => 'UTILITY',
            'direction'        => 'out',
            'template_name'    => $template->name,
            'wamid'            => $wamid,
            'status'           => $result['ok'] ? 'sent' : 'failed',
            'error_code'       => $result['ok'] ? null : (string) ($result['error'] ?? 'error'),
            'billable'         => 1,
            'payload_redacted' => $eventKey . ' → ' . implode(', ', $bodyParams),
        ]);

        return $result['ok'];
    }

    /**
     * Send Authentication OTP template. Returns false to allow SMS fallback.
     */
    public static function sendAuthOtp(int $adminUserId, string $waPhone, string $otpCode): bool
    {
        if (!self::isWhatsAppEntitled($adminUserId)) {
            return false;
        }

        $account = self::getAccountForAdmin($adminUserId);
        if (!$account || !(int) $account->is_active || !(int) $account->authentication_enabled) {
            return false;
        }

        $template = model('App\Models\WhatsAppTemplate')
            ->where([
                'admin_user_id' => $adminUserId,
                'category'      => 'AUTHENTICATION',
                'is_enabled'    => 1,
                'status'        => 'APPROVED',
            ])->first();

        if (!$template) {
            // Fallback: event_key otp
            $template = model('App\Models\WhatsAppTemplate')->findEnabledForEvent($adminUserId, 'otp');
            if (!$template || strtoupper((string) $template->category) !== 'AUTHENTICATION') {
                return false;
            }
        }

        $to = self::normalizePhone($waPhone);
        if ($to === '') {
            return false;
        }

        $result = self::sendViaChatbot([
            'phone_number_id' => $account->phone_number_id,
            'access_token'    => $account->access_token,
            'to'              => $to,
            'type'            => 'template',
            'template'        => [
                'name'     => $template->name,
                'language' => ['code' => $template->language ?: 'en'],
                'components' => [[
                    'type'       => 'body',
                    'parameters'  => [['type' => 'text', 'text' => $otpCode]],
                ]],
            ],
        ]);

        $masked = strlen($otpCode) > 2
            ? substr($otpCode, 0, 1) . str_repeat('*', strlen($otpCode) - 2) . substr($otpCode, -1)
            : '***';

        $wamid = is_array($result['body']) ? ($result['body']['messages'][0]['id'] ?? null) : null;
        self::logMessage([
            'admin_user_id'    => $adminUserId,
            'wa_phone'         => $to,
            'category'         => 'AUTHENTICATION',
            'direction'        => 'out',
            'template_name'    => $template->name,
            'wamid'            => $wamid,
            'status'           => $result['ok'] ? 'sent' : 'failed',
            'error_code'       => $result['ok'] ? null : (string) ($result['error'] ?? 'error'),
            'billable'         => 1,
            'payload_redacted' => 'OTP ' . $masked,
        ]);

        return $result['ok'];
    }
}
