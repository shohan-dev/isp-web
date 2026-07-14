<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\allResellerPackage;
use Exception;

class Dashboard extends BaseController
{

    protected $payment_model, $user_model, $area_model, $ticket_model, $package_model, $router_model, $AdminPackage, $expense_model, $news_model;

    public function __construct()
    {
        /**
         * Payment Model
         */

        $this->payment_model = model('App\Models\Payment');
        $this->AdminPackage = model('App\Models\AdminPackage');

        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');

        /**
         * Area Model
         */
        $this->area_model = model('App\Models\Area');

        /**
         * Ticket Model
         */
        $this->ticket_model = model('App\Models\Ticket');

        /**
         * Package Model
         */
        $this->package_model = model('App\Models\Package');

        /**
         * Router Model
         */
        $this->router_model = model('App\Models\Router');

        /**
         * Expense Model
         */
        $this->expense_model = model('App\Models\ExpenseModel');
        $this->news_model = model('App\Models\News_notice');
    }



    function calculatePayments($admin)
    {
        // Step 1: Fetch user IDs for successful payments where user_type is 'user'
        $successfulUsers = $this->payment_model
            ->select('user_id')
            ->where(['user_type' => 'user', 'status' => 'successful'])
            ->findAll();

        $successfulUserIds = array_column($successfulUsers, 'user_id');



        // Step 2: Fetch user IDs for pending payments where user_type is 'user'
        $pendingUsers = $this->payment_model
            ->select('user_id')
            ->where(['user_type' => 'user', 'status' => 'pending'])
            ->findAll();

        $pendingUserIds = array_column($pendingUsers, 'user_id');

        $id = getSession('user_id');

        // Step 3: Filter these user IDs by checking if they were created by $admin
        $adminCreatedSuccessfulUsers = !empty($successfulUserIds) ? $this->user_model
            ->select('id')
            ->whereIn('id', $successfulUserIds)
            ->where('admin_id', $id)
            ->findAll() : [];

        $adminCreatedPendingUsers = !empty($pendingUserIds) ? $this->user_model
            ->select('id')
            ->whereIn('id', $pendingUserIds)
            ->where('admin_id', $id)
            ->findAll() : [];

        $adminSuccessfulUserIds = array_column($adminCreatedSuccessfulUsers, 'id');
        $adminPendingUserIds = array_column($adminCreatedPendingUsers, 'id');

        log_message('debug', 'User successfulUserIds id Type: ' . print_r($adminSuccessfulUserIds, true));
        log_message('debug', 'User pendingUserIds id Type: ' . print_r($adminPendingUserIds, true));

        // Step 4: Calculate sums
        $customers_payment_received = !empty($adminSuccessfulUserIds) ? (int) $this->payment_model
            ->selectSum('amount')
            ->whereIn('user_id', $adminSuccessfulUserIds)
            ->where(['user_type' => 'user', 'status' => 'successful'])
            ->first()
            ->amount : 0;

        $customers_payment_pending = !empty($adminPendingUserIds) ? (int) $this->payment_model
            ->selectSum('amount')
            ->whereIn('user_id', $adminPendingUserIds)
            ->where(['user_type' => 'user', 'status' => 'pending'])
            ->first()
            ->amount : 0;
        log_message('debug', 'User successfulUserIds id Type: ' . print_r($customers_payment_received, true));
        log_message('debug', 'User pendingUserIds id Type: ' . print_r($customers_payment_pending, true));
        return [
            'customers_payment_received' => $customers_payment_received,
            'customers_payment_pending' => $customers_payment_pending,
        ];
    }

    function employeePayments($admin)
    {
        // Step 1: Fetch user IDs for successful payments where user_type is 'user'
        $successfulUsers = $this->payment_model
            ->select('user_id')
            ->where(['user_type' => 'employee', 'status' => 'successful'])
            ->findAll();

        $successfulUserIds = array_column($successfulUsers, 'user_id');



        // Step 2: Fetch user IDs for pending payments where user_type is 'user'
        $pendingUsers = $this->payment_model
            ->select('user_id')
            ->where(['user_type' => 'employee', 'status' => 'pending'])
            ->findAll();

        $pendingUserIds = array_column($pendingUsers, 'user_id');

        $id = getSession('user_id');
        // Step 3: Filter these user IDs by checking if they were created by $admin
        $adminCreatedSuccessfulUsers = !empty($successfulUserIds) ? $this->user_model
            ->select('id')
            ->whereIn('id', $successfulUserIds)
            ->where('admin_id', $id)
            ->findAll() : [];

        $adminCreatedPendingUsers = !empty($pendingUserIds) ? $this->user_model
            ->select('id')
            ->whereIn('id', $pendingUserIds)
            ->where('admin_id', $id)
            ->findAll() : [];

        $adminSuccessfulUserIds = array_column($adminCreatedSuccessfulUsers, 'id');
        $adminPendingUserIds = array_column($adminCreatedPendingUsers, 'id');

        log_message('debug', 'User successfulUserIds id Type: ' . print_r($adminSuccessfulUserIds, true));
        log_message('debug', 'User pendingUserIds id Type: ' . print_r($adminPendingUserIds, true));

        // Step 4: Calculate sums
        $customers_payment_received = !empty($adminSuccessfulUserIds) ? (int) $this->payment_model
            ->selectSum('amount')
            ->whereIn('user_id', $adminSuccessfulUserIds)
            ->where(['user_type' => 'employee', 'status' => 'successful'])
            ->first()
            ->amount : 0;

        $customers_payment_pending = !empty($adminPendingUserIds) ? (int) $this->payment_model
            ->selectSum('amount')
            ->whereIn('user_id', $adminPendingUserIds)
            ->where(['user_type' => 'employee', 'status' => 'pending'])
            ->first()
            ->amount : 0;
        log_message('debug', 'User successfulUserIds id Type: ' . print_r($customers_payment_received, true));
        log_message('debug', 'User pendingUserIds id Type: ' . print_r($customers_payment_pending, true));
        return [
            'customers_payment_received' => $customers_payment_received,
            'customers_payment_pending' => $customers_payment_pending,
        ];
    }

