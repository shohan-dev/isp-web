<?php

namespace Zapi\Modules\Reseller\Customer\Services\CustomerService;

use App\Models\AuditLogModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @property \CodeIgniter\HTTP\IncomingRequest $request
 * @property object $user_model
 * @property \CodeIgniter\Database\BaseConnection $db
 * @method array getPaginationParams()
 * @method array enrichCustomer($customer, $allowRouterLookup = false)
 * @method array toArray($row)
 * @method mixed respondError(string $message, int $statusCode = 400, string $errorCode = 'REQUEST_FAILED', array $errors = [])
 * @method mixed respondPaginatedSuccess(array $data, int $total, int $currentPage, int $perPage, array $meta = [], string $message = 'Success')
 * @method mixed respondSuccess($data = null, string $message = 'Success', array $meta = [])
 */
trait CustomerServicePart02Segment
{
        private function part02AsInt($value): int
        {
            return (int) (is_numeric($value) ? $value : 0);
        }

        private function part02AsFloat($value): float
        {
            return (float) (is_numeric($value) ? $value : 0);
        }

        private function part02AsString($value, string $fallback = '--'): string
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

        /**
         * Bandwidth usage from `user_data_usage`.
         * Note: column `admin_id` stores the **customer user id** (see cron saveUsage), not the reseller id.
         *
         * Summary aligns with `app/Views/customers/details.php` (today → yesterday → latest date):
         *  - download_today_mb / upload_today_mb / total_usage_mb: sums for the selected calendar day (all interfaces).
         *  - peak_usage_mb: max single-row (rx_today+tx_today) on that day (same as legacy web “peak” chip).
         *  - peak_day_total_mb: max over history of (sum of rx_today + sum of tx_today) per date.
         *  - rx_total_mb / tx_total_mb: cumulative counters summed across all interfaces on the **latest** date in history.
         */
        private function getUsagePayload(array $customer)
        {
            $userId = $this->part02AsInt($customer['id'] ?? 0);
            $pppoeName = trim((string) ($customer['pppoe_name'] ?? $customer['secret'] ?? ''));

            $usageModel = model('App\Models\UserDataUsageModel');
            $rows = $usageModel
                ->where('admin_id', $userId)
                ->orderBy('date', 'ASC')
                ->findAll();

            if (empty($rows) && $pppoeName !== '') {
                $rows = $usageModel
                    ->where('user_name', $pppoeName)
                    ->orderBy('date', 'ASC')
                    ->findAll();
            }

            $history = [];
            foreach ($rows as $row) {
                $r = is_object($row) ? (array) $row : $row;
                $history[] = [
                    'id' => $this->part02AsInt($r['id'] ?? 0),
                    'admin_id' => $this->part02AsInt($r['admin_id'] ?? 0),
                    'user_name' => $this->part02AsString($r['user_name'] ?? ''),
                    'interface' => $this->part02AsString($r['interface'] ?? ''),
                    'date' => $this->part02AsString($r['date'] ?? ''),
                    'rx_mb' => round($this->part02AsFloat($r['rx_mb'] ?? 0), 4),
                    'tx_mb' => round($this->part02AsFloat($r['tx_mb'] ?? 0), 4),
                    'rx_today' => round($this->part02AsFloat($r['rx_today'] ?? 0), 4),
                    'tx_today' => round($this->part02AsFloat($r['tx_today'] ?? 0), 4),
                ];
            }

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $datesPresent = [];
            foreach ($history as $h) {
                $d = $h['date'] ?? '';
                if ($d !== '') {
                    $datesPresent[$d] = true;
                }
            }
            $datesList = array_keys($datesPresent);
            sort($datesList);

            $selectedDate = null;
            if (in_array($today, $datesList, true)) {
                $selectedDate = $today;
            } elseif (in_array($yesterday, $datesList, true)) {
                $selectedDate = $yesterday;
            } elseif (!empty($datesList)) {
                $selectedDate = $datesList[count($datesList) - 1];
            }

            $downloadToday = 0.0;
            $uploadToday = 0.0;
            $totalUsage = 0.0;
            $peakRowDay = 0.0;

            if ($selectedDate !== null) {
                foreach ($history as $row) {
                    if (($row['date'] ?? '') !== $selectedDate) {
                        continue;
                    }
                    $rxToday = (float) ($row['rx_today'] ?? 0);
                    $txToday = (float) ($row['tx_today'] ?? 0);
                    $rowTotal = $rxToday + $txToday;
                    $downloadToday += $rxToday;
                    $uploadToday += $txToday;
                    $totalUsage += $rowTotal;
                    if ($rowTotal > $peakRowDay) {
                        $peakRowDay = $rowTotal;
                    }
                }
            }

            $byDate = [];
            foreach ($history as $row) {
                $d = $row['date'] ?? '';
                if ($d === '') {
                    continue;
                }
                if (!isset($byDate[$d])) {
                    $byDate[$d] = [
                        'date' => $d,
                        'download_mb' => 0.0,
                        'upload_mb' => 0.0,
                        'total_mb' => 0.0,
                    ];
                }
                $rx = (float) ($row['rx_today'] ?? 0);
                $tx = (float) ($row['tx_today'] ?? 0);
                $byDate[$d]['download_mb'] += $rx;
                $byDate[$d]['upload_mb'] += $tx;
                $byDate[$d]['total_mb'] += $rx + $tx;
            }

            $peakDayTotal = 0.0;
            foreach ($byDate as $agg) {
                if (($agg['total_mb'] ?? 0) > $peakDayTotal) {
                    $peakDayTotal = (float) $agg['total_mb'];
                }
            }

            $latestDate = !empty($datesList) ? $datesList[count($datesList) - 1] : null;
            $cumulativeRx = 0.0;
            $cumulativeTx = 0.0;
            if ($latestDate !== null) {
                foreach ($history as $row) {
                    if (($row['date'] ?? '') !== $latestDate) {
                        continue;
                    }
                    $cumulativeRx += (float) ($row['rx_mb'] ?? 0);
                    $cumulativeTx += (float) ($row['tx_mb'] ?? 0);
                }
            }

            $byDateList = array_values($byDate);
            usort($byDateList, static function (array $a, array $b): int {
                return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
            });

            return [
                'history' => $history,
                'by_date' => $byDateList,
                'summary' => [
                    'selected_date' => $selectedDate,
                    'download_today_mb' => round($downloadToday, 4),
                    'upload_today_mb' => round($uploadToday, 4),
                    'total_usage_mb' => round($totalUsage, 4),
                    'peak_usage_mb' => round($peakRowDay, 4),
                    'peak_day_total_mb' => round($peakDayTotal, 4),
                    'rx_total_mb' => round($cumulativeRx, 4),
                    'tx_total_mb' => round($cumulativeTx, 4),
                    'cumulative_total_mb' => round($cumulativeRx + $cumulativeTx, 4),
                    'latest_meter_date' => $latestDate,
                    'history_day_count' => count($byDate),
                    'record_count' => count($history),
                ],
            ];
        }
    
