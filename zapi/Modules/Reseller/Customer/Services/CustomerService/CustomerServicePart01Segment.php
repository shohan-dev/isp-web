<?php

namespace Zapi\Modules\Reseller\Customer\Services\CustomerService;

use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CustomerServicePart01Segment
{
        private function toArray($row)
        {
            if (is_array($row)) {
                return $row;
            }
    
            return is_object($row) ? (array) $row : [];
        }
    
        private function normalizeIds($ids)
        {
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) {
                    $ids = $decoded;
                } else {
                    $ids = array_filter(array_map('trim', explode(',', $ids)), 'strlen');
                }
            }
    
            if (!is_array($ids)) {
                return null;
            }
    
            $normalized = [];
            foreach ($ids as $id) {
                if (is_numeric($id) && (int) $id > 0) {
                    $normalized[] = (int) $id;
                }
            }
    
            return array_values(array_unique($normalized));
        }
    
        /**
         * Robustly read the request body as an associative array.
         * Works for POST, DELETE, PUT, PATCH regardless of Content-Type.
         */
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
    
            $raw = $this->request->getRawInput();
            if (is_array($raw) && !empty($raw)) {
                return $raw;
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
    
        private function asInt($value)
        {
            return (int) (is_numeric($value) ? $value : 0);
        }
    
        private function asFloat($value)
        {
            return (float) (is_numeric($value) ? $value : 0);
        }
    
        private function asString($value, $fallback = '--')
        {
            if ($value === null) {
                return $fallback;
            }
    
            $text = trim((string) $value);
            if ($text === '' || strtolower($text) === 'null') {
                return $fallback;
            }
    
            return $text;
        }
    
        private function getConnectionDetails($userId)
        {
            $row = $this->db->table('connection_details')->where('user_id', (int) $userId)->get()->getRowArray();
            return !empty($row) ? $row : null;
        }
    
        private function saveConnectionDetails($userId, array $payload)
        {
            if (!$this->db->tableExists('connection_details')) {
                log_message('warning', 'connection_details table not found; skipping connection details save for user_id=' . (int) $userId);
                return;
            }

            $allowed = [
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
    
            $data = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $payload)) {
                    $data[$field] = $payload[$field];
                }
            }
    
            if (empty($data)) {
                return;
            }
    
            $existing = $this->db->table('connection_details')->where('user_id', (int) $userId)->get()->getRowArray();
    
            if (!empty($existing) && !empty($existing['id'])) {
                try {
                    $this->db->table('connection_details')->where('id', (int) $existing['id'])->update($data);
                } catch (\Throwable $e) {
                    log_message(
                        'error',
                        'Failed updating connection_details for user_id=' . (int) $userId .
                            ' payload=' . json_encode($data) .
                            ' db_error=' . json_encode((array) $this->db->error()) .
                            ' exception=' . $e->getMessage()
                    );
                }
                return;
            }
    
            $data['user_id'] = (int) $userId;
            try {
                $this->db->table('connection_details')->insert($data);
            } catch (\Throwable $e) {
                log_message(
                    'error',
                    'Failed inserting connection_details for user_id=' . (int) $userId .
                        ' payload=' . json_encode($data) .
                        ' db_error=' . json_encode((array) $this->db->error()) .
                        ' exception=' . $e->getMessage()
                );
            }
        }
    
        private function getPppoeDetails(array $customer, $allowRouterLookup = false)
        {
            $basePppoeName = trim((string) ($customer['pppoe_name'] ?? $customer['pppoe_secret'] ?? $customer['secret'] ?? $customer['pppoe_id'] ?? '--'));
            $basePppoePassword = trim((string) ($customer['pppoe_password'] ?? $customer['router_password'] ?? '--'));
            $basePppoeService = trim((string) ($customer['pppoe_service'] ?? 'pppoe'));
            $basePppoeProfile = trim((string) ($customer['pppoe_profile'] ?? '--'));
            $pppoe = [
                'pppoe_name' => $basePppoeName !== '' ? $basePppoeName : '--',
                'pppoe_password' => $basePppoePassword !== '' ? $basePppoePassword : '--',
                'pppoe_service' => $basePppoeService !== '' ? $basePppoeService : 'pppoe',
                'pppoe_profile' => $basePppoeProfile !== '' ? $basePppoeProfile : '--',
                'pppoe_uptime' => '--',
                'pppoe_address' => '--',
                'pppoe_caller_id' => '--',
                'pppoe_last_logged_out' => '--',
                'pppoe_last_caller_id' => '--',
                'pppoe_last_disconnect_reason' => '--',
                'pppoe_disabled' => '--',
                'pppoe_comment' => '--',
            ];
    
            if (function_exists('getRouterPassById')) {
                $credentials = getRouterPassById((int) ($customer['id'] ?? 0));
                if (is_array($credentials)) {
                    $pppoe['pppoe_name'] = $credentials['pppoe_secret'] ?? '--';
                    $pppoe['pppoe_password'] = $credentials['router_password'] ?? '--';
                }
            }
    
            if ($allowRouterLookup && function_exists('routerClient')) {
                $routerId = $customer['router_id'] ?? null;
                $pppoeId = $customer['pppoe_id'] ?? null;
                $customerId = $customer['id'] ?? null;
    
                if (!empty($routerId)) {
                    $routerClient = routerClient($routerId);
                    if ($routerClient instanceof \RouterOS\Client) {
                        $pppRow = [];

                        // Mirror the web (`Customer::details`): start from
                        // `user_router_data.pppoe_secret` (stable name) and
                        // query `/ppp/secret/print` by name. This survives
                        // router rebuilds where the volatile `.id` no longer
                        // matches what's stored on the customer row.
                        if (function_exists('getPPPoEUserUserId') && !empty($customerId)) {
                            $userPpp = getPPPoEUserUserId($routerClient, $customerId);
                            $candidate = $userPpp[0] ?? [];
                            if (!empty($candidate['name']) || !empty($candidate['.id'])) {
                                $pppRow = $candidate;
                            }
                        }

                        // Fallback 1: lookup by stored `.id`
                        if (empty($pppRow) && !empty($pppoeId) && function_exists('getPPPoEUser')) {
                            $userPpp = getPPPoEUser($routerClient, $pppoeId);
                            $candidate = $userPpp[0] ?? [];
                            if (!empty($candidate['name']) || !empty($candidate['.id'])) {
                                $pppRow = $candidate;
                            }
                        }

                        // Fallback 2: lookup by PPPoE name as a last resort
                        if (empty($pppRow)
                            && function_exists('getPPPoEUserByName')
                            && !empty($pppoe['pppoe_name'])
                            && $pppoe['pppoe_name'] !== '--') {
                            $userPpp = getPPPoEUserByName($routerClient, $pppoe['pppoe_name']);
                            $candidate = $userPpp[0] ?? [];
                            if (!empty($candidate['name']) || !empty($candidate['.id'])) {
                                $pppRow = $candidate;
                            }
                        }

                        if (!empty($pppRow)) {
                            $pppoe['pppoe_name'] = $pppRow['name'] ?? $pppoe['pppoe_name'];
                            $pppoe['pppoe_password'] = $pppRow['password'] ?? $pppoe['pppoe_password'];
                            $pppoe['pppoe_service'] = $pppRow['service'] ?? '--';
                            $pppoe['pppoe_profile'] = $pppRow['profile'] ?? '--';
                            $pppoe['pppoe_uptime'] = $pppRow['uptime'] ?? '--';
                            $pppoe['pppoe_address'] = $pppRow['address'] ?? '--';
                            $pppoe['pppoe_caller_id'] = $pppRow['caller-id'] ?? '--';
                            $pppoe['pppoe_last_logged_out'] = $pppRow['last-logged-out'] ?? '--';
                            $pppoe['pppoe_last_caller_id'] = $pppRow['last-caller-id'] ?? '--';
                            $pppoe['pppoe_last_disconnect_reason'] = $pppRow['last-disconnect-reason'] ?? '--';
                            $pppoe['pppoe_disabled'] = $pppRow['disabled'] ?? '--';
                            $pppoe['pppoe_comment'] = $pppRow['comment'] ?? '--';
                            // Keep the live secret id so other helpers (active session, OLT) can rely on it
                            if (!empty($pppRow['.id'])) {
                                $pppoe['pppoe_secret_id'] = $pppRow['.id'];
                            }
                            // Pass the raw Mikrotik row through so the web shape is preserved verbatim
                            $pppoe['pppoe_raw'] = $pppRow;
                        }
                    }
                }
            }
    
            return $pppoe;
        }
    
        private function enrichCustomer($customer, $allowRouterLookup = false)
        {
            $row = $this->toArray($customer);
            if (empty($row)) {
                return [];
            }

            // Explicitly pass conn_status from database - this is critical for app status display
            $row['conn_status'] = $row['conn_status'] ?? 'disconn';
            $row['connection_status'] = $row['connection_status'] ?? ($row['conn_status'] ?? 'disconn');
            // Web list Status uses users.activity strictly:
            // active => Online, anything else => Offline.
            $row['activity'] = $row['activity'] ?? 'inactive';
            // Web list "Acc. Status" semantics: Connected / Disconnected from conn_status.
            $row['acc_status'] = (($row['conn_status'] ?? 'disconn') === 'conn') ? 'Connected' : 'Disconnected';
            // Web list "Status" semantics come from users.activity (active/inactive).
            $row['online_status'] = (($row['activity'] ?? 'inactive') === 'active') ? 'Online' : 'Offline';

            $row['area_name'] = function_exists('getAreaNameById') ? (getAreaNameById($row) ?? '--') : '--';
            $row['router_name'] = function_exists('getRouterById') ? (getRouterById($row['router_id'] ?? null)->name ?? '--') : '--';
    
            $package = function_exists('getUserPackage') ? getUserPackage((int) $row['id']) : null;
            $packageArr = $this->toArray($package);
            $row['package'] = $packageArr;
            $row['package_name'] = $packageArr['package_name'] ?? $packageArr['name'] ?? '--';
            $row['package_price'] = $packageArr['selling_price'] ?? $packageArr['price'] ?? '--';
    
            $pppoe = $this->getPppoeDetails($row, $allowRouterLookup);
            $row['pppoe_name'] = $pppoe['pppoe_name'];
            $row['pppoe_password'] = $pppoe['pppoe_password'];
            $row['pppoe_service'] = $pppoe['pppoe_service'];
            $row['pppoe_profile'] = $pppoe['pppoe_profile'];
            $row['pppoe_uptime'] = $pppoe['pppoe_uptime'];
            $row['pppoe_address'] = $pppoe['pppoe_address'];
            $row['pppoe_caller_id'] = $pppoe['pppoe_caller_id'];
            $row['pppoe_last_logged_out'] = $pppoe['pppoe_last_logged_out'];
            $row['pppoe_last_caller_id'] = $pppoe['pppoe_last_caller_id'];
            $row['pppoe_last_disconnect_reason'] = $pppoe['pppoe_last_disconnect_reason'];
            $row['pppoe_disabled'] = $pppoe['pppoe_disabled'];
            $row['pppoe_comment'] = $pppoe['pppoe_comment'];
            $row['secret'] = $pppoe['pppoe_name'];
            $row['router_password'] = $pppoe['pppoe_password'];
            // Live Mikrotik secret `.id` (volatile) — required by the active-session lookup
            if (!empty($pppoe['pppoe_secret_id'])) {
                $row['pppoe_id'] = $pppoe['pppoe_secret_id'];
            }
            // Raw Mikrotik row (hyphenated keys, exactly like the web view receives)
            if (!empty($pppoe['pppoe_raw'])) {
                $row['pppoe_raw'] = $pppoe['pppoe_raw'];
            }
    
            $connectionDetails = $this->getConnectionDetails((int) $row['id']);
            $row['connection_details'] = $connectionDetails;

            // Add is_online flag - critical for app status display
            $row['is_online'] = ($row['conn_status'] ?? 'disconn') === 'conn';

            if (!empty($connectionDetails)) {
                foreach ($connectionDetails as $key => $value) {
                    if (!array_key_exists($key, $row)) {
                        $row[$key] = $value;
                    }
                }
            }

            return $this->appendSubscriptionPaymentSummary($row);
        }

        /**
         * Plain-text payment badge matching web DataTables `subscription_status` column
         * (see app/Controllers/Customer.php datatables subscription_status).
         */
        private function appendSubscriptionPaymentSummary(array $row): array
        {
            if (!function_exists('checkPaymentStatus')) {
                helper('user');
            }

            $userId = (int) ($row['id'] ?? 0);
            $subscriptionStatus = strtolower(trim((string) ($row['subscription_status'] ?? '')));
            $willExpireRaw = $row['will_expire'] ?? null;

            if ($willExpireRaw === null || $willExpireRaw === '') {
                $row['payment_summary'] = 'Inactive';
                $row['payment_summary_kind'] = 'inactive';

                return $row;
            }

            try {
                $today = \Carbon\Carbon::today();
                $willExpire = \Carbon\Carbon::parse($willExpireRaw);
                $daysRemaining = $today->diffInDays($willExpire, false);
            } catch (\Throwable $e) {
                $row['payment_summary'] = '--';
                $row['payment_summary_kind'] = 'inactive';

                return $row;
            }

            $now = date('Y-m-d');
            $renewalDate = $row['last_renewed'] ?? null;
            $subscriptionEnd = $willExpireRaw;

            $lastPaymentMonth = !empty($renewalDate)
                ? date('F', strtotime((string) $renewalDate))
                : null;

            $expiringMonth = !empty($subscriptionEnd)
                ? date('F', strtotime((string) $subscriptionEnd))
                : null;

            $initialStatus = $lastPaymentMonth
                ? checkPaymentStatus($userId, $lastPaymentMonth)
                : 'Unknown';

            $finalStatus = $expiringMonth
                ? checkPaymentStatus($userId, $expiringMonth)
                : 'Unknown';

            $bothUnknown =
                ($initialStatus === 'Unknown' || $initialStatus === null) &&
                ($finalStatus === 'Unknown' || $finalStatus === null);

            if ($subscriptionStatus === 'active') {
                $subEndTs = strtotime((string) $subscriptionEnd);

                if ($subEndTs !== false && strtotime($now) <= $subEndTs) {
                    if ($bothUnknown || $initialStatus === 'successful' || $finalStatus === 'successful') {
                        $row['payment_summary'] = 'paid (' . $daysRemaining . ' left)';
                        $row['payment_summary_kind'] = 'paid';
                    } else {
                        $row['payment_summary'] = 'due (' . $daysRemaining . ' left)';
                        $row['payment_summary_kind'] = 'due';
                    }
                } else {
                    $row['payment_summary'] = 'expired (' . abs($daysRemaining) . ' days ago)';
                    $row['payment_summary_kind'] = 'expired';
                }
            } else {
                $row['payment_summary'] = 'Inactive';
                $row['payment_summary_kind'] = 'inactive';
            }

            return $row;
        }
    
        private function getMacBinding($customerId)
        {
            if (!function_exists('isMacBound')) {
                return [
                    'is_bound' => false,
                    'mac' => '--',
                    'label' => 'Not Bound',
                ];
            }
    
            $result = isMacBound((int) $customerId);
            $isBound = is_array($result) ? ((bool) ($result['status'] ?? false)) : false;
            $mac = is_array($result) ? ($result['mac'] ?? '--') : '--';
    
            return [
                'is_bound' => $isBound,
                'mac' => $this->asString($mac),
                'label' => $isBound ? 'Bound' : 'Not Bound',
            ];
        }
    
        private function getOltOnuDetails(array $customer)
        {
            $defaultOnu = [
                'onu_id' => '--',
                'mac' => '--',
                'status' => '--',
                'rx' => '--',
                'reason' => '--',
                'last_seen' => '--',
                'description' => '--',
                'olt_name' => '--',
                'available' => false,
            ];
    
            // Try to use pppoe_caller_id (MAC address) to fetch ONU details
            $callerid = trim((string) ($customer['pppoe_caller_id'] ?? $customer['pppoe_last_caller_id'] ?? ''));
    
            if (empty($callerid) || $callerid === '--') {
                return $defaultOnu;
            }
    
            try {
                // Use OltController to fetch ONU details
                if (!class_exists('App\Controllers\OltController')) {
                    return $defaultOnu;
                }
    
                $oltController = new \App\Controllers\OltController();
                $onu = $oltController->getOnuByMac($callerid);
    
                if (empty($onu) || !is_array($onu)) {
                    return $defaultOnu;
                }
    
                // Map ONU data and mark as available if we have valid data
                $result = [
                    'onu_id' => $this->asString($onu['onu_id'] ?? '--'),
                    'mac' => $this->asString($onu['mac'] ?? '--'),
                    'status' => $this->asString($onu['status'] ?? '--'),
                    'rx' => $this->asString($onu['rx'] ?? '--'),
                    'reason' => $this->asString($onu['reason'] ?? '--'),
                    'last_seen' => $this->asString($onu['last_seen'] ?? '--'),
                    'description' => $this->asString($onu['description'] ?? '--'),
                    'olt_name' => $this->asString($onu['olt_name'] ?? '--'),
                    'available' => !($this->asString($onu['onu_id'] ?? '--') === 'Not Found'),
                ];
    
                return $result;
            } catch (\Throwable $e) {
                return $defaultOnu;
            }
        }
    
        /**
         * Fetch the live PPPoE session row from MikroTik (`/ppp/active/print`).
         * Mikrotik secrets only carry historical fields (last-logged-out, last-caller-id);
         * the *current* uptime / address / caller-id only exist on the active row.
         */
        private function getActiveSession(array $customer)
        {
            $default = [
                'available' => false,
                'name' => '--',
                'uptime' => '--',
                'address' => '--',
                'caller_id' => '--',
                'encoding' => '--',
                'service' => '--',
                'session_id' => '--',
            ];

            if (!function_exists('routerClient')) {
                return $default;
            }

            $routerId = $this->asInt($customer['router_id'] ?? 0);
            $pppoeName = trim((string) (
                $customer['pppoe_name']
                    ?? $customer['secret']
                    ?? $customer['pppoe_secret']
                    ?? ''
            ));
            if ($routerId <= 0 || $pppoeName === '' || $pppoeName === '--') {
                return $default;
            }

            try {
                $client = routerClient($routerId);
                if (!($client instanceof \RouterOS\Client)) {
                    return $default;
                }

                $query = (new \RouterOS\Query('/ppp/active/print'))->where('name', $pppoeName);
                $rows = $client->query($query)->read();
                if (empty($rows) || !is_array($rows[0])) {
                    return $default;
                }

                $row = $rows[0];
                return [
                    'available' => true,
                    'name' => $this->asString($row['name'] ?? $pppoeName),
                    'uptime' => $this->asString($row['uptime'] ?? '--'),
                    'address' => $this->asString($row['address'] ?? '--'),
                    'caller_id' => $this->asString($row['caller-id'] ?? '--'),
                    'encoding' => $this->asString($row['encoding'] ?? '--'),
                    'service' => $this->asString($row['service'] ?? '--'),
                    'session_id' => $this->asString($row['.id'] ?? '--'),
                ];
            } catch (\Throwable $e) {
                log_message('error', 'ResellerCustomerService active session error: ' . $e->getMessage());
                return $default;
            }
        }

        /**
         * Connectivity-friendly view of the OLT/ONU signal so callers can read
         * a single top-level field instead of digging into `olt_onu`.
         *
         * The display value is *always* something a user can read:
         *   - numeric reading → "-22.50 dBm"
         *   - missing / lookup failed → "Not Found"
         */
        private function getOpticalSnapshot(array $oltOnu)
        {
            $rxRaw = trim((string) ($oltOnu['rx'] ?? ''));
            $available = (bool) ($oltOnu['available'] ?? false);

            $hasReading = $available
                && $rxRaw !== ''
                && $rxRaw !== '--'
                && strtolower($rxRaw) !== 'not found'
                && strtolower($rxRaw) !== 'n/a';

            if ($hasReading) {
                $display = $this->formatOpticalReading($rxRaw);
            } else {
                $display = 'Not Found';
            }

            return [
                'available' => $hasReading,
                'rx' => $display,
                'rx_raw' => $rxRaw !== '' ? $rxRaw : '--',
                'status' => $this->asString($oltOnu['status'] ?? '--'),
                'olt_name' => $this->asString($oltOnu['olt_name'] ?? '--'),
                'last_seen' => $this->asString($oltOnu['last_seen'] ?? '--'),
                'reason' => $this->asString($oltOnu['reason'] ?? '--'),
            ];
        }

        /**
         * Add a "dBm" suffix to plain numeric optical readings; pass through
         * already-formatted values (e.g. "-22.5 dBm") unchanged.
         */
        private function formatOpticalReading(string $raw): string
        {
            $value = trim($raw);
            if ($value === '') {
                return 'Not Found';
            }
            // Already has unit?
            if (stripos($value, 'dbm') !== false) {
                return $value;
            }
            // Pure numeric (signed, optional decimals)?
            if (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
                $num = (float) $value;
                $formatted = number_format($num, 2);
                // Trim trailing ".00" → keep ".0" minimum or whole number when integer
                if (preg_match('/\.\d0$/', $formatted)) {
                    $formatted = rtrim($formatted, '0');
                }
                if (substr($formatted, -1) === '.') {
                    $formatted .= '0';
                }
                return $formatted . ' dBm';
            }
            return $value;
        }

        private function getTrafficSnapshot(array $customer)
        {
            $routerId = $this->asInt($customer['router_id'] ?? 0);
            $pppoeName = trim((string) ($customer['pppoe_name'] ?? $customer['secret'] ?? $customer['pppoe_id'] ?? ''));
    
            $payload = [
                'rxbyte' => 0.0,
                'txbyte' => 0.0,
                'unit' => 'Mbps',
                'caller_id' => $this->asString($customer['pppoe_caller_id'] ?? null),
                'updated_at' => date('Y-m-d H:i:s'),
                'available' => false,
            ];
    
            if ($routerId <= 0 || $pppoeName === '') {
                return $payload;
            }
    
            if (!function_exists('routerClient') || !function_exists('getusersSystemResources')) {
                return $payload;
            }
    
            $routerClient = routerClient($routerId);
            if (!($routerClient instanceof \RouterOS\Client)) {
                return $payload;
            }
    
            try {
                $resource = getusersSystemResources($routerClient, $pppoeName, '');
                $traffic = $resource['data']['traffic'] ?? [];
    
                if (is_array($traffic) && isset($traffic[0]) && is_array($traffic[0])) {
                    $traffic = $traffic[0];
                }
    
                if (is_array($traffic)) {
                    $payload['rxbyte'] = $this->asFloat($traffic['rxbyte'] ?? $traffic['rx'] ?? 0);
                    $payload['txbyte'] = $this->asFloat($traffic['txbyte'] ?? $traffic['tx'] ?? 0);
                    $payload['available'] = true;
                }
    
                $activeUsers = $resource['data']['activeusers'] ?? [];
                if (is_array($activeUsers)) {
                    foreach ($activeUsers as $activeUser) {
                        if (!is_array($activeUser)) {
                            continue;
                        }
    
                        $activeName = trim((string) ($activeUser['name'] ?? ''));
                        if ($activeName === $pppoeName) {
                            $payload['caller_id'] = $this->asString($activeUser['caller-id'] ?? $payload['caller_id']);
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'ResellerCustomerService traffic snapshot error: ' . $e->getMessage());
            }
    
            return $payload;
        }
    
}