    /**
     * Dashboard
     * @action: Dashboard View
     */
    public function index()
    {
        $id = getSession('user_id');
        $role = getSession('user_role');

        $currentTime = date('Y-m-d H:i:s');
        $currentM = date('m');
        $currentYear = date('Y');
        $data = ['ticket_solving_rate' => 0]; // Default for safety

        if ($role === 'super_admin') {
            $userId = session()->get('user_id');
            log_message('debug', 'userId insertId: ' . $userId);

            $db = db_connect();
            $thisMonth = date('F');
            $thisYear = (int) date('Y');
            $lastMonthTs = strtotime('first day of last month');
            $lastMonth = date('F', $lastMonthTs);
            $lastMonthYear = (int) date('Y', $lastMonthTs);

            $sumSadminPayments = function (string $month, int $year) use ($db) {
                $row = $db->table('payments')
                    ->select('COALESCE(SUM(payments.amount), 0) as total, COUNT(payments.id) as cnt')
                    ->join('users', 'users.id = payments.user_id', 'inner')
                    ->where('users.role', 'admin')
                    ->where('payments.status', 'successful')
                    ->where('payments.month', $month)
                    ->groupStart()
                        ->where('YEAR(COALESCE(payments.paid_at, payments.created_at)) =', $year, false)
                        ->orLike('payments.period', $year . '-', 'after')
                    ->groupEnd()
                    ->get()
                    ->getRowArray();

                return [
                    'amount' => (float) ($row['total'] ?? 0),
                    'count' => (int) ($row['cnt'] ?? 0),
                ];
            };

            $allTime = $db->table('payments')
                ->select('COALESCE(SUM(payments.amount), 0) as total, COUNT(payments.id) as cnt')
                ->join('users', 'users.id = payments.user_id', 'inner')
                ->where('users.role', 'admin')
                ->where('payments.status', 'successful')
                ->get()
                ->getRowArray();

            $pending = $db->table('payments')
                ->select('COALESCE(SUM(payments.amount), 0) as total, COUNT(payments.id) as cnt')
                ->join('users', 'users.id = payments.user_id', 'inner')
                ->where('users.role', 'admin')
                ->where('payments.status', 'pending')
                ->get()
                ->getRowArray();

            $thisMonthData = $sumSadminPayments($thisMonth, $thisYear);
            $lastMonthData = $sumSadminPayments($lastMonth, $lastMonthYear);

            $monthly = [];
            for ($i = 5; $i >= 0; $i--) {
                $ts = strtotime("-{$i} months");
                $dataMonth = $sumSadminPayments(date('F', $ts), (int) date('Y', $ts));
                $monthly[] = [
                    'label' => date('M Y', $ts),
                    'amount' => $dataMonth['amount'],
                    'count' => $dataMonth['count'],
                ];
            }

            $packageUsers = $db->table('users')
                ->select('COALESCE(admin_packages.package_name, "No package") as package_name, COUNT(users.id) as user_count')
                ->join('admin_packages', 'admin_packages.id = users.package_id', 'left')
                ->where('users.role', 'admin')
                ->groupBy('users.package_id')
                ->orderBy('user_count', 'DESC')
                ->get()
                ->getResultArray();

            $data = [
                'total_users' => (int) $this->user_model
                    ->where(['role' => 'admin'])
                    ->countAllResults(),
                'users_active' => (int) $this->user_model
                    ->where(['role' => 'admin', 'subscription_status' => 'active'])
                    ->countAllResults(),
                'users_inactive' => (int) $this->user_model
                    ->where(['role' => 'admin', 'subscription_status' => 'inactive'])
                    ->where('will_expire <', $currentTime)
                    ->countAllResults(),
                'total_packages' => (int) $this->AdminPackage
                    ->countAllResults(),
                'revenue_total_all' => (float) ($allTime['total'] ?? 0),
                'revenue_total_count' => (int) ($allTime['cnt'] ?? 0),
                'revenue_this_month' => $thisMonthData['amount'],
                'revenue_this_month_count' => $thisMonthData['count'],
                'revenue_last_month' => $lastMonthData['amount'],
                'revenue_last_month_count' => $lastMonthData['count'],
                'revenue_pending' => (float) ($pending['total'] ?? 0),
                'revenue_pending_count' => (int) ($pending['cnt'] ?? 0),
                'revenue_monthly' => $monthly,
                'package_users' => $packageUsers,
                'revenue_this_month_label' => date('F Y'),
                'revenue_last_month_label' => date('F Y', $lastMonthTs),
            ];
        } elseif ($role === 'resellerAdmin') {
            $userId = session()->get('user_id');
            $details = $this->user_model->where(['id' => $userId])->first();
            $router_id = $details->router_id ?? null;
            $currentMonth = date('F');
            $customers_payment_received = (int) $this->payment_model
                ->selectSum('amount')
                ->where('admin_id', $userId)
                ->where('month', $currentMonth)
                ->where(['user_type' => 'user', 'status' => 'successful'])
                ->first()
                ->amount;

            $customers_payment_received_count = (int) $this->payment_model
                ->selectSum('amount')
                ->where('admin_id', $userId)
                ->where('month', $currentMonth)
                ->where(['user_type' => 'user', 'status' => 'successful'])
                ->countAllResults();
            $userIds = $this->user_model->select('id')->where('admin_id', $userId)->where('role', 'user')->countAllResults();

            // Calculate estimated daily and monthly charges based on active customer package selling prices
            $activeCustomers = $this->user_model->select('id, package_id')
                ->where([
                    'role' => 'user',
                    'admin_id' => $userId,
                    'subscription_status' => 'active',
                ])
                ->where('will_expire >', $currentTime)
                ->where('conn_status', 'conn')
                ->findAll();

            $estimated_monthly = 0;
            $estimated_daily = 0;

            if (!empty($activeCustomers)) {
                $resellerPackageSimpleModel = new allResellerPackage();
                $packagePriceObj = $resellerPackageSimpleModel->where('user_id', $userId)->first();
                $resellerPkgPriceMap = [];
                if ($packagePriceObj) {
                    $packageDetails = json_decode($packagePriceObj['package_details'], true);
                    if (is_array($packageDetails)) {
                        foreach ($packageDetails as $detail) {
                            if (isset($detail['id'])) {
                                $detailprice = (isset($detail['selling_price']) && is_numeric($detail['selling_price']) && $detail['selling_price'] > 0)
                                    ? $detail['selling_price']
                                    : (is_numeric($detail['price'] ?? null) ? $detail['price'] : 0);
                                $resellerPkgPriceMap[$detail['id']] = (float) $detailprice;
                            }
                        }
                    }
                }

                foreach ($activeCustomers as $customer) {
                    $pkgId = $customer->package_id;
                    if (!empty($pkgId) && isset($resellerPkgPriceMap[$pkgId])) {
                        $price = $resellerPkgPriceMap[$pkgId];
                        $estimated_monthly += $price;
                        $estimated_daily += round($price / 30, 2);
                    }
                }
            }

            $data = [
                'billing_type' => $details->billing_type ?? 'postpaid',
                'estimated_daily_cost' => $estimated_daily,
                'estimated_monthly_cost' => $estimated_monthly,
                'customers_payment_received' => $customers_payment_received,
                'customers_payment_total' => (int) ($packageTotal = getPackagePrice()),
                'customers_Expayment_total' => ($packageTotal > 0)
                    ? (int) $packageTotal - $customers_payment_received
                    : 0,
                'customers_Expayment_count' => ((int) $userIds) > 0
                    ? (int) $userIds - $customers_payment_received_count
                    : 0,
                'customers_payment_received_count' => (int) $customers_payment_received_count,
                'customers_payment_pending' => (int) $this->payment_model
                    ->selectSum('amount')
                    ->where('admin_id', $userId)
                    ->where('month', $currentMonth)
                    ->where(['user_type' => 'user', 'status' => 'pending'])
                    ->first()
                    ->amount,
                'employee_payment_received' => (int) $this->payment_model
                    ->selectSum('amount')
                    ->where('admin_id', $userId)
                    ->where(['user_type' => 'employee', 'status' => 'successful'])
                    ->first()
                    ->amount,
                'employees_payment_pending' => (int) $this->payment_model
                    ->selectSum('amount')
                    ->where('admin_id', $userId)
                    ->where(['user_type' => 'employee', 'status' => 'pending'])
                    ->first()
                    ->amount,
                'users_active' => (int) $this->user_model
                    ->where(['role' => 'user', 'admin_id' => $id, 'subscription_status' => 'active'])
                    ->Where('will_expire >', $currentTime)
                    ->where('conn_status', 'conn')
                    ->countAllResults(),
                'users_new' => (int) $this->user_model
                    ->where(['role' => 'user', 'admin_id' => $id])
                    ->where("MONTH(created_at) = $currentM", null, false)
                    ->where("YEAR(created_at) = $currentYear", null, false)
                    ->countAllResults(),
                'users_inactive' => (int) $this->user_model
                    ->where('role', 'user')
                    ->where('admin_id', $id)
                    ->where('conn_status', 'disconn')
                    ->countAllResults(),
                'expired_inactive' => (int) $this->user_model
                    ->where('role', 'user')
                    ->where('admin_id', $id)
                    ->where('will_expire <', $currentTime)
                    ->groupStart()
                        ->where('conn_status !=', 'disconn')
                        ->orWhere('conn_status', null)
                    ->groupEnd()
                    ->countAllResults(),
                'employee_active' => (int) $this->user_model
                    ->where(['role' => 'employee', 'admin_id' => $id, 'status' => 'active'])
                    ->countAllResults(),
                'employee_inactive' => (int) $this->user_model
                    ->where(['role' => 'employee', 'admin_id' => $id, 'status' => 'inactive'])
                    ->countAllResults(),
                'total_packages' => (int) $this->package_model
                    ->where(['status' => 'active'])
                    ->where('user_id', $userId)
                    ->countAllResults(),
                'total_area' => (int) $this->area_model
                    ->where(['status' => 'active'])
                    ->where('user_id', $userId)
                    ->countAllResults(),
                'router_active' => (int) $this->router_model
                    ->where(['status' => 'active'])
                    ->where('user_id', $userId)
                    ->countAllResults(),
                'router_inactive' => (int) $this->router_model
                    ->where(['status' => 'inactive'])
                    ->where('user_id', $userId)
                    ->countAllResults(),
                'customer_payment_statistics' => $this->statistics('user'),
                'employee_payment_statistics' => $this->statistics('employee'),
                'routers' => (function() use ($router_id, $userId) {
                    $routers = $this->router_model
                        ->where('status', 'active')
                        ->where('id', $router_id)
                        ->findAll();
                    foreach ($routers as &$router) {
                        $rId = is_array($router) ? $router['id'] : $router->id;
                        // Read per-user cache first, fall back to shared (legacy) cache
                        $cached = cache('router_stats_summary_' . $rId . '_user_' . $userId);
                        if (!$cached) {
                            $cached = cache("router_stats_summary_" . $rId);
                        }
                        $total = $cached['total'] ?? 0;
                        $active = $cached['active'] ?? 0;
                        $status = $cached['status'] ?? 'online';
                        $lastUpdated = $cached['last_updated'] ?? '';
                        
                        if (is_array($router)) {
                            $router['cached_total'] = $total;
                            $router['cached_active'] = $active;
                            $router['cached_status'] = $status;
                            $router['cached_last_updated'] = $lastUpdated;
                        } else {
                            $router->cached_total = $total;
                            $router->cached_active = $active;
                            $router->cached_status = $status;
                            $router->cached_last_updated = $lastUpdated;
                        }
                    }
                    return $routers;
                })(),
            ];
        } elseif ($role === 'admin') {
            $userId = session()->get('user_id');
            helper('subscription');
            $trialUser = $this->user_model->find($userId);
            $customerQuota = getTenantCustomerQuota((int) $userId);
            // NOTE: Card metrics are loaded asynchronously via /dashboard/sadmin-data AJAX.
            // We only fetch the minimal data needed to render the page skeleton here.
            
            $routers = $this->router_model
                ->where('status', 'active')
                ->where('user_id', $userId)
                ->findAll();

            foreach ($routers as &$router) {
                $routerId = is_array($router) ? $router['id'] : $router->id;
                // Read per-user cache first, fall back to shared (legacy) cache
                $cached = cache('router_stats_summary_' . $routerId . '_user_' . $userId);
                if (!$cached) {
                    $cached = cache("router_stats_summary_" . $routerId);
                }
                
                $total = $cached['total'] ?? 0;
                $active = $cached['active'] ?? 0;
                $status = $cached['status'] ?? 'online'; // Default to online at first as requested
                $lastUpdated = $cached['last_updated'] ?? '';
                
                if (is_array($router)) {
                    $router['cached_total'] = $total;
                    $router['cached_active'] = $active;
                    $router['cached_status'] = $status;
                    $router['cached_last_updated'] = $lastUpdated;
                } else {
                    $router->cached_total = $total;
                    $router->cached_active = $active;
                    $router->cached_status = $status;
                    $router->cached_last_updated = $lastUpdated;
                }
            }

            // Read card metrics from cache — same approach as router data.
            // If cache exists, values render INSTANTLY in HTML with no AJAX wait.
            // AJAX still fires in background to refresh stale values.
            $cachedMetrics = cache('sadmin_card_metrics_user_' . $userId) ?? [];

            $data = [
                'routers'                        => $routers,
                'trialUser'                      => $trialUser,
                'customer_quota'                 => $customerQuota,
                'users_active'                   => $cachedMetrics['users_active'] ?? 0,
                'users_new'                      => $cachedMetrics['users_new'] ?? 0,
                'users_inactive'                 => $cachedMetrics['users_inactive'] ?? 0,
                'expired_inactive'               => $cachedMetrics['expired_inactive'] ?? 0,
                'customers_payment_total'        => $cachedMetrics['customers_payment_total'] ?? 0,
                'customers_Expayment_total'      => $cachedMetrics['customers_Expayment_total'] ?? 0,
                'customers_Expayment_count'      => $cachedMetrics['customers_Expayment_count'] ?? 0,
                'customers_payment_received'     => $cachedMetrics['customers_payment_received'] ?? 0,
                'customers_payment_received_count' => $cachedMetrics['customers_payment_received_count'] ?? 0,
                'customers_payment_pending'      => $cachedMetrics['customers_payment_pending'] ?? 0,
                'total_packages'                 => $cachedMetrics['total_packages'] ?? 0,
                'total_area'                     => $cachedMetrics['total_area'] ?? 0,
                'employee_active'                => $cachedMetrics['employee_active'] ?? 0,
                'employee_inactive'              => $cachedMetrics['employee_inactive'] ?? 0,
                'employee_payment_received'      => $cachedMetrics['employee_payment_received'] ?? 0,
                'employees_payment_pending'      => $cachedMetrics['employees_payment_pending'] ?? 0,
                'router_active'                  => $cachedMetrics['router_active'] ?? 0,
                'router_inactive'                => $cachedMetrics['router_inactive'] ?? 0,
                'all_resellers'                  => $cachedMetrics['all_resellers'] ?? 0,
                'customer_payment_statistics'    => ['months' => [], 'successful' => [], 'pending' => [], 'failed' => []],
                'employee_payment_statistics'    => ['months' => [], 'successful' => [], 'pending' => [], 'failed' => []],
                'ticket_stats'                   => ['open' => 0, 'ongoing' => 0, 'solved' => 0, 'closed' => 0],
                'package_distribution'           => [],
                'payment_methods'                => [],
                'geo_revenue'                    => [],
                'weekly_collections'             => [],
                'growth_churn'                   => [],
                'revenue_overview'               => [],
                'col_rate'                       => 0,
                'efficiency_rate'                => 0,
                'ticket_solving_rate'            => 0,
                'retention_rate'                 => 0,
                'weekly_growth'                  => 0,
                'bandwidth_peak'                 => 0,
                'total_data_gb'                  => 0,
            ];


        } elseif ($role === 'employee') {
            $userId = session()->get('user_id');
            $details = $this->user_model->where(['id' => $userId])->first();
            $area_ids = explode(',', $details->area_id ?? '');

            if (!empty($area_ids)) {
                $conditions = implode(' OR ', array_map(fn($id) => "FIND_IN_SET($id, area_id) > 0", $area_ids));
                $total_users = $this->user_model
                    ->where('role', 'user')
                    ->where('subscription_status', 'active')
                    ->where('admin_id', $details->admin_id)
                    ->where("($conditions)")
                    ->countAllResults();
                $inactive_users = $this->user_model
                    ->where('role', 'user')
                    ->where('admin_id', $details->admin_id)
                    ->where("($conditions)")
                    ->groupStart()
                    ->where('subscription_status', 'inactive')
                    ->groupEnd()
                    ->countAllResults();
                $expired_inactive = (int) $this->user_model
                    ->where('role', 'user')
                    ->where('admin_id', $id)
                    ->groupStart()
                    ->where('will_expire <', $currentTime)
                    ->groupEnd()
                    ->countAllResults();
            } else {
                $total_users = 0;
            }

            $data = [
                'payment_received' => (int) $this->payment_model
                    ->selectSum('amount')
                    ->where([
                        'user_id' => getSession('user_id'),
                        'user_type' => 'employee',
                        'status' => 'successful'
                    ])
                    ->first()
                    ->amount,
                'payment_pending' => (int) $this->payment_model
                    ->selectSum('amount')
                    ->where([
                        'user_id' => getSession('user_id'),
                        'user_type' => 'employee',
                        'status' => 'pending'
                    ])
                    ->first()
                    ->amount,
                'total_area_customers_active' => (int) $total_users,
                'total_area_customers_inactive' => (int) $inactive_users,
                'statistics' => $this->statistics('employee', getSession('user_id')),
            ];
        } elseif ($role === 'user') {
            set_time_limit(0);

            $details = $this->user_model->where(['id' => $id, 'role' => 'user'])->first();
            $ppoe = '';
            if (!empty($details)) {
                $router_client = routerClient($details->router_id);
                if (!is_array($router_client)) {
                    $pppoe = getPPPoEUserUserId($router_client, $id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $details->pppoe_id ?? null;
                    log_message('info', "PPPoE ID for User ID {$id}: {$pppoe_id}");
                    $user_ppp = getPPPoEUser($router_client, $pppoe_id);
                    $interface = getGetInput('interface') ?? null;
                    $ppoe = $user_ppp[0]['name'] ?? '--';
                }
            }

            $data = [
                'pppoe' => $ppoe,
                'details' => $details,
                'package' => $this->package_model->find($details->package_id),
                'payment_received' => (int) $this->payment_model
                    ->selectSum('amount')
                    ->where([
                        'user_id' => getSession('user_id'),
                        'paidby' => getSession('user_id'),
                        'user_type' => 'user',
                        'status' => 'successful'
                    ])
                    ->first()
                    ->amount,
                'payment_pending' => (int) $this->payment_model
                    ->selectSum('amount')
                    ->where([
                        'user_id' => getSession('user_id'),
                        'paidby' => getSession('user_id'),
                        'user_type' => 'user',
                        'status' => 'pending'
                    ])
                    ->first()
                    ->amount,
                'total_support_ticket' => (int) $this->ticket_model
                    ->where([
                        'user_id' => getSession('user_id'),
                    ])
                    ->countAllResults(),
                'statistics' => $this->statistics('user', getSession('user_id')),
                'admin_details' => $this->user_model->asObject()->where('id', $details->admin_id)->first(),
                'notices' => $this->news_model->asObject()->where('admin_id', $details->admin_id)->orderBy('id', 'DESC')->limit(10)->findAll(),
            ];
        }




        if (isset($data)) {
            /* str_replace('Admin', '', $role) maps resellerAdmin -> reseller, and
               leaves 'user'/'employee' alone. But it is case-sensitive, so
               'super_admin' passes through unchanged and resolved to the view
               'dashboard/super_admin', which does not exist — the platform
               owner's dashboard threw "View file not found" on every login. The
               super_admin branch above builds revenue_total_all / package_users /
               total_users, which is exactly what dashboard/admin.php renders
               (dashboard/sAdmin.php is the role=admin view). Map it explicitly. */
            $viewName = 'dashboard/' . str_replace('Admin', '', $role);
            if ($role === 'admin') {
                $viewName = 'dashboard/sAdmin';
            } elseif ($role === 'super_admin') {
                $viewName = 'dashboard/admin';
            }
            return view($viewName, $data);
        }

        show_404();
    }


    /**
     * Dashboard
     * @action: Payment Statistics
     */
    protected function statistics($role = null, $user_id = null)
    {
        $sessionId = getSession('user_id');
        $sessionRole = session()->get('user_role');

        $cacheKey = 'dashboard_statistics_' . (empty($role) ? 'none' : $role) . '_' . (empty($user_id) ? 'none' : $user_id) . '_' . $sessionId . '_' . $sessionRole;
        if ($cached = cache($cacheKey)) {
            return $cached;
        }

        $conditions = [];
        if (!empty($role)) {
            $conditions['user_type'] = $role;
        }

        if ($sessionRole === 'user') {
            $conditions['user_id'] = $sessionId;
        } else {
            $conditions['admin_id'] = !empty($user_id) ? $user_id : $sessionId;
        }

        // Get all transactions grouped by month and status
        $results = $this->payment_model
            ->select('month, status, SUM(amount) as total_amount')
            ->where($conditions)
            ->groupBy('month, status')
            ->findAll();

        $lookup = [];
        foreach ($results as $row) {
            $monthKey = strtolower($row->month);
            $lookup[$monthKey][$row->status] = (int)$row->total_amount;
        }

        $months = [];
        $success_payment = [];
        $pending_payment = [];
        $failed_payment = [];

        $currentMonthNum = (int)date('m');

        for ($i = 1; $i <= $currentMonthNum; $i++) {
            $monthName = strtolower(date('F', mktime(0, 0, 0, $i, 1, 2022)));
            $monthAbbr = date('M', mktime(0, 0, 0, $i, 1, 2022));

            $successful = $lookup[$monthName]['successful'] ?? 0;
            $pending = $lookup[$monthName]['pending'] ?? 0;
            $failed = $lookup[$monthName]['failed'] ?? 0;

            $months[] = $monthAbbr;
            $success_payment[] = $successful;
            $pending_payment[] = $pending;
            $failed_payment[] = $failed;
        }

        $result = [
            'months' => $months,
            'successful' => $success_payment,
            'pending' => $pending_payment,
            'failed' => $failed_payment
        ];

        cache()->save($cacheKey, $result, 300);

        return $result;
    }




    /**
     * Dashboard
     * @action: Transaction Statictics
     */
    protected function __transactionStatistics($status, $month, $role = null, $user_id = null)
    {

        $conditions = [
            'month' => $month,
            'status' => $status
        ];

        if (!empty($role)) {
            $conditions['user_type'] = $role;
        }

        $sessionId = getSession('user_id');
        $sessionRole = session()->get('user_role');

        if ($sessionRole === 'user') {
            // Customer: filter to their own payments only
            $conditions['user_id'] = $sessionId;
        } else {
            // Admin/sAdmin/employee: use admin_id
            // If a specific admin_id was passed (e.g., from sAdmin block), use it
            $conditions['admin_id'] = !empty($user_id) ? $user_id : $sessionId;
        }

        $amount = $this->payment_model->selectSum('amount')->where($conditions)->first()->amount;
        return (int) $amount;
    }

    public function bandwidthUsage($routerId = null)
    {
        $userId = session()->get('user_id');
        $role = session()->get('user_role');
        $routerId = ($routerId === null || $routerId === '') ? 'all' : (string) $routerId;

        // Everything below is read-only and session-free. PHP holds an exclusive lock
        // on the session file for the life of a request, so without this the dashboard's
        // other in-flight AJAX calls queue behind this one — the switch felt slow even
        // when the query was not.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $cacheKey = 'bw_usage_' . (int) $userId . '_' . preg_replace('/[^A-Za-z0-9]/', '', $routerId);
        if (($cached = cache($cacheKey)) !== null) {
            return $this->response->setJSON($cached);
        }

        $db = db_connect();

        // Latest date with usage data (avoids empty "today" on local/dev)
        $maxDateBuilder = $db->table('user_data_usage')
            ->selectMax('user_data_usage.date', 'date')
            ->join('users', 'users.id = user_data_usage.admin_id', 'inner');

        if ($role === 'admin') {
            $maxDateBuilder->join('routers', 'routers.id = users.router_id', 'left')
                ->groupStart()
                    ->where('routers.user_id', $userId)
                    ->orWhere('users.admin_id', $userId)
                ->groupEnd();
        } else {
            $maxDateBuilder->where('users.admin_id', $userId);
        }

        if ($routerId !== 'all') {
            $maxDateBuilder->where('users.router_id', $routerId);
        }

        $maxDateRow = $maxDateBuilder->get()->getRowArray();
        $today = $maxDateRow['date'] ?? date('Y-m-d');
        if (empty($today)) {
            $today = date('Y-m-d');
        }

        // The whole week in ONE grouped query. This used to run a JOIN+SUM per day
        // inside a loop (7), plus one for the day's total, plus the max-date probe
        // above — nine round trips, every one of them re-scanning user_data_usage,
        // for a router switch. That was the delay.
        $windowStart = date('Y-m-d', strtotime('-6 days', strtotime($today)));

        $builder = $db->table('user_data_usage')
            ->select('user_data_usage.date AS d, SUM(user_data_usage.rx_today + user_data_usage.tx_today) AS total', false)
            ->join('users', 'users.id = user_data_usage.admin_id', 'inner')
            ->where('user_data_usage.date >=', $windowStart)
            ->where('user_data_usage.date <=', $today);

        if ($role === 'admin') {
            $builder->join('routers', 'routers.id = users.router_id', 'left')
                ->groupStart()
                    ->where('routers.user_id', $userId)
                    ->orWhere('users.admin_id', $userId)
                ->groupEnd();
        } else {
            $builder->where('users.admin_id', $userId);
        }

        if ($routerId !== 'all') {
            $builder->where('users.router_id', $routerId);
        }

        $byDate = [];
        foreach ($builder->groupBy('user_data_usage.date')->get()->getResultArray() as $row) {
            // substr guards a DATETIME column: the bucket key must be the plain day.
            $byDate[substr((string) $row['d'], 0, 10)] = (float) $row['total'];
        }

        $history = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days", strtotime($today)));
            $labels[] = date('D', strtotime($d));
            $history[] = round($byDate[$d] ?? 0, 2); // MB
        }

        $totalGB = round(($byDate[$today] ?? 0) / 1024, 2);

        $payload = [
            'status' => 'success',
            'data' => $history,
            'labels' => $labels,
            'total_gb' => $totalGB,
            'router_id' => $routerId,
            'as_of' => $today,
        ];

        // These are per-day aggregates fed by cron — they do not move second to
        // second, so a short cache makes flipping back and forth between routers
        // free instead of re-running the scan each time.
        cache()->save($cacheKey, $payload, 60);

        return $this->response->setJSON($payload);
    }

