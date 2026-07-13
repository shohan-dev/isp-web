<?php

namespace Zapi\Modules\Reseller\Dashboard\Services\DashboardService;

use App\Models\allResellerPackage;
use App\Models\Package;

trait DashboardServicePart01Segment
{
        /**
         * GET /api/reseller/dashboard
         * Returns reseller dashboard metrics as JSON
         */
        public function dashboard($id = null)
        {
            // Prefer ID from route. Validate presence.
            $userId = $id;
            if (empty($userId)) {
                return $this->respondPayload(['error' => 'Missing route parameter: id'], 400);
            }
            
            
            $details = $this->user_model->where(['id' => $userId])->first();
            if (empty($details)) {
                return $this->respondPayload(['error' => 'Reseller not found'], 404);
            }
    
            $detailsArr = is_array($details) ? $details : (array) $details;
    
            // In many deployments, customer/payment rows use admin_id (parent account)
            // while router/package/area rows are linked to reseller user's id.
            // Resolve a resource owner id that matches web dashboard behavior.
            $resourceOwnerId = (int) $userId;
            $detailsRole = (string) ($detailsArr['role'] ?? '');
            if ($detailsRole !== 'resellerAdmin') {
                $resellerAccount = $this->user_model
                    ->where('role', 'resellerAdmin')
                    ->where('admin_id', $userId)
                    ->orderBy('id', 'asc')
                    ->first();
                if (!empty($resellerAccount)) {
                    $resourceOwnerId = (int) (is_array($resellerAccount) ? ($resellerAccount['id'] ?? $userId) : ($resellerAccount->id ?? $userId));
                }
            }
    
            $router_id = $detailsArr['router_id'] ?? null;
            $currentTime = date('Y-m-d H:i:s');
            $currentM = date('m');
            $currentYear = date('Y');
            $currentMonth = date('F');
    
            $sum = $this->payment_model
                ->selectSum('amount')
                ->where('admin_id', $userId)
                ->where('month', $currentMonth)
                ->where(['user_type' => 'user', 'status' => 'successful'])
                ->first();
    
            $customers_payment_received = (int) ($sum->amount ?? 0);
    
            $customers_payment_received_count = (int) $this->payment_model
                ->where('admin_id', $userId)
                ->where('month', $currentMonth)
                ->where(['user_type' => 'user', 'status' => 'successful'])
                ->countAllResults();
    
            $userIds = (int) $this->user_model->select('id')->where('admin_id', $userId)->where('role', 'user')->countAllResults();
    
            log_message('debug', 'userId customers_payment_received_count: ' . $customers_payment_received_count);
            log_message('debug', 'userIds insertId: ' . $userIds);
    
            $packagePrice = (int) ($this->getPackagePrice($userId, $detailsRole) ?? 0);
    
    
            $customers_payment_pending_res = $this->payment_model
                ->selectSum('amount')
                ->where('admin_id', $userId)
                ->where('month', $currentMonth)
                ->where(['user_type' => 'user', 'status' => 'pending'])
                ->first();
    
            $employee_payment_received_res = $this->payment_model
                ->selectSum('amount')
                ->where('admin_id', $userId)
                ->where(['user_type' => 'employee', 'status' => 'successful'])
                ->first();
    
            $employees_payment_pending_res = $this->payment_model
                ->selectSum('amount')
                ->where('admin_id', $userId)
                ->where(['user_type' => 'employee', 'status' => 'pending'])
                ->first();
    
            // Match web reseller dashboard behavior:
            // primary source is assigned router_id, then fallback to owner-based routers.
            $activeRouters = [];
            if (!empty($router_id)) {
                $activeRouters = $this->router_model
                    ->where('status', 'active')
                    ->where('id', $router_id)
                    ->findAll();
            }
            if (empty($activeRouters)) {
                $activeRouters = $this->router_model
                    ->where('status', 'active')
                    ->where('user_id', $resourceOwnerId)
                    ->findAll();
            }
    
            $routerUserStats = [];
            $routerUsersTotal = 0;
            $routerUsersOnline = 0;
            $routerUsersOffline = 0;
    
            foreach ($activeRouters as $router) {
                $routerId = is_array($router) ? ($router['id'] ?? null) : ($router->id ?? null);
                $routerName = is_array($router) ? ($router['name'] ?? '--') : ($router->name ?? '--');
                if (empty($routerId)) {
                    continue;
                }
    
                // Keep API aligned with web dashboard cards:
                // web uses /routers/load-traffic/{routerId} and derives counts from
                // filtered allusers/activeusers, not from users.conn_status.
                $trafficStats = $this->resolveRouterTrafficStats((int) $routerId, (int) $userId);
                $totalUsers = $trafficStats['users_total'];
                $onlineUsers = $trafficStats['users_online'];
                $offlineUsers = $trafficStats['users_offline'];
    
                $routerUsersTotal += $totalUsers;
                $routerUsersOnline += $onlineUsers;
                $routerUsersOffline += $offlineUsers;
    
                $routerUserStats[] = [
                    'router_id' => (string) $routerId,
                    'router_name' => (string) $routerName,
                    'users_total' => $totalUsers,
                    'users_online' => $onlineUsers,
                    'users_offline' => $offlineUsers,
                ];
            }
    
            $routerActiveCount = !empty($router_id)
                ? (int) $this->router_model->where(['status' => 'active'])->where('id', $router_id)->countAllResults()
                : (int) $this->router_model->where(['status' => 'active'])->where('user_id', $resourceOwnerId)->countAllResults();
            $routerInactiveCount = !empty($router_id)
                ? (int) $this->router_model->where(['status' => 'inactive'])->where('id', $router_id)->countAllResults()
                : (int) $this->router_model->where(['status' => 'inactive'])->where('user_id', $resourceOwnerId)->countAllResults();
    
            $usersActive = (int) $this->user_model
                ->where(['role' => 'user', 'admin_id' => $userId, 'subscription_status' => 'active'])
                ->where('will_expire >', $currentTime)
                ->where('conn_status', 'conn')
                ->countAllResults();
    
            $usersNew = (int) $this->user_model
                ->where(['role' => 'user', 'admin_id' => $userId])
                ->where("MONTH(created_at) = $currentM", null, false)
                ->where("YEAR(created_at) = $currentYear", null, false)
                ->countAllResults();
    
            $usersInactive = (int) $this->user_model
                ->where('role', 'user')
                ->where('admin_id', $userId)
                ->groupStart()
                ->where('conn_status', 'disconn')
                ->where('will_expire >', $currentTime)
                ->groupEnd()
                ->countAllResults();
    
            $usersExpired = (int) $this->user_model
                ->where('role', 'user')
                ->where('admin_id', $userId)
                ->groupStart()
                ->where('will_expire <', $currentTime)
                ->groupEnd()
                ->countAllResults();
    
            $totalCustomers = $usersActive + $usersInactive + $usersExpired;
            $customersPaymentDue = ($packagePrice > 0)
                ? ($packagePrice - $customers_payment_received)
                : 0;
            $customersExpaymentCount = ($userIds > 0)
                ? ($userIds - $customers_payment_received_count)
                : 0;
            $resellerName = (string) ($detailsArr['name'] ?? '');
            $resellerBalance = (float) ($detailsArr['fund'] ?? $detailsArr['balance'] ?? 0);
            $resellerStatus = (string) ($detailsArr['status'] ?? $detailsArr['subscription_status'] ?? $detailsArr['conn_status'] ?? '');
            $connStatus = (string) ($detailsArr['conn_status'] ?? '');
            $activity = (string) ($detailsArr['activity'] ?? '');
    
            $data = [
                // Reseller summary block
                'name' => $resellerName,
                'reseller_name' => $resellerName,
                'balance' => $resellerBalance,
                'current_balance' => $resellerBalance,
                'status' => $resellerStatus,
                'reseller_status' => $resellerStatus,
                'conn_status' => $connStatus,
                'activity' => $activity,
                'router_id' => !empty($router_id) ? (string) $router_id : null,
                'service_area_total_count' => (int) $this->area_model->where('user_id', $resourceOwnerId)->countAllResults(),
    
                'customers_payment_received' => $customers_payment_received,
                'customers_payment_total' => $packagePrice,
                'customer_payment_total' => $packagePrice,
                'customers_Expayment_total' => $customersPaymentDue,
                'customers_payment_due' => $customersPaymentDue,
                'customer_payment_due' => $customersPaymentDue,
                'customers_Expayment_count' => $customersExpaymentCount,
                'customers_expayment_count' => $customersExpaymentCount,
                'customers_payment_received_count' => $customers_payment_received_count,
                'customers_payment_pending' => (int) ($customers_payment_pending_res->amount ?? 0),
                'employee_payment_received' => (int) ($employee_payment_received_res->amount ?? 0),
                'employees_payment_pending' => (int) ($employees_payment_pending_res->amount ?? 0),
    
                'users_active' => $usersActive,
                'active_customers' => $usersActive,
                'users_new' => $usersNew,
                'new_users' => $usersNew,
                'users_inactive' => $usersInactive,
                'inactive_customers' => $usersInactive,
                'expired_inactive' => $usersExpired,
                'expired_customers' => $usersExpired,
                'users_expired' => $usersExpired,
                'total_customers' => $totalCustomers,
                'employee_active' => (int) $this->user_model->where(['role' => 'employee', 'admin_id' => $userId, 'status' => 'active'])->countAllResults(),
                'employee_inactive' => (int) $this->user_model->where(['role' => 'employee', 'admin_id' => $userId, 'status' => 'inactive'])->countAllResults(),
    
                'total_packages' => (int) $this->package_model->where(['status' => 'active'])->where('user_id', $resourceOwnerId)->countAllResults(),
                'total_area' => (int) $this->area_model->where(['status' => 'active'])->where('user_id', $resourceOwnerId)->countAllResults(),
    
                'router_active' => $routerActiveCount,
                'router_inactive' => $routerInactiveCount,
                'pop_total' => $routerUsersTotal,
                'pop_online' => $routerUsersOnline,
                'pop_offline' => $routerUsersOffline,
    
                'customer_payment_statistics' => $this->statistics('user', null, $userId),
                'employee_payment_statistics' => $this->statistics('employee', null, $userId),
    
                'routers' => $activeRouters,
                'router_user_statistics' => $routerUserStats,
                'profile' => [
                    'name' => $resellerName,
                    'status' => $resellerStatus,
                    'conn_status' => $connStatus,
                    'activity' => $activity,
                    'router_id' => !empty($router_id) ? (string) $router_id : null,
                    'fund' => $resellerBalance,
                ],
            ];
    
            return $this->respondSuccess($data);
        }
    
