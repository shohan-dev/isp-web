<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\CLI\Console;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;
use App\Models\UserRouterDataModel;
use App\Models\User;
use \RouterOS\Client;
use RouterOS\Query;
use App\Models\UserBindingModel;
use App\Libraries\RouterService;

set_time_limit(0);

class Routers extends BaseController
{
    protected $router_model, $user_model;
    protected static $router_client;

    public function __construct()
    {
        /**
         * Router Model
         */
        $this->router_model = model('App\Models\Router');

        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
    }


    /**
     * Routers
     * @action: All Router View
     */
    public function index()
    {
        if (!userHasPermission('routers', 'read'))
            show_404();
        if (getSession('user_role') === 'super_admin') {
            $id = $this->request->getGet('id');
            log_message('info', 'routerId: ' . $id);
        }

        $data = [
            'title' => 'Mikrotik Routers',
            'id' => $id ?? null,
        ];

        return view('routers/all', $data);
    }
    public function allusers()
    {
        if (!userHasPermission('routers', 'read'))
            show_404();
        $routerId = getGetInput('routerId') ?? null;

        log_message('info', 'routerId: ' . $routerId);

        $data = [
            'title' => 'All Routers Users',
            'routerId' => $routerId,
        ];

        return view('RouterUsers/allUsers', $data);
    }
    public function inactiveusers()
    {
        if (!userHasPermission('routers', 'read'))
            show_404();
        $routerId = getGetInput('routerId') ?? null;

        log_message('info', 'routerId: ' . $routerId);

        $data = [
            'title' => 'Inactive Router Users',
            'routerId' => $routerId,
        ];

        return view('RouterUsers/inactiveUsers', $data);
    }

    public function activeusers()
    {
        $routerId = getGetInput('routerId') ?? null;

        //     log_message('info', 'routerId: ' . $routerId);

        //     // Use CodeIgniter's request object to get the 'routerId' from the query parameters
        // $routerId = $this->request->getGet('routerId'); 

        // Log the routerId to verify if it's being retrieved
        // log_message('info', 'routers routerId: ' . $routerId);


        if (!userHasPermission('routers', 'read'))
            show_404();



        $data = [
            'title' => 'Active Router Users',
            'routerId' => $routerId,
        ];

        return view('RouterUsers/activeUsers', $data);
    }