    public function sadminData()
    {
        if (session()->get('user_role') !== 'admin') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $userId = session()->get('user_id');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        helper('flag');
        $dashCacheKey = 'dash_sadmin_' . $userId;
        if (flag('degrade_mode') || ! flag('dashboard_polling', true)) {
            if (($cached = cache($dashCacheKey)) !== null) {
                return $this->response->setJSON($cached);
            }
        }

        $cardMetrics = $this->getSadminCardMetrics($userId, true);
        $responseData = array_merge(['status' => 'success'], $cardMetrics);

        cache()->save('sadmin_dashboard_metrics_data_user_' . $userId, $responseData, 86400);
        cache()->save($dashCacheKey, $responseData, 30);

        return $this->response->setJSON($responseData);
    }

    public function sadminChartsData()
    {
        if (session()->get('user_role') !== 'admin') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $userId = session()->get('user_id');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $cacheKey = 'sadmin_dashboard_charts_data_user_' . $userId;

        if ($cachedResponse = cache($cacheKey)) {
            return $this->response->setJSON($cachedResponse);
        }

        $currentMonth = date('F');
        $currentTime = date('Y-m-d H:i:s');
        $currentM = date('m');
        $currentYear = date('Y');

        $cardMetrics = $this->getSadminCardMetrics($userId);
        $users_active = $cardMetrics['users_active'];
        $expired_inactive = $cardMetrics['expired_inactive'];
        $customers_payment_total = $cardMetrics['customers_payment_total'];
        $customers_payment_received = $cardMetrics['customers_payment_received'];
        $customers_payment_received_count = $cardMetrics['customers_payment_received_count'];

        // 1.5. Support Statistics Rate — use shared cache from getSadminCardMetrics/sadminData
        $ticket_stats_cache_key = 'sadmin_ticket_stats_user_' . $userId;
        $ticket_solving_rate_cache_key = 'sadmin_ticket_solving_rate_user_' . $userId;
        $ticket_stats = cache($ticket_stats_cache_key);
        $ticket_solving_rate = cache($ticket_solving_rate_cache_key);
        if ($ticket_stats === null || $ticket_solving_rate === null) {
            $ticket_stats = [
                'open' => $this->ticket_model->where("JSON_CONTAINS(admin_ids, '\"{$userId}\"')", null, false)->where('status', 'opened')->countAllResults(),
                'ongoing' => $this->ticket_model->where("JSON_CONTAINS(admin_ids, '\"{$userId}\"')", null, false)->where('status', 'processing')->countAllResults(),
                'solved' => $this->ticket_model->where("JSON_CONTAINS(admin_ids, '\"{$userId}\"')", null, false)->where('status', 'solved')->countAllResults(),
                'closed' => $this->ticket_model->where("JSON_CONTAINS(admin_ids, '\"{$userId}\"')", null, false)->where('status', 'closed')->countAllResults(),
            ];
            $total_tickets = $ticket_stats['open'] + $ticket_stats['ongoing'] + $ticket_stats['solved'] + $ticket_stats['closed'];
            $ticket_solving_rate = ($total_tickets > 0)
                ? round((($ticket_stats['solved'] + $ticket_stats['closed']) / $total_tickets) * 100, 1)
                : 0;
            cache()->save($ticket_stats_cache_key, $ticket_stats, 300);
            cache()->save($ticket_solving_rate_cache_key, $ticket_solving_rate, 300);
        }

        // Efficiency (MTD Collection Rate)
        $efficiency_rate = ($customers_payment_total > 0)
            ? round(($customers_payment_received / $customers_payment_total) * 100, 1)
            : 0;

        // Retention
        $retention_rate = ($users_active + $expired_inactive > 0)
            ? round(($users_active / ($users_active + $expired_inactive)) * 100, 1)
            : 0;

        // Package distribution
        $package_dist_cache_key = 'sadmin_package_distribution_user_' . $userId;
        if (($package_distribution = cache($package_dist_cache_key)) === null) {
            $package_distribution = $this->user_model
                ->select('packages.package_name, COUNT(users.id) as count')
                ->join('packages', 'packages.id = users.package_id', 'left')
                ->where('users.role', 'user')
                ->where('users.admin_id', $userId)
                ->groupBy('users.package_id')
                ->findAll();
            cache()->save($package_dist_cache_key, $package_distribution, 300);
        }

        // Payment methods
        $methods_cache_key = 'sadmin_payment_methods_user_' . $userId;
        if (($payment_methods = cache($methods_cache_key)) === null) {
            $methods = ['Cash', 'Bkash', 'Bkash Send Money', 'Nagad', 'Rocket', 'Upay', 'SSLCommerz', 'EPS', 'shurjoPay', 'PayStation', 'Other'];
            $default_methods = [];
            foreach ($methods as $m) {
                $default_methods[$m] = (object) ['paid_via' => $m, 'total' => 0];
            }

            $payment_methods_db = $this->payment_model
                ->select('COALESCE(NULLIF(payments.paid_via, ""), "Other") as paid_via, SUM(payments.amount) as total')
                ->join('users', 'users.id = payments.user_id')
                ->groupStart()
                    ->where('users.admin_id', $userId)
                    ->orWhere('payments.paidby', $userId)
                ->groupEnd()
                ->where('payments.status', 'successful')
                ->groupBy('paid_via')
                ->findAll();

            foreach ($payment_methods_db as $d) {
                $dObj = (object)$d;
                $default_methods[$dObj->paid_via] = $dObj;
            }
            $payment_methods = array_values($default_methods);
            cache()->save($methods_cache_key, $payment_methods, 300);
        }

        // Geo revenue
        $geo_rev_cache_key = 'sadmin_geo_revenue_user_' . $userId;
        if (($geo_revenue = cache($geo_rev_cache_key)) === null) {
            $geo_revenue = $this->payment_model
                ->select('COALESCE(areas.area_name, "Unassigned") as area_name, SUM(payments.amount) as revenue, COUNT(DISTINCT payments.user_id) as persons')
                ->join('users', 'users.id = payments.user_id')
                ->join('areas', 'areas.id = users.area_id', 'left')
                ->groupStart()
                    ->where('users.admin_id', $userId)
                    ->orWhere('payments.paidby', $userId)
                ->groupEnd()
                ->where('payments.status', 'successful')
                ->groupBy('users.area_id')
                ->orderBy('revenue', 'DESC')
                ->limit(50)
                ->findAll();
            cache()->save($geo_rev_cache_key, $geo_revenue, 300);
        }

        // Optimized Weekly Collections (1 query)
        $weekly_col_cache_key = 'sadmin_weekly_collections_user_' . $userId;
        if (($weekly_collections_data = cache($weekly_col_cache_key)) === null) {
            $since_weekly = date('Y-m-d', strtotime('-6 days'));
            $weeklyResults = $this->payment_model
                ->select('DATE(payments.paid_at) as pay_date, SUM(payments.amount) as amount')
                ->join('users', 'users.id = payments.user_id')
                ->groupStart()
                    ->where('users.admin_id', $userId)
                    ->orWhere('payments.paidby', $userId)
                ->groupEnd()
                ->where('payments.status', 'successful')
                ->where('DATE(payments.paid_at) >=', $since_weekly)
                ->groupBy('pay_date')
                ->findAll();

            $weeklyLookup = [];
            foreach ($weeklyResults as $row) {
                $rowObj = (object)$row;
                $weeklyLookup[$rowObj->pay_date] = (int)$rowObj->amount;
            }

            $weekly_collections = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $amount = $weeklyLookup[$d] ?? 0;
                $weekly_collections[] = [
                    'day' => date('D', strtotime($d)),
                    'amount' => $amount / 1000
                ];
            }

            // Weekly Growth
            $this_week_total = array_sum(array_column($weekly_collections, 'amount'));
            $prev_query = $this->payment_model
                ->selectSum('payments.amount')
                ->join('users', 'users.id = payments.user_id')
                ->where('payments.status', 'successful')
                ->where('payments.paid_at >=', date('Y-m-d', strtotime('-14 days')))
                ->where('payments.paid_at <', date('Y-m-d', strtotime('-7 days')));

            if (session()->get('user_role') !== 'admin') {
                $prev_query->where('users.admin_id', $userId);
            }
            $prev_week_total = (int) ($prev_query->first()->amount / 1000 ?? 0);
            $weekly_growth = ($prev_week_total > 0)
                ? round((($this_week_total - $prev_week_total) / $prev_week_total) * 100, 1)
                : (($this_week_total > 0) ? 100 : 0);

            $weekly_collections_data = [
                'weekly_collections' => $weekly_collections,
                'weekly_growth' => $weekly_growth
            ];
            cache()->save($weekly_col_cache_key, $weekly_collections_data, 300);
        }
        $weekly_collections = $weekly_collections_data['weekly_collections'];
        $weekly_growth = $weekly_collections_data['weekly_growth'];