        /**
         * Mirrors web router card logic from app/Views/dashboard/reseller.php:
         * - getSystemResources(routerClient)
         * - filter allusers by reseller customers' pppoe_id
         * - filter activeusers by matching user name from filtered allusers
         */
        private function resolveRouterTrafficStats(int $routerId, int $resellerId): array
        {
            $fallbackTotal = (int) $this->user_model
                ->where('role', 'user')
                ->where('admin_id', $resellerId)
                ->where('router_id', $routerId)
                ->countAllResults();
            $fallbackOnline = (int) $this->user_model
                ->where('role', 'user')
                ->where('admin_id', $resellerId)
                ->where('router_id', $routerId)
                ->where('conn_status', 'conn')
                ->countAllResults();
            $fallbackOffline = max(0, $fallbackTotal - $fallbackOnline);
    
            if (!function_exists('routerClient') || !function_exists('getSystemResources')) {
                return [
                    'users_total' => $fallbackTotal,
                    'users_online' => $fallbackOnline,
                    'users_offline' => $fallbackOffline,
                ];
            }
    
            $routerClient = routerClient($routerId);
            if (is_array($routerClient)) {
                return [
                    'users_total' => $fallbackTotal,
                    'users_online' => $fallbackOnline,
                    'users_offline' => $fallbackOffline,
                ];
            }
    
            $resource = getSystemResources($routerClient, null);
            $allUsers = $resource['data']['allusers'] ?? [];
            $activeUsers = $resource['data']['activeusers'] ?? [];
            if (!is_array($allUsers) || !is_array($activeUsers)) {
                return [
                    'users_total' => $fallbackTotal,
                    'users_online' => $fallbackOnline,
                    'users_offline' => $fallbackOffline,
                ];
            }
    
            $customers = $this->user_model
                ->select('pppoe_id')
                ->where('admin_id', $resellerId)
                ->findAll();
            $pppoeIds = array_filter(array_map(function ($item) {
                if (is_array($item)) {
                    return trim((string) ($item['pppoe_id'] ?? ''));
                }
                return trim((string) ($item->pppoe_id ?? ''));
            }, $customers));
    
            $filteredUsers = [];
            $validNames = [];
            foreach ($allUsers as $user) {
                $secretId = trim((string) ($user['.id'] ?? ''));
                $secretName = trim((string) ($user['name'] ?? ''));
                if (in_array($secretId, $pppoeIds, true)) {
                    $filteredUsers[] = $user;
                    if ($secretName !== '') {
                        $validNames[] = $secretName;
                    }
                }
            }
    
            $filteredActiveUsers = [];
            foreach ($activeUsers as $activeUser) {
                $activeName = trim((string) ($activeUser['name'] ?? ''));
                if (in_array($activeName, $validNames, true)) {
                    $filteredActiveUsers[] = $activeUser;
                }
            }
    
            $totalUsers = count($filteredUsers);
            $onlineUsers = count($filteredActiveUsers);
            $offlineUsers = max(0, $totalUsers - $onlineUsers);
    
            return [
                'users_total' => $totalUsers,
                'users_online' => $onlineUsers,
                'users_offline' => $offlineUsers,
            ];
        }
    
