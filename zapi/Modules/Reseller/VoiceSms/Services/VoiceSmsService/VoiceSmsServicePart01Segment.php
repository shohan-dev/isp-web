<?php

namespace Zapi\Modules\Reseller\VoiceSms\Services\VoiceSmsService;

trait VoiceSmsServicePart01Segment
{
        private function getInput(): array
        {
            $input = $this->request->getJSON(true);
            if (is_array($input) && !empty($input)) {
                return $input;
            }
    
            $input = $this->request->getPost();
            if (is_array($input) && !empty($input)) {
                return $input;
            }
    
            $body = $this->request->getBody();
            if (!empty($body)) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
    
            return [];
        }
    
        /**
         * GET /api/reseller/voice-sms/{resellerId}/recipients
         * Get customers available for voice SMS, grouped by area, and voice templates.
         */
        public function recipients($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $customers = $this->user_model
                ->select('id, name, mobile, area_id, subscription_status')
                ->where(['admin_id' => $resellerId, 'role' => 'user'])
                ->orderBy('name', 'asc')
                ->findAll();
    
            $areaModel = model('App\Models\Area');
            $areas = $areaModel->where('user_id', $resellerId)->findAll();
    
            $areaMap = [];
            foreach ($areas as $area) {
                $a = is_array($area) ? $area : (array) $area;
                $areaMap[(string) ($a['id'] ?? '')] = $a['area_name'] ?? $a['name'] ?? '';
            }
    
            $templates = $this->voice_model
                ->select('id, name, message_id')
                ->where('admin_id', $resellerId)
                ->orderBy('id', 'desc')
                ->findAll();
    
            $templateArr = [];
            foreach ($templates as $template) {
                $template = is_array($template) ? $template : (array) $template;
                $templateArr[] = [
                    'id' => (string) ($template['id'] ?? ''),
                    'title' => $template['name'] ?? '',
                    'message_id' => (string) ($template['message_id'] ?? ''),
                ];
            }
    
            $customerArr = [];
            foreach ($customers as $customer) {
                $customer = is_array($customer) ? $customer : (array) $customer;
                $customerArr[] = [
                    'id' => (string) ($customer['id'] ?? ''),
                    'name' => $customer['name'] ?? '',
                    'mobile' => $customer['mobile'] ?? '',
                    'area_id' => (string) ($customer['area_id'] ?? ''),
                    'area_name' => $areaMap[(string) ($customer['area_id'] ?? '')] ?? '',
                    'status' => $customer['subscription_status'] ?? 'inactive',
                ];
            }
            $pager = $this->getPaginationParams();
            $totalFound = count($customerArr);
            $pagedCustomers = array_slice($customerArr, $pager['offset'], $pager['limit']);
    
            return $this->respondSuccess([
                    'customers' => $pagedCustomers,
                    'areas' => array_map(function ($id, $name) {
                        return ['id' => (string) $id, 'name' => $name];
                    }, array_keys($areaMap), array_values($areaMap)),
                    'templates' => $templateArr,
                    'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedCustomers)),
                ],);
        }
    
