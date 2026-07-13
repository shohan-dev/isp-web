<?php

namespace Zapi\Modules\Reseller\Sms\Services\SmsService;

trait SmsServicePart01Segment
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
         * GET /api/reseller/sms/{resellerId}
         * List SMS history for the reseller.
         */
        public function fetch($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $pager = $this->getPaginationParams();
            $builder = $this->sms_model->builder()
                ->select('id, datetime, content, send_to, status, logs')
                ->where('user_id', $resellerId);
            $totalFound = (int) $builder->countAllResults(false);
            $rows = $builder
                ->orderBy('id', 'desc')
                ->limit($pager['per_page'], $pager['offset'])
                ->get()
                ->getResultArray();
    
            return $this->respondPaginatedSuccess($rows, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
        /**
         * GET /api/reseller/sms/{resellerId}/recipients
         * Get customers available as SMS recipients, grouped by area.
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
    
            $templateModel = model('App\Models\SmsTemplateModel');
            $templates = $templateModel
                ->where(['template_type' => 'default'])
                ->orWhere('user_id', $resellerId)
                ->findAll();
    
            $tmplArr = [];
            foreach ($templates as $t) {
                $t = is_array($t) ? $t : (array) $t;
                $tmplArr[] = [
                    'id' => (string) ($t['id'] ?? ''),
                    'title' => $t['title'] ?? $t['name'] ?? '',
                    'content' => $t['content'] ?? $t['template_body'] ?? '',
                ];
            }
    
            $custArr = [];
            foreach ($customers as $c) {
                $c = is_array($c) ? $c : (array) $c;
                $custArr[] = [
                    'id' => (string) ($c['id'] ?? ''),
                    'name' => $c['name'] ?? '',
                    'mobile' => $c['mobile'] ?? '',
                    'area_id' => (string) ($c['area_id'] ?? ''),
                    'area_name' => $areaMap[(string) ($c['area_id'] ?? '')] ?? '',
                    'status' => $c['subscription_status'] ?? 'inactive',
                ];
            }
            $pager = $this->getPaginationParams();
            $totalFound = count($custArr);
            $pagedCustomers = array_slice($custArr, $pager['offset'], $pager['limit']);
    
            return $this->respondSuccess([
                    'customers' => $pagedCustomers,
                    'areas' => array_map(function ($id, $name) {
                        return ['id' => (string) $id, 'name' => $name];
                    }, array_keys($areaMap), array_values($areaMap)),
                    'templates' => $tmplArr,
                    'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedCustomers)),
                ],);
        }
    
        /**
         * POST /api/reseller/sms/{resellerId}/send
         * Send SMS to selected customers.
         *
         * Body: { content, send_to: ['all'] or [id,...], area?: id }
         */
        public function send($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
    
            $content = trim($input['content'] ?? '');
            if (empty($content)) {
                return $this->respondPayload([
                    'status' => 'validation-error',
                    'message' => 'Enter SMS content',
                ], 400);
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
    
            if (in_array('all', $sendTo)) {
                if (!empty($areaId)) {
                    $condition['area_id'] = (int) $areaId;
                }
                $users = $this->user_model->where($condition)->asArray()->findAll();
            } else {
                $ids = array_filter($sendTo, fn($v) => is_numeric($v));
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
                $personalContent = $this->personaliseContent($content, $user);
    
                $row = [
                    'user_id' => $resellerId,
                    'datetime' => date('Y-m-d H:i:s'),
                    'content' => $personalContent,
                    'send_to' => ($user['name'] ?? '') . ' (' . ($user['mobile'] ?? '--') . ')',
                ];
                $this->sms_model->insert($row, false);
                $insertedId = $this->sms_model->getInsertID();
    
                $result = Send_SMs([$user], null, null, null, $personalContent, $insertedId);
    
                if (!empty($result['status']) && $result['status'] === 'success') {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
    
            $total = count($users);
            if ($successCount === $total) {
                return $this->respondPayload([
                    'status' => 'success',
                    'message' => "SMS sent to all {$total} customer(s) successfully",
                    'sent' => $successCount,
                    'failed' => $failCount,
                ]);
            } elseif ($successCount > 0) {
                return $this->respondPayload([
                    'status' => 'success',
                    'message' => "SMS sent to {$successCount}/{$total} customer(s). {$failCount} failed.",
                    'sent' => $successCount,
                    'failed' => $failCount,
                ]);
            } else {
                return $this->respondPayload([
                    'status' => 'error',
                    'message' => 'SMS could not be sent. Check logs for details.',
                    'sent' => 0,
                    'failed' => $total,
                ], 500);
            }
        }
    
        /**
         * DELETE /api/reseller/sms/{resellerId}
         * Delete SMS records by IDs.
         */
        public function delete($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
            $ids = $input['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }
    
            $this->sms_model->where('user_id', $resellerId)->whereIn('id', $ids)->delete();
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'SMS record(s) deleted',
            ]);
        }
    
        private function personaliseContent(string $content, array $user): string
        {
            $userId = $user['id'] ?? 0;
            $packageAmount = '--';
    
            if (!empty($userId)) {
                $packageModel = model('App\Models\Package');
                $packageId = $user['package_id'] ?? null;
                if (!empty($packageId)) {
                    $pkg = $packageModel->find($packageId);
                    if (!empty($pkg)) {
                        $pkgArr = is_array($pkg) ? $pkg : (array) $pkg;
                        $packageAmount = $pkgArr['price'] ?? '--';
                    }
                }
            }
    
            $replacements = [
                '{name}' => $user['name'] ?? '',
                '{mobile}' => $user['mobile'] ?? '',
                '{email}' => $user['email'] ?? '',
                '{address}' => $user['address'] ?? '',
                '{package_amount}' => $packageAmount,
                '{due_amount}' => $user['due_amount'] ?? '0',
                '{balance}' => $user['balance'] ?? '0',
                '{expire_date}' => $user['will_expire'] ?? '--',
            ];
    
            return str_replace(array_keys($replacements), array_values($replacements), $content);
        }
    
}
