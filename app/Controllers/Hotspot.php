<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class Hotspot extends BaseController
{

    public function get_portal_info()
    {
        $user_id = getSession('user_id');
        if (!$user_id) {
            // Fallback to first sAdmin if not logged in
            $user_model = model('App\Models\User');
            $admin = $user_model->where('role', 'admin')->first();
            $user_id = $admin->id ?? 1;
        }

        $user_model = model('App\Models\User');
        $reg_model  = model('App\Models\Registration');

        $user = $user_model->find($user_id);
        $reg  = $reg_model->where('userid', $user_id)->first();

        // org name — from registrations table
        $org_name = null;
        if ($reg) {
            $org_name = is_array($reg) ? ($reg['organization_name'] ?? null) : ($reg->organization_name ?? null);
        }
        if (empty($org_name)) {
            $org_name = is_array($user) ? ($user['name'] ?? 'isppaybd') : ($user->name ?? 'isppaybd');
        }

        // payment number — check users table first, then registrations
        $payment_number = null;
        if ($user) {
            $payment_number = is_array($user) ? ($user['payment_receive_number'] ?? null) : ($user->payment_receive_number ?? null);
        }
        if (empty($payment_number) && $reg) {
            $payment_number = is_array($reg) ? ($reg['payment_receive_number'] ?? null) : ($reg->payment_receive_number ?? null);
        }
        if (empty($payment_number)) {
            $payment_number = '01880397090';
        }

        // Dedicated payment numbers from Software Settings (fallback to generic payment_number)
        $bkash_number = getSetting('bkash_payment_number') ?: $payment_number;
        $nagad_number  = getSetting('nagad_payment_number')  ?: $payment_number;

        return $this->response->setJSON([
            'status'                => 'success',
            'org_name'              => $org_name,
            'payment_number'        => $payment_number,
            'bkash_payment_number'  => $bkash_number,
            'nagad_payment_number'  => $nagad_number,
        ]);
    }

    public function user_profiles()
    {
        if (
            !userHasPermission('hotspot', 'read')
        ) show_404();


        $user_id = getSession('user_id');
        $router_model = new \App\Models\Router();
        $routers = $router_model
            ->select('*')
            ->where('user_id', $user_id)
            ->orderBy('id', 'desc')
            ->get()
            ->getResultArray();

        // log_message('debug', 'Fetching routers for user_id: ' . print_r($routers, true));


        $data = [
            'title' => 'Hotspot Packages',
            'routers' => $routers,
        ];

        return view('hotspot/user_package', $data);
    }

    public function get_user_profiles()
    {
        $router_id = $this->request->getGet('router_id');
        log_message('debug', 'get_user_profiles called for router_id: ' . $router_id);

        $profiles = getHotspotUserProfiles($router_id);
        // log_message('debug', 'Hotspot user profiles: ' . print_r($profiles, true));

        $queues = getSimpleQueues($router_id);
        // log_message('debug', 'Simple Queues: ' . print_r($queues, true));
        $pools  = getIpPools($router_id);
        // log_message('debug', 'IP Pools: ' . print_r($pools, true));

        // getHotspotUserProfiles()/getSimpleQueues()/getIpPools() return false
        // (not []) when both the RouterOS API and the fsocket fallback fail to
        // reach the router. Normalize to arrays so the client never has to
        // guess the shape of the payload.
        $data = [
            'profiles' => is_array($profiles) ? $profiles : [],
            'queues'   => is_array($queues) ? $queues : [],
            'pools'    => is_array($pools) ? $pools : [],
        ];

        if ($profiles === false) {
            $data['error'] = 'Unable to reach the router. Check the router context and try again.';
        }

        return $this->response->setJSON($data);
    }

    public function Hotspot_Dashboard()
    {
        if (
            !userHasPermission('hotspot', 'read')
        ) show_404();
        $user_id = getSession('user_id');
        $router_model = new \App\Models\Router();
        $routers = $router_model
            ->select('*')
            ->where('user_id', $user_id)
            ->orderBy('id', 'desc')
            ->get()
            ->getResultArray();

        // log_message('debug', 'Fetching routers for user_id: ' . print_r($routers, true));


        $data = [
            'title' => 'Hotspot Dashboard',
            'routers' => $routers,
        ];

        return view('hotspot/hotspoat_dashboard', $data);
    }

    public function get_Hotspot_Dashboard()
    {
        $router_id = $this->request->getGet('router_id');
        log_message('debug', 'get_Hotspot_Dashboard called for router_id: ' . $router_id);

        $router_client = routerClient($router_id);

        try {
            if ($router_client instanceof \RouterOS\Client) {

                /* ===============================
             * 1️⃣ System Clock
             * =============================== */
                $clockQuery = new \RouterOS\Query('/system/clock/print');
                $clockData  = $router_client->query($clockQuery)->read();
                $clock      = $clockData[0] ?? [];

                if (!empty($clock['time-zone-name'])) {
                    date_default_timezone_set($clock['time-zone-name']);
                }

                $today     = date('d-m-Y');    // e.g., 09-01-2026
                $thisMonth = date('m-Y');      // e.g., 01-2026

                /* ===============================
             * 2️⃣ System Resource
             * =============================== */
                $resourceQuery = new \RouterOS\Query('/system/resource/print');
                $resourceData  = $router_client->query($resourceQuery)->read();
                $resource      = $resourceData[0] ?? [];

                /* ===============================
             * 3️⃣ RouterBoard Info
             * =============================== */
                $boardQuery = new \RouterOS\Query('/system/routerboard/print');
                $boardData  = $router_client->query($boardQuery)->read();
                $board      = $boardData[0] ?? [];

                /* ===============================
             * 4️⃣ Hotspot Users
             * =============================== */
                $activeQuery = new \RouterOS\Query('/ip/hotspot/active/print');
                $activeUsers = $router_client->query($activeQuery)->read();

                $usersQuery  = new \RouterOS\Query('/ip/hotspot/user/print');
                $allUsers    = $router_client->query($usersQuery)->read();

                /* ===============================
             * 5️⃣ Income Calculation (Today & Month)
             * =============================== */
                $query = new \RouterOS\Query('/system/script/print');
                $query->where('comment', 'mikhmon');
                $scripts = $router_client->query($query)->read();

                $todayIncome = 0;
                $monthIncome = 0;
                $todayDate = date('Y-m-d');
                $thisMonthStr = date('Y-m');

                foreach ($scripts as $script) {
                    if (!isset($script['name'])) continue;

                    $parts = explode('-|-', $script['name']);
                    if (count($parts) < 4) continue;

                    $dateStr = $parts[0] ?? '';
                    $price   = (int)($parts[3] ?? 0);

                    // Convert script date to Y-m-d
                    $timestamp = strtotime(str_replace(['/', '.',], '-', $dateStr)); // Replace / or . with - for strtotime
                    if (!$timestamp) continue;

                    $scriptDate = date('Y-m-d', $timestamp);
                    $scriptMonth = date('Y-m', $timestamp);

                    // Today's income
                    if ($scriptDate === $todayDate) {
                        $todayIncome += $price;
                    }

                    // This month's income
                    if ($scriptMonth === $thisMonthStr) {
                        $monthIncome += $price;
                    }
                }

                /* =============================== */
                $logQuery = new \RouterOS\Query('/log/print');
                $logQuery->where('topics', 'hotspot,info,debug');

                $logData = $router_client->query($logQuery)->read();

                // Latest first (same as array_reverse)
                $logData = array_reverse($logData);

                // Limit logs (optional but recommended)
                $logData = array_slice($logData, 0, 50);
                log_message('debug', 'get_Hotspot_Dashboard logData router_id: ' . print_r($logData, true));


                $totalLogs = count($logData);

                $logs = [];

                foreach ($logData as $row) {
                    $logs[] = [
                        'time'    => ($row['time'] ?? '') . ' ' . ($row['date'] ?? ''),
                        'topic'   => $row['topics'] ?? '',
                        'message' => $row['message'] ?? '',
                    ];
                }

                log_message('debug', 'get_Hotspot_Dashboard log router_id: ' . print_r($logs, true));

                /* ===============================
             * 6️⃣ Response
             * =============================== */
                return $this->response->setJSON([
                    'system' => [
                        'time'   => date('Y-m-d H:i:s'),
                        'uptime' => $resource['uptime'] ?? 'N/A',
                    ],
                    'board' => [
                        'name' => $board['model'] ?? 'Unknown',
                        'os'   => $resource['version'] ?? 'Unknown',
                    ],
                    'resource' => [
                        'cpu' => (int)($resource['cpu-load'] ?? 0),
                        'ram' => isset($resource['total-memory'])
                            ? round($resource['total-memory'] / 1024 / 1024 / 1024, 2) . ' GiB'
                            : 'N/A',
                        'hdd' => isset($resource['total-hdd-space'])
                            ? round($resource['total-hdd-space'] / 1024 / 1024, 2) . ' MiB'
                            : 'N/A',
                    ],
                    'hotspot' => [
                        'active' => count($activeUsers),
                        'users'  => count($allUsers),
                    ],
                    'income' => [
                        'today' => $todayIncome,
                        'month' => $monthIncome,
                    ],
                    'logs' => [
                        'total' => $totalLogs,
                        'items' => $logs,
                    ],

                ]);
            }

            throw new \Exception('Invalid router client');
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard error: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'error'   => true,
                'message' => 'Unable to fetch router dashboard data',
            ]);
        }
    }



    public function users()
    {
        if (
            !userHasPermission('hotspot', 'read')
        ) show_404();

        $user_id = getSession('user_id');
        $router_model = new \App\Models\Router();
        $routers = $router_model
            ->select('*')
            ->where('user_id', $user_id)
            ->orderBy('id', 'desc')
            ->get()
            ->getResultArray();


        $data = [
            'title' => 'Hotspot Users',
            'routers' => $routers,
        ];

        return view('hotspot/users', $data);
    }

    public function get_users()
    {
        $router_id = $this->request->getGet('router_id');
        log_message('debug', 'get_user_profiles called for get_users router_id: ' . $router_id);

        //  $router_id = $this->request->getGet('router_id');

        // 🔽 Read filters from request (AJAX GET)
        $filterProfile = trim($this->request->getGet('profile') ?? '');
        $filterServer  = trim($this->request->getGet('server') ?? '');
        $filterComment = trim($this->request->getGet('comment') ?? '');

        $router_client = routerClient($router_id);

        try {
            if ($router_client instanceof \RouterOS\Client) {
                /* ===============================
             * 1️⃣ Get Hotspot Users
             * =============================== */
                $userQuery = new \RouterOS\Query('/ip/hotspot/user/print');
                $users = $router_client->query($userQuery)->read();

                /* ===============================
             * 2️⃣ Get User Profiles
             * =============================== */
                $profileQuery = new \RouterOS\Query('/ip/hotspot/user/profile/print');
                $profiles = $router_client->query($profileQuery)->read();

                log_message('debug', 'Total profiles found: ' . count($profiles));

                /* ===============================
             * 3️⃣ Get Hotspot Servers
             * =============================== */
                $serverQuery = new \RouterOS\Query('/ip/hotspot/print');
                $servers = $router_client->query($serverQuery)->read();

                log_message('debug', 'Total servers found: ' . count($servers));

                /* ===============================
             * 4️⃣ FORMAT + FILTER USERS
             * =============================== */
                $formattedUsers = [];

                foreach ($users as $user) {
                    $userProfile = $user['profile'] ?? 'default';
                    $userServer  = $user['server'] ?? 'all';
                    $userComment = $user['comment'] ?? '';

                    // ✅ APPLY FILTERS
                    if ($filterProfile !== '' && $userProfile !== $filterProfile) {
                        continue;
                    }

                    if ($filterServer !== '' && $userServer !== $filterServer) {
                        continue;
                    }

                    if (
                        $filterComment !== '' &&
                        stripos($userComment, $filterComment) === false
                    ) {
                        continue;
                    }

                    // ✅ ADD USER IF PASSES FILTER
                    $formattedUsers[] = [
                        '.id'               => $user['.id'] ?? null,
                        'id'                => $user['.id'] ?? null,
                        'server'            => $userServer,
                        'name'              => $user['name'] ?? '',
                        'password'          => $user['password'] ?? '',
                        'profile'           => $userProfile,
                        'mac-address'       => $user['mac-address'] ?? '',
                        'disabled'          => $user['disabled'] ?? 'no',
                        'limit-uptime'      => $user['limit-uptime'] ?? '0',
                        'limit-bytes-total' => $user['limit-bytes-total'] ?? '0',
                        'bytes-in'          => $user['bytes-in'] ?? '0',
                        'bytes-out'         => $user['bytes-out'] ?? '0',
                        'uptime'            => $user['uptime'] ?? '00:00:00',
                        'comment'           => $userComment,
                        'last-logged-out'   => $user['last-logged-out'] ?? '',
                        'address'           => $user['address'] ?? '',
                    ];
                }

                /* ===============================
             * 5️⃣ FORMAT PROFILES
             * =============================== */
                $formattedProfiles = [];
                foreach ($profiles as $profile) {
                    $formattedProfiles[] = [
                        '.id'           => $profile['.id'] ?? null,
                        'name'          => $profile['name'] ?? '',
                        'shared-users'  => $profile['shared-users'] ?? '1',
                        'rate-limit'    => $profile['rate-limit'] ?? '',
                        'address-pool'  => $profile['address-pool'] ?? 'none',
                        'parent-queue'  => $profile['parent-queue'] ?? 'none',
                        'default'       => $profile['default'] ?? false,
                        'on-login'      => $profile['on-login'] ?? '',
                    ];
                }

                /* ===============================
             * 6️⃣ FORMAT SERVERS
             * =============================== */
                $formattedServers = [];
                foreach ($servers as $server) {
                    $formattedServers[] = [
                        '.id'               => $server['.id'] ?? null,
                        'name'              => $server['name'] ?? '',
                        'interface'         => $server['interface'] ?? '',
                        'address-pool'      => $server['address-pool'] ?? '',
                        'disabled'          => $server['disabled'] ?? 'no',
                        'addresses-per-mac' => $server['addresses-per-mac'] ?? '1',
                    ];
                }

                log_message('debug', 'Data prepared for JSON response');

                return $this->response->setJSON([
                    'success'        => true,
                    'users'          => array_values($formattedUsers),
                    'profiles'       => $formattedProfiles,
                    'servers'        => $formattedServers,
                    'total_users'    => count($formattedUsers),
                    'total_profiles' => count($formattedProfiles),
                    'total_servers'  => count($formattedServers),
                ]);
            } else {
                /* ===============================
             * fsocket fallback method
             * =============================== */
                $fp = connect_using_Fsocket($router_id);

                if (!$fp) {
                    throw new \Exception('Unable to connect to router via fsocket');
                }

                // Get users via fsocket
                $users = getFsockUsers($fp);
                if (is_resource($fp)) fclose($fp);

                /* ===============================
             * FILTER fsocket users
             * =============================== */
                $filteredUsers = [];
                foreach ($users as $user) {
                    $userProfile = $user['profile'] ?? 'default';
                    $userServer  = $user['server'] ?? 'all';
                    $userComment = $user['comment'] ?? '';

                    // ✅ APPLY FILTERS
                    if ($filterProfile !== '' && $userProfile !== $filterProfile) {
                        continue;
                    }

                    if ($filterServer !== '' && $userServer !== $filterServer) {
                        continue;
                    }

                    if (
                        $filterComment !== '' &&
                        stripos($userComment, $filterComment) === false
                    ) {
                        continue;
                    }

                    $filteredUsers[] = $user;
                }

                // Get profiles via fsocket (reconnect)
                $fp = connect_using_Fsocket($router_id);
                $profiles = getFsockProfiles($fp);
                if (is_resource($fp)) fclose($fp);

                // Get servers via fsocket (reconnect)
                $fp = connect_using_Fsocket($router_id);
                $servers = getFsockServers($fp);
                if (is_resource($fp)) fclose($fp);

                return $this->response->setJSON([
                    'success'        => true,
                    'users'          => array_values($filteredUsers),
                    'profiles'       => $profiles,
                    'servers'        => $servers,
                    'total_users'    => count($filteredUsers),
                    'total_profiles' => count($profiles),
                    'total_servers'  => count($servers),
                ]);
            }
        } catch (\Throwable $e) {
            if (isset($fp) && is_resource($fp)) fclose($fp);
            log_message('error', 'Error in get_users: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Failed to fetch data: ' . $e->getMessage(),
                'users'   => [],
                'profiles' => [],
                'servers' => [],
                'total_users'    => 0,
                'total_profiles' => 0,
                'total_servers'  => 0,
            ]);
        }
    }






    function addHotspotUserProfile()
    {
        if (
            !userHasPermission('hotspot', 'create')
        ) show_404();

        $router_id = $this->request->getGet('router_id');
        //  $router_id = $this->request->getGet('router_id'); // Default router ID, modify as needed
        log_message('debug', 'addHotspotUserProfile called with router_id: ' . $router_id);

        $request = $this->request;

        // CSRF & validation handled automatically in CI4 if enabled
        $data = [
            'name'        => $request->getPost('name'),
            'sharedusers' => $request->getPost('shared_users'),
            'ratelimit'   => $request->getPost('rate_limit'),
            'ppool'       => $request->getPost('address_pool'),
            'parent'      => $request->getPost('parent_queue'),
            'expmode'     => $request->getPost('expired_mode'),
            'validity'    => $request->getPost('validity'),
            'price'       => $request->getPost('price_bdt'),
            'sprice'      => $request->getPost('selling_price_bdt'),
            'lockunlock'  => $request->getPost('lock_user'),
        ];

        log_message('debug', 'addHotspotUserProfile input data: ' . print_r($data, true));

        $router_client = routerClient($router_id);

        /* ===============================
     * INPUT & SANITIZE
     * =============================== */
        $name        = preg_replace('/\s+/', '-', $data['name']);
        $sharedusers = $data['sharedusers'];
        $ratelimit   = $data['ratelimit'];
        $expmode     = $data['expmode'];
        $validity    = $data['validity'] ?? '';
        $price       = $data['price'] ?? '0';
        $sprice      = $data['sprice'] ?? '0';
        $addrpool    = $data['ppool'];
        $parent      = $data['parent'] ?? '';
        $lockmode    = $data['lockunlock'] ?? 'Disable';

        $price  = $price ?: '0';
        $sprice = $sprice ?: '0';

        /* ===============================
     * LOCK SCRIPT
     * =============================== */


        /* ===============================
     * RANDOM SCHEDULER TIMING
     * =============================== */
        $randstarttime = '0' . rand(1, 5) . ':' . rand(10, 59) . ':' . rand(10, 59);
        $randinterval  = '00:02:' . rand(10, 59);

        /* ===============================
     * ON-LOGIN & BACKGROUND SERVICE
     * =============================== */
        $lockText = ($lockmode === 'Enable') ? 'Enable' : '';
        $lockScript = ($lockmode === 'Enable')
            ? '; [:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pick $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ]; :local year [ :pick $date 7 11 ]; :local month [ :pick $date 0 3 ]; /sys sch add name="$user" disable=no start-date=$date interval="15d"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pick $exp 0 6]; :local t [:pick $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];}; :delay 5s; /sys sch remove [find where name="$user"]; :local mac $"mac-address"; :local time [/system clock get time]; /system script add name="$date-|-$time-|-$user-|-90-|-$address-|-$mac-|-15d-|-15-Days-|-$comment" owner="$month$year" source="$date" comment="mikhmon"; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]}}]'
            : '';


        $onlogin = ':put (",' . $expmode . ',' . $price . ',' . $validity . ',' . $sprice . ',,' . $lockText . ',")' . $lockScript;
        $bgservice = buildHotspotBackgroundService($name, $expmode);




        // Prepare the data array
        $profileData = [
            'name'               => $name,
            'address-pool'       => $addrpool,
            'rate-limit'         => $ratelimit,
            'shared-users'       => $sharedusers,
            'status-autorefresh' => '1m',
            'on-login'           => $onlogin,
            'parent-queue'       => $parent,
        ];

        // Log the data before sending to Mikrotik
        log_message('debug', 'Adding Hotspot Profile: ' . print_r($profileData, true));

        /* ===============================
     * 1️⃣ RouterOS\Client (API)
     * =============================== */
        if ($router_client instanceof \RouterOS\Client) {
            try {
                log_message('debug', 'Using RouterOS API to add hotspot profile.');


                $query = new \RouterOS\Query('/ip/hotspot/user/profile/add');

                $query->equal('name', $name);
                $query->equal('address-pool', $addrpool);
                $query->equal('rate-limit', $ratelimit);
                $query->equal('shared-users', $sharedusers);
                $query->equal('status-autorefresh', '1m');
                $query->equal('on-login', $onlogin);

                $parent = (!empty($parent) && $parent !== 'none') ? $parent : 'none';

                if ($parent) {
                    $query->equal('parent-queue', $parent);
                }

                $response = $router_client
                    ->query($query)
                    ->read();
                log_message('debug', 'Hotspot profile added via API: ' . print_r($response, true));


                handleProfileSchedulerAPI(
                    $router_client,
                    $name,
                    $bgservice,
                    $randstarttime,
                    $randinterval,
                    $expmode
                );

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Profile added successfully'
                ]);
            } catch (\Throwable $e) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Failed to add profile: ' . $e->getMessage()
                ]);
            }
        }

        /* ===============================
         * 2️⃣ fsocket fallback
         * =============================== */
        $fp = connect_using_Fsocket($router_id);
        if (!$fp) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Unable to connect to router'
            ]);
        }

        $result = addHotspotProfileFsock(
            $fp,
            $name,
            $addrpool,
            $ratelimit,
            $sharedusers,
            $onlogin,
            $parent,
            $bgservice,
            $randstarttime,
            $randinterval,
            $expmode
        );

        return $this->response->setJSON([
            'success' => (bool)$result,
            'error' => $result ? null : 'Failed to add profile via Fsocket'
        ]);
    }

    function updateHotspotUserProfile()
    {
        if (
            !userHasPermission('hotspot', 'update')
        ) show_404();
        $router_id = $this->request->getGet('router_id'); // change if dynamic
        log_message('debug', 'updateHotspotUserProfile called');

        $request = $this->request;

        /* ===============================
     * INPUT DATA
     * =============================== */
        $data = [
            'id'          => $request->getPost('id'),
            'name'        => $request->getPost('name'),
            'sharedusers' => $request->getPost('shared_users'),
            'ratelimit'   => $request->getPost('rate_limit'),
            'ppool'       => $request->getPost('address_pool'),
            'parent'      => $request->getPost('parent_queue'),
            'expmode'     => $request->getPost('expired_mode'),
            'validity'    => $request->getPost('validity'),
            'price'       => $request->getPost('price_bdt'),
            'sprice'      => $request->getPost('selling_price_bdt'),
            'lockunlock'  => $request->getPost('lock_user'),
        ];

        log_message('debug', 'Update profile input: ' . print_r($data, true));

        if (empty($data['id'])) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Invalid profile ID'
            ]);
        }

        /* ===============================
     * SANITIZE
     * =============================== */
        $profileId  = $data['id'];
        $name       = preg_replace('/\s+/', '-', $data['name']);
        $sharedusers = $data['sharedusers'];
        $ratelimit  = $data['ratelimit'];
        $expmode    = $data['expmode'];
        $validity   = $data['validity'] ?? '';
        $price      = $data['price'] ?: '0';
        $sprice     = $data['sprice'] ?: '0';
        $addrpool   = $data['ppool'];
        $parent     = $data['parent'] ?? '';
        $lockmode   = $data['lockunlock'] ?? 'Disable';

        /* ===============================
     * LOCK SCRIPT
     * =============================== */


        /* ===============================
     * RANDOM SCHEDULER TIME
     * =============================== */
        $randstarttime = '0' . rand(1, 5) . ':' . rand(10, 59) . ':' . rand(10, 59);
        $randinterval  = '00:02:' . rand(10, 59);

        /* ===============================
     * SCRIPTS
     * =============================== */
        $lockText = ($lockmode === 'Enable') ? 'Enable' : '';
        $lockScript = ($lockmode === 'Enable')
            ? '; [:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pick $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ]; :local year [ :pick $date 7 11 ]; :local month [ :pick $date 0 3 ]; /sys sch add name="$user" disable=no start-date=$date interval="15d"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pick $exp 0 6]; :local t [:pick $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];}; :delay 5s; /sys sch remove [find where name="$user"]; :local mac $"mac-address"; :local time [/system clock get time]; /system script add name="$date-|-$time-|-$user-|-90-|-$address-|-$mac-|-15d-|-15-Days-|-$comment" owner="$month$year" source="$date" comment="mikhmon"; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]}}]'
            : '';

        $onlogin = ':put (",' . $expmode . ',' . $price . ',' . $validity . ',' . $sprice . ',,' . $lockText . ',")' . $lockScript;


        $bgservice = buildHotspotBackgroundService($name, $expmode);

        /* ===============================
     * ROUTER CLIENT
     * =============================== */
        $router_client = routerClient($router_id);

        /* ===============================
     * 1️⃣ RouterOS API UPDATE
     * =============================== */
        if ($router_client instanceof \RouterOS\Client) {
            try {
                log_message('debug', 'Updating hotspot profile via RouterOS API');

                $query = new \RouterOS\Query('/ip/hotspot/user/profile/set');
                $query->equal('.id', $profileId);
                $query->equal('name', $name);
                $query->equal('address-pool', $addrpool);
                $query->equal('rate-limit', $ratelimit);
                $query->equal('shared-users', $sharedusers);
                $query->equal('status-autorefresh', '1m');
                $query->equal('on-login', $onlogin);

                $parent = (!empty($parent) && $parent !== 'none') ? $parent : 'none';

                if ($parent) {
                    $query->equal('parent-queue', $parent);
                }

                $response = $router_client->query($query)->read();
                log_message('debug', 'Profile updated: ' . print_r($response, true));

                /* ===============================
             * Scheduler update
             * =============================== */
                handleProfileSchedulerAPI(
                    $router_client,
                    $name,
                    $bgservice,
                    $randstarttime,
                    $randinterval,
                    $expmode
                );

                return $this->response->setJSON([
                    'success' => true
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Update profile API error: ' . $e->getMessage());
            }
        }

        /* ===============================
     * 2️⃣ fsocket fallback
     * =============================== */
        $fp = connect_using_Fsocket($router_id);
        if (!$fp) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Router connection failed'
            ]);
        }

        $result = updateHotspotProfileFsock(
            $fp,
            $profileId,
            $name,
            $addrpool,
            $ratelimit,
            $sharedusers,
            $onlogin,
            $parent,
            $bgservice,
            $randstarttime,
            $randinterval,
            $expmode
        );

        return $this->response->setJSON([
            'success' => (bool) $result
        ]);
    }



    function user_profiles_delete()
    {
        if (
            !userHasPermission('hotspot', 'delete')
        ) show_404();

        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Invalid request'
            ]);
        }

        $profileId = $this->request->getPost('id');

        if (!$profileId) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Profile ID missing'
            ]);
        }

        $router_id = $this->request->getGet('router_id');
        $client = routerClient($router_id);

        try {
            if ($client instanceof \RouterOS\Client) {

                try {
                    if ($client instanceof \RouterOS\Client) {

                        /* ===============================
     * 1️⃣ DELETE SCHEDULER FIRST
     * =============================== */
                        if (!empty($profileName)) {

                            $printScheduler = new \RouterOS\Query('/system/scheduler/print');
                            $printScheduler->where('name', $profileName);

                            $schedulers = $client->query($printScheduler)->read();

                            if (!empty($schedulers)) {
                                $removeScheduler = new \RouterOS\Query('/system/scheduler/remove');
                                $removeScheduler->equal('.id', $schedulers[0]['.id']);

                                $client->query($removeScheduler)->read();

                                log_message('debug', "Scheduler deleted: {$profileName}");
                            }
                        }

                        /* ===============================
     * 2️⃣ DELETE HOTSPOT PROFILE
     * =============================== */
                        $removeProfile = new \RouterOS\Query('/ip/hotspot/user/profile/remove');
                        $removeProfile->equal('.id', $profileId);

                        // 🔥 read() is REQUIRED for remove
                        $client->query($removeProfile)->read();

                        log_message('debug', "Hotspot profile deleted successfully: {$profileId}");

                        /* ===============================
     * 3️⃣ SUCCESS RESPONSE
     * =============================== */
                        return $this->response->setJSON([
                            'success' => true,
                            'message' => 'Profile and scheduler deleted successfully'
                        ]);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Delete profile error: ' . $e->getMessage());

                    return $this->response->setJSON([
                        'success' => false,
                        'error' => 'Delete failed'
                    ]);
                }
            }

            return $this->response->setJSON([
                'success' => false,
                'error' => 'Router connection failed'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Delete profile error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'error' => 'API error'
            ]);
        }
    }







    public function addHotspotUser()
    {
        if (
            !userHasPermission('hotspot', 'create')
        ) show_404();
        $router_id = $this->request->getGet('router_id');
        log_message('debug', 'addHotspotUser called with router_id: ' . $router_id);

        $request = $this->request;

        $data = [
            'server'    => $request->getPost('server'),
            'name'      => $request->getPost('name'),
            'password'  => $request->getPost('password'),
            'profile'   => $request->getPost('profile'),
            'mac_address' => $request->getPost('mac_address'),
            'timelimit' => $request->getPost('timelimit'),
            'datalimit' => $request->getPost('datalimit'),
            'comment'   => $request->getPost('comment'),
        ];

        log_message('debug', 'addHotspotUser input data: ' . print_r($data, true));

        $router_client = routerClient($router_id);

        $server    = $data['server'] ?? 'all';
        $name      = trim($data['name']);
        $password  = trim($data['password']);
        $profile   = $data['profile'] ?? 'default';
        $mac_address = $data['mac_address'] ?? '';
        $timelimit = $data['timelimit'] ?? '';
        $datalimit = $data['datalimit'] ?? '';
        $additional_comment = $data['comment'] ?? '';

        // Get current date in EXACT format as original: m.d.y
        $current_date = date('m.d.y');
        $random_code = rand(100, 999);

        // Determine user mode and format comment
        if ($name === $password) {
            $usermode = "vc";
        } else {
            $usermode = "up";
        }

        // Build comment in EXACT original format: up-999-12.19.25-
        // ALWAYS include trailing dash, even if no additional comment
        $final_comment = "{$usermode}-{$random_code}-{$current_date}-";
        if (!empty($additional_comment)) {
            // If there's additional comment, replace the trailing dash with it
            $final_comment = "{$usermode}-{$random_code}-{$current_date}-{$additional_comment}";
        }

        // Check profile details
        try {
            $profileQuery = new \RouterOS\Query('/ip/hotspot/user/profile/print');
            $profileQuery->where('name', $profile);
            $profiles = $router_client->query($profileQuery)->read();

            if (!empty($profiles)) {
                $profileData = $profiles[0];
                log_message('debug', 'Profile details: ' . print_r($profileData, true));

                // Parse the on-login script to get validity
                if (isset($profileData['on-login'])) {
                    $onlogin = $profileData['on-login'];
                    if (preg_match('/\((".*?")\)/', $onlogin, $matches)) {
                        $params = explode(',', trim($matches[1], '"\''));
                        if (count($params) >= 4) {
                            $validity = $params[3] ?? '';
                            // Auto-set timelimit from profile validity if not provided
                            if (!empty($validity) && $validity !== '0' && empty($timelimit)) {
                                $timelimit = $validity;
                                log_message('debug', "Auto-setting timelimit to profile validity: {$timelimit}");
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('debug', 'Could not fetch profile details: ' . $e->getMessage());
        }

        // For time-based profiles, ensure timelimit is set
        if (empty($timelimit) && !empty($profile) && $profile !== 'default') {
            if (preg_match('/(\d+)[-_]?Days?/i', $profile, $matches)) {
                $days = (int)$matches[1];
                $timelimit = $days . 'd';
            } elseif (preg_match('/(\d+)[-_]?(Hours?|Hrs?)/i', $profile, $matches)) {
                $hours = (int)$matches[1];
                $timelimit = $hours . 'h';
            }
        }

        // Prepare user data - SET BOTH limit-uptime AND comment in correct format
        $userData = [
            'server' => $server,
            'name' => $name,
            'password' => $password,
            'profile' => $profile,
            'disabled' => 'no',
            'comment' => $final_comment,
        ];

        // CRITICAL: Always set limit-uptime for time-based profiles
        if (!empty($timelimit)) {
            $userData['limit-uptime'] = $timelimit;
        } elseif (!empty($profile) && $profile !== 'default') {
            // If profile is time-based but no timelimit given, set default
            $userData['limit-uptime'] = '1d';
        }

        if (!empty($mac_address)) {
            $userData['mac-address'] = $mac_address;
        }

        if (!empty($datalimit) && is_numeric($datalimit)) {
            $datalimit_bytes = $datalimit * 1024 * 1024;
            $userData['limit-bytes-total'] = (string)$datalimit_bytes;
        }

        log_message('debug', 'Adding Hotspot User: ' . print_r($userData, true));

        if ($router_client instanceof \RouterOS\Client) {
            try {
                $query = new \RouterOS\Query('/ip/hotspot/user/add');

                foreach ($userData as $key => $value) {
                    $query->equal($key, $value);
                }

                $response = $router_client->query($query)->read();

                log_message('debug', 'Hotspot user added via API: ' . print_r($response, true));

                // Wait and verify
                sleep(2);
                $getUserQuery = new \RouterOS\Query('/ip/hotspot/user/print');
                $getUserQuery->where('name', $name);
                $users = $router_client->query($getUserQuery)->read();

                $uid = null;
                $user_found = !empty($users);

                if ($user_found) {
                    $uid = $users[0]['.id'] ?? null;
                    log_message('debug', 'User verified with ID: ' . $uid);
                    log_message('debug', 'Current user comment: ' . ($users[0]['comment'] ?? 'N/A'));
                } else {
                    log_message('error', 'User was created but not found after 2 seconds!');

                    // Try to find any recently deleted users
                    $allUsersQuery = new \RouterOS\Query('/ip/hotspot/user/print');
                    $allUsers = $router_client->query($allUsersQuery)->read();
                    log_message('debug', 'Total users in system: ' . count($allUsers));

                    // Check for users with similar names
                    foreach ($allUsers as $u) {
                        if (strpos($u['name'] ?? '', $name) !== false) {
                            log_message('debug', 'Found similar user: ' . print_r($u, true));
                        }
                    }
                }

                return $this->response->setJSON([
                    'success' => $user_found,
                    'message' => $user_found ? 'User added successfully' : 'User added but may have been auto-removed',
                    'uid' => $uid,
                    'comment' => $final_comment,
                    'verified' => $user_found,
                    'profile_requires_timelimit' => !empty($timelimit),
                    'data' => $userData
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Failed to add hotspot user via API: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Failed to add user: ' . $e->getMessage()
                ]);
            }
        }

        /* ===============================
     * 2️⃣ fsocket fallback
     * =============================== */
        $fp = connect_using_Fsocket($router_id);
        if (!$fp) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Unable to connect to router'
            ]);
        }

        $result = addHotspotUserFsock(
            $fp,
            $server,
            $name,
            $password,
            $profile,
            $mac_address,
            $timelimit,
            $datalimit,
            $final_comment
        );

        return $this->response->setJSON([
            'success' => (bool)$result,
            'message' => $result ? 'User added successfully' : 'Failed to add user',
            'error' => $result ? null : 'Failed to add user via Fsocket'
        ]);
    }

    public function generateUsers()
    {
        if (
            !userHasPermission('hotspot', 'create')
        ) show_404();
        $router_id = $this->request->getGet('router_id'); // Default router ID
        log_message('debug', 'generateUsers called');

        $request = $this->request;

        // Get all form data
        $data = [
            'qty' => $request->getPost('qty'),
            'server' => $request->getPost('server'),
            'user_mode' => $request->getPost('user_mode'),
            'name_length' => $request->getPost('name_length'),
            'password_length' => $request->getPost('password_length'),
            'prefix_type' => $request->getPost('prefix_type'),
            'prefix_value' => $request->getPost('prefix_value'),
            'profile' => $request->getPost('profile'),
            'time_limit' => $request->getPost('time_limit'),
            'data_limit' => $request->getPost('data_limit'),
            'comment' => $request->getPost('comment'),
        ];

        log_message('debug', 'generateUsers input: ' . print_r($data, true));

        $router_client = routerClient($router_id);

        $qty = (int)$data['qty'];
        $server = $data['server'] ?? 'all';
        $profile = $data['profile'] ?? 'default';
        $time_limit = $data['time_limit'] ?? '';
        $data_limit = $data['data_limit'] ?? '';
        $additional_comment = $data['comment'] ?? '';

        $name_length = (int)$data['name_length'];
        $password_length = (int)$data['password_length'];
        $prefix_type = $data['prefix_type'] ?? 'character';
        $prefix_value = $data['prefix_value'] ?? 'abcd';

        $user_mode = $data['user_mode'] ?? 'username_password';

        // Convert MB to bytes if data limit provided
        $data_limit_bytes = '';
        if (!empty($data_limit) && is_numeric($data_limit)) {
            $data_limit_bytes = (string)($data_limit * 1024 * 1024);
        }

        // Get current date in m.d.y format (like 12.19.25)
        $current_date = date('m.d.y');

        // Character sets based on prefix type - MATCH THE ORIGINAL FORMAT
        $charsets = [
            'character' => 'abcdefghijklmnopqrstuvwxyz',           // lower case letters
            'number' => '0123456789',                             // numbers only
            'alphanumeric' => 'abcdefghijklmnopqrstuvwxyz0123456789', // lower case + numbers
            'random' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', // mixed case + numbers
            'custom' => $prefix_value
        ];

        $charset = $charsets[$prefix_type] ?? $charsets['character'];

        // If prefix_type is "custom" but prefix_value is empty, use default
        if ($prefix_type === 'custom' && empty($prefix_value)) {
            $charset = $charsets['character'];
        }

        // Generate random lowercase string (like original randLC)
        function generateRandomLC($length)
        {
            $chars = 'abcdefghijklmnopqrstuvwxyz';
            $result = '';
            for ($i = 0; $i < $length; $i++) {
                $result .= $chars[rand(0, 25)];
            }
            return $result;
        }

        // Generate random uppercase string (like original randUC)
        function generateRandomUC($length)
        {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $result = '';
            for ($i = 0; $i < $length; $i++) {
                $result .= $chars[rand(0, 25)];
            }
            return $result;
        }

        // Generate random mixed case string (like original randULC)
        function generateRandomULC($length)
        {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $result = '';
            for ($i = 0; $i < $length; $i++) {
                $result .= $chars[rand(0, 51)];
            }
            return $result;
        }

        // Generate random numbers (like original randN)
        function generateRandomN($length)
        {
            $chars = '0123456789';
            $result = '';
            for ($i = 0; $i < $length; $i++) {
                $result .= $chars[rand(0, 9)];
            }
            return $result;
        }

        // Generate random lowercase + numbers (like original randNLC)
        function generateRandomNLC($length)
        {
            $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $result = '';
            for ($i = 0; $i < $length; $i++) {
                $result .= $chars[rand(0, 35)];
            }
            return $result;
        }

        // Generate random uppercase + numbers (like original randNUC)
        function generateRandomNUC($length)
        {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $result = '';
            for ($i = 0; $i < $length; $i++) {
                $result .= $chars[rand(0, 35)];
            }
            return $result;
        }

        // Generate random mixed case + numbers (like original randNULC)
        function generateRandomNULC($length)
        {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $result = '';
            for ($i = 0; $i < $length; $i++) {
                $result .= $chars[rand(0, 61)];
            }
            return $result;
        }

        $generated_users = [];
        $success_count = 0;

        try {
            for ($i = 0; $i < $qty; $i++) {
                // Generate random code for comment (3 digits)
                $random_code = rand(100, 999);

                // Generate comment in original format: up-999-12.19.25-comment
                $comment_base = '';
                $username = '';
                $password = '';

                // Determine user mode and generate accordingly
                if ($user_mode === 'username_only') {
                    // VC Mode: username = password
                    $comment_base = "vc-{$random_code}-{$current_date}";

                    // Generate username based on prefix_type
                    $prefix_length = $name_length;

                    if ($prefix_type === 'character') {
                        $username = generateRandomLC($prefix_length);
                    } elseif ($prefix_type === 'number') {
                        $username = generateRandomN($prefix_length);
                    } elseif ($prefix_type === 'alphanumeric') {
                        $username = generateRandomNLC($prefix_length);
                    } elseif ($prefix_type === 'random') {
                        $username = generateRandomNULC($prefix_length);
                    } else {
                        // Custom - use the provided characters
                        $username = generateRandomString($prefix_length, $charset);
                    }

                    $password = $username; // Same as username for VC mode

                } else {
                    // UP Mode: username ≠ password
                    $comment_base = "up-{$random_code}-{$current_date}";

                    // Generate username (lowercase letters only for username in UP mode)
                    $username = generateRandomLC($name_length);

                    // Generate password based on user_mode
                    if ($user_mode === 'password_only') {
                        $password = generateRandomN($password_length);
                    } else {
                        // username_password mode - mixed password
                        $password = generateRandomN($password_length);
                    }
                }

                // Add prefix to username if provided
                if (!empty($prefix_value) && $prefix_type !== 'custom') {
                    $username = $prefix_value . $username;
                }

                // Skip if username or password is empty
                if (empty($username) || empty($password)) {
                    log_message('debug', 'Skipping empty username/password');
                    continue;
                }

                // Final comment with additional text if provided
                $final_comment = $comment_base;
                if (!empty($additional_comment)) {
                    $final_comment .= '-' . $additional_comment;
                }

                // Prepare user data
                $userData = [
                    'server' => $server,
                    'name' => $username,
                    'password' => $password,
                    'profile' => $profile,
                    'disabled' => 'no',
                    'comment' => $final_comment,
                ];

                // Add time limit if provided
                if (!empty($time_limit)) {
                    $userData['limit-uptime'] = $time_limit;
                }

                // Add data limit if provided
                if (!empty($data_limit_bytes)) {
                    $userData['limit-bytes-total'] = $data_limit_bytes;
                }

                log_message('debug', 'Generating user ' . ($i + 1) . ': ' . print_r($userData, true));

                // Add user using API
                if ($router_client instanceof \RouterOS\Client) {
                    $query = new \RouterOS\Query('/ip/hotspot/user/add');

                    foreach ($userData as $key => $value) {
                        if (($key === 'limit-uptime' || $key === 'limit-bytes-total') && empty($value)) {
                            continue;
                        }
                        $query->equal($key, $value);
                    }

                    try {
                        $response = $router_client->query($query)->read();

                        if (!empty($response)) {
                            $success_count++;
                            $generated_users[] = [
                                'username' => $username,
                                'password' => $password,
                                'comment' => $final_comment,
                                'success' => true
                            ];

                            log_message('debug', 'User ' . $username . ' added successfully');
                        }
                    } catch (\Throwable $userError) {
                        log_message('error', 'Failed to add user ' . $username . ': ' . $userError->getMessage());
                        $generated_users[] = [
                            'username' => $username,
                            'password' => $password,
                            'comment' => $final_comment,
                            'success' => false,
                            'error' => $userError->getMessage()
                        ];
                    }
                }
            }

            log_message('debug', 'Total users generated: ' . $success_count . ' out of ' . $qty);

            return $this->response->setJSON([
                'success' => $success_count > 0,
                'message' => $success_count . ' users generated successfully!',
                'count' => $success_count,
                'total_requested' => $qty,
                'generated_users' => $generated_users
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to generate users: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Failed to generate users: ' . $e->getMessage(),
                'count' => $success_count,
                'total_requested' => $qty
            ]);
        }
    }



    public function updateHotspotUser()
    {
        if (
            !userHasPermission('hotspot', 'update')
        ) show_404();
        $router_id = $this->request->getGet('router_id');
        log_message('debug', 'updateHotspotUser called');

        $request = $this->request;

        $data = [
            'id' => $request->getPost('id'),
            'server' => $request->getPost('server'),
            'name' => $request->getPost('name'),
            'password' => $request->getPost('password'),
            'profile' => $request->getPost('profile'),
            'mac_address' => $request->getPost('mac_address'),
            'comment' => $request->getPost('comment'),
        ];

        log_message('debug', 'updateHotspotUser input: ' . print_r($data, true));

        if (empty($data['id'])) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'User ID is required'
            ]);
        }

        $router_client = routerClient($router_id);

        try {
            // First check if user exists
            $query = new \RouterOS\Query('/ip/hotspot/user/print');
            $query->where('.id', $data['id']);
            $users = $router_client->query($query)->read();

            if (empty($users)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'User not found'
                ]);
            }

            // Update user
            $updateQuery = new \RouterOS\Query('/ip/hotspot/user/set');
            $updateQuery->equal('.id', $data['id']);

            if (!empty($data['server'])) {
                $updateQuery->equal('server', $data['server']);
            }

            if (!empty($data['name'])) {
                $updateQuery->equal('name', $data['name']);
            }

            if (!empty($data['password'])) {
                $updateQuery->equal('password', $data['password']);
            }

            if (!empty($data['profile'])) {
                $updateQuery->equal('profile', $data['profile']);
            }

            if (!empty($data['mac_address'])) {
                $updateQuery->equal('mac-address', $data['mac_address']);
            }


            $additional_comment = $data['comment'] ?? '';


            $updateQuery->equal('comment', $additional_comment);


            $response = $router_client->query($updateQuery)->read();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to update user: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Failed to update user: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteHotspotUser()
    {
        if (
            !userHasPermission('hotspot', 'delete')
        ) show_404();
        $router_id = $this->request->getGet('router_id');
        log_message('debug', 'deleteHotspotUser called');

        $request = $this->request;
        $id = $request->getPost('id');

        if (empty($id)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'User ID is required'
            ]);
        }

        $router_client = routerClient($router_id);

        try {
            $query = new \RouterOS\Query('/ip/hotspot/user/remove');
            $query->equal('.id', $id);

            $response = $router_client->query($query)->read();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to delete user: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Failed to delete user: ' . $e->getMessage()
            ]);
        }
    }

    public function report()
    {
        if (
            !userHasPermission('hotspot', 'read')
        ) show_404();


        return view('hotspot/report', [
            'title' => 'Hotspot Selling Report'
        ]);
    }

    public function get_report()
    {
        $router_id = $this->request->getGet('router_id');
        log_message('debug', 'get_report called with router_id: ' . $router_id);

        if (empty($router_id)) {
            log_message('error', 'Router ID missing in get_report');
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Router ID is required'
            ]);
        }

        // Optional filters
        $day   = $this->request->getGet('day');
        $month = $this->request->getGet('month');
        $year  = $this->request->getGet('year');

        log_message('debug', 'Report filters: ' . json_encode([
            'day' => $day,
            'month' => $month,
            'year' => $year
        ]));

        // Get router client
        $router_client = routerClient($router_id);

        if (!($router_client instanceof \RouterOS\Client)) {
            log_message('error', 'Invalid router client in get_report');
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Failed to connect to router'
            ]);
        }

        try {
            log_message('debug', 'Fetching selling scripts from MikroTik');

            $query = new \RouterOS\Query('/system/script/print');
            $query->where('comment', 'mikhmon');

            $scripts = $router_client->query($query)->read();

            log_message('debug', 'Total scripts fetched: ' . count($scripts));

            $rows  = [];
            $total = 0;
            $no    = 1;

            foreach ($scripts as $script) {

                if (!isset($script['name'])) {
                    continue;
                }

                /**
                 * Mikhmon encoded format:
                 * date-|-time-|-username-|-price-|-...-|-profile-|-comment
                 */
                $parts = explode('-|-', $script['name']);

                if (count($parts) < 4) {
                    log_message('debug', 'Skipping invalid script name: ' . $script['name']);
                    continue;
                }

                $date     = $parts[0] ?? '';
                $time     = $parts[1] ?? '';
                $username = $parts[2] ?? '';
                $price    = (int) ($parts[3] ?? 0);
                $profile  = $parts[7] ?? '-';
                $comment  = $parts[8] ?? '-';

                /**
                 * OPTIONAL date filter
                 * Format example: jan/09/2026 or 01.09.26
                 */
                if (!empty($day) || !empty($month) || !empty($year)) {

                    $dateStr = strtolower($date);

                    if (!empty($year) && strpos($dateStr, (string)$year) === false) {
                        continue;
                    }

                    if (!empty($month) && strpos($dateStr, strtolower($month)) === false) {
                        continue;
                    }

                    if (!empty($day) && strpos($dateStr, (string)$day) === false) {
                        continue;
                    }
                }

                $rows[] = [
                    'no'       => $no++,
                    'date'     => $date,
                    'time'     => $time,
                    'username' => $username,
                    'profile'  => $profile,
                    'comment'  => $comment,
                    'price'    => $price,
                ];

                $total += $price;
            }

            log_message('debug', 'Report rows prepared: ' . count($rows));
            log_message('debug', 'Total amount calculated: ' . $total);

            return $this->response->setJSON([
                'success' => true,
                'rows'    => $rows,
                'total'   => $total,
                'count'   => count($rows),
            ]);
        } catch (\Throwable $e) {

            log_message('error', 'Failed to fetch selling report: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Failed to fetch report',
                'details' => $e->getMessage()
            ]);
        }
    }
}