        /**
         * POST /api/reseller/voice-sms/{resellerId}/send
         * Send voice SMS to selected customers.
         * Body: { template_id, send_to: ['all'] or [id,...], area?: id }
         */
        public function send($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
    
            $templateId = (int) ($input['template_id'] ?? 0);
            if (empty($templateId)) {
                return $this->respondPayload([
                    'status' => 'validation-error',
                    'message' => 'Select a voice template',
                ], 400);
            }
    
            $template = $this->voice_model
                ->where('admin_id', $resellerId)
                ->where('id', $templateId)
                ->first();
    
            if (empty($template)) {
                return $this->respondError((string) 'Voice template not found', 404, 'REQUEST_FAILED');
            }
    
            $template = is_array($template) ? $template : (array) $template;
            $messageId = trim((string) ($template['message_id'] ?? ''));
            if ($messageId === '') {
                return $this->respondError((string) 'Selected template is missing message_id', 400, 'REQUEST_FAILED');
            }
    
            $sendTo = $input['send_to'] ?? [];
            if (!is_array($sendTo)) {
                $sendTo = [$sendTo];
            }
            if (empty($sendTo)) {
                return $this->respondError((string) 'Select at least one recipient', 400, 'REQUEST_FAILED');
            }
    
            $areaId = $input['area'] ?? null;
            $condition = [
                'role' => 'user',
                'admin_id' => $resellerId,
                'status' => 'active',
            ];
    
            if (in_array('all', $sendTo, true)) {
                if (!empty($areaId)) {
                    $condition['area_id'] = (int) $areaId;
                }
                $users = $this->user_model->where($condition)->asArray()->findAll();
            } else {
                $ids = array_filter($sendTo, fn($value) => is_numeric($value));
                if (empty($ids)) {
                    return $this->respondError((string) 'No valid customers selected', 400, 'REQUEST_FAILED');
                }
    
                $users = $this->user_model
                    ->where(['admin_id' => $resellerId, 'role' => 'user'])
                    ->whereIn('id', $ids)
                    ->asArray()
                    ->findAll();
            }
    
            if (empty($users)) {
                return $this->respondError((string) 'No matching customers found', 400, 'REQUEST_FAILED');
            }
    
            $successCount = 0;
            $failCount = 0;
            foreach ($users as $user) {
                $mobile = trim((string) ($user['mobile'] ?? ''));
                if ($mobile === '') {
                    $failCount++;
                    continue;
                }
    
                $result = sendVoiceSms($mobile, $messageId, $resellerId);
                if (($result['status'] ?? '') === 'success') {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
    
            $total = count($users);
            if ($successCount === $total) {
                return $this->respondPayload([
                    'status' => 'success',
                    'message' => "Voice SMS sent to all {$total} customer(s) successfully",
                    'sent' => $successCount,
                    'failed' => $failCount,
                ]);
            }
    
            if ($successCount > 0) {
                return $this->respondPayload([
                    'status' => 'success',
                    'message' => "Voice SMS sent to {$successCount}/{$total} customer(s). {$failCount} failed.",
                    'sent' => $successCount,
                    'failed' => $failCount,
                ]);
            }
    
            return $this->respondPayload([
                'status' => 'error',
                'message' => 'Voice SMS could not be sent. Check gateway settings and logs.',
                'sent' => 0,
                'failed' => $total,
            ], 500);
        }
    
        public function templates($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if ($resellerId <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $rows = $this->voice_model
                ->where('admin_id', $resellerId)
                ->orderBy('id', 'desc')
                ->findAll();
    
            $templates = array_map(function ($row) {
                $item = is_array($row) ? $row : (array) $row;
                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'name' => (string) ($item['name'] ?? ''),
                    'message_id' => (string) ($item['message_id'] ?? ''),
                ];
            }, $rows);
            $pager = $this->getPaginationParams();
            $totalFound = count($templates);
            $pagedTemplates = array_slice($templates, $pager['offset'], $pager['limit']);

            return $this->respondSuccess([
                'templates' => $pagedTemplates,
                'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedTemplates)),
            ]);
        }
    
        public function createTemplate($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            $input = $this->getInput();
            $name = trim((string) ($input['name'] ?? ''));
            $messageId = trim((string) ($input['message_id'] ?? ''));
            if ($resellerId <= 0 || $name === '' || $messageId === '') {
                return $this->respondError((string) 'name and message_id are required', 400, 'REQUEST_FAILED');
            }
    
            $this->voice_model->insert([
                'admin_id' => $resellerId,
                'name' => $name,
                'message_id' => $messageId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
    
            return $this->respondSuccess(['message' => 'Template created successfully']);
        }
    
        public function updateTemplate($resellerId = null, $templateId = null)
        {
            $resellerId = (int) $resellerId;
            $templateId = (int) $templateId;
            $input = $this->getInput();
            $name = trim((string) ($input['name'] ?? ''));
            $messageId = trim((string) ($input['message_id'] ?? ''));
            if ($resellerId <= 0 || $templateId <= 0 || $name === '' || $messageId === '') {
                return $this->respondError((string) 'template_id, name and message_id are required', 400, 'REQUEST_FAILED');
            }
    
            $this->voice_model
                ->where('admin_id', $resellerId)
                ->where('id', $templateId)
                ->set(['name' => $name, 'message_id' => $messageId])
                ->update();
    
            return $this->respondSuccess(['message' => 'Template updated successfully']);
        }
    
        public function deleteTemplate($resellerId = null, $templateId = null)
        {
            $resellerId = (int) $resellerId;
            $templateId = (int) $templateId;
            if ($resellerId <= 0 || $templateId <= 0) {
                return $this->respondError((string) 'Missing reseller id or template id', 400, 'REQUEST_FAILED');
            }
    
            $this->voice_model
                ->where('admin_id', $resellerId)
                ->where('id', $templateId)
                ->delete();
    
            return $this->respondSuccess(['message' => 'Template deleted successfully']);
        }
    
        public function settings($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if ($resellerId <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $gateway = getSetting('default_voice_sms_gateway', '');
            return $this->respondSuccess([
                'default_voice_sms_gateway' => $gateway,
                'gateways' => ['bulksmsbd', 'greenwebsms', 'smsq', 'telnet', 'bulksmsdhaka', 'awajdigital'],
            ]);
        }
    
        public function updateSettings($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            $input = $this->getInput();
            $gateway = trim((string) ($input['default_voice_sms_gateway'] ?? ''));
            if ($resellerId <= 0 || $gateway === '') {
                return $this->respondError((string) 'default_voice_sms_gateway is required', 400, 'REQUEST_FAILED');
            }
    
            setSetting(['default_voice_sms_gateway' => $gateway]);
            return $this->respondSuccess(['message' => 'Voice SMS gateway updated successfully']);
        }
    
        public function updateEventConfig($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            $input = $this->getInput();
            if ($resellerId <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $events = ['user_created', 'payment_done', 'user_expired', 'expiry_notice', 'employee_create', 'employee_pay', 'password_reset'];
            foreach ($events as $event) {
                if (!array_key_exists($event, $input) || !is_array($input[$event])) {
                    continue;
                }
                $conf = $input[$event];
                $templateId = isset($conf['template_id']) && $conf['template_id'] !== '' ? (int) $conf['template_id'] : null;
                $enabled = !empty($conf['enabled']) ? 1 : 0;
                $this->event_config_model->upsert($resellerId, $event, $templateId, $enabled);
            }
    
            return $this->respondSuccess(['message' => 'Voice SMS event config updated successfully']);
        }
    
        public function gatewayVoices($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if ($resellerId <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $gateway = getSetting('default_voice_sms_gateway', '');
            if ($gateway !== 'awajdigital') {
                return $this->respondError((string) 'Fetching voices is only supported for Awaj Digital currently.', 400, 'REQUEST_FAILED');
            }
    
            try {
                $awaj = new \App\Libraries\AwajDigital($resellerId);
                $result = $awaj->getVoices();
                if (isset($result['success']) && $result['success']) {
                    $voices = is_array($result['voices'] ?? null) ? $result['voices'] : [];
                    $pager = $this->getPaginationParams();
                    $totalFound = count($voices);
                    $pagedVoices = array_slice($voices, $pager['offset'], $pager['limit']);
                    return $this->respondSuccess([
                        'voices' => $pagedVoices,
                        'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedVoices)),
                    ]);
                }
                return $this->respondError((string) ($result['message'] ?? 'Failed to fetch voices'), 500, 'REQUEST_FAILED');
            } catch (\Throwable $e) {
                return $this->respondError((string) ('Library Error: ' . $e->getMessage()), 500, 'REQUEST_FAILED');
            }
        }
    
}