        // Optimized Growth & Churn (2 queries)
        $growth_churn_cache_key = 'sadmin_growth_churn_user_' . $userId;
        if (($growth_churn = cache($growth_churn_cache_key)) === null) {
            $sinceMonth = date('Y-m-01', strtotime('-6 months'));
            
            $newUsersGrouped = $this->user_model
                ->select('DATE_FORMAT(created_at, "%Y-%m") as month_key, COUNT(id) as count')
                ->where(['admin_id' => $userId, 'role' => 'user'])
                ->where('created_at >=', $sinceMonth)
                ->groupBy('month_key')
                ->findAll();
                
            $newLookup = [];
            foreach ($newUsersGrouped as $row) {
                $rowObj = (object)$row;
                $newLookup[$rowObj->month_key] = (int)$rowObj->count;
            }

            $churnUsersGrouped = $this->user_model
                ->select('DATE_FORMAT(updated_at, "%Y-%m") as month_key, COUNT(id) as count')
                ->where(['role' => 'user', 'subscription_status' => 'inactive', 'admin_id' => $userId])
                ->where('updated_at >=', $sinceMonth)
                ->groupBy('month_key')
                ->findAll();

            $churnLookup = [];
            foreach ($churnUsersGrouped as $row) {
                $rowObj = (object)$row;
                $churnLookup[$rowObj->month_key] = (int)$rowObj->count;
            }

            $growth_churn = [];
            foreach (range(6, 0) as $i) {
                $monthStart = date('Y-m-01', strtotime("-$i months"));
                $monthKey = date('Y-m', strtotime($monthStart));
                $growth_churn[] = [
                    'month' => date('M', strtotime($monthStart)),
                    'new' => $newLookup[$monthKey] ?? 0,
                    'churn' => $churnLookup[$monthKey] ?? 0
                ];
            }
            cache()->save($growth_churn_cache_key, $growth_churn, 300);
        }

