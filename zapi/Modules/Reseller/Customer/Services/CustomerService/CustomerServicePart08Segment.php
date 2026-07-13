<?php

namespace Zapi\Modules\Reseller\Customer\Services\CustomerService;

use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CustomerServicePart08Segment
{
        /**
         * POST /api/reseller/customers/(:resellerId)/(:customerId)/mac-bind
         * Same router flow as web customers/mac_ajax …/bind (bindMacForUser).
         */
        public function macBind($resellerId = null, $customerId = null)
        {
            if (empty($resellerId) || empty($customerId)) {
                return $this->respondError((string) 'Missing reseller id or customer id', 400, 'REQUEST_FAILED');
            }
    
            if ($this->resellerCustomerRow($resellerId, $customerId) === null) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            if (!function_exists('bindMacForUser')) {
                helper('router');
            }
    
            $result = bindMacForUser((int) $customerId);
            $ok = $this->bindMacResultIsSuccess($result);
            if (!$ok) {
                $msg = is_array($result) ? ($result['message'] ?? 'MAC bind failed') : 'MAC bind failed';
                log_message('warning', 'mac-bind failed user=' . $customerId . ' msg=' . $msg);
    
                return $this->respondPayload([
                    'status' => 'error',
                    'message' => $msg,
                    'error' => 'mac_bind_failed',
                ], 400);
            }
    
            $updated = $this->user_model
                ->where('id', (int) $customerId)
                ->where('admin_id', (int) $resellerId)
                ->where('role', 'user')
                ->first();
    
            $payload = $this->enrichCustomer($updated, true);
            $binding = $this->getMacBinding((int) $customerId);
            $payload['mac_binding'] = $binding;
            $payload['mac_bound'] = (bool) ($binding['is_bound'] ?? false);
            $payload['mac_address'] = $this->asString($binding['mac'] ?? '--');
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => is_array($result) ? ($result['message'] ?? 'MAC bound') : 'MAC bound',
                'data' => $payload,
            ]);
        }
    
        /**
         * POST /api/reseller/customers/(:resellerId)/(:customerId)/mac-unbind
         * Same router flow as web customers/mac_ajax …/unbind (removeMacBind).
         */
        public function macUnbind($resellerId = null, $customerId = null)
        {
            if (empty($resellerId) || empty($customerId)) {
                return $this->respondError((string) 'Missing reseller id or customer id', 400, 'REQUEST_FAILED');
            }
    
            if ($this->resellerCustomerRow($resellerId, $customerId) === null) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            if (!function_exists('removeMacBind')) {
                helper('router');
            }
    
            if (!removeMacBind((int) $customerId)) {
                return $this->respondError((string) 'Failed to remove MAC binding', 400, 'REQUEST_FAILED');
            }
    
            $updated = $this->user_model
                ->where('id', (int) $customerId)
                ->where('admin_id', (int) $resellerId)
                ->where('role', 'user')
                ->first();
    
            $payload = $this->enrichCustomer($updated, true);
            $binding = $this->getMacBinding((int) $customerId);
            $payload['mac_binding'] = $binding;
            $payload['mac_bound'] = (bool) ($binding['is_bound'] ?? false);
            $payload['mac_address'] = $this->asString($binding['mac'] ?? '--');
    
            return $this->respondSuccess(['message' => 'MAC unbound', 'payload' => $payload,]);
        }
    
        /**
         * GET /api/reseller/customers/(:resellerId)/(:customerId)/audit-logs
         * Query: from, to, per_page, page, pppoe_name (optional filter on audit client field)
         */
        public function auditLogs($resellerId = null, $customerId = null)
        {
            if (empty($resellerId) || empty($customerId)) {
                return $this->respondError((string) 'Missing reseller id or customer id', 400, 'REQUEST_FAILED');
            }
    
            $customer = $this->user_model
                ->where('id', (int) $customerId)
                ->where('admin_id', (int) $resellerId)
                ->where('role', 'user')
                ->first();
    
            if (empty($customer)) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            $from = $this->request->getGet('from');
            $to = $this->request->getGet('to');
            $perPage = (int) ($this->request->getGet('per_page') ?? 25);
            if ($perPage < 1) {
                $perPage = 25;
            }
            if ($perPage > 100) {
                $perPage = 100;
            }
    
            $pppoeFilter = $this->request->getGet('pppoe_name');
            if ($pppoeFilter !== null) {
                $pppoeFilter = trim((string) $pppoeFilter);
                if ($pppoeFilter === '') {
                    $pppoeFilter = null;
                }
            }
    
            $auditModel = new AuditLogModel();
            $logs = $auditModel->getFiltered($from, $to, $perPage, (int) $customerId, $pppoeFilter);
            $pager = $auditModel->pager;
    
            $rows = [];
            foreach ($logs as $row) {
                $r = is_object($row) ? get_object_vars($row) : (array) $row;
                $rows[] = [
                    'id' => isset($r['id']) ? (int) $r['id'] : null,
                    'user_id' => isset($r['user_id']) ? (int) $r['user_id'] : null,
                    'action' => $r['action'] ?? null,
                    'entity' => $r['entity'] ?? null,
                    'client' => $r['client'] ?? null,
                    'router' => $r['router'] ?? null,
                    'details' => $r['details'] ?? null,
                    'actor' => $r['actor'] ?? null,
                    'ip_address' => $r['ip_address'] ?? null,
                    'user_agent' => $r['user_agent'] ?? null,
                    'created_at' => $r['created_at'] ?? null,
                ];
            }
    
            return $this->respondSuccess([
                    'logs' => $rows,
                    'pager' => [
                        'current_page' => $pager->getCurrentPage(),
                        'per_page' => $perPage,
                        'total' => $pager->getTotal(),
                        'page_count' => $pager->getPageCount(),
                    ],
                    'filters' => [
                        'from' => $from,
                        'to' => $to,
                        'pppoe_name' => $pppoeFilter,
                    ],
                ],);
        }
    
        /**
         * POST /api/reseller/customers/{resellerId}/pppoe-status
         * Body: {router_id, pppoe_ids: []}
         */
        public function pppoeStatus($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
            $routerId = (int) ($input['router_id'] ?? 0);
            $pppoeIds = $input['pppoe_ids'] ?? [];
            if (!is_array($pppoeIds)) {
                $pppoeIds = array_filter(array_map('trim', explode(',', (string) $pppoeIds)), 'strlen');
            }
            $pppoeIds = array_values(array_unique(array_filter(array_map(function ($id) {
                return trim((string) $id);
            }, $pppoeIds), 'strlen')));
    
            if ($routerId <= 0 || empty($pppoeIds)) {
                return $this->respondError((string) 'router_id and pppoe_ids are required', 400, 'REQUEST_FAILED');
            }
    
            if (!function_exists('routerClient') || !function_exists('getusersSystemResources')) {
                return $this->respondError((string) 'Router helper functions are not available', 500, 'REQUEST_FAILED');
            }
    
            $routerClient = routerClient($routerId);
            if (!($routerClient instanceof \RouterOS\Client)) {
                $error = is_array($routerClient) ? ($routerClient['error'] ?? 'Router connection failed') : 'Router connection failed';
                return $this->respondError((string) $error, 500, 'REQUEST_FAILED');
            }
    
            $activeMap = [];
            foreach ($pppoeIds as $pppoeId) {
                $activeMap[$pppoeId] = false;
            }
    
            try {
                $resource = getusersSystemResources($routerClient, '', '');
                $activeUsers = $resource['data']['activeusers'] ?? [];
                if (is_array($activeUsers)) {
                    foreach ($activeUsers as $activeUser) {
                        if (!is_array($activeUser)) {
                            continue;
                        }
                        $name = trim((string) ($activeUser['name'] ?? ''));
                        if ($name !== '' && array_key_exists($name, $activeMap)) {
                            $activeMap[$name] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'PPPoE status lookup failed: ' . $e->getMessage());
                return $this->respondError((string) 'Failed to fetch PPPoE status', 500, 'REQUEST_FAILED');
            }
    
            $items = [];
            foreach ($activeMap as $pppoeId => $isOnline) {
                $items[] = [
                    'pppoe_id' => $pppoeId,
                    'status' => $isOnline ? 'online' : 'offline',
                    'is_online' => $isOnline,
                ];
            }
    
            return $this->respondSuccess([
                'router_id' => $routerId,
                'count' => count($items),
                'items' => $items,
            ]);
        }
    
        /**
         * POST /api/reseller/customers/{resellerId}/update-pop
         * Body: {customer_id, sub_area_id}
         */
        public function updatePop($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
            $customerId = (int) ($input['customer_id'] ?? $input['id'] ?? 0);
            $subAreaId = (int) ($input['sub_area_id'] ?? 0);
    
            if ($customerId <= 0 || $subAreaId <= 0) {
                return $this->respondError((string) 'customer_id and sub_area_id are required', 400, 'REQUEST_FAILED');
            }
    
            $customer = $this->resellerCustomerRow($resellerId, $customerId);
            if ($customer === null) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            $this->saveConnectionDetails($customerId, ['sub_area_id' => $subAreaId]);
            $updated = $this->user_model->find($customerId);
    
            return $this->respondSuccess([
                'message' => 'POP updated successfully',
                'payload' => $this->enrichCustomer($updated, false),
            ]);
        }
    
        /**
         * POST /api/reseller/customers/{resellerId}/bulk-update-pop
         * Body: {ids:[], sub_area_id}
         */
        public function bulkUpdatePop($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
            $ids = $this->normalizeIds($input['ids'] ?? null);
            $subAreaId = (int) ($input['sub_area_id'] ?? 0);
            if (empty($ids) || $subAreaId <= 0) {
                return $this->respondError((string) 'ids and sub_area_id are required', 400, 'REQUEST_FAILED');
            }
    
            $success = [];
            $failed = [];
            foreach ($ids as $customerId) {
                try {
                    $this->saveConnectionDetails((int) $customerId, ['sub_area_id' => $subAreaId]);
                    $success[] = (int) $customerId;
                } catch (\Throwable $e) {
                    $failed[] = ['id' => (int) $customerId, 'message' => $e->getMessage()];
                }
            }
    
            return $this->respondSuccess([
                'updated_count' => count($success),
                'failed_count' => count($failed),
                'updated_ids' => $success,
                'failed' => $failed,
                'sub_area_id' => $subAreaId,
            ]);
        }
    
        /**
         * POST /api/reseller/customers/{resellerId}/update-router
         * Body: {customer_id, router_id}
         */
        public function updateRouter($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
            $customerId = (int) ($input['customer_id'] ?? $input['id'] ?? 0);
            $routerId = (int) ($input['router_id'] ?? 0);
            if ($customerId <= 0 || $routerId <= 0) {
                return $this->respondError((string) 'customer_id and router_id are required', 400, 'REQUEST_FAILED');
            }
    
            $customer = $this->resellerCustomerRow($resellerId, $customerId);
            if ($customer === null) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            $this->user_model
                ->where('id', (int) $customerId)
                ->where('admin_id', (int) $resellerId)
                ->where('role', 'user')
                ->set(['router_id' => $routerId])
                ->update();
            $updated = $this->user_model->find($customerId);
    
            return $this->respondSuccess([
                'message' => 'Router updated successfully',
                'payload' => $this->enrichCustomer($updated, false),
            ]);
        }
    
        /**
         * POST /api/reseller/customers/{resellerId}/bulk-update-router
         * Body: {ids:[], router_id}
         */
        public function bulkUpdateRouter($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
            $ids = $this->normalizeIds($input['ids'] ?? null);
            $routerId = (int) ($input['router_id'] ?? 0);
            if (empty($ids) || $routerId <= 0) {
                return $this->respondError((string) 'ids and router_id are required', 400, 'REQUEST_FAILED');
            }
    
            $matched = $this->user_model
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId)
                ->whereIn('id', $ids)
                ->findAll();
            $matchedIds = [];
            foreach ($matched as $row) {
                $arr = $this->toArray($row);
                if (!empty($arr['id'])) {
                    $matchedIds[] = (int) $arr['id'];
                }
            }
    
            if (empty($matchedIds)) {
                return $this->respondError((string) 'No matching customers found', 404, 'REQUEST_FAILED');
            }
    
            $this->user_model
                ->whereIn('id', $matchedIds)
                ->set(['router_id' => $routerId])
                ->update();
    
            return $this->respondSuccess([
                'updated_count' => count($matchedIds),
                'updated_ids' => $matchedIds,
                'router_id' => $routerId,
            ]);
        }
    
}
