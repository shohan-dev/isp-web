<?php

namespace Zapi\Modules\Reseller\Customer\Services\CustomerService;

use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CustomerServicePart06Segment
{
        /**
         * DELETE or POST /api/reseller/customers/{resellerId}/bulk-delete
         * Alias to existing delete(ids[]) behavior.
         */
        public function bulkDelete($resellerId = null)
        {
            return $this->delete($resellerId, null);
        }
    
        /**
         * POST /api/reseller/customers/{resellerId}/sync-pppoe
         */
        public function syncPppoeIds($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            if (!function_exists('routerClient') || !function_exists('getPPPoEUserByName') || !function_exists('getRouterPassById')) {
                return $this->respondError((string) 'Router helper functions are not available', 500, 'REQUEST_FAILED');
            }
    
            $customers = $this->user_model
                ->select('id, router_id, pppoe_id')
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId)
                ->findAll();
    
            $updated = 0;
            $skipped = 0;
            $errors = [];
    
            foreach ($customers as $customer) {
                $row = $this->toArray($customer);
                $userId = (int) ($row['id'] ?? 0);
                $routerId = (int) ($row['router_id'] ?? 0);
    
                if ($userId <= 0 || $routerId <= 0) {
                    $skipped++;
                    continue;
                }
    
                $routerPass = getRouterPassById($userId);
                $secret = is_array($routerPass) ? trim((string) ($routerPass['pppoe_secret'] ?? '')) : '';
                if ($secret === '') {
                    $cache = $this->userRouterDataModel->where('user_id', $userId)->first();
                    $cacheRow = $this->toArray($cache);
                    $secret = trim((string) ($cacheRow['pppoe_secret'] ?? ''));
                }
    
                if ($secret === '') {
                    $skipped++;
                    continue;
                }
    
                $client = routerClient($routerId);
                if (!($client instanceof \RouterOS\Client)) {
                    $errors[] = ['user_id' => $userId, 'message' => 'Router connection failed'];
                    continue;
                }
    
                $ppp = getPPPoEUserByName($client, $secret);
                $pppoeId = is_array($ppp) ? ($ppp[0]['id'] ?? '') : '';
                if ($pppoeId === '') {
                    $errors[] = ['user_id' => $userId, 'message' => 'PPPoE user not found'];
                    continue;
                }
    
                if ((string) ($row['pppoe_id'] ?? '') !== (string) $pppoeId) {
                    $this->user_model->update($userId, ['pppoe_id' => $pppoeId]);
                    $updated++;
                } else {
                    $skipped++;
                }
            }
    
            return $this->respondSuccess(['message' => 'PPPoE sync completed', 'payload' => [
                    'total' => count($customers),
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ],]);
        }
    
        /**
         * POST /api/reseller/customers/{resellerId}/transfer
         *
         * Parity with web Customer::transfer(): derives `created_by` from the
         * calling reseller's own role and created_by, matching the session-based
         * branching the web controller uses.
         */
        public function transfer($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            /* This re-parents customers from the URL-supplied source reseller to a
               body-supplied target, with no check that the caller owns either. A
               reseller could POST /api/reseller/customers/{victim}/transfer with
               target_reseller_id = their own id and take the victim's whole roster.
               RoleAuthFilter only checks the JWT role, not this id. */
            if (!$this->canAccessReseller((int) $resellerId)) {
                return $this->respondError((string) 'Access denied', 403, 'REQUEST_FAILED');
            }

            $input = $this->getInput();
    
            $ids = $this->normalizeIds($input['ids'] ?? null);
            $targetResellerId = (int) ($input['target_reseller_id'] ?? $input['reseller_id'] ?? 0);
    
            if (empty($ids)) {
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }
            if ($targetResellerId <= 0) {
                return $this->respondError((string) 'Target reseller id is required', 400, 'REQUEST_FAILED');
            }
    
            $targetReseller = $this->user_model
                ->where('id', $targetResellerId)
                ->where('role', 'resellerAdmin')
                ->first();
    
            if (empty($targetReseller)) {
                return $this->respondError((string) 'Target reseller not found', 404, 'REQUEST_FAILED');
            }
    
            $callerObj = $this->user_model->find((int) $resellerId);
            $caller = $this->toArray($callerObj);
            $callerRole = $caller['role'] ?? 'resellerAdmin';
            $callerCreatedBy = $caller['created_by'] ?? '';
    
            if ($callerRole === 'resellerAdmin') {
                $createdBy = 'admin';
            } elseif ($callerRole === 'employee') {
                $createdBy = ($callerCreatedBy === 'resellerAdmin') ? 'admin' : 'resellerAdmin';
            } else {
                $createdBy = 'resellerAdmin';
            }
    
            $customers = $this->user_model
                ->select('id')
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId)
                ->whereIn('id', $ids)
                ->findAll();
    
            $transferIds = [];
            foreach ($customers as $row) {
                $arr = $this->toArray($row);
                if (!empty($arr['id'])) {
                    $transferIds[] = (int) $arr['id'];
                }
            }
    
            if (empty($transferIds)) {
                return $this->respondError((string) 'No matching customers found', 404, 'REQUEST_FAILED');
            }
    
            $this->user_model
                ->whereIn('id', $transferIds)
                ->set([
                    'admin_id' => $targetResellerId,
                    'created_by' => $createdBy,
                ])
                ->update();
    
            return $this->respondSuccess(['message' => 'Customers transferred successfully', 'payload' => [
                    'transferred_count' => count($transferIds),
                    'ids' => $transferIds,
                    'target_reseller_id' => $targetResellerId,
                ],]);
        }
    
        /**
         * POST /api/reseller/customers/{resellerId}/bulk-recharge
         * Applies subscription date/status updates for one or multiple customers.
         */
        public function bulkRecharge($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getInput();
    
            $ids = $this->normalizeIds($input['ids'] ?? null);
            $willExpire = trim((string) ($input['will_expire'] ?? ''));
            $packageId = isset($input['package_id']) && $input['package_id'] !== '' ? (int) $input['package_id'] : null;
            $pppoeProfile = trim((string) ($input['pppoe_profile'] ?? ''));
    
            if (empty($ids)) {
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }
            if ($willExpire === '') {
                return $this->respondError((string) 'will_expire is required', 400, 'REQUEST_FAILED');
            }
    
            $ts = strtotime(str_replace('T', ' ', $willExpire));
            if ($ts === false) {
                return $this->respondError((string) 'Invalid will_expire datetime', 400, 'REQUEST_FAILED');
            }
            $normalizedWillExpire = date('Y-m-d H:i:s', $ts);
            $isActive = $ts > time();
    
            $customers = $this->user_model
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId)
                ->whereIn('id', $ids)
                ->findAll();
    
            $success = [];
            $failed = [];
    
            foreach ($customers as $customer) {
                $row = $this->toArray($customer);
                $customerId = (int) ($row['id'] ?? 0);
                if ($customerId <= 0) {
                    continue;
                }
    
                $updateData = [
                    'will_expire' => $normalizedWillExpire,
                    'last_renewed' => date('Y-m-d H:i:s'),
                    'subscription_status' => $isActive ? 'active' : 'inactive',
                    'conn_status' => $isActive ? 'conn' : 'disconn',
                ];
    
                if ($packageId !== null && $packageId > 0) {
                    $updateData['package_id'] = $packageId;
                }
    
                try {
                    $this->user_model->update($customerId, $updateData);
    
                    if ($pppoeProfile !== '' && function_exists('routerClient') && function_exists('getPPPoEUser') && function_exists('updatePPPoEUser')) {
                        $routerId = (int) ($row['router_id'] ?? 0);
                        $pppoeId = (string) ($row['pppoe_id'] ?? '');
                        if ($routerId > 0 && $pppoeId !== '') {
                            $client = routerClient($routerId);
                            if ($client instanceof \RouterOS\Client) {
                                $ppp = getPPPoEUser($client, $pppoeId);
                                $pppRow = is_array($ppp) ? ($ppp[0] ?? []) : [];
                                if (!empty($pppRow)) {
                                    updatePPPoEUser($client, [
                                        'pppoe_id' => $pppoeId,
                                        'pppoe_name' => $pppRow['name'] ?? '',
                                        'pppoe_password' => $pppRow['password'] ?? '',
                                        'pppoe_service' => $pppRow['service'] ?? 'pppoe',
                                        'pppoe_profile' => $pppoeProfile,
                                    ]);
                                }
                            }
                        }
                    }
    
                    $success[] = $customerId;
                } catch (\Throwable $e) {
                    $failed[] = ['id' => $customerId, 'message' => $e->getMessage()];
                }
            }
    
            $missing = array_values(array_diff($ids, $success));
            foreach ($missing as $id) {
                $existsInFailed = false;
                foreach ($failed as $entry) {
                    if ((int) ($entry['id'] ?? 0) === (int) $id) {
                        $existsInFailed = true;
                        break;
                    }
                }
                if (!$existsInFailed) {
                    $failed[] = ['id' => (int) $id, 'message' => 'Customer not found or not owned by reseller'];
                }
            }
    
            return $this->respondSuccess(['message' => 'Bulk recharge processing finished', 'payload' => [
                    'success_count' => count($success),
                    'failed_count' => count($failed),
                    'success_ids' => $success,
                    'failed' => $failed,
                    'will_expire' => $normalizedWillExpire,
                ],]);
        }
    
        /**
         * GET /api/reseller/customers/{resellerId}/export-excel
         */
        public function exportExcel($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $customers = $this->user_model
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId)
                ->orderBy('id', 'desc')
                ->findAll();
    
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
    
            $headers = ['ID', 'Customer Name', 'Package', 'Mobile', 'Router', 'PPPoE Secret', 'PPPoE Password', 'Conn. Status', 'Acc. Status', 'PPPOE ID', 'Email'];
            $sheet->fromArray($headers, null, 'A1');
    
            $rowNum = 2;
            foreach ($customers as $customer) {
                $row = $this->toArray($customer);
                $userId = (int) ($row['id'] ?? 0);
                $package = function_exists('getUserPackage') ? getUserPackage($userId) : null;
                $packageArr = $this->toArray($package);
                $router = function_exists('getRouterById') ? getRouterById($row['router_id'] ?? null) : null;
                $routerArr = $this->toArray($router);
                $routerPass = function_exists('getRouterPassById') ? getRouterPassById($userId) : [];
    
                $sheet->fromArray([
                    $row['c_id'] ?? $userId,
                    $row['name'] ?? '--',
                    $packageArr['package_name'] ?? '--',
                    $row['mobile'] ?? '--',
                    $routerArr['name'] ?? '--',
                    is_array($routerPass) ? ($routerPass['pppoe_secret'] ?? '--') : '--',
                    is_array($routerPass) ? ($routerPass['router_password'] ?? '--') : '--',
                    (($row['conn_status'] ?? '') === 'conn') ? 'Connected' : 'Disconnected',
                    (($row['status'] ?? '') === 'active') ? 'Active' : 'Inactive',
                    $row['pppoe_id'] ?? '--',
                    $row['email'] ?? '--',
                ], null, 'A' . $rowNum);
    
                $rowNum++;
            }
    
            $filename = 'customers_' . date('Ymd') . '.xlsx';
    
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
    
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
    
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
    
}