        // Optimized Revenue Overview (3 queries)
        $rev_overview_cache_key = 'sadmin_revenue_overview_user_' . $userId;
        if (($revenue_overview = cache($rev_overview_cache_key)) === null) {
            $monthsList = [];
            for ($i = 6; $i >= 0; $i--) {
                $monthsList[] = date('F', strtotime("-$i months"));
            }

            $revResults = $this->payment_model
                ->select('payments.month, SUM(payments.pay_amount) as pay_amount')
                ->join('users', 'users.id = payments.user_id')
                ->groupStart()
                    ->where('users.admin_id', $userId)
                    ->orWhere('payments.paidby', $userId)
                ->groupEnd()
                ->whereIn('payments.month', $monthsList)
                ->groupBy('payments.month')
                ->findAll();
                
            $revLookup = [];
            foreach ($revResults as $row) {
                $rowObj = (object)$row;
                $revLookup[strtolower($rowObj->month)] = (int)$rowObj->pay_amount;
            }

            $colResults = $this->payment_model
                ->select('payments.month, SUM(payments.amount) as amount')
                ->join('users', 'users.id = payments.user_id')
                ->groupStart()
                    ->where('users.admin_id', $userId)
                    ->orWhere('payments.paidby', $userId)
                ->groupEnd()
                ->where(['payments.status' => 'successful'])
                ->whereIn('payments.month', $monthsList)
                ->groupBy('payments.month')
                ->findAll();

            $colLookup = [];
            foreach ($colResults as $row) {
                $rowObj = (object)$row;
                $colLookup[strtolower($rowObj->month)] = (int)$rowObj->amount;
            }

            $expResults = $this->expense_model
                ->select('MONTHNAME(date) as month_name, SUM(amount) as amount')
                ->where(['user_id' => $userId, 'status' => 'approved'])
                ->whereIn('MONTHNAME(date)', $monthsList)
                ->groupBy('month_name')
                ->findAll();

            $expLookup = [];
            foreach ($expResults as $row) {
                $rowObj = (object)$row;
                $expLookup[strtolower($rowObj->month_name)] = (int)$rowObj->amount;
            }

            $revenue_overview = [];
            foreach (array_reverse(range(0, 6)) as $i) {
                $monthName = date('F', strtotime("-$i months"));
                $monthKey = strtolower($monthName);
                $revenue_overview[] = [
                    'month' => date('M', strtotime($monthName)),
                    'revenue' => ($revLookup[$monthKey] ?? 0) / 1000,
                    'collection' => ($colLookup[$monthKey] ?? 0) / 1000,
                    'expense' => ($expLookup[$monthKey] ?? 0) / 1000
                ];
            }
            cache()->save($rev_overview_cache_key, $revenue_overview, 300);
        }

