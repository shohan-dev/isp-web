<?php

namespace App\Controllers;

use App\Libraries\WhatsAppBusiness;

class WhatsAppSettings extends BaseController
{
    /**
     * ACL + platform entitlement gate.
     * Super Admin always passes entitlement for platform management UI.
     *
     * @return true|\CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface
     */
    protected function requireWhatsAppAccess(string $aclAction = 'read', bool $json = false)
    {
        if (!userHasPermission('whatsapp_business', $aclAction)) {
            return $this->denyWhatsApp('Unauthorized', 403, $json);
        }

        $role = (string) (session()->get('user_role') ?? '');
        $isPlatform = function_exists('isPlatformSuperAdmin')
            ? isPlatformSuperAdmin($role)
            : strtolower($role) === 'super_admin';

        if ($isPlatform) {
            return true;
        }

        if (!WhatsAppBusiness::currentTenantHasWhatsAppAccess()) {
            return $this->denyWhatsApp(
                'WhatsApp is not enabled for your account. Contact the platform owner.',
                403,
                $json
            );
        }

        return true;
    }

    /**
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface
     */
    protected function denyWhatsApp(string $message, int $status, bool $json)
    {
        if ($json || $this->request->isAJAX()) {
            return requestResponse('error', $message, $status);
        }

        return redirect()->to('/')->with('error', $message);
    }

    protected function isPlatformOwner(): bool
    {
        $role = (string) (session()->get('user_role') ?? '');

        return function_exists('isPlatformSuperAdmin')
            ? isPlatformSuperAdmin($role)
            : strtolower($role) === 'super_admin';
    }

    /**
     * @return list<array<string,mixed>>
     */
    protected function buildSecondAdminEntitlementRows(): array
    {
        $users = model('App\Models\User')
            ->groupStart()
            ->where('role', 'admin')
            ->orWhere('role', 'sAdmin')
            ->groupEnd()
            ->orderBy('name', 'ASC')
            ->findAll();

        $entMap = model('App\Models\WhatsAppEntitlement')->mapByAdminUserId();
        $acctModel = model('App\Models\WhatsAppAccount');
        $rows = [];

        foreach ($users as $u) {
            $id = (int) (is_object($u) ? $u->id : $u['id']);
            $ent = $entMap[$id] ?? null;
            $account = $acctModel->findByAdminUserId($id);
            $rows[] = [
                'admin_user_id'  => $id,
                'name'           => (string) (is_object($u) ? ($u->name ?? '') : ($u['name'] ?? '')),
                'mobile'         => (string) (is_object($u) ? ($u->mobile ?? '') : ($u['mobile'] ?? '')),
                'email'          => (string) (is_object($u) ? ($u->email ?? '') : ($u['email'] ?? '')),
                'enabled'        => $ent !== null && (int) ($ent->enabled ?? 0) === 1,
                'granted_at'     => $ent->granted_at ?? null,
                'has_account'    => $account !== null,
                'is_active'      => $account !== null && (int) ($account->is_active ?? 0) === 1,
                'display_phone'  => $account->display_phone ?? '',
                'phone_number_id'=> $account->phone_number_id ?? '',
            ];
        }

        return $rows;
    }

