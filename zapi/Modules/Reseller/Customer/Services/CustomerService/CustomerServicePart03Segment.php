<?php

namespace Zapi\Modules\Reseller\Customer\Services\CustomerService;

use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CustomerServicePart03Segment
{
        /**
         * GET /api/reseller/customers/(:resellerId)/(:customerId)
         * Full detail by default. Pass compact=1 or mode=edit for a lighter payload (mobile edit sheet):
         * skips OLT lookup, live session, usage/traffic, and uses DB-only PPPoE enrichment.
         */
        public function details($resellerId = null, $customerId = null)
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
    
            $customerArr = $this->toArray($customer);

            $paymentsPayload = $this->buildCustomerPaymentsPayload((int) $resellerId, (int) $customerId);

            // Compact mode (mobile edit sheet): skip OLT/traffic/Mikrotik live session — much faster.
            $mode = strtolower(trim((string) ($this->request->getGet('mode') ?? '')));
            $compact =
                $this->request->getGet('compact') === '1'
                || $this->request->getGet('compact') === 'true'
                || $mode === 'edit';

            // Full detail uses live router PPPoE lookup; compact uses DB/cache only (see enrichCustomer).
            $enriched = $this->enrichCustomer($customerArr, !$compact);

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

            if ($compact) {
                $enriched['mac_binding'] = [
                    'is_bound' => false,
                    'mac' => '--',
                    'label' => 'Not Bound',
                ];
                $enriched['olt_onu'] = $defaultOnu;
                $activeSession = [
                    'available' => false,
                    'uptime' => '--',
                    'address' => '--',
                    'caller_id' => '--',
                ];
                $opticalSnapshot = ['rx' => '--'];
                $usagePayload = [];
                $trafficSnapshot = [];
                $enriched['detail_sections'] = [];
                $enriched['active_session'] = $activeSession;
                $enriched['optical'] = $opticalSnapshot;
                $enriched['usage'] = $usagePayload;
                $enriched['traffic'] = $trafficSnapshot;
            } else {
                $enriched['mac_binding'] = $this->getMacBinding((int) $customerId);
                $enriched['olt_onu'] = $this->getOltOnuDetails($enriched);
                $activeSession = $this->getActiveSession($enriched);
                $opticalSnapshot = $this->getOpticalSnapshot($enriched['olt_onu']);

                if (!empty($activeSession['available'])) {
                    $enriched['pppoe_uptime'] = $activeSession['uptime'];
                    if ($activeSession['address'] !== '--') {
                        $enriched['pppoe_address'] = $activeSession['address'];
                        $enriched['ip_address'] = $enriched['ip_address'] ?? $activeSession['address'];
                    }
                    if ($activeSession['caller_id'] !== '--') {
                        $enriched['pppoe_caller_id'] = $activeSession['caller_id'];
                    }
                }
                $enriched['active_session'] = $activeSession;
                $enriched['optical'] = $opticalSnapshot;

                $usagePayload = $this->getUsagePayload($enriched);
                $trafficSnapshot = $this->getTrafficSnapshot($enriched);

                $enriched['usage'] = $usagePayload;
                $enriched['traffic'] = $trafficSnapshot;
                $enriched['detail_sections'] = $this->buildDetailSections($enriched, $usagePayload, $trafficSnapshot);
            }

            $connectionDetails = is_array($enriched['connection_details'] ?? null)
                ? $enriched['connection_details']
                : $this->getConnectionDetails((int) $customerId);
    
            // Build complete response with all fields for Flutter model
            $fullData = isset($enriched['full_data']) && is_array($enriched['full_data']) ? $enriched['full_data'] : [];
            $fullPackage = isset($fullData['package']) && is_array($fullData['package']) ? $fullData['package'] : [];
            $resolvedBandwidth = $this->asString(
                $enriched['bandwidth']
                    ?? $fullPackage['bandwidth']
                    ?? $fullData['bandwidth']
                    ?? '--'
            );
            $resolvedPackagePrice = $this->asFloat(
                $enriched['package_price']
                    ?? $fullPackage['selling_price']
                    ?? $fullPackage['price']
                    ?? $fullData['package_price']
                    ?? 0
            );
            $resolvedPackageName = $this->asString(
                $enriched['package_name']
                    ?? $fullPackage['package_name']
                    ?? $fullData['package_name']
                    ?? null
            );
            $responseData = [
                // Basic customer info
                'id' => $this->asString($customerArr['id'] ?? null),
                'c_id' => $this->asString($customerArr['c_id'] ?? $customerArr['id'] ?? null),
                'customer_id' => $this->asString($customerArr['id'] ?? null),
                'name' => $this->asString($customerArr['name'] ?? null),
                'email' => $this->asString($customerArr['email'] ?? null),
                'phone' => $this->asString($customerArr['phone'] ?? $customerArr['mobile'] ?? null),
                'mobile' => $this->asString($customerArr['mobile'] ?? null),
                'address' => $this->asString($customerArr['address'] ?? null),
                
                // Status fields
                'status' => $this->asString($customerArr['status'] ?? $customerArr['subscription_status'] ?? 'inactive'),
                'subscription_status' => $this->asString($customerArr['subscription_status'] ?? 'inactive'),
                'conn_status' => $this->asString($customerArr['conn_status'] ?? 'disconn'),
                'connection_status' => $this->asString($customerArr['conn_status'] ?? 'disconn'),
                'activity' => $this->asString($customerArr['activity'] ?? '--'),
                
                // Financial info
                'balance' => $this->asFloat($customerArr['fund'] ?? $customerArr['balance'] ?? 0),
                'fund' => $this->asFloat($customerArr['fund'] ?? $customerArr['balance'] ?? 0),
                'due_amount' => $this->asFloat($customerArr['due_amount'] ?? 0),
                'due' => $this->asFloat($customerArr['due_amount'] ?? 0),
                'total_due' => $this->asFloat($customerArr['due_amount'] ?? 0),
                'payment_status' => $this->asString($customerArr['payment_status'] ?? 'inactive'),
                'last_payment_date' => $customerArr['last_payment_date'] ?? null,
                
                // Package info
                'package_id' => $this->asString($customerArr['package_id'] ?? null),
                'package_name' => $resolvedPackageName,
                'bandwidth' => $resolvedBandwidth,
                'package_price' => $resolvedPackagePrice,
                'selling_price' => $resolvedPackagePrice,
                'price' => $resolvedPackagePrice,
                'pricing' => $resolvedPackageName,
                'will_expire' => $customerArr['will_expire'] ?? null,
                'expiry_date' => $customerArr['will_expire'] ?? null,
                
                // Router & Area
                'router_id' => $this->asString($customerArr['router_id'] ?? null),
                'router' => $this->asString($enriched['router_name'] ?? '--'),
                'router_name' => $this->asString($enriched['router_name'] ?? '--'),
                'area_id' => $this->asString($customerArr['area_id'] ?? null),
                'service_area' => $this->asString($enriched['area_name'] ?? '--'),
                'area_name' => $this->asString($enriched['area_name'] ?? '--'),
                
                // PPPoE fields
                'pppoe_id' => $this->asString($customerArr['pppoe_id'] ?? null),
                'pppoe_name' => $this->asString($enriched['pppoe_name'] ?? '--'),
                'pppoe_secret' => $this->asString($enriched['pppoe_name'] ?? '--'),
                'secret' => $this->asString($enriched['pppoe_name'] ?? '--'),
                'pppoe_password' => $this->asString($enriched['pppoe_password'] ?? '--'),
                'router_password' => $this->asString($enriched['pppoe_password'] ?? '--'),
                'pppoe_service' => $this->asString($enriched['pppoe_service'] ?? 'pppoe'),
                'pppoe_profile' => $this->asString($enriched['pppoe_profile'] ?? '--'),
                'pppoe_caller_id' => $this->asString($enriched['pppoe_caller_id'] ?? '--'),
                'pppoe_last_caller_id' => $this->asString($enriched['pppoe_last_caller_id'] ?? '--'),
                'pppoe_uptime' => $this->asString($enriched['pppoe_uptime'] ?? '--'),
                'pppoe_address' => $this->asString($enriched['pppoe_address'] ?? '--'),
                'pppoe_status' => $this->asString($enriched['pppoe_disabled'] ?? 'no'),
                
                // Connection details
                'connection_type' => $this->asString($connectionDetails['connection_type'] ?? 'utp'),
                'client_type' => $this->asString($connectionDetails['client_type'] ?? 'home'),
                'billing_status' => $this->asString($connectionDetails['billing_status'] ?? 'active'),
                'cable_requirement' => $this->asString($connectionDetails['cable_requirement'] ?? '--'),
                'fiber_code' => $this->asString($connectionDetails['fiber_code'] ?? '--'),
                'number_of_core' => $this->asString($connectionDetails['number_of_core'] ?? '--'),
                'core_color' => $this->asString($connectionDetails['core_color'] ?? '--'),
                'otc' => $this->asString($connectionDetails['otc'] ?? '--'),
                'otc_status' => $this->asString($connectionDetails['otc_status'] ?? '--'),
                
                // Technical info
                'ip_address' => $this->asString($customerArr['ip_address'] ?? null),
                'mac_address' => $this->asString($customerArr['mac_address'] ?? null),
                'mac_bound' => (bool) ($enriched['mac_binding']['is_bound'] ?? false),
                'nid_number' => $this->asInt($customerArr['nid_number'] ?? 0),
                
                // User role & creation
                'role' => $this->asString($customerArr['role'] ?? 'user'),
                'admin_id' => $this->asString($customerArr['admin_id'] ?? null),
                'created_by' => $this->asString($customerArr['created_by'] ?? '--'),
                'designation' => $this->asString($customerArr['designation'] ?? '--'),
                
                // Dates
                'created_at' => $customerArr['created_at'] ?? null,
                'updated_at' => $customerArr['updated_at'] ?? null,
                
                // Nested connection details
                'connection_details' => $connectionDetails,
                
                // Full enriched object
                'full_data' => $enriched,
                'package' => $fullPackage,
    
                // Frequently used nested payloads for mobile.
                // We expose BOTH conventions so callers (web view, mobile, JS) all work without translation:
                //   - Mikrotik raw keys (`last-logged-out`, `last-caller-id`, …) like `app/Views/customers/details.php`
                //   - Underscore aliases (`last_logged_out`, `last_caller_id`, …) for typed parsers
                'pppoe' => array_merge(
                    is_array($enriched['pppoe_raw'] ?? null) ? $enriched['pppoe_raw'] : [],
                    [
                        'name' => $enriched['pppoe_name'] ?? '--',
                        'password' => $enriched['pppoe_password'] ?? '--',
                        'service' => $enriched['pppoe_service'] ?? '--',
                        'profile' => $enriched['pppoe_profile'] ?? '--',
                        'uptime' => $enriched['pppoe_uptime'] ?? '--',
                        'address' => $enriched['pppoe_address'] ?? '--',
                        'caller_id' => $enriched['pppoe_caller_id'] ?? '--',
                        'last_logged_out' => $enriched['pppoe_last_logged_out'] ?? '--',
                        'last_caller_id' => $enriched['pppoe_last_caller_id'] ?? '--',
                        'last_disconnect_reason' => $enriched['pppoe_last_disconnect_reason'] ?? '--',
                        'disabled' => $enriched['pppoe_disabled'] ?? '--',
                        'comment' => $enriched['pppoe_comment'] ?? '--',
                        // Hyphenated mirrors (match Mikrotik / web template keys)
                        'last-logged-out' => $enriched['pppoe_last_logged_out'] ?? '--',
                        'last-caller-id' => $enriched['pppoe_last_caller_id'] ?? '--',
                        'last-disconnect-reason' => $enriched['pppoe_last_disconnect_reason'] ?? '--',
                        'caller-id' => $enriched['pppoe_caller_id'] ?? '--',
                        // Connection state — `Enabled` / `Disabled` derived from `disabled` flag
                        'status' => $this->derivePppoeStatus($enriched['pppoe_disabled'] ?? null, !empty($activeSession['available'])),
                        // Live session flag for clients that need a quick boolean
                        'is_active' => !empty($activeSession['available']),
                    ]
                ),

                // Live PPP active session (uptime, address, caller-id while connected)
                'active_session' => $activeSession,
                'is_online' => !empty($activeSession['available']),

                // OLT/ONU optical signal — full block + flat aliases for easy mobile access
                'olt_onu' => $enriched['olt_onu'] ?? [],
                'optical' => $opticalSnapshot,
                'optical_signal' => $opticalSnapshot['rx'],
                'rx' => $opticalSnapshot['rx'],
                'olt_rx' => $opticalSnapshot['rx'],

                'mac_binding' => $enriched['mac_binding'] ?? [],
                'usage' => $usagePayload,
                'traffic' => $trafficSnapshot,
                'detail_sections' => $enriched['detail_sections'] ?? [],

                'payments' => $paymentsPayload['items'],
                'payments_summary' => $paymentsPayload['summary'],
            ];
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Customer details fetched successfully',
                // Flat response for easy parsing
                'data' => $responseData,
                
                // Organized sections for different UI areas
                'sections' => [
                    'account_info' => [
                        'id' => $responseData['id'],
                        'name' => $responseData['name'],
                        'email' => $responseData['email'],
                        'phone' => $responseData['phone'],
                        'mobile' => $responseData['mobile'],
                        'address' => $responseData['address'],
                        'nid_number' => $responseData['nid_number'],
                        'designation' => $responseData['designation'],
                        'created_by' => $responseData['created_by'],
                        'role' => $responseData['role'],
                        'admin_id' => $responseData['admin_id'],
                    ],
                    'package_info' => [
                        'package_id' => $responseData['package_id'],
                        'package_name' => $responseData['package_name'],
                        'bandwidth' => $responseData['bandwidth'],
                        'package_price' => $responseData['package_price'],
                        'will_expire' => $responseData['will_expire'],
                        'service_area' => $responseData['service_area'],
                        'router_name' => $responseData['router_name'],
                    ],
                    'pppoe_info' => [
                        'pppoe_id' => $responseData['pppoe_id'],
                        'pppoe_name' => $responseData['pppoe_name'],
                        'pppoe_password' => $responseData['pppoe_password'],
                        'pppoe_service' => $responseData['pppoe_service'],
                        'pppoe_profile' => $responseData['pppoe_profile'],
                        'pppoe_uptime' => $enriched['pppoe_uptime'] ?? '--',
                        'pppoe_address' => $enriched['pppoe_address'] ?? '--',
                        'pppoe_caller_id' => $responseData['pppoe_caller_id'],
                        'pppoe_last_caller_id' => $responseData['pppoe_last_caller_id'],
                    ],
                    'connection_info' => [
                        'connection_type' => $responseData['connection_type'],
                        'client_type' => $responseData['client_type'],
                        'billing_status' => $responseData['billing_status'],
                        'cable_requirement' => $responseData['cable_requirement'],
                        'fiber_code' => $responseData['fiber_code'],
                        'number_of_core' => $responseData['number_of_core'],
                        'core_color' => $responseData['core_color'],
                        'otc' => $responseData['otc'],
                        'otc_status' => $responseData['otc_status'],
                        'ip_address' => $responseData['ip_address'],
                        'mac_address' => $responseData['mac_address'],
                    ],
                    'financial_info' => [
                        'balance' => $responseData['balance'],
                        'due_amount' => $responseData['due_amount'],
                        'payment_status' => $responseData['payment_status'],
                        'last_payment_date' => $responseData['last_payment_date'],
                    ],
                    'payments' => [
                        'items' => $paymentsPayload['items'],
                        'summary' => $paymentsPayload['summary'],
                    ],
                ],
    
                // Status sections
                'customer_status' => $responseData['status'],
                'subscription_status' => $responseData['subscription_status'],
                'conn_status' => $responseData['conn_status'],
    
                // Device binding
                'mac_binding' => $enriched['mac_binding'],
    
                // OLT/ONU info + flat aliases
                'olt_onu' => $enriched['olt_onu'] ?? [],
                'optical' => $opticalSnapshot,
                'optical_signal' => $opticalSnapshot['rx'],
                'rx' => $opticalSnapshot['rx'],
                'olt_rx' => $opticalSnapshot['rx'],
    
                // PPPoE block for direct use
                'pppoe' => $responseData['pppoe'],
                'active_session' => $activeSession,
                'is_online' => $responseData['is_online'],
    
                // Usage & Traffic
                'usage' => $usagePayload,
                'traffic' => $trafficSnapshot,

                'payments' => $paymentsPayload['items'],
                'payments_summary' => $paymentsPayload['summary'],
            ]);
        }

        /**
         * All customer payment rows for this reseller (same scope as customer-payments/user API),
         * newest first, capped for safety.
         */
        private function buildCustomerPaymentsPayload(int $resellerId, int $customerId): array
        {
            $paymentModel = model('App\Models\Payment');
            $paymentRows = $paymentModel
                ->where('user_type', 'user')
                ->where('admin_id', $resellerId)
                ->groupStart()
                    ->where('user_id', $customerId)
                    ->orWhere('paidby', $customerId)
                ->groupEnd()
                ->orderBy('id', 'desc')
                ->findAll(10000);

            $list = [];
            $successful = 0.0;
            $pending = 0.0;
            $failed = 0.0;

            foreach ($paymentRows as $p) {
                $r = is_object($p) ? (array) $p : $p;
                $uid = (int) ($r['user_id'] ?? 0);
                $paidToId = (int) ($r['paid_to'] ?? 0);
                $paidById = (int) ($r['paidby'] ?? 0);

                $customerName = '--';
                if ($uid > 0 && function_exists('getUserById')) {
                    $u = getUserById($uid);
                    $customerName = $this->asString(($u && isset($u->name)) ? $u->name : '--');
                }
                $paidToName = '--';
                if ($paidToId > 0 && function_exists('getUserById')) {
                    $u = getUserById($paidToId);
                    $paidToName = $this->asString(($u && isset($u->name)) ? $u->name : '--');
                }
                $paidByName = '--';
                if ($paidById > 0 && function_exists('getUserById')) {
                    $u = getUserById($paidById);
                    $paidByName = $this->asString(($u && isset($u->name)) ? $u->name : '--');
                }

                $st = strtolower(trim((string) ($r['status'] ?? '')));
                $amt = $this->asFloat($r['amount'] ?? 0);
                if ($st === 'successful') {
                    $successful += $amt;
                } elseif ($st === 'pending') {
                    $pending += $amt;
                } elseif ($st === 'failed') {
                    $failed += $amt;
                }

                $list[] = [
                    'id' => $this->asString($r['id'] ?? ''),
                    'user_id' => $this->asString($r['user_id'] ?? ''),
                    'user_type' => $this->asString($r['user_type'] ?? 'user'),
                    'admin_id' => $this->asString($r['admin_id'] ?? ''),
                    'paidby' => $this->asString($r['paidby'] ?? ''),
                    'paid_by_name' => $paidByName,
                    'invoice' => $this->asString($r['invoice'] ?? ''),
                    'amount' => round($amt, 4),
                    'pay_amount' => round($this->asFloat($r['pay_amount'] ?? 0), 4),
                    'month' => $this->asString($r['month'] ?? ''),
                    'created_at' => $r['created_at'] ?? null,
                    'paid_at' => $r['paid_at'] ?? null,
                    'paid_via' => $this->asString($r['paid_via'] ?? ''),
                    'paid_to' => $this->asString($r['paid_to'] ?? ''),
                    'method_trx' => $this->asString($r['method_trx'] ?? ''),
                    'comment' => $this->asString($r['comment'] ?? ''),
                    'status' => $this->asString($r['status'] ?? ''),
                    'customer_name' => $customerName,
                    'paid_to_name' => $paidToName,
                ];
            }

            return [
                'items' => $list,
                'summary' => [
                    'total_count' => count($list),
                    'successful_amount' => round($successful, 2),
                    'pending_amount' => round($pending, 2),
                    'failed_amount' => round($failed, 2),
                ],
            ];
        }

        /**
         * Convert MikroTik's `disabled` flag (`true|false|--`) into a UI-friendly
         * status. When the live session is up we always report `Enabled`.
         */
        private function derivePppoeStatus($disabledFlag, bool $sessionActive): string
        {
            if ($sessionActive) {
                return 'Enabled';
            }
            $value = strtolower(trim((string) ($disabledFlag ?? '')));
            if ($value === 'false' || $value === 'no' || $value === '0') {
                return 'Enabled';
            }
            if ($value === 'true' || $value === 'yes' || $value === '1') {
                return 'Disabled';
            }
            return '--';
        }
    
}