        // Bandwidth peak usage (cached to avoid 3 raw queries every request)
        $bw_cache_key = 'sadmin_bandwidth_peak_user_' . $userId;
        $bw_cached = cache($bw_cache_key);
        if ($bw_cached === null) {
            $maxDateRow = $this->user_model->db->table('user_data_usage')->selectMax('date')->get()->getRow();
            $targetDate = $maxDateRow->date ?? date('Y-m-d');

            $rx_peak = (float) ($this->user_model->db->table('user_data_usage')
                ->join('users', 'users.id = user_data_usage.admin_id', 'inner')
                ->where(['user_data_usage.date' => $targetDate])
                ->selectSum('rx_today', 'rx')
                ->get()->getRow()->rx ?? 0);

            $tx_peak = (float) ($this->user_model->db->table('user_data_usage')
                ->join('users', 'users.id = user_data_usage.admin_id', 'inner')
                ->where(['user_data_usage.date' => $targetDate])
                ->selectSum('tx_today', 'tx')
                ->get()->getRow()->tx ?? 0);

            $total_data_gb = round(($rx_peak + $tx_peak) / 1024, 2);
            cache()->save($bw_cache_key, $total_data_gb, 300);
        } else {
            $total_data_gb = $bw_cached;
        }

        // Payment Report statistics
        $customer_payment_statistics = $this->statistics('user', $userId);
        $employee_payment_statistics = $this->statistics('employee', $userId);