        private function buildDetailSections(array $customer, array $usagePayload, array $trafficSnapshot)
        {
            return [
                'account_info' => [
                    'customer_id' => $this->part02AsString($customer['c_id'] ?? $customer['code'] ?? null),
                    'name' => $this->part02AsString($customer['name'] ?? null),
                    'mobile' => $this->part02AsString($customer['mobile'] ?? null),
                    'email' => $this->part02AsString($customer['email'] ?? null),
                    'address' => $this->part02AsString($customer['address'] ?? null),
                    'status' => $this->part02AsString($customer['status'] ?? null),
                    'subscription_status' => $this->part02AsString($customer['subscription_status'] ?? null),
                    'mac_bound' => (bool) ($customer['mac_bound'] ?? false),
                ],
                'package_info' => [
                    'package_id' => $this->part02AsString($customer['package_id'] ?? null),
                    'package_name' => $this->part02AsString($customer['package_name'] ?? null),
                    'package_price' => $this->part02AsFloat($customer['package_price'] ?? 0),
                    'will_expire' => $customer['will_expire'] ?? null,
                    'area_name' => $this->part02AsString($customer['area_name'] ?? null),
                    'router_name' => $this->part02AsString($customer['router_name'] ?? null),
                ],
                'connection_details' => $customer['connection_details'] ?? [],
                'pppoe_info' => [
                    'pppoe_id' => $this->part02AsString($customer['pppoe_id'] ?? null),
                    'pppoe_name' => $this->part02AsString($customer['pppoe_name'] ?? null),
                    'pppoe_password' => $this->part02AsString($customer['pppoe_password'] ?? null),
                    'pppoe_service' => $this->part02AsString($customer['pppoe_service'] ?? null),
                    'pppoe_profile' => $this->part02AsString($customer['pppoe_profile'] ?? null),
                    'uptime' => $this->part02AsString($customer['pppoe_uptime'] ?? null),
                    'address' => $this->part02AsString($customer['pppoe_address'] ?? null),
                    'caller_id' => $this->part02AsString($customer['pppoe_caller_id'] ?? null),
                ],
                'traffic' => $trafficSnapshot,
                'usage' => $usagePayload,
                'mac_binding' => $customer['mac_binding'] ?? [],
                'olt_onu' => $customer['olt_onu'] ?? [],
            ];
        }
    
