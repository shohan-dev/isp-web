<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\VoiceSmsModel;
use App\Models\VoiceEventConfig;

class VoiceSms extends BaseController
{
    protected $voice_model;
    protected $event_config_model;

    public function __construct()
    {
        $this->voice_model = model(VoiceSmsModel::class);
        $this->event_config_model = model(VoiceEventConfig::class);
    }

    /**
     * Voice SMS Index Page
     */
    public function index()
    {
        $adminId = session()->get('user_id');
        
        $messages = $this->voice_model->getMessagesForAdmin($adminId);
        $eventConfigs = $this->event_config_model->getConfigsForAdmin((int) $adminId);

        $data = [
            'title' => 'Voice SMS Management',
            'messages' => $messages,
            'current_gateway' => getSetting('default_voice_sms_gateway', ''),
            'gateways' => ['bulksmsbd', 'greenwebsms', 'smsq', 'telnet', 'bulksmsdhaka', 'awajdigital'],
            'configs' => $eventConfigs,
            'events' => [
                'user_created'    => ['label' => 'User / Customer Created'],
                'payment_done'    => ['label' => 'Payment / Renewal Done'],
                'user_expired'    => ['label' => 'Subscription Expired'],
                'expiry_notice'   => ['label' => 'Expiry Reminder (Before Exp.)'],
                'employee_create' => ['label' => 'Employee Account Created'],
                'employee_pay'    => ['label' => 'Employee Salary Payment'],
                'password_reset'  => ['label' => 'Password Reset (Customer)'],
            ],
        ];

        return view('voice_sms/index', $data);
    }