        $responseData = [
            'status' => 'success',
            'ticket_stats' => $ticket_stats,
            'ticket_solving_rate' => $ticket_solving_rate,
            'efficiency_rate' => $efficiency_rate,
            'retention_rate' => $retention_rate,
            'package_distribution' => $package_distribution,
            'payment_methods' => $payment_methods,
            'geo_revenue' => $geo_revenue,
            'weekly_collections' => $weekly_collections,
            'weekly_growth' => $weekly_growth,
            'growth_churn' => $growth_churn,
            'revenue_overview' => $revenue_overview,
            'total_data_gb' => $total_data_gb,
            'customer_payment_statistics' => $customer_payment_statistics,
            'employee_payment_statistics' => $employee_payment_statistics,
        ];

        cache()->save($cacheKey, $responseData, 300);

        return $this->response->setJSON($responseData);
    }

    private function getSadminCardMetrics($userId, $bypassCache = false)
    {
        $currentMonth = date('F');
        $currentTime = date('Y-m-d H:i:s');
        $currentM = date('m');
        $currentYear = date('Y');

        $cacheKey = 'sadmin_card_metrics_user_' . $userId;
        if (!$bypassCache && ($cached = cache($cacheKey))) { // Cache hit — return immediately (no DB queries)
            return $cached;
        }

        $customers_payment_received = (int) $this->payment_model
            ->selectSum('payments.amount')
            ->join('users', 'users.id = payments.user_id')
            ->groupStart()
                ->where('users.admin_id', $userId)
                ->orWhere('payments.paidby', $userId)
            ->groupEnd()
            ->where('payments.month', $currentMonth)
            ->where(['payments.status' => 'successful'])
            ->first()
            ->amount;

        $customers_payment_received_count = (int) $this->payment_model
            ->join('users', 'users.id = payments.user_id')
            ->where('users.admin_id', $userId)
            ->where('payments.month', $currentMonth)
            ->where(['payments.status' => 'successful'])
            ->countAllResults();

        $userIds = $this->user_model->select('id')->where('admin_id', $userId)->where('role', 'user')->countAllResults();

        $ConnectionDetails = model('App\Models\ConnectionData');
        
        // Use try-catch or safe fallback if table/model connection_details doesn't exist or is connection_data
        $freeUsers = [];
        try {
            $freeUsers = $ConnectionDetails
                ->whereIn('billing_status', ['free', 'Free'])
                ->findColumn('user_id');
        } catch (Exception $e) {
            log_message('error', 'Error fetching free users: ' . $e->getMessage());
        }

        $customers_payment_total = 0;
        try {
            $customers_payment_total = (int) $this->user_model->builder()
                ->selectSum('packages.price')
                ->join('packages', 'packages.id = users.package_id')
                ->join('connection_details', 'connection_details.user_id = users.id', 'left')
                ->where('users.admin_id', $userId)
                ->where('users.role', 'user')
                ->groupStart()
                    ->where('connection_details.billing_status !=', 'free')
                    ->orWhere('connection_details.billing_status', NULL)
                ->groupEnd()
                ->get()->getRow()->price ?? 0;
        } catch (Exception $e) {
            log_message('error', 'Error calculating customers payment total: ' . $e->getMessage());
        }

        $since_pending = date('Y-m-d', strtotime('-32 days'));
        $customers_payment_pending = 0;
        try {
            $customers_payment_pending = (int) $this->payment_model->selectSum('amount')
                ->groupStart()
                    ->where('admin_id', $userId)
                    ->orWhere('paidby', $userId)
                ->groupEnd()
                ->where('created_at >=', $since_pending)
                ->where(['user_type' => 'user', 'status' => 'pending'])
                ->whereNotIn('user_id', !empty($freeUsers) ? $freeUsers : [0])
                ->first()->amount ?? 0;
        } catch (Exception $e) {
            log_message('error', 'Error calculating customers payment pending: ' . $e->getMessage());
        }

        $employee_payment_received = (int) $this->payment_model
            ->selectSum('amount')
            ->where('admin_id', $userId)
            ->where(['user_type' => 'employee', 'status' => 'successful'])
            ->first()
            ->amount;

        $employees_payment_pending = (int) $this->payment_model
            ->selectSum('amount')
            ->where('admin_id', $userId)
            ->where('month', $currentMonth)
            ->where(['user_type' => 'employee', 'status' => 'pending'])
            ->first()
            ->amount;

        $all_resellers = (int) $this->user_model
            ->where('role', 'resellerAdmin')
            ->where('admin_id', $userId)
            ->countAllResults();

        $users_active = (int) $this->user_model
            ->where(['role' => 'user', 'admin_id' => $userId, 'subscription_status' => 'active'])
            ->where('will_expire >', $currentTime)
            ->where('conn_status', 'conn')
            ->countAllResults();

        $users_new = (int) $this->user_model
            ->where(['role' => 'user', 'admin_id' => $userId])
            ->where("MONTH(created_at) = $currentM", null, false)
            ->where("YEAR(created_at) = $currentYear", null, false)
            ->countAllResults();

        $users_inactive = (int) $this->user_model
            ->where('role', 'user')
            ->where('admin_id', $userId)
            ->where('conn_status', 'disconn')
            ->countAllResults();

        $expired_inactive = (int) $this->user_model
            ->where('role', 'user')
            ->where('admin_id', $userId)
            ->where('will_expire <', $currentTime)
            ->groupStart()
                ->where('conn_status !=', 'disconn')
                ->orWhere('conn_status', null)
            ->groupEnd()
            ->countAllResults();

        $employee_active = (int) $this->user_model
            ->where(['role' => 'employee', 'admin_id' => $userId, 'status' => 'active'])
            ->countAllResults();

        $employee_inactive = (int) $this->user_model
            ->where(['role' => 'employee', 'admin_id' => $userId, 'status' => 'inactive'])
            ->countAllResults();

        $total_packages = (int) $this->package_model
            ->where(['status' => 'active'])
            ->where('user_id', $userId)
            ->countAllResults();

        $total_area = (int) $this->area_model
            ->where(['status' => 'active'])
            ->where('user_id', $userId)
            ->countAllResults();

        $router_active = (int) $this->router_model
            ->where(['status' => 'active'])
            ->where('user_id', $userId)
            ->countAllResults();

        $router_inactive = (int) $this->router_model
            ->where(['status' => 'inactive'])
            ->where('user_id', $userId)
            ->countAllResults();

        helper('subscription');
        $customer_quota = getTenantCustomerQuota((int) $userId);

        $result = [
            'customers_payment_received' => $customers_payment_received,
            'customers_payment_total' => $customers_payment_total,
            'customers_Expayment_total' => ($customers_payment_total > 0) ? $customers_payment_total - $customers_payment_received : 0,
            'customers_Expayment_count' => ($userIds > 0) ? $userIds - $customers_payment_received_count : 0,
            'customers_payment_received_count' => $customers_payment_received_count,
            'customers_payment_pending' => $customers_payment_pending,
            'employee_payment_received' => $employee_payment_received,
            'employees_payment_pending' => $employees_payment_pending,
            'all_resellers' => $all_resellers,
            'users_active' => $users_active,
            'users_new' => $users_new,
            'users_inactive' => $users_inactive,
            'expired_inactive' => $expired_inactive,
            'employee_active' => $employee_active,
            'employee_inactive' => $employee_inactive,
            'total_packages' => $total_packages,
            'total_area' => $total_area,
            'router_active' => $router_active,
            'router_inactive' => $router_inactive,
            'customer_quota' => $customer_quota,
        ];

        cache()->save($cacheKey, $result, 86400);

        return $result;
    }
}