        /**
         * GET /api/reseller/customers/(:num?)
         */
        public function fetch($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            /* Scoped only by the URL-supplied {resellerId}, so any reseller could
               GET /api/reseller/customers/{competitor_id} and read that competitor's
               entire customer list (credentials, balances, addresses). */
            if (!$this->canAccessReseller((int) $resellerId)) {
                return $this->respondError((string) 'Access denied', 403, 'REQUEST_FAILED');
            }

            $status = strtolower(trim((string) $this->request->getGet('status')));
            $subscriptionStatus = strtolower(trim((string) $this->request->getGet('subscription_status')));
            $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('q')));
            $currentTime = date('Y-m-d H:i:s');
            $currentMonth = date('m');
            $currentYear = date('Y');
    
            $builder = $this->user_model
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId);
    
            if ($status !== '' && $status !== 'all') {
                switch ($status) {
                    case 'active':
                        $builder
                            ->where('subscription_status', 'active')
                            ->where('will_expire >', $currentTime);
                        break;
    
                    case 'inactive':
                        $builder
                            ->groupStart()
                                ->where('subscription_status', 'inactive')
                                ->orWhere('status', 'inactive')
                                ->orWhere('conn_status', 'disconn')
                            ->groupEnd()
                            ->where('will_expire >', $currentTime);
                        break;
    
                    case 'expired':
                        $builder->where('will_expire <', $currentTime);
                        break;
    
                    case 'new':
                        $builder
                            ->where("MONTH(created_at) = $currentMonth", null, false)
                            ->where("YEAR(created_at) = $currentYear", null, false);
                        break;
    
                    default:
                        // Backward compatibility for older clients passing account status values.
                        $builder->where('status', $status);
                        break;
                }
            }
    
            if ($subscriptionStatus !== '' && $subscriptionStatus !== 'all') {
                $builder->where('subscription_status', $subscriptionStatus);
            }

            // Server-side search (debounced from clients) — match across the
            // most useful identity columns. Only enabled when the column exists
            // on the `users` table so this stays compatible with older schemas.
            if ($search !== '') {
                $candidateColumns = [
                    'name',
                    'email',
                    'mobile',
                    'phone',
                    'address',
                    'pppoe_secret',
                    'pppoe_id',
                    'pppoe_name',
                    'c_id',
                    'code',
                    'nid_number',
                ];
                $searchableColumns = ['id'];
                foreach ($candidateColumns as $column) {
                    if ($this->db->fieldExists($column, 'users')) {
                        $searchableColumns[] = $column;
                    }
                }

                $term = $search;
                $builder->groupStart();
                $first = true;
                foreach ($searchableColumns as $column) {
                    if ($first) {
                        $builder->like($column, $term, 'both', null, true);
                        $first = false;
                    } else {
                        $builder->orLike($column, $term, 'both', null, true);
                    }
                }
                $builder->groupEnd();
            }
    
            $pager = $this->getPaginationParams();
            $totalFound = (int) $builder->countAllResults(false);
            $customers = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);
    
            $result = [];
            foreach ($customers as $customer) {
                $result[] = $this->enrichCustomer($customer, false);
            }
    
            return $this->respondPaginatedSuccess(
                $result,
                $totalFound,
                $pager['current_page'],
                $pager['per_page']
            );
        }

        /**
         * GET /api/reseller/customers/{resellerId}/index
         * Lightweight full customer list for dropdowns.
         */
        public function index($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            $selectFields = ['id', 'name'];
            if ($this->db->fieldExists('c_id', 'users')) {
                $selectFields[] = 'c_id';
            }
            if ($this->db->fieldExists('code', 'users')) {
                $selectFields[] = 'code';
            }

            $rows = $this->user_model
                ->select(implode(', ', $selectFields))
                ->where('role', 'user')
                ->where('admin_id', (int) $resellerId)
                ->orderBy('name', 'asc')
                ->findAll();

            $result = [];
            foreach ($rows as $row) {
                $customer = $this->toArray($row);
                $id = trim((string) ($customer['id'] ?? ''));
                if ($id === '') {
                    continue;
                }

                $customerId = trim((string) ($customer['c_id'] ?? ''));
                if ($customerId === '') {
                    $customerId = trim((string) ($customer['code'] ?? $id));
                }

                $name = trim((string) ($customer['name'] ?? ''));
                if ($name === '') {
                    $name = $customerId !== '' ? $customerId : $id;
                }

                $result[] = [
                    'id' => $id,
                    'customer_id' => $customerId,
                    'name' => $name,
                ];
            }

            return $this->respondSuccess($result);
        }
    
}
