<?php

namespace Zapi\Modules\Reseller\Router\Services\RouterService;

trait RouterServicePart01Segment
{
        /**
         * GET /api/reseller/router-users/{resellerId}/{routerId}
         */
        public function fetch($resellerId = null, $routerId = null)
        {
            if (empty($resellerId) || empty($routerId)) {
                return $this->respondError((string) 'Missing reseller id or router id', 400, 'REQUEST_FAILED');
            }
            $status = strtolower(trim((string) ($this->request->getGet('status') ?? 'all')));
            if (!in_array($status, ['all', 'online', 'offline'], true)) {
                $status = 'all';
            }
    
            $customers = $this->user_model
                ->select('pppoe_id, name')
                ->where('admin_id', $resellerId)
                ->findAll();

            $pppoeIdToName = [];
            $pppoeIds = [];

            foreach ($customers as $customer) {
                $pppoeId = trim((string) (is_object($customer) ? ($customer->pppoe_id ?? '') : ($customer['pppoe_id'] ?? '')));
                $customerName = trim((string) (is_object($customer) ? ($customer->name ?? '') : ($customer['name'] ?? '')));

                if ($pppoeId !== '') {
                    $pppoeIds[] = $pppoeId;
                    $pppoeIdToName[$pppoeId] = $customerName;
                }
            }
    
            if (!function_exists('routerClient') || !function_exists('getSystemResources')) {
                return $this->respondError((string) 'Router helper functions not available', 500, 'REQUEST_FAILED');
            }
    
            $routerClient = routerClient($routerId);
    
            if (is_array($routerClient)) {
                return $this->respondError((string) 'Cannot connect to router', 500, 'REQUEST_FAILED');
            }
    
            $data = getSystemResources($routerClient);
    
            $allUsers = $data['data']['allusers'] ?? [];
            $activeUsers = $data['data']['activeusers'] ?? [];
    
            $filteredAll = [];
            $validNames = [];

            foreach ($allUsers as $user) {
                $userId = trim((string) ($user['.id'] ?? ''));
                if (in_array($userId, $pppoeIds, true)) {
                    $customerName = $pppoeIdToName[$userId] ?? '';
                    $user['customer_name'] = $customerName;
                    $filteredAll[] = $user;
                    $name = trim((string) ($user['name'] ?? ''));
                    if ($name !== '') {
                        $validNames[] = $name;
                    }
                }
            }

            $filteredActive = [];
            foreach ($activeUsers as $activeUser) {
                $activeName = trim((string) ($activeUser['name'] ?? ''));
                if (in_array($activeName, $validNames, true)) {
                    $customerName = '';
                    foreach ($pppoeIdToName as $id => $name) {
                        $userForId = null;
                        foreach ($allUsers as $u) {
                            if (($u['.id'] ?? '') === $id && ($u['name'] ?? '') === $activeName) {
                                $userForId = $u;
                                break;
                            }
                        }
                        if ($userForId !== null) {
                            $customerName = $name;
                            break;
                        }
                    }
                    $activeUser['customer_name'] = $customerName;
                    $filteredActive[] = $activeUser;
                }
            }
    
            $activeNames = array_map(
                static fn($item) => strtolower(trim((string) ($item['name'] ?? ''))),
                $filteredActive
            );
            $activeNameSet = array_flip(array_filter($activeNames));
            $filteredInactive = array_values(array_filter($filteredAll, static function ($item) use ($activeNameSet) {
                $name = strtolower(trim((string) ($item['name'] ?? '')));
                return $name === '' || !isset($activeNameSet[$name]);
            }));
            // Online list must use the same PPP user rows as "all"/offline (active session objects differ in shape/fields).
            $filteredOnline = array_values(array_filter($filteredAll, static function ($item) use ($activeNameSet) {
                $name = strtolower(trim((string) ($item['name'] ?? '')));
                return $name !== '' && isset($activeNameSet[$name]);
            }));

            $totalUsers = count($filteredAll);
            $onlineUsers = count($filteredActive);
            $offlineUsers = $totalUsers - $onlineUsers;
            $filteredUsers = match ($status) {
                'online' => $filteredOnline,
                'offline' => $filteredInactive,
                default => $filteredAll,
            };
            $pager = $this->getPaginationParams();
            $totalFound = count($filteredUsers);
            $pagedUsers = array_slice($filteredUsers, $pager['offset'], $pager['limit']);
    
            return $this->respondSuccess([
                    'total_users' => $totalUsers,
                    'users_online' => $onlineUsers,
                    'users_offline' => $offlineUsers,
                    'status_filter' => $status,
                    'users' => $pagedUsers,
                    'active_users' => $filteredActive,
                    'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedUsers)),
                ],);
        }
    
        /**
         * GET /api/reseller/routers/{resellerId}
         */
        public function list($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $reseller = $this->user_model->find($resellerId);
            $routerId = $reseller->router_id ?? null;
    
            if (empty($routerId)) {
                return $this->respondSuccess([]);
            }
    
            $routers = $this->router_model->where('id', $routerId)->where('status', 'active')->findAll();
            $pager = $this->getPaginationParams();
            $totalFound = count($routers);
            $pagedRouters = array_slice($routers, $pager['offset'], $pager['limit']);

            return $this->respondSuccess([
                'data' => $pagedRouters,
                'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedRouters)),
            ]);
        }
    
}
