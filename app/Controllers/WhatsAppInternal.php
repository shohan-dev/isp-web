<?php

namespace App\Controllers;

use App\Libraries\WhatsAppBusiness;
use CodeIgniter\RESTful\ResourceController;

/**
 * Service-to-service WhatsApp APIs for the AI Chatbot (X-ISP-Internal-Key).
 */
class WhatsAppInternal extends ResourceController
{
    protected function assertInternalKey(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        $expected = WhatsAppBusiness::internalKey();
        $provided = (string) ($this->request->getHeaderLine('X-ISP-Internal-Key') ?: '');

        // If no key configured, allow only from localhost (dev).
        if ($expected === '') {
            $ip = $this->request->getIPAddress();
            if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
                return $this->failUnauthorized('Internal key not configured');
            }
            return null;
        }

        if (!hash_equals($expected, $provided)) {
            return $this->failUnauthorized('Invalid internal key');
        }

        return null;
    }

    public function accountByPhoneId(string $phoneNumberId)
    {
        if ($deny = $this->assertInternalKey()) {
            return $deny;
        }

        $row = model('App\Models\WhatsAppAccount')->findByPhoneNumberId($phoneNumberId);
        if (!$row) {
            return $this->failNotFound('Account not found');
        }

        return $this->respond([
            'status'  => 'success',
            'account' => model('App\Models\WhatsAppAccount')->toInternalArray($row),
        ]);
    }

    public function accountByVerifyToken()
    {
        if ($deny = $this->assertInternalKey()) {
            return $deny;
        }

        $token = (string) ($this->request->getGet('verify_token') ?? '');
        if ($token === '') {
            return $this->failValidationErrors('verify_token required');
        }

        $row = model('App\Models\WhatsAppAccount')->findByVerifyToken($token);
        if (!$row) {
            return $this->failNotFound('Account not found');
        }

        return $this->respond([
            'status'  => 'success',
            'account' => model('App\Models\WhatsAppAccount')->toInternalArray($row),
        ]);
    }

    public function resolveCustomer()
    {
        if ($deny = $this->assertInternalKey()) {
            return $deny;
        }

        $adminUserId = (int) ($this->request->getGet('admin_user_id') ?? 0);
        $waPhone     = WhatsAppBusiness::normalizePhone((string) ($this->request->getGet('wa_phone') ?? ''));
        if ($adminUserId <= 0 || $waPhone === '') {
            return $this->failValidationErrors('admin_user_id and wa_phone required');
        }

        $userModel = model('App\Models\User');
        // Match mobile / whatsapp_number with flexible leading zeros / country code
        $candidates = array_unique(array_filter([
            $waPhone,
            ltrim($waPhone, '0'),
            preg_replace('/^88/', '', $waPhone),
            preg_replace('/^880/', '0', $waPhone),
            '0' . preg_replace('/^88/', '', $waPhone),
        ]));

        $builder = $userModel->builder();
        $builder->groupStart();
        foreach ($candidates as $i => $c) {
            if ($i === 0) {
                $builder->where('mobile', $c)->orWhere('whatsapp_number', $c);
            } else {
                $builder->orWhere('mobile', $c)->orWhere('whatsapp_number', $c);
            }
        }
        $builder->groupEnd();
        $builder->groupStart()
            ->where('admin_id', $adminUserId)
            ->orWhere('id', $adminUserId)
            ->groupEnd();
        $user = $builder->get()->getRow();

        if (!$user) {
            return $this->respond([
                'status'   => 'success',
                'customer' => null,
            ]);
        }

        return $this->respond([
            'status'   => 'success',
            'customer' => [
                'user_id' => (int) $user->id,
                'name'    => (string) ($user->name ?? ''),
                'mobile'  => (string) ($user->mobile ?? ''),
                'email'   => (string) ($user->email ?? ''),
                'status'  => (string) ($user->status ?? ''),
            ],
        ]);
    }

    public function notifyEscalation()
    {
        if ($deny = $this->assertInternalKey()) {
            return $deny;
        }

        $json = $this->request->getJSON(true) ?? [];
        $adminUserId = (int) ($json['admin_user_id'] ?? 0);
        $summary     = (string) ($json['summary'] ?? 'WhatsApp escalation');
        $waPhone     = (string) ($json['wa_phone'] ?? '');
        $customerId  = $json['customer_user_id'] ?? null;

        log_message('warning', "WhatsApp escalation admin={$adminUserId} phone={$waPhone} customer={$customerId}: {$summary}");

        // Best-effort support ticket if helper/model exists
        try {
            if ($customerId && class_exists('\App\Models\SupportTicket')) {
                model('App\Models\SupportTicket')->insert([
                    'user_id'  => (int) $customerId,
                    'admin_id' => $adminUserId,
                    'subject'  => 'WhatsApp escalation',
                    'message'  => $summary,
                    'status'   => 'open',
                    'priority' => 'high',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'WhatsApp escalation ticket failed: ' . $e->getMessage());
        }

        return $this->respond(['status' => 'success']);
    }

    public function logMessage()
    {
        if ($deny = $this->assertInternalKey()) {
            return $deny;
        }

        $json = $this->request->getJSON(true) ?? [];
        WhatsAppBusiness::logMessage($json);

        return $this->respond(['status' => 'success']);
    }

    public function marketingOptOut()
    {
        if ($deny = $this->assertInternalKey()) {
            return $deny;
        }

        $json = $this->request->getJSON(true) ?? [];
        $adminUserId = (int) ($json['admin_user_id'] ?? 0);
        $waPhone = WhatsAppBusiness::normalizePhone((string) ($json['wa_phone'] ?? ''));
        if ($adminUserId <= 0 || $waPhone === '') {
            return $this->failValidationErrors('admin_user_id and wa_phone required');
        }

        $model = model('App\Models\WhatsAppOptIn');
        $existing = $model->where(['admin_user_id' => $adminUserId, 'wa_phone' => $waPhone])->first();
        $row = [
            'admin_user_id'    => $adminUserId,
            'wa_phone'         => $waPhone,
            'marketing_opt_in' => 0,
            'opted_out_at'     => date('Y-m-d H:i:s'),
            'source'           => 'keyword',
        ];
        if ($existing) {
            $model->update($existing->id, $row);
        } else {
            $model->insert($row);
        }

        return $this->respond(['status' => 'success']);
    }
}
