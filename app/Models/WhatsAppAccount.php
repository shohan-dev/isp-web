<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppAccount extends Model
{
    protected $table            = 'whatsapp_accounts';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'admin_user_id',
        'phone_number_id',
        'display_phone',
        'waba_id',
        'access_token',
        'verify_token',
        'app_secret',
        'is_active',
        'ai_enabled',
        'utility_enabled',
        'authentication_enabled',
        'marketing_enabled',
        'otp_channel',
    ];

    public function findByPhoneNumberId(string $phoneNumberId): ?object
    {
        return $this->where('phone_number_id', $phoneNumberId)->first();
    }

    public function findByAdminUserId(int $adminUserId): ?object
    {
        return $this->where('admin_user_id', $adminUserId)->first();
    }

    public function findByVerifyToken(string $verifyToken): ?object
    {
        return $this->where('verify_token', $verifyToken)->first();
    }

    /**
     * Public-safe account payload (tokens masked).
     */
    public function toPublicArray(?object $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $token = (string) ($row->access_token ?? '');
        $masked = $token === '' ? '' : (str_repeat('*', max(0, strlen($token) - 4)) . substr($token, -4));

        return [
            'id'                      => (int) $row->id,
            'admin_user_id'           => (int) $row->admin_user_id,
            'phone_number_id'         => (string) $row->phone_number_id,
            'display_phone'           => (string) ($row->display_phone ?? ''),
            'waba_id'                 => (string) ($row->waba_id ?? ''),
            'access_token_masked'     => $masked,
            'has_access_token'        => $token !== '',
            'verify_token'            => (string) ($row->verify_token ?? ''),
            'has_app_secret'          => !empty($row->app_secret),
            'is_active'               => (int) ($row->is_active ?? 0) === 1,
            'ai_enabled'              => (int) ($row->ai_enabled ?? 0) === 1,
            'utility_enabled'         => (int) ($row->utility_enabled ?? 0) === 1,
            'authentication_enabled'  => (int) ($row->authentication_enabled ?? 0) === 1,
            'marketing_enabled'       => (int) ($row->marketing_enabled ?? 0) === 1,
            'otp_channel'             => (string) ($row->otp_channel ?? 'sms'),
            'updated_at'              => $row->updated_at ?? null,
        ];
    }

    /**
     * Internal payload for AI Chatbot / Graph send (includes secrets).
     */
    public function toInternalArray(?object $row): ?array
    {
        if ($row === null) {
            return null;
        }

        return [
            'id'                     => (int) $row->id,
            'admin_user_id'          => (int) $row->admin_user_id,
            'phone_number_id'        => (string) $row->phone_number_id,
            'display_phone'          => (string) ($row->display_phone ?? ''),
            'waba_id'                => (string) ($row->waba_id ?? ''),
            'access_token'           => (string) ($row->access_token ?? ''),
            'verify_token'           => (string) ($row->verify_token ?? ''),
            'app_secret'             => (string) ($row->app_secret ?? ''),
            'is_active'              => (int) ($row->is_active ?? 0) === 1,
            'ai_enabled'             => (int) ($row->ai_enabled ?? 0) === 1,
            'utility_enabled'        => (int) ($row->utility_enabled ?? 0) === 1,
            'authentication_enabled' => (int) ($row->authentication_enabled ?? 0) === 1,
            'marketing_enabled'      => (int) ($row->marketing_enabled ?? 0) === 1,
            'otp_channel'            => (string) ($row->otp_channel ?? 'sms'),
        ];
    }
}