    public function index()
    {
        $gate = $this->requireWhatsAppAccess('read');
        if ($gate !== true) {
            return $gate;
        }

        $adminId = WhatsAppBusiness::resolveTenantAdminId();
        $model   = model('App\Models\WhatsAppAccount');
        $account = $model->findByAdminUserId($adminId);
        $isPlatform = $this->isPlatformOwner();

        if (strtolower($this->request->getMethod()) === 'post') {
            if (!userHasPermission('whatsapp_business', 'update')) {
                return requestResponse('error', 'Unauthorized', 403);
            }
            if (!$isPlatform && !WhatsAppBusiness::isWhatsAppEntitled($adminId)) {
                return requestResponse('error', 'WhatsApp is not enabled for your account', 403);
            }

            $phoneNumberId = trim((string) $this->request->getPost('phone_number_id'));
            if ($phoneNumberId === '') {
                return requestResponse('error', 'Phone Number ID is required', 422);
            }

            $existingToken = $account->access_token ?? '';
            $newToken      = trim((string) $this->request->getPost('access_token'));
            $accessToken   = $newToken !== '' ? $newToken : $existingToken;

            $existingSecret = $account->app_secret ?? '';
            $newSecret      = trim((string) $this->request->getPost('app_secret'));
            $appSecret      = $newSecret !== '' ? $newSecret : $existingSecret;

            $data = [
                'admin_user_id'          => $adminId,
                'phone_number_id'        => $phoneNumberId,
                'display_phone'          => trim((string) $this->request->getPost('display_phone')),
                'waba_id'                => trim((string) $this->request->getPost('waba_id')),
                'access_token'           => $accessToken,
                'verify_token'           => trim((string) $this->request->getPost('verify_token')),
                'app_secret'             => $appSecret,
                'is_active'              => $this->request->getPost('is_active') ? 1 : 0,
                'ai_enabled'             => $this->request->getPost('ai_enabled') ? 1 : 0,
                'utility_enabled'        => $this->request->getPost('utility_enabled') ? 1 : 0,
                'authentication_enabled' => $this->request->getPost('authentication_enabled') ? 1 : 0,
                'marketing_enabled'      => $this->request->getPost('marketing_enabled') ? 1 : 0,
                'otp_channel'            => trim((string) ($this->request->getPost('otp_channel') ?: 'sms')),
            ];

            if ($account) {
                $model->update($account->id, $data);
            } else {
                $model->insert($data);
            }

            return requestResponse('succes', 'WhatsApp settings saved', 200);
        }

        $data = [
            'title'         => 'WhatsApp Business',
            'account'       => $model->toPublicArray($account),
            'webhook_url'   => WhatsAppBusiness::webhookPublicUrl(),
            'can_update'    => userHasPermission('whatsapp_business', 'update'),
            'can_ai'        => userHasPermission('whatsapp_business', 'ai'),
            'can_utility'   => userHasPermission('whatsapp_business', 'utility'),
            'can_auth'      => userHasPermission('whatsapp_business', 'authentication'),
            'can_marketing' => userHasPermission('whatsapp_business', 'marketing'),
            'is_platform'   => $isPlatform,
            'entitlements'  => $isPlatform ? $this->buildSecondAdminEntitlementRows() : [],
        ];

        return view('whatsapp/settings', $data);
    }

    /**
     * Super Admin only — grant / revoke WhatsApp for a Second Admin.
     */
    public function setEntitlement()
    {
        if (!$this->isPlatformOwner()) {
            return requestResponse('error', 'Unauthorized', 403);
        }
        if (!userHasPermission('whatsapp_business', 'update') && !userHasPermission('whatsapp_business', 'read')) {
            // Super Admin bypasses ACL in userHasPermission — this is belt-and-suspenders.
            return requestResponse('error', 'Unauthorized', 403);
        }

        $adminUserId = (int) ($this->request->getPost('admin_user_id') ?? 0);
        $enabled     = (int) ($this->request->getPost('enabled') ?? 0) === 1;

        if ($adminUserId <= 0 || !WhatsAppBusiness::isSecondAdminUser($adminUserId)) {
            return requestResponse('error', 'Select a valid Second Admin', 422);
        }

        $grantedBy = (int) (session()->get('user_id') ?? 0);
        model('App\Models\WhatsAppEntitlement')->setEnabled($adminUserId, $enabled, $grantedBy ?: null);

        return requestResponse(
            'succes',
            $enabled
                ? 'WhatsApp enabled for this Second Admin'
                : 'WhatsApp disabled for this Second Admin',
            200
        );
    }