    /**
     * Routers
     * @action: Fetch Routers
     */
    public function fetch()
    {
        if (!userHasPermission('routers', 'read'))
            show_404();

        if (getSession('user_role') === 'super_admin') {
            $userId = $this->request->getPost('id');
        } else {
            $userId = session()->get('user_id');
        }

        $data = $this->router_model->builder()
            ->select('*')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc');

        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('routers', 'delete')) {
            $datatables->addColumn('select', function ($row) {
                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        // $datatables->format('status', function (string $value) {
        $datatables->addColumn('status', function ($row) {
            $value = $row->status;
            return ($value === 'active')
                ? '<span class="ipb-pay-badge is-success">Active</span>'
                : '<span class="ipb-pay-badge is-danger">Disabled</span>';
        });

        // Fetch user/package info once
        $userModel = model('App\Models\User');
        $details = $userModel->where('id', $userId)->first();

        $count = $details->posPrinter ?? 0;

        $AdminPackage = model('App\Models\AdminPackage');
        $package = $AdminPackage->select('duration')
            ->where('id', $details->package_id ?? 0)
            ->first();

        $duration = $package['duration'] ?? 0;

        $datatables->addColumn('action', function ($row) use ($count, $duration) {

            $html = '<div class="ipb-row-actions">';
            $html .= '<a href="' . route_to('route.routers.details', $row->id) . '" class="ipb-row-btn tone-info" title="Details" data-toggle="tooltip"><i class="fa fa-eye" aria-hidden="true"></i><span class="sr-only">Details</span></a>';

            if (userHasPermission('routers', 'update')) {
                $html .= '<a href="' . route_to('route.routers.setup_expired_profile', $row->id) . '" class="ipb-row-btn tone-violet btn-setup-expired" title="Setup expired profile" data-toggle="tooltip"><i class="fa fa-clock" aria-hidden="true"></i><span class="sr-only">Expired</span></a>';
                $html .= '<a href="' . route_to('route.routers.setup_radius', $row->id) . '" class="ipb-row-btn tone-success btn-setup-radius" title="Setup RADIUS" data-toggle="tooltip"><i class="fa fa-broadcast-tower" aria-hidden="true"></i><span class="sr-only">RADIUS</span></a>';
                $html .= '<a href="' . route_to('route.routers.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Edit" data-toggle="tooltip"><i class="far fa-pen-to-square" aria-hidden="true"></i><span class="sr-only">Edit</span></a>';
            }

            // duration 0/empty = unlimited (Pay-As-You-Go and unmetered plans)
            if (userHasPermission('routers', 'sync') && ((int) $duration <= 0 || $count < $duration)) {
                $html .= '<a href="' . route_to('route.routers.sync', $row->id) . '" class="ipb-row-btn tone-slate" title="Sync users" data-toggle="tooltip"><i class="fa fa-rotate" aria-hidden="true"></i><span class="sr-only">Sync</span></a>';
            }

            $html .= '</div>';
            return $html;
        });

        $datatables->except(['id']);
        $datatables->asObject();
        $datatables->generate();
    }


    /**
     * Routers
     * @action: New Router View
     */
    public function new()
    {
        if (!userHasPermission('routers', 'create'))
            show_404();

        $data = [
            'title' => 'New Router',
        ];

        return view('routers/new', $data);
    }


    /**
     * Routers
     * @action: Create New Router
     */
    public function create()
    {
        $this->validate([
            // 'name' => [
            //     'rules' => 'required|is_unique[routers.name]',
            //     'errors' => [
            //         'required' => 'Enter a name for the router',
            //         'is_unique' => 'Another router with same name already exists',
            //     ]
            // ],
            // 'host' => [
            //     'rules' => 'required|is_unique[routers.host]',
            //     'errors' => [
            //         'required' => 'Enter mikrotik host name / ip address',
            //         'is_unique' => 'Another router with same host already exists',
            //     ]
            // ],
            // 'name' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Enter a name for the router',

            //     ]
            // ],
            'port' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter mikrotik api port number',
                ]
            ],
            'username' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter mikrotik username',
                ]
            ],
            'password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter mikrotik password',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select router status',
                ]
            ],
        ]);

        if ($this->validation->run()) {

            try {

                if (empty(getPostInput('hotspot_name') || empty(getPostInput('dns_name')))) {

                    $client = new Client([
                        'host' => getPostInput('host'),
                        'user' => getPostInput('username'),
                        'pass' => getPostInput('password'),
                        'port' => (int) getPostInput('port'),
                        'timeout' => 15,
                        'socket_timeout' => 15,
                    ]);

                    setRouterIdentity($client, getPostInput('name'));
                }
                $userId = session()->get('user_id');
                log_message('debug', 'System interface getRouterPassById  here:');
                $name = empty(getPostInput('name')) ? getPostInput('hotspot_name') : getPostInput('name');
                $data = [
                    'user_id' => $userId,
                    'name' => $name,
                    'host' => getPostInput('host'),
                    'username' => getPostInput('username'),
                    'password' => getPostInput('password'),
                    'port' => getPostInput('port'),
                    'status' => getPostInput('status'),
                    'hotspot_name' => getPostInput('hotspot_name'),
                    'dns_name' => getPostInput('dns_name'),
                    'currency' => getPostInput('currency'),

                ];

                $result = $this->router_model->insert($data, false);

                if ($result) {

                    return requestResponse('success', "Router added successfully", 200);
                }

                return requestResponse('error', "Something went wrong! Please try again", 500);
            } catch (\Exception $e) {

                return requestResponse('error', "Could not connect to Mikrotik router! Check the ip address, username, password and port number", 500);
            }
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Routers
     * @action: Router user sync
     */
    public function sync($id)
    {

        if (!userHasPermission('routers', 'sync'))
            show_404();


        $userId = session()->get('user_id');

        $userModel = model('App\Models\User');
        $udetails = $userModel->where(['id' => $userId])->first();
        $role = $udetails->role;
        log_message('debug', 'System response interfaces Data: ' . print_r($udetails, true));

        if ($role === 'admin') {
            log_message('debug', 'its sAdmin');
            if ($udetails->status === 'inactive' || $udetails->subscription_status === 'inactive' || $udetails->conn_status != 'conn') {
                log_message('debug', 'System response interfaces Data: ' . print_r($udetails, true));
                return view('routers/error', [
                    'title' => 'Mikrotik Error',
                    'error' => 'You are not allowed to sync users. Please check your subscription status and connection status',
                    'router_id' => $id,
                ]);
            }
        }



        $details = $this->router_model->find($id);
        log_message('debug', 'router_action details: ' . json_encode($details, true));

        if (empty($details)) {
            show_404();
        }

        // Page-load-performance audit (Axis1 #5): this used to open routerClient()
        // + pull the router's FULL PPPoE secret list synchronously before the page
        // could render, and replaced the whole page with an error view if the
        // router happened to be slow/offline at that instant. This page's whole
        // purpose is the live secret list, so there's no meaningful DB-cached
        // fallback to show — instead the shell (title, router row, areas,
        // packages — all plain DB reads) renders immediately with an empty
        // secrets list, and the live secrets load via get_router_sync_secrets()
        // after paint (same split as Customer::details()/get_mikrotik_info()).
        $area_model = model('App\Models\Area');
        $package_model = model('App\Models\Package');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $data = [
            'title' => 'Mikrotik Router Sync',
            'details' => $details,
            'secrets' => [],
            'areas' => $area_model->where('status', 'active')->where('user_id', $userId)->findAll(),
            'packages' => $package_model->where('status', 'active')->where('user_id', $userId)->findAll(),
            'mikrotik_pending' => true,
        ];

        return view('routers/sync', $data);
    }

    /**
     * Routers
     * @action: Live PPPoE secret list for the sync page, loaded via AJAX after
     * the shell has painted. Mirrors the JSON shape convention used by
     * Customer::get_mikrotik_info() (ok / offline / error on failure).
     */
    public function get_router_sync_secrets($id)
    {
        if (!userHasPermission('routers', 'sync'))
            show_404();

        $userId = session()->get('user_id');

        $userModel = model('App\Models\User');
        $udetails = $userModel->where(['id' => $userId])->first();
        $role = $udetails->role ?? null;

        if ($role === 'admin') {
            if ($udetails->status === 'inactive' || $udetails->subscription_status === 'inactive' || $udetails->conn_status != 'conn') {
                return $this->response->setStatusCode(403)->setJSON([
                    'ok' => false,
                    'offline' => false,
                    'error' => 'You are not allowed to sync users. Please check your subscription status and connection status',
                ]);
            }
        }

        $details = $this->router_model->find($id);
        if (empty($details)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'offline' => false, 'error' => 'Router not found']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $router_client = routerClient($details->id);

            if (!($router_client instanceof \RouterOS\Client)) {
                return $this->response->setJSON([
                    'ok' => false,
                    'offline' => true,
                    'error' => 'Unable to connect to router',
                    'secrets' => [],
                ]);
            }

            $secrets = getAllPPPoEUsers($router_client);

            // Mirror the view's existing "already synced" rule (getUserByPPPoEId):
            // tell the client which secret IDs are already imported so it can
            // skip rendering rows for them, exactly like the old server-rendered
            // table did.
            $syncedIds = [];
            foreach ($secrets as $secret) {
                $pppoeId = $secret['.id'] ?? null;
                if ($pppoeId !== null && !empty(getUserByPPPoEId($pppoeId, $details->id))) {
                    $syncedIds[] = $pppoeId;
                }
            }

            return $this->response->setJSON([
                'ok' => true,
                'offline' => false,
                'secrets' => $secrets,
                'synced_ids' => $syncedIds,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'get_router_sync_secrets router ' . $id . ': ' . $e->getMessage());
            return $this->response->setJSON([
                'ok' => false,
                'offline' => true,
                'error' => 'Router communication error',
                'secrets' => [],
            ]);
        }
    }

    /**
     * Routers
     * @action: Import users
     */
    public function import($id)
    {

        if (!userHasPermission('routers', 'sync'))
            show_404();

        $this->validate([
            'id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'PPPoE ID is required',
                ]
            ],
            'username' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Username is required',
                ]
            ],
            'password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Password is required',
                ]
            ],
            'name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Customer name is required',
                ]
            ],
            // 'address' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Address is required',
            //     ]
            // ],
            'package_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Package is required',
                ]
            ],
            'area_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Area is required',
                ]
            ],
            // 'mobile' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Mobile number is required',
            //     ]
            // ],
        ]);

        if ($this->validation->run()) {

            $pppoeId = getPostInput('id');
            $username = getPostInput('username');
            $password = getPostInput('password');
            $name = getPostInput('name');
            // $email = getPostInput('email');
            // $address = getPostInput('address');
            $package_id = getPostInput('package_id');
            $area_id = getPostInput('area_id');
            // $mobile = getPostInput('mobile');

            $count = count($pppoeId);

            $userId = session()->get('user_id');
            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $userId])->first();
            $created_by = $details->role;
            $data = [];

            for ($i = 0; $i < $count; $i++) {

                array_push($data, [
                    'package_id' => $package_id[$i],
                    'area_id' => $area_id[$i],
                    'router_id' => $id,
                    'name' => $name[$i],
                    'mobile' => $username[$i],
                    'email' => $username[$i] . '@gmail.com',
                    'code' => $password[$i],
                    'password' => password_hash($password[$i], PASSWORD_DEFAULT),
                    'address' => 'None',
                    'pppoe_id' => $pppoeId[$i],

                    'last_renewed' => date('Y-m-d H:i:s'),
                    'will_expire' => date('Y-m-d H:i:s'),

                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'auto_disconnect' => 'yes',
                    'role' => 'user',
                    'admin_id' => $userId,
                    'created_by' => $created_by,

                ]);
            }

            if (!empty($data)) {

                $result = $this->user_model->insertBatch($data, null, $count);

                if ($result) {

                    return requestResponse('success', 'All user data imported successfully', 200);
                }

                return requestResponse('error', 'Something went wrong! Please try again', 500);
            }

            return requestResponse('error', 'No data to import', 500);
        }

        return requestResponse('validation-error', array_values($this->validation->getErrors())[0], 400);
    }


    /**
     * Routers
     * @action: Delete Routers
     */
    public function delete()
    {
        if (!userHasPermission('routers', 'delete'))
            show_404();

        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            if (getSession('user_role') === 'super_admin') {
                $this->user_model
                    ->whereIn('router_id', $ids)
                    ->delete();
                $result = $this->router_model->whereIn('id', $ids)->delete();
            } else {
                $result = $this->router_model->whereIn('id', $ids)->set('status', 'inactive')->update();
            }

            if ($result) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected!", 400);
    }


    /**
     * Routers
     * @action: Router Details View
     */
    public function details($id)
    {

        if (!userHasPermission('routers', 'read'))
            show_404();

        $details = $this->router_model->find($id);

        if (empty($details)) {
            show_404();
        }

        // Page-load-performance audit (Axis1 #6): this used to open routerClient()
        // + getInterface()/getLogs() synchronously before the page could render,
        // and replaced the whole page with an error view if the router happened
        // to be slow/offline at that instant. This page's whole purpose is live
        // router interfaces/logs, so there's no meaningful DB-cached fallback —
        // instead the shell (title, router row) renders immediately with empty
        // interfaces/logs, and the live data loads via get_router_details_info()
        // after paint (same split as Customer::details()/get_mikrotik_info()).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $data = [
            'title' => $details->name . ' Router Details',
            'details' => $details,
            'interfaces' => [],
            'logs' => [],
            'mikrotik_pending' => true,
        ];

        return view('routers/details', $data);
    }

    /**
     * Routers
     * @action: Live interfaces/logs for the details page, loaded via AJAX
     * after the shell has painted. Mirrors the JSON shape convention used by
     * Customer::get_mikrotik_info() (ok / offline / error on failure).
     */
    public function get_router_details_info($id)
    {
        if (!userHasPermission('routers', 'read'))
            show_404();

        $details = $this->router_model->find($id);
        if (empty($details)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'offline' => false, 'error' => 'Router not found']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $router_client = routerClient($details->id);

            if (!($router_client instanceof \RouterOS\Client)) {
                return $this->response->setJSON([
                    'ok' => false,
                    'offline' => true,
                    'error' => 'Unable to connect to router',
                    'interfaces' => [],
                    'logs' => [],
                ]);
            }

            $interfaces = getInterface($router_client);
            $logs = getLogs($router_client);

            return $this->response->setJSON([
                'ok' => true,
                'offline' => false,
                'interfaces' => is_array($interfaces) ? $interfaces : [],
                'logs' => is_array($logs) ? $logs : [],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'get_router_details_info router ' . $id . ': ' . $e->getMessage());
            return $this->response->setJSON([
                'ok' => false,
                'offline' => true,
                'error' => 'Router communication error',
                'interfaces' => [],
                'logs' => [],
            ]);
        }
    }


    /**
     * Routers
     * @action: Edit Router View
     */
    public function edit($id)
    {
        if (!userHasPermission('routers', 'update'))
            show_404();

        $details = $this->router_model->find($id);

        if (!empty($details)) {

            $data = [
                'title' => 'Update Router',
                'details' => $details,
            ];

            return view('routers/edit', $data);
        }

        show_404();
    }


    /**
     * Routers
     * @action: Update Router
     */
    public function update($id)
    {

        if (!userHasPermission('routers', 'update'))
            show_404();

        $this->validate([
            // 'name' => [
            //     'rules' => 'required|is_unique[routers.name, id, ' . $id . ']',
            //     'errors' => [
            //         'required' => 'Enter a name for the router',
            //         'is_unique' => 'Another router with same name already exists',
            //     ]
            // ],
            // 'host' => [
            //     'rules' => 'required|is_unique[routers.host, id, ' . $id . ']',
            //     'errors' => [
            //         'required' => 'Enter mikrotik host name / ip address',
            //         'is_unique' => 'Another router with same host already exists',
            //     ]
            // ],
            'port' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter mikrotik api port number',
                ]
            ],
            'username' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter mikrotik username',
                ]
            ],
            'password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter mikrotik password',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select router status',
                ]
            ],
        ]);

        if ($this->validation->run()) {

            try {

                if (empty(getPostInput('hotspot_name') || empty(getPostInput('dns_name')))) {

                    $client = new Client([
                        'host' => getPostInput('host'),
                        'user' => getPostInput('username'),
                        'pass' => getPostInput('password'),
                        'port' => (int) getPostInput('port'),
                        'timeout' => 5,
                        'socket_timeout' => 5,
                    ]);

                    setRouterIdentity($client, getPostInput('name'));
                }
                // $userId = session()->get('user_id');
                log_message('debug', 'System interface getRouterPassById  here:');
                $name = empty(getPostInput('name')) ? getPostInput('hotspot_name') : getPostInput('name');
                $data = [
                    // 'user_id' => $userId,
                    'name' => $name,
                    'host' => getPostInput('host'),
                    'username' => getPostInput('username'),
                    'password' => getPostInput('password'),
                    'port' => getPostInput('port'),
                    'status' => getPostInput('status'),
                    'hotspot_name' => getPostInput('hotspot_name'),
                    'dns_name' => getPostInput('dns_name'),
                    'currency' => getPostInput('currency'),

                ];

                $result = $this->router_model->update($id, $data);

                //  $result = $this->router_model
                //                ->set('id', $id)
                //                ->where('id', $mid)
                //                ->update();
                if ($result) {

                    return requestResponse('success', "Router updated successfully", 200);
                }

                return requestResponse('error', "Something went wrong! Please try again", 500);
            } catch (\Exception $e) {

                return requestResponse('error', "Could not connect to Mikrotik router! Check the ip address, username, password and port number", 500);
            }
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Routers
     * @action: Load Router Trafic
     */
    // public function loadTraffic($id)
    // {
    //     // log_message('debug', 'System interface its here load: ');

    //     if (!userHasPermission('routers', 'read'))
    //         show_404();

    //     self::$router_client = routerClient($id);

    //     $userId = session()->get('user_id');
    //     $details = $this->user_model
    //         ->select('pppoe_id')
    //         ->where(['admin_id' => $userId])
    //         ->findAll();

    //     log_message('debug', 'Customers: ' . print_r($details, true));


    //     if (!is_array(self::$router_client)) {

    //         while (true) {

    //             //$interface = getGetInput('interface') ?? null;
    //             $interface = getGetInput('interface') ?? null;
    //             $data = getSystemResources(self::$router_client, $interface);
    //             // $dnsData = getDnsCacheData(self::$router_client);
    //             // Log the data
    //             log_message('debug', 'System response interfaces Data: ' . print_r($data, true));

    //             return requestResponse('success', $data, 200);
    //         }
    //     }
    //     log_message('debug', 'System interface my Data: loadTraffic error');
    //     //return requestResponse('error', self::$router_client['error'], 500);
    // }

    public function loadTraffic($id)
    {
        log_message('debug', 'System interface its here load: ' . $id);

        $interface = getGetInput('interface') ?? '';
        $userRole = $this->request->getGet('user_role') ?? session()->get('user_role');
        $userId   = $this->request->getGet('user_id')   ?? session()->get('user_id');

        // Release the session lock early. This read-only AJAX endpoint is polled
        // every ~3s; with file-based sessions the exclusive lock would otherwise
        // serialize ALL of this user's parallel in-flight requests. Session is only
        // read above this line and never written below it. (Phase 2 / T3)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $cacheKey = 'traffic_router_' . $id . '_' . md5((string)$interface . (string)$userRole . (string)$userId);

        if ($cachedData = cache($cacheKey)) {
            return requestResponse('success', $cachedData, 200);
        }

        // Kill-switch honor-point (Phase 6): when the operator sheds load
        // (degrade_mode) or turns off live router widgets, skip the live MikroTik
        // I/O and return a shape-compatible empty payload so this 3s poll renders
        // zeros instead of hammering the router. Fail-safe: live_router_widgets
        // defaults TRUE, so default operation is unchanged. (routerClient() itself
        // is NOT gated — it's the shared provisioning/control client; the router
        // load-shed belongs here, at the live-widget caller.)
        helper('flag');
        if (flag('degrade_mode') || ! flag('live_router_widgets', true)) {
            // Shape-COMPLETE empty payload: it mirrors getSystemResources() key-for-key
            // (clock/resource/traffic/active/users/activeusers/allusers) so every
            // unconditional frontend deref (e.g. routers/details.php reads
            // result.data.resource['cpu-load'], .clock.date, .traffic.rxbyte) resolves
            // to a placeholder instead of throwing. traffic is an OBJECT (not []) to
            // match the live shape.
            return requestResponse('success', ['data' => [
                'clock'       => ['date' => '--', 'time' => '--'],
                'resource'    => [
                    'up-time' => '--', 'free-memory' => '--', 'total-memory' => '--',
                    'free-hdd-space' => '--', 'total-hdd-space' => '--', 'cpu-load' => 0,
                    'version' => '--', 'board-name' => '--', 'cpu' => '--', 'cpu-count' => '1',
                    'architecture-name' => '--', 'serial-number' => '--', 'firmware' => '--',
                    'software-id' => '--', 'cpu-frequency' => '--', 'build-time' => '--',
                ],
                'active'      => 0,
                'users'       => 0,
                'activeusers' => [],
                'allusers'    => [],
                'traffic'     => ['rxbyte' => 0, 'txbyte' => 0, 'date' => gmdate(DATE_ISO8601)],
                'degraded'    => true,
            ]], 200);
        }

        self::$router_client = RouterService::getClient($id);

        log_message('debug', 'User ID: ' . $userId);
        log_message('debug', 'User Role: ' . $userRole);

        $pppoeIds = [];

        // Only get pppoe_ids if not sAdmin
        if ($userRole !== 'admin') {
            $details = $this->user_model
                ->select('pppoe_id')
                ->where(['admin_id' => $userId])
                ->findAll();

            // Extract and trim pppoe_id values
            $pppoeIds = array_filter(array_map(function ($item) {
                return trim((string) ($item->pppoe_id ?? ''));
            }, $details));
        }

        if (!is_array(self::$router_client)) {
            $interface = getGetInput('interface') ?? null;
            
            // If interface is specified, use it. Otherwise try to find a sensible default.
            if (empty($interface) || $interface === 'WAN') {
                $interfaces = getInterface(self::$router_client);
                if (is_array($interfaces)) {
                    // Try to find WAN-like interface
                    foreach ($interfaces as $iface) {
                        if (stripos($iface['name'], 'wan') !== false || stripos($iface['name'], 'sfp') !== false || stripos($iface['name'], 'ether1') !== false) {
                            $interface = $iface['name'];
                            break;
                        }
                    }
                    // Fallback to first interface if still empty/WAN and no match
                    if ((empty($interface) || $interface === 'WAN') && !empty($interfaces[0]['name'])) {
                        $interface = $interfaces[0]['name'];
                    }
                }
            }

            $data = getSystemResources(self::$router_client, $interface);
            if (!empty($data['data']['traffic'])) {
                $data['data']['active_interface'] = $interface;
            }

            if (
                isset($data['data']['allusers']) &&
                is_array($data['data']['allusers']) &&
                $userRole !== 'admin'
            ) {
                $filteredUsers = [];
                $filteredActiveUsers = [];
                $validNames = [];

                // Filter all users and collect their names
                foreach ($data['data']['allusers'] as $user) {
                    $uId = trim((string) ($user['.id'] ?? ''));
                    $userName = trim((string) ($user['name'] ?? ''));

                    if (in_array($uId, $pppoeIds, true)) {
                        $filteredUsers[] = $user;

                        // Collect valid names to match active users
                        if ($userName !== '') {
                            $validNames[] = $userName;
                        }
                    }
                }

                // Filter active users by name
                if (isset($data['data']['activeusers']) && is_array($data['data']['activeusers'])) {
                    foreach ($data['data']['activeusers'] as $activeUser) {
                        $activeName = trim((string) ($activeUser['name'] ?? ''));

                        if (in_array($activeName, $validNames, true)) {
                            $filteredActiveUsers[] = $activeUser;
                        }
                    }
                }

                // Replace data with filtered
                $data['data']['allusers'] = $filteredUsers;
                $data['data']['activeusers'] = $filteredActiveUsers;

                if ($userRole !== 'admin') {
                    // Count them
                    $data['data']['users']    = count($filteredUsers);
                    $data['data']['active'] = count($filteredActiveUsers);
                }
            }

            if (!empty($data)) {
                cache()->save($cacheKey, $data, 5);
                
                // Save long-lived stats summary cache (per-user so each admin/reseller has their own)
                $totalUsers = $data['data']['users'] ?? 0;
                $activeUsers = $data['data']['active'] ?? 0;
                $statsCacheKey = 'router_stats_summary_' . $id . '_user_' . $userId;
                cache()->save($statsCacheKey, [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'status' => 'online',
                    'last_updated' => date('h:i:s A')
                ], 86400 * 30); // 30 days cache
            }

            return requestResponse('success', $data, 200);
        }

        log_message('error', 'RouterOS client is not initialized.');
        $errorMsg = is_array(self::$router_client) ? (self::$router_client['error'] ?? 'Connection failed') : 'Connection failed';
        
        // Retrieve last known statistics from per-user cache
        $statsCacheKey = 'router_stats_summary_' . $id . '_user_' . $userId;
        $cached = cache($statsCacheKey);
        // Fallback: try the legacy shared cache key for backward compatibility
        if (!$cached) {
            $cached = cache("router_stats_summary_" . $id);
        }
        $totalUsers = $cached['total'] ?? 0;
        $activeUsers = $cached['active'] ?? 0;
        
        // Update cache to show error status but keep the last known counts
        cache()->save($statsCacheKey, [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'status' => 'error',
            'last_updated' => date('h:i:s A')
        ], 86400 * 30);

        return $this->response->setStatusCode(503)
            ->setJSON([
                'status'   => 'error',
                'response' => [
                    'data' => [
                        'users'  => $totalUsers,
                        'active' => $activeUsers,
                    ]
                ],
                'message'  => $errorMsg,
            ]);
    }

    public function UsersloadTraffic_api($id)
    {
        self::$router_client = RouterService::getClient($id);

        if (!is_array(self::$router_client)) {

            while (true) {

                $interface = '';
                $pppoeName = $this->request->getGet('pppoe_name');

                $data = getusersSystemResources(self::$router_client, $pppoeName, $interface);

                $data = [
                    "data" => [

                        "traffic" => [
                            $data['data']['traffic'] ?? [] // traffic data 
                        ]
                    ]
                ];

                return requestResponse('success', $data, 200);
            }
        }

        log_message('debug', 'System interface my Data: loadTraffic error');
    }



    public function UsersloadTraffic($id)
    {
        $pppoeName = $this->request->getGet('pppoe_name');
        $cacheKey = 'traffic_user_' . $id . '_' . md5((string)$pppoeName);

        if ($cachedData = cache($cacheKey)) {
            return requestResponse('success', $cachedData, 200);
        }

        self::$router_client = RouterService::getClient($id);

        if (!is_array(self::$router_client)) {
            $interface = '';
            $data = getusersSystemResources(self::$router_client, $pppoeName, $interface);

            // Cache the result for 5 seconds to reduce router load
            if (!empty($data)) {
                cache()->save($cacheKey, $data, 5);
            }

            return requestResponse('success', $data, 200);
        }
        
        log_message('debug', 'System interface my Data: loadTraffic error');
        return requestResponse('error', 'Connection failed', 500);
    }



    public function getRouterPassById()
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        $model = new UserRouterDataModel();
        $user_model = new User();

        $builder = $model->builder();

        $builder->select('users.*, user_router_data.pppoe_secret, user_router_data.router_password');
        $builder->join('users', 'users.id = user_router_data.user_id');
        $builder->where('users.admin_id', $userId);

        $query = $builder->get();

        $rows = $query->getResultArray();
        if ($userRole !== 'admin') {
            // Filter rows for non-sAdmin users
            $details = $user_model->where(['id' => $userId])->first();
            $router_id = $details->router_id ?? null;

            $router_client = routerClient($router_id);

            if (!is_object($router_client)) {
                log_message('error', 'Failed to get router client: ');
                return;  // or handle error properly
            }
        }




        $updateData = [];

        foreach ($rows as $row) {
            if ($userRole === 'admin') {
                // Filter rows for non-sAdmin users
                $router_id = $row['router_id'] ?? null;
                $router_client = routerClient($router_id);
                log_message('debug', 'Router client for sAdmin: ' . print_r($router_client, true));
            }
            // log_message('debug', 'Processing user_router_data row: ' . json_encode($row, true));
            $ppp_name = $row['pppoe_secret'] ?? null;
            if (!$ppp_name) {
                log_message('warning', "Missing PPPoE name for user_router_data id: " . ($row['id'] ?? 'unknown'));
                continue;
            }

            $pppoeUser = getPPPoEUserByName($router_client, $ppp_name);
            log_message('debug', "PPPoE user fetched for ppp_name={$ppp_name}: " . print_r($pppoeUser, true));

            if ($pppoeUser && isset($pppoeUser[0]['.id'])) {
                $pppoe_id = $pppoeUser[0]['.id'];
                // log_message('info', "PPPoE user found for ppp_name={$ppp_name}: id = " . $pppoe_id);

                // Update user table where id = $row['id'] with pppoe_id
                $updateData = ['pppoe_id' => $pppoe_id];
                $userIdToUpdate = $row['id'];

                $updated = $user_model->update($userIdToUpdate, $updateData);
                if ($updated) {
                    log_message('info', "Updated user ID {$userIdToUpdate} with PPPoE ID {$pppoe_id}");
                } else {
                    log_message('error', "Failed to update user ID {$userIdToUpdate} with PPPoE ID {$pppoe_id}");
                }
            } else {
                log_message('warning', "PPPoE user NOT found for ppp_name={$ppp_name}");
            }
        }
        return requestResponse('success', "Router clients pppoe_id updated successfully", 200);
    }


    //macbinding

    public function saveBinding()
    {
        $request = service('request');

        // Input Data from POST
        $adminId  = $request->getPost('admin_id');
        $userName = $request->getPost('user_name');
        $mac      = $request->getPost('mac_address');
        $ip       = $request->getPost('ip_address') ?? null;
        $type     = $request->getPost('binding_type') ?? 'regular';
        $routerId = $request->getPost('router_id') ?? null;

        if (!$adminId || !$userName || !$mac) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Admin ID, User Name, and MAC are required'
            ]);
        }

        // 1️⃣ Save to Database
        $bindingModel = new UserBindingModel();
        $saved = $bindingModel->saveBinding($adminId, $userName, $mac, $ip, $type);

        if (!$saved) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Failed to save binding in database'
            ]);
        }

        // 2️⃣ Push Binding to Router using RouterOS PHP Client
        if ($routerId) {
            $client = routerClient($routerId); // RouterOS\Client instance
            if ($client) {
                try {
                    $query = new Query('/ip/hotspot/ip-binding/add');
                    $query->equal('mac-address', $mac)
                        ->equal('type', $type);

                    if ($ip) {
                        $query->equal('address', $ip);
                    }

                    $client->query($query)->read();
                } catch (\Exception $e) {
                    return $this->response->setJSON([
                        'status' => false,
                        'message' => 'Saved in DB, but failed to push binding to router: ' . $e->getMessage()
                    ]);
                }
            }
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'MAC Binding saved successfully'
        ]);
    }
}
