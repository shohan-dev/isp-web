<?php

namespace Zapi\Modules\Reseller\Customer\Services\CustomerService;

use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CustomerServicePart05Segment
{
        /**
         * POST /api/reseller/customers/(:resellerId)/(:customerId)
         */
        public function update($resellerId = null, $customerId = null)
        {
            $input = $this->getInput();
    
            if (empty($resellerId) || empty($customerId)) {
                return $this->respondError((string) 'Missing reseller id or customer id', 400, 'REQUEST_FAILED');
            }
    
            $customerObj = $this->user_model
                ->where('id', (int) $customerId)
                ->where('role', 'user')
                ->first();
    
            if (empty($customerObj)) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            if ((int) $customerObj->admin_id !== (int) $resellerId) {
                return $this->respondError((string) 'Customer does not belong to reseller', 403, 'REQUEST_FAILED');
            }
    
            $customer = $this->toArray($customerObj);
            $normalizeInputValue = static function ($value) {
                $text = trim((string) $value);
                $lower = strtolower($text);
                if ($text === '' || $lower === '--' || $lower === 'null' || $lower === 'n/a') {
                    return '';
                }
                return $text;
            };
    
            $userFields = [
                'package_id',
                'pre_package',
                'area_id',
                'router_id',
                'name',
                'designation',
                'mobile',
                'nid_number',
                'email',
                'password',
                'code',
                'address',
                'pppoe_id',
                'conn_status',
                'last_renewed',
                'will_expire',
                'subscription_status',
                'auto_disconnect',
                'status',
                'created_by',
                'activity',
                'posPrinter',
            ];
    
            $data = [];
            foreach ($userFields as $field) {
                if (array_key_exists($field, $input)) {
                    $data[$field] = $input[$field];
                }
            }
    
            $connectionFields = [
                'sub_area_id',
                'connection_type',
                'cable_requirement',
                'fiber_code',
                'number_of_core',
                'core_color',
                'client_type',
                'billing_status',
                'otc',
                'otc_status',
            ];
    
            $connectionData = [];
            foreach ($connectionFields as $field) {
                if (array_key_exists($field, $input)) {
                    $connectionData[$field] = $input[$field];
                }
            }
    
            $pppoeFields = ['pppoe_name', 'pppoe_password', 'pppoe_service', 'pppoe_profile'];
            $hasPppoeInput = false;
            foreach ($pppoeFields as $field) {
                if (array_key_exists($field, $input)) {
                    $hasPppoeInput = true;
                    break;
                }
            }
    
            $macBindRan = false;
            if (array_key_exists('mac_bind', $input) || array_key_exists('mac_bound', $input)) {
                if (!function_exists('bindMacForUser')) {
                    helper('router');
                }
    
                $wantBind = true;
                if (array_key_exists('mac_bind', $input)) {
                    $v = $input['mac_bind'];
                    $parsed = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $wantBind = $parsed !== null ? $parsed : (bool) $v;
                } else {
                    $v = $input['mac_bound'];
                    $parsed = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $wantBind = $parsed !== null ? $parsed : (bool) $v;
                }
    
                if ($wantBind) {
                    $result = bindMacForUser((int) $customerId);
                    $ok = is_array($result) ? (($result['status'] ?? false) === true) : false;
                    if (!$ok) {
                        $msg = is_array($result) ? ($result['message'] ?? 'MAC bind failed') : 'MAC bind failed';
    
                        return $this->respondError((string) $msg, 400, 'REQUEST_FAILED');
                    }
                } elseif (!removeMacBind((int) $customerId)) {
                    return $this->respondError((string) 'Failed to remove MAC binding', 400, 'REQUEST_FAILED');
                }
    
                $macBindRan = true;
            }
    
            if (empty($data) && empty($connectionData) && !$hasPppoeInput && !$macBindRan) {
                return $this->respondError((string) 'No updatable fields provided', 400, 'REQUEST_FAILED');
            }
    
            // Keep customer ownership fixed to this reseller
            $data['admin_id'] = (int) $resellerId;
            $data['role'] = 'user';
    
            if (!empty($data['email'])) {
                $exists = $this->user_model
                    ->where('email', $data['email'])
                    ->where('id !=', (int) $customerId)
                    ->first();
                if ($exists) {
                    return $this->respondError((string) 'Email already in use', 400, 'REQUEST_FAILED');
                }
            }
    
            if (!empty($data['mobile'])) {
                $exists = $this->user_model
                    ->where('mobile', $data['mobile'])
                    ->where('id !=', (int) $customerId)
                    ->first();
                if ($exists) {
                    return $this->respondError((string) 'Mobile already in use', 400, 'REQUEST_FAILED');
                }
            }
    
            foreach (['will_expire', 'last_renewed'] as $dateField) {
                if (!empty($data[$dateField])) {
                    $normalized = str_replace('T', ' ', (string) $data[$dateField]);
                    $timestamp = strtotime($normalized);
                    if ($timestamp === false) {
                        return $this->respondError((string) 'Invalid datetime format for ' . $dateField, 400, 'REQUEST_FAILED');
                    }
                    $data[$dateField] = date('Y-m-d H:i:s', $timestamp);
                }
            }
    
            if (array_key_exists('password', $data)) {
                if ($data['password'] === '' || $data['password'] === null) {
                    unset($data['password']);
                } else {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
            }
    
            // Update PPPoE connection status on router when conn_status is supplied.
            if (array_key_exists('conn_status', $data)) {
                $requested = strtolower(trim((string) $data['conn_status']));
                $requestedConn = in_array($requested, ['active', 'conn', 'connected', 'online'], true) ? 'conn' : 'disconn';
    
                if (!function_exists('routerClient') || !function_exists('enablePPPoEUser') || !function_exists('disablePPPoEUser')) {
                    return $this->respondError((string) 'Router helper functions are not available for connection status update', 500, 'REQUEST_FAILED');
                }
    
                $routerId = (int) ($data['router_id'] ?? $customer['router_id'] ?? 0);
                $pppoeId = $data['pppoe_id'] ?? $customer['pppoe_id'] ?? null;
    
                if ($routerId <= 0 || empty($pppoeId)) {
                    return $this->respondError((string) 'Missing router_id or pppoe_id for connection status update', 400, 'REQUEST_FAILED');
                }
    
                $routerClient = routerClient($routerId);
                if (!($routerClient instanceof \RouterOS\Client)) {
                    $error = is_array($routerClient) ? ($routerClient['error'] ?? 'Router connection failed') : 'Router connection failed';
                    return $this->respondError((string) $error, 500, 'REQUEST_FAILED');
                }
    
                if ($requestedConn === 'conn') {
                    $enabled = enablePPPoEUser($routerClient, $pppoeId);
    
                    if (!$enabled && function_exists('enablePPPoEUser_by_pppoe_secret')) {
                        $routerCache = $this->userRouterDataModel->where('user_id', (int) $customerId)->first();
                        $cacheArr = $this->toArray($routerCache);
                        $pppoeSecret = $cacheArr['pppoe_secret'] ?? ($input['pppoe_name'] ?? null);
                        if (!empty($pppoeSecret)) {
                            $enabled = enablePPPoEUser_by_pppoe_secret($routerClient, $pppoeSecret);
                        }
                    }
    
                    if (!$enabled) {
                        return $this->respondError((string) 'Failed to activate PPPoE connection on router', 500, 'REQUEST_FAILED');
                    }
    
                    $data['conn_status'] = 'conn';
                    $data['activity'] = 'active';
                    $data['posPrinter'] = 'conn';
                } else {
                    disablePPPoEUser($routerClient, $pppoeId);
                    $data['conn_status'] = 'disconn';
                    $data['activity'] = 'inactive';
                }
            }
    
            // Update PPPoE on router when PPPoE fields are supplied
            if ($hasPppoeInput) {
                if (!function_exists('routerClient') || !function_exists('getPPPoEUser') || !function_exists('updatePPPoEUser')) {
                    return $this->respondError((string) 'Router helper functions are not available', 500, 'REQUEST_FAILED');
                }
    
                $routerId = (int) ($data['router_id'] ?? $customer['router_id'] ?? 0);
                $pppoeId = $normalizeInputValue($data['pppoe_id'] ?? $customer['pppoe_id'] ?? null);
                $requestedPppoeName = $normalizeInputValue($input['pppoe_name'] ?? null);
                if ($pppoeId === '' || ($requestedPppoeName !== '' && $pppoeId === $requestedPppoeName)) {
                    $pppoeId = $normalizeInputValue($customer['pppoe_id'] ?? null);
                }
    
                if (empty($routerId) || empty($pppoeId)) {
                    return $this->respondError((string) 'Missing router_id or pppoe_id for PPPoE update', 400, 'REQUEST_FAILED');
                }
    
                $routerClient = routerClient($routerId);
                if (!($routerClient instanceof \RouterOS\Client)) {
                    $error = is_array($routerClient) ? ($routerClient['error'] ?? 'Router connection failed') : 'Router connection failed';
                    return $this->respondError((string) $error, 500, 'REQUEST_FAILED');
                }
    
                $userPpp = getPPPoEUser($routerClient, $pppoeId);
                $currentPpp = $userPpp[0] ?? [];
    
                $routerPayload = [
                    'pppoe_name' => $normalizeInputValue($input['pppoe_name'] ?? ($currentPpp['name'] ?? null)),
                    'pppoe_password' => $normalizeInputValue($input['pppoe_password'] ?? ($currentPpp['password'] ?? null)),
                    'pppoe_service' => $normalizeInputValue($input['pppoe_service'] ?? ($currentPpp['service'] ?? null)),
                    'pppoe_profile' => $normalizeInputValue($input['pppoe_profile'] ?? ($currentPpp['profile'] ?? null)),
                    'pppoe_id' => $pppoeId,
                ];
    
                if (empty($routerPayload['pppoe_name']) || empty($routerPayload['pppoe_password']) || empty($routerPayload['pppoe_service']) || empty($routerPayload['pppoe_profile'])) {
                    return $this->respondError((string) 'PPPoE update requires name, password, service and profile', 400, 'REQUEST_FAILED');
                }
    
                $routerAction = updatePPPoEUser($routerClient, $routerPayload);
                if (!is_array($routerAction) || ($routerAction['status'] ?? 'error') !== 'success') {
                    $error = is_array($routerAction) ? ($routerAction['error'] ?? 'Failed to update PPPoE user') : 'Failed to update PPPoE user';
                    return $this->respondError((string) $error, 500, 'REQUEST_FAILED');
                }
    
                $data['pppoe_id'] = $routerAction['pppoe_id'] ?? $pppoeId;
    
                if (array_key_exists('pppoe_password', $input) && !empty($input['pppoe_password'])) {
                    $data['code'] = $input['pppoe_password'];
                    $data['password'] = password_hash($input['pppoe_password'], PASSWORD_DEFAULT);
                }
    
                $cacheData = [
                    'user_id' => (int) $customerId,
                    'router_id' => (int) $routerId,
                    'router_password' => $routerPayload['pppoe_password'],
                    'pppoe_secret' => $routerPayload['pppoe_name'],
                    'last_updated' => date('Y-m-d H:i:s'),
                ];
    
                $routerCache = $this->userRouterDataModel->where('user_id', (int) $customerId)->first();
                if (!empty($routerCache)) {
                    $cacheId = is_array($routerCache) ? ($routerCache['id'] ?? null) : ($routerCache->id ?? null);
                    if (!empty($cacheId)) {
                        $this->userRouterDataModel->update((int) $cacheId, $cacheData);
                    } else {
                        $this->userRouterDataModel->where('user_id', (int) $customerId)->set($cacheData)->update();
                    }
                } else {
                    $this->userRouterDataModel->insert($cacheData);
                }
            }
    
            try {
                if (!empty($data)) {
                    $this->user_model
                        ->where('id', (int) $customerId)
                        ->where('admin_id', (int) $resellerId)
                        ->where('role', 'user')
                        ->set($data)
                        ->update();
                }
    
                if (!empty($connectionData)) {
                    $this->saveConnectionDetails((int) $customerId, $connectionData);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Failed to update customer via reseller API: ' . $e->getMessage());
                return $this->respondError((string) 'Update failed', 500, 'REQUEST_FAILED');
            }
    
            $updated = $this->user_model
                ->where('id', (int) $customerId)
                ->where('admin_id', (int) $resellerId)
                ->where('role', 'user')
                ->first();
    
            return $this->respondSuccess(['message' => 'Customer record updated successfully', 'payload' => $this->enrichCustomer($updated, true),]);
        }
    
        /**
         * DELETE or POST /api/reseller/customers/(:resellerId)/(:customerId)
         *
         * Parity with web Customer::delete():
         *   - Removes PPPoE user from MikroTik when the router is active
         *   - Deletes user_router_data, connection_details, registrations, and users rows
         */
        public function delete($resellerId = null, $customerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            if (!empty($customerId)) {
                $ids = [(int) $customerId];
            } else {
                $input = $this->getInput();
                $ids = $this->normalizeIds($input['ids'] ?? null);
    
                if (empty($ids)) {
                    return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
                }
            }
    
            $existing = $this->user_model
                ->select('id, router_id, pppoe_id')
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId)
                ->whereIn('id', $ids)
                ->findAll();
    
            $toDelete = [];
            foreach ($existing as $row) {
                $entry = $this->toArray($row);
                if (empty($entry['id'])) {
                    continue;
                }
                $toDelete[] = (int) $entry['id'];
    
                $routerId = $entry['router_id'] ?? null;
                if (empty($routerId) || $routerId === '0') {
                    continue;
                }
    
                if (!function_exists('routerClient') || !function_exists('removePPPoEUser')) {
                    continue;
                }
    
                $routerModel = model('App\Models\Router');
                $router = $routerModel->find((int) $routerId);
                $routerArr = $this->toArray($router);
                if (($routerArr['status'] ?? '') !== 'active') {
                    continue;
                }
    
                $routerClient = routerClient((int) $routerId);
                if (!($routerClient instanceof \RouterOS\Client)) {
                    continue;
                }
    
                $pppoeId = $entry['pppoe_id'] ?? null;
                if (function_exists('getPPPoEUserUserId')) {
                    $pppoe = getPPPoEUserUserId($routerClient, (int) $entry['id']);
                    $resolvedId = $pppoe[0]['.id'] ?? $pppoeId;
                    if (!empty($resolvedId)) {
                        $pppoeId = $resolvedId;
                    }
                }
    
                if (!empty($pppoeId)) {
                    removePPPoEUser($routerClient, $pppoeId);
                }
            }
    
            if (empty($toDelete)) {
                return $this->respondError((string) 'No matching customers found', 404, 'REQUEST_FAILED');
            }
    
            $deleted = $this->user_model
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId)
                ->whereIn('id', $toDelete)
                ->delete();
    
            if (!$deleted) {
                return $this->respondError((string) 'Delete failed', 500, 'REQUEST_FAILED');
            }
    
            $this->db->table('connection_details')->whereIn('user_id', $toDelete)->delete();
            $this->userRouterDataModel->whereIn('user_id', $toDelete)->delete();
    
            $registrationModel = model('App\Models\Registration');
            $registrationModel->whereIn('userid', $toDelete)->delete();
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Selected records deleted successfully',
                'deleted_count' => count($toDelete),
                'deleted' => $toDelete,
            ]);
        }
    
}