    /**
     * Add new voice message
     */
    public function addMessage()
    {
        $adminId = session()->get('user_id');
        $name = $this->request->getPost('name');
        $msgId = $this->request->getPost('message_id');

        if (empty($name) || empty($msgId)) {
            return requestResponse('error', 'Name and Message ID are required', 400);
        }

        $data = [
            'admin_id' => $adminId,
            'name' => $name,
            'message_id' => $msgId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->voice_model->insert($data);
            return requestResponse('success', 'Voice message added successfully', 200);
        } catch (\Exception $e) {
            log_message('error', 'Voice message insert failed: ' . $e->getMessage());
            return requestResponse('error', 'Database Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update existing voice message
     */
    public function updateMessage()
    {
        $adminId = session()->get('user_id');
        $id = $this->request->getPost('id');
        $name = $this->request->getPost('name');
        $msgId = $this->request->getPost('message_id');

        if (empty($id) || empty($name) || empty($msgId)) {
            return requestResponse('error', 'All fields are required', 400);
        }

        try {
            $this->voice_model
                ->where('admin_id', $adminId)
                ->where('id', $id)
                ->set(['name' => $name, 'message_id' => $msgId])
                ->update();

            return requestResponse('success', 'Voice message updated successfully', 200);
        } catch (\Exception $e) {
            log_message('error', 'Voice message update failed: ' . $e->getMessage());
            return requestResponse('error', 'Update Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete voice message
     */
    public function deleteMessage($id)
    {
        $adminId = session()->get('user_id');
        $this->voice_model
            ->where('id', $id)
            ->where('admin_id', $adminId)
            ->delete();

        return redirect()->to(route_to('route.voice-sms'))->with('success', 'Message deleted');
    }

    /**
     * Save Voice Gateway
     */
    public function saveGateway()
    {
        $gateway = $this->request->getPost('default_voice_sms_gateway');
        if (empty($gateway)) {
            return requestResponse('error', 'Select a gateway', 400);
        }

        $saved = setSetting(['default_voice_sms_gateway' => $gateway]);

        if ($saved) {
            return requestResponse('success', 'Voice SMS gateway updated to: ' . ucwords($gateway), 200);
        } else {
            return requestResponse('error', 'Failed to save settings. Check system logs.', 500);
        }
    }

    /**
     * Save Voice Event Configs
     */
    public function saveEventConfig()
    {
        $adminId = session()->get('user_id');
        $events = ['user_created', 'payment_done', 'user_expired', 'expiry_notice', 'employee_create', 'employee_pay', 'password_reset'];

        try {
            foreach ($events as $event) {
                $templateId = $this->request->getPost("template_{$event}") ?: null;
                $isEnabled  = $this->request->getPost("enabled_{$event}") ? 1 : 0;
                if ($templateId !== null) $templateId = (int) $templateId;

                $this->event_config_model->upsert((int) $adminId, $event, $templateId, $isEnabled);
            }
            return requestResponse('success', 'Voice event settings saved successfully', 200);
        } catch (\Exception $e) {
            log_message('error', 'Voice event config save failed: ' . $e->getMessage());
            return requestResponse('error', 'Failed to save event configs: ' . $e->getMessage(), 500);
        }
    }

    public function new()
    {
        $area_model = model('App\Models\Area');
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        $user_model = model('App\Models\User');

        if ($userRole === 'super_admin') {
            $customers = $user_model->where(['role' => 'admin', 'status' => 'active'])->findAll();
        } else {
            $customers = $user_model->where(['role' => 'user', 'status' => 'active'])->where('admin_id', $userId)->findAll();
        }

        $data = [
            'title' => 'New Voice SMS',
            'area' => $area_model->where('user_id', $userId)->findAll(),
            'customers' => $customers,
            'voice_messages' => $this->voice_model->getMessagesForAdmin($userId),
        ];

        return view('voice_sms/new', $data);
    }

    public function create()
    {
        if (!function_exists('curl_init')) {
            return requestResponse('error', 'CURL extension is not enabled on this server. Please enable it in php.ini', 500);
        }

        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        $sendTo = $this->request->getPost('send_to'); // Array of IDs or 'all'
        $voiceMsgId = $this->request->getPost('voice_msg_id');

        if (empty($sendTo) || empty($voiceMsgId)) {
            return requestResponse('error', 'Recipients and Voice Message are required', 400);
        }

        $user_model = model('App\Models\User');
        $query = $user_model->where('status', 'active');

        if ($userRole === 'super_admin') {
            $query->where('role', 'admin');
        } else {
            $query->where('role', 'user')->where('admin_id', $userId);
        }

        if (!in_array('all', (array) $sendTo)) {
            $query->whereIn('id', (array) $sendTo);
        }

        $customers = $query->select('mobile')->findAll();
        $numbers = array_filter(array_column($customers, 'mobile'));

        if (empty($numbers)) {
            return requestResponse('error', 'No valid mobile numbers found for selected recipients', 400);
        }

        try {
            $gateway = getSetting('default_voice_sms_gateway', '');
            if ($gateway !== 'awajdigital') {
                return requestResponse('error', 'Voice sending is only supported via Awaj Digital currently.', 400);
            }

            $awaj = new \App\Libraries\AwajDigital($userId);
            $result = $awaj->sendBroadcast($numbers, $voiceMsgId);

            if ($result['status'] === 'success') {
                return requestResponse('success', 'Voice broadcast initiated successfully to ' . count($numbers) . ' recipients.', 200);
            } else {
                return requestResponse('error', 'Gateway Error: ' . $result['logs'], 500);
            }
        } catch (\Exception $e) {
            log_message('error', 'Voice broadcast failed: ' . $e->getMessage());
            return requestResponse('error', 'Server Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fetch voices from the active gateway (Awaj Digital)
     */
    public function getGatewayVoices()
    {
        $gateway = getSetting('default_voice_sms_gateway', '');
        if ($gateway !== 'awajdigital') {
            return requestResponse('error', 'Fetching voices is only supported for Awaj Digital currently.', 400);
        }

        $userId = session()->get('user_id');
        try {
            $awaj = new \App\Libraries\AwajDigital($userId);
            $result = $awaj->getVoices();

            if (isset($result['success']) && $result['success']) {
                return requestResponse('success', $result['voices'], 200);
            }

            return requestResponse('error', $result['message'] ?? 'Failed to fetch voices', 500);
        } catch (\Exception $e) {
            return requestResponse('error', 'Library Error: ' . $e->getMessage(), 500);
        }
    }
}