    public function inbox()
    {
        $gate = $this->requireWhatsAppAccess('read');
        if ($gate !== true) {
            return $gate;
        }

        $adminId = WhatsAppBusiness::resolveInboxAdminId();
        $isPlatform = $this->isPlatformOwner();
        $tenantOptions = [];
        if ($isPlatform) {
            foreach ($this->buildSecondAdminEntitlementRows() as $row) {
                if (!empty($row['enabled'])) {
                    $tenantOptions[] = $row;
                }
            }
        }

        $data = [
            'title'           => 'WhatsApp Inbox',
            'admin_id'        => $adminId,
            'can_send'        => userHasPermission('whatsapp_business', 'update')
                && ($isPlatform || WhatsAppBusiness::isWhatsAppEntitled($adminId)),
            'is_platform'     => $isPlatform,
            'tenant_options'  => $tenantOptions,
            'selected_tenant' => $adminId,
        ];

        return view('whatsapp/inbox', $data);
    }

    public function conversations()
    {
        $gate = $this->requireWhatsAppAccess('read', true);
        if ($gate !== true) {
            return $gate;
        }

        $adminId = WhatsAppBusiness::resolveInboxAdminId();
        $url = WhatsAppBusiness::chatbotBaseUrl() . '/api/whatsapp/conversations?tenant_admin_id=' . $adminId;
        $payload = $this->proxyChatbotGet($url);

        return $this->response->setJSON($payload['body'])->setStatusCode($payload['status'] ?: 200);
    }

    public function conversation(string $id)
    {
        $gate = $this->requireWhatsAppAccess('read', true);
        if ($gate !== true) {
            return $gate;
        }

        $adminId = WhatsAppBusiness::resolveInboxAdminId();
        $url = WhatsAppBusiness::chatbotBaseUrl() . '/api/whatsapp/conversations/' . rawurlencode($id)
            . '?tenant_admin_id=' . $adminId;
        $payload = $this->proxyChatbotGet($url);

        return $this->response->setJSON($payload['body'])->setStatusCode($payload['status'] ?: 200);
    }