        /**
         * Payment statistics by month for a role (API version).
         * @param string|null $role
         * @param int|null $user_id
         * @param int|null $admin_id
         * @return array
         */
        protected function statistics($role = null, $user_id = null, $admin_id = null)
        {
            $months = [];
            $success_payment = [];
            $pending_payment = [];
            $failed_payment = [];
    
            $currentMonthNumber = (int) date('m');
    
            for ($i = 1; $i <= $currentMonthNumber; $i++) {
                $monthName = date('F', mktime(0, 0, 0, $i, 1, (int) date('Y')));
    
                $successful = $this->__transactionStatistics('successful', $monthName, $role, $user_id, $admin_id);
                $pending = $this->__transactionStatistics('pending', $monthName, $role, $user_id, $admin_id);
                $failed = $this->__transactionStatistics('failed', $monthName, $role, $user_id, $admin_id);
    
                array_push($months, date('M', mktime(0, 0, 0, $i, 1, (int) date('Y'))));
                array_push($success_payment, $successful);
                array_push($pending_payment, $pending);
                array_push($failed_payment, $failed);
            }
    
            return [
                'months' => $months,
                'successful' => $success_payment,
                'pending' => $pending_payment,
                'failed' => $failed_payment,
            ];
        }
    
        /**
         * Transaction statistics helper for API.
         */
        protected function __transactionStatistics($status, $month, $role = null, $user_id = null, $admin_id = null)
        {
            $conditions = [
                'month' => $month,
                'status' => $status,
            ];
    
            if (!empty($role)) {
                $conditions['user_type'] = $role;
            }
    
            if (!empty($user_id)) {
                $conditions['user_id'] = $user_id;
            }
    
            if (!empty($admin_id)) {
                $conditions['admin_id'] = $admin_id;
            }
    
            $row = $this->payment_model->selectSum('amount')->where($conditions)->first();
    
            return (int) ($row->amount ?? 0);
        }
    
}