    public function send()
    {
        $gate = $this->requireWhatsAppAccess('update', true);
        if ($gate !== true) {
            return $gate;
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $to = WhatsAppBusiness::normalizePhone((string) ($json['to'] ?? ''));
        $text = trim((string) ($json['text'] ?? ''));
        if ($to === '' || $text === '') {
            return requestResponse('error', 'to and text are required', 422);
        }

        // Sends always use the inbox-scoped tenant (supports Super Admin oversight).
        $adminId = WhatsAppBusiness::resolveInboxAdminId(
            isset($json['tenant_admin_id']) ? (int) $json['tenant_admin_id'] : null
        );
        if (!WhatsAppBusiness::isWhatsAppEntitled($adminId) && !$this->isPlatformOwner()) {
            return requestResponse('error', 'WhatsApp is not enabled for this account', 403);
        }
        // Platform send into another tenant requires that tenant to be entitled.
        if ($this->isPlatformOwner() && $adminId !== WhatsAppBusiness::resolveTenantAdminId()) {
            if (!WhatsAppBusiness::isWhatsAppEntitled($adminId)) {
                return requestResponse('error', 'Target Second Admin is not entitled', 403);
            }
        }

        $account = WhatsAppBusiness::getAccountForAdmin($adminId);
        if (!$account || !(int) $account->is_active) {
            return requestResponse('error', 'WhatsApp account inactive or missing', 400);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $result = WhatsAppBusiness::sendViaChatbot([
            'phone_number_id' => $account->phone_number_id,
            'access_token'    => $account->access_token,
            'to'              => $to,
            'type'            => 'text',
            'text'            => $text,
        ]);

        $wamid = is_array($result['body']) ? ($result['body']['messages'][0]['id'] ?? null) : null;
        WhatsAppBusiness::logMessage([
            'admin_user_id'    => $adminId,
            'wa_phone'         => $to,
            'category'         => 'SERVICE',
            'direction'        => 'out',
            'wamid'            => $wamid,
            'status'           => $result['ok'] ? 'sent' : 'failed',
            'error_code'       => $result['ok'] ? null : (string) ($result['error'] ?? 'error'),
            'billable'         => 0,
            'payload_redacted' => mb_substr($text, 0, 200),
        ]);

        if (!$result['ok']) {
            return requestResponse('error', (string) ($result['error'] ?? 'Send failed'), 502);
        }

        return requestResponse('succes', ['message' => 'Message sent', 'data' => $result['body']], 200);
    }

    public function messageLog()
    {
        $gate = $this->requireWhatsAppAccess('read');
        if ($gate !== true) {
            return $gate;
        }

        $adminId = WhatsAppBusiness::resolveTenantAdminId();
        $logs = model('App\Models\WhatsAppMessageLog')
            ->where('admin_user_id', $adminId)
            ->orderBy('id', 'DESC')
            ->findAll(100);

        return view('whatsapp/message_log', [
            'title' => 'WhatsApp Message Log',
            'logs'  => $logs,
        ]);
    }

    public function templates()
    {
        if (!userHasPermission('whatsapp_business', 'read')
            && !userHasPermission('whatsapp_business', 'utility')
            && !userHasPermission('whatsapp_business', 'authentication')
            && !userHasPermission('whatsapp_business', 'marketing')) {
            return redirect()->to('/')->with('error', 'Unauthorized');
        }
        if (!$this->isPlatformOwner() && !WhatsAppBusiness::currentTenantHasWhatsAppAccess()) {
            return redirect()->to('/')->with('error', 'WhatsApp is not enabled for your account. Contact the platform owner.');
        }

        $adminId = WhatsAppBusiness::resolveTenantAdminId();

        if (strtolower($this->request->getMethod()) === 'post') {
            if (!userHasPermission('whatsapp_business', 'update')) {
                return requestResponse('error', 'Unauthorized', 403);
            }

            $action = (string) $this->request->getPost('action');
            $tplModel = model('App\Models\WhatsAppTemplate');

            if ($action === 'save') {
                $id = (int) $this->request->getPost('id');
                $row = [
                    'admin_user_id'   => $adminId,
                    'name'            => trim((string) $this->request->getPost('name')),
                    'language'        => trim((string) ($this->request->getPost('language') ?: 'en')),
                    'category'        => strtoupper(trim((string) $this->request->getPost('category'))),
                    'status'          => strtoupper(trim((string) ($this->request->getPost('status') ?: 'PENDING'))),
                    'event_key'       => trim((string) $this->request->getPost('event_key')) ?: null,
                    'body_preview'    => trim((string) $this->request->getPost('body_preview')),
                    'is_enabled'      => $this->request->getPost('is_enabled') ? 1 : 0,
                    'meta_template_id'=> trim((string) $this->request->getPost('meta_template_id')) ?: null,
                ];
                if ($id > 0) {
                    $existing = $tplModel->where(['id' => $id, 'admin_user_id' => $adminId])->first();
                    if (!$existing) {
                        return requestResponse('error', 'Template not found', 404);
                    }
                    $tplModel->update($id, $row);
                } else {
                    $tplModel->insert($row);
                }
                return requestResponse('succes', 'Template saved', 200);
            }

            if ($action === 'delete') {
                $id = (int) $this->request->getPost('id');
                $tplModel->where('admin_user_id', $adminId)->delete($id);
                return requestResponse('succes', 'Template deleted', 200);
            }
        }

        $templates = model('App\Models\WhatsAppTemplate')
            ->where('admin_user_id', $adminId)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('whatsapp/templates', [
            'title'     => 'WhatsApp Templates',
            'templates' => $templates,
            'can_update'=> userHasPermission('whatsapp_business', 'update'),
        ]);
    }

    public function optIns()
    {
        if (!userHasPermission('whatsapp_business', 'marketing') && !userHasPermission('whatsapp_business', 'read')) {
            return redirect()->to('/')->with('error', 'Unauthorized');
        }
        if (!$this->isPlatformOwner() && !WhatsAppBusiness::currentTenantHasWhatsAppAccess()) {
            return redirect()->to('/')->with('error', 'WhatsApp is not enabled for your account. Contact the platform owner.');
        }

        $adminId = WhatsAppBusiness::resolveTenantAdminId();

        if (strtolower($this->request->getMethod()) === 'post') {
            if (!userHasPermission('whatsapp_business', 'marketing')) {
                return requestResponse('error', 'Unauthorized', 403);
            }
            $phone = WhatsAppBusiness::normalizePhone((string) $this->request->getPost('wa_phone'));
            $optIn = $this->request->getPost('marketing_opt_in') ? 1 : 0;
            if ($phone === '') {
                return requestResponse('error', 'Phone required', 422);
            }
            $model = model('App\Models\WhatsAppOptIn');
            $existing = $model->where(['admin_user_id' => $adminId, 'wa_phone' => $phone])->first();
            $row = [
                'admin_user_id'    => $adminId,
                'wa_phone'         => $phone,
                'marketing_opt_in' => $optIn,
                'source'           => 'manual',
                'opted_in_at'      => $optIn ? date('Y-m-d H:i:s') : null,
                'opted_out_at'     => $optIn ? null : date('Y-m-d H:i:s'),
            ];
            if ($existing) {
                $model->update($existing->id, $row);
            } else {
                $model->insert($row);
            }
            return requestResponse('succes', 'Opt-in updated', 200);
        }

        $rows = model('App\Models\WhatsAppOptIn')
            ->where('admin_user_id', $adminId)
            ->orderBy('id', 'DESC')
            ->findAll(200);

        return view('whatsapp/opt_ins', [
            'title'  => 'WhatsApp Marketing Opt-ins',
            'rows'   => $rows,
            'can_edit' => userHasPermission('whatsapp_business', 'marketing'),
        ]);
    }

    public function campaigns()
    {
        if (!userHasPermission('whatsapp_business', 'marketing') && !userHasPermission('whatsapp_business', 'read')) {
            return redirect()->to('/')->with('error', 'Unauthorized');
        }
        if (!$this->isPlatformOwner() && !WhatsAppBusiness::currentTenantHasWhatsAppAccess()) {
            return redirect()->to('/')->with('error', 'WhatsApp is not enabled for your account. Contact the platform owner.');
        }

        $adminId = WhatsAppBusiness::resolveTenantAdminId();

        if (strtolower($this->request->getMethod()) === 'post') {
            if (!userHasPermission('whatsapp_business', 'marketing')) {
                return requestResponse('error', 'Unauthorized', 403);
            }

            $action = (string) $this->request->getPost('action');
            $campModel = model('App\Models\WhatsAppCampaign');

            if ($action === 'create') {
                $campModel->insert([
                    'admin_user_id' => $adminId,
                    'template_id'   => (int) $this->request->getPost('template_id') ?: null,
                    'name'          => trim((string) $this->request->getPost('name')),
                    'status'        => 'draft',
                    'audience_json' => json_encode(['all_opted_in' => true]),
                    'stats_json'    => json_encode(['sent' => 0, 'failed' => 0]),
                ]);
                return requestResponse('succes', 'Campaign created', 200);
            }

            if ($action === 'launch') {
                $id = (int) $this->request->getPost('id');
                $campaign = $campModel->where(['id' => $id, 'admin_user_id' => $adminId])->first();
                if (!$campaign) {
                    return requestResponse('error', 'Campaign not found', 404);
                }
                $launched = $this->launchCampaign((int) $campaign->id, $adminId);
                return requestResponse(
                    $launched['ok'] ? 'succes' : 'error',
                    ['message' => $launched['message'], 'stats' => $launched['stats'] ?? null],
                    $launched['ok'] ? 200 : 400
                );
            }
        }

        $campaigns = model('App\Models\WhatsAppCampaign')
            ->where('admin_user_id', $adminId)
            ->orderBy('id', 'DESC')
            ->findAll();
        $templates = model('App\Models\WhatsAppTemplate')
            ->where(['admin_user_id' => $adminId, 'category' => 'MARKETING', 'status' => 'APPROVED'])
            ->findAll();

        return view('whatsapp/campaigns', [
            'title'     => 'WhatsApp Campaigns',
            'campaigns' => $campaigns,
            'templates' => $templates,
            'can_edit'  => userHasPermission('whatsapp_business', 'marketing'),
        ]);
    }

    /**
     * @return array{ok:bool,message:string,stats?:array}
     */
    protected function launchCampaign(int $campaignId, int $adminId): array
    {
        if (!WhatsAppBusiness::isWhatsAppEntitled($adminId) && !$this->isPlatformOwner()) {
            return ['ok' => false, 'message' => 'WhatsApp is not enabled for this account'];
        }

        $account = WhatsAppBusiness::getAccountForAdmin($adminId);
        if (!$account || !(int) $account->is_active || !(int) $account->marketing_enabled) {
            return ['ok' => false, 'message' => 'WhatsApp marketing not active'];
        }

        $campaign = model('App\Models\WhatsAppCampaign')->find($campaignId);
        if (!$campaign || (int) $campaign->admin_user_id !== $adminId) {
            return ['ok' => false, 'message' => 'Campaign not found'];
        }

        $template = model('App\Models\WhatsAppTemplate')->find($campaign->template_id);
        if (!$template || strtoupper((string) $template->category) !== 'MARKETING' || $template->status !== 'APPROVED') {
            return ['ok' => false, 'message' => 'Approved marketing template required'];
        }

        $optIns = model('App\Models\WhatsAppOptIn')
            ->where(['admin_user_id' => $adminId, 'marketing_opt_in' => 1])
            ->findAll();

        $sent = 0;
        $failed = 0;
        $recipModel = model('App\Models\WhatsAppCampaignRecipient');
        model('App\Models\WhatsAppCampaign')->update($campaignId, ['status' => 'running']);

        foreach ($optIns as $opt) {
            $to = WhatsAppBusiness::normalizePhone((string) $opt->wa_phone);
            $result = WhatsAppBusiness::sendViaChatbot([
                'phone_number_id' => $account->phone_number_id,
                'access_token'    => $account->access_token,
                'to'              => $to,
                'type'            => 'template',
                'template'        => [
                    'name'     => $template->name,
                    'language' => ['code' => $template->language ?: 'en'],
                    'components' => [],
                ],
            ]);
            $wamid = is_array($result['body']) ? ($result['body']['messages'][0]['id'] ?? null) : null;
            $recipModel->insert([
                'campaign_id' => $campaignId,
                'wa_phone'    => $to,
                'status'      => $result['ok'] ? 'sent' : 'failed',
                'wamid'       => $wamid,
                'error'       => $result['ok'] ? null : (string) ($result['error'] ?? 'error'),
            ]);
            WhatsAppBusiness::logMessage([
                'admin_user_id'    => $adminId,
                'wa_phone'         => $to,
                'category'         => 'MARKETING',
                'direction'        => 'out',
                'template_name'    => $template->name,
                'wamid'            => $wamid,
                'status'           => $result['ok'] ? 'sent' : 'failed',
                'error_code'       => $result['ok'] ? null : (string) ($result['error'] ?? 'error'),
                'billable'         => 1,
                'payload_redacted' => 'campaign:' . $campaign->name,
            ]);
            if ($result['ok']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $stats = ['sent' => $sent, 'failed' => $failed];
        model('App\Models\WhatsAppCampaign')->update($campaignId, [
            'status'     => 'done',
            'stats_json' => json_encode($stats),
        ]);

        return ['ok' => true, 'message' => "Campaign finished: {$sent} sent, {$failed} failed", 'stats' => $stats];
    }

    /**
     * @return array{status:int,body:mixed}
     */
    protected function proxyChatbotGet(string $url): array
    {
        // Auth already done by callers — release session before ≤20s curl.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $key = WhatsAppBusiness::internalKey();
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array_filter([
                $key !== '' ? 'X-ISP-Internal-Key: ' . $key : null,
            ]),
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode((string) $raw, true);
        return ['status' => $code ?: 502, 'body' => $decoded ?? ['status' => 'error', 'message' => 'Chatbot unreachable']];
    }
}
