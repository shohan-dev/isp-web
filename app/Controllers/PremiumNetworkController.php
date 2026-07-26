<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OltModel;
use App\Models\OltSynchronizedOnuModel;
use App\Models\User;
use App\Models\UserBindingModel;
use App\Models\Area;

class PremiumNetworkController extends BaseController
{
    /**
     * @var \CodeIgniter\HTTP\ResponseInterface
     */
    protected $response;

    protected $oltModel;
    protected $syncModel;
    protected $userModel;

    public function __construct()
    {
        $this->oltModel = new OltModel();
        $this->syncModel = new OltSynchronizedOnuModel();
        $this->userModel = new User();
    }

    public function premiumDiagram()
    {
        if (!userHasPermission('network', 'read') && !(function_exists('isTenantAdminRole') && isTenantAdminRole())) {
            show_404();
        }

        $userId = session()->get('user_id');
        $olts = $this->oltModel->where('user_id', $userId)->findAll();
        
        // If sAdmin or admin, get all or fallback
        if (empty($olts)) {
            $olts = $this->oltModel->findAll();
        }

        $areaModel = new Area();
        $areas = $areaModel->where('status', 'active')->where('user_id', $userId)->findAll();

        $data = [
            'title' => 'Premium Network Diagram',
            'olts'  => $olts,
            'areas' => $areas,
        ];

        return view('network/diagram_premium', $data);
    }

    public function sync($oltId)
    {
        if (!userHasPermission('network', 'update')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Permission denied.']);
        }
        session_write_close();

        $olt = $this->oltModel->find($oltId);
        if (!$olt) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'OLT not found.']);
        }

        $output = $this->runPython($olt, 'status');
        
        // Log the raw OLT output for debugging
        log_message('info', "OLT Raw Output for OLT ID {$oltId}: " . substr($output, 0, 5000));

        // Extract JSON portion from output in case of warning/debug messages
        if (preg_match('/\{.*\}/s', $output, $matches)) {
            $output = $matches[0];
        }
        $isJson = is_string($output) && is_array(json_decode($output, true)) && (json_last_error() == JSON_ERROR_NONE);

        if (!$isJson) {
            log_message('error', 'PremiumNetworkController sync failed: OLT output is not valid JSON. Raw output: ' . substr($output, 0, 500));
            return $this->response->setJSON([
                'status'  => 'error', 
                'message' => 'Failed to connect to OLT or retrieve data. Please check connection settings.'
            ]);
        }

        $resultData = json_decode($output, true);

        if (!isset($resultData['onu_id']) || !is_array($resultData['onu_id'])) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'OLT response format is invalid: missing onu_id list.'
            ]);
        }

        // Prevent purging cache if OLT returned connection error
        if (isset($resultData['onu_id'][0]) && $resultData['onu_id'][0] === 'ERROR') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to connect to OLT: ' . ($resultData['des'][0] ?? 'Connection Error') . '. Showing cached data.'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Remove old cache for this OLT
        $this->syncModel->where('olt_id', $oltId)->delete();

        $count = count($resultData['onu_id']);
        for ($i = 0; $i < $count; $i++) {
            $onuId = $resultData['onu_id'][$i];
            if (empty($onuId) || $onuId === 'ERROR') {
                continue;
            }

            // Parse PON Port and ONU Index from various OLT brands:
            // Standard: "EPON0/1:2"  → ponPort="EPON0/1", onuIndex="2"
            // Corelink:  "ONU_1/1"   → ponPort="EPON0/1",  onuIndex="1"
            // Corelink:  "Corelink:1/1" → ponPort="EPON0/1", onuIndex="1"
            if (str_starts_with($onuId, 'ONU_')) {
                // Format: ONU_port/onuindex  e.g. ONU_1/1
                $inner = substr($onuId, 4); // "1/1"
                $slashParts = explode('/', $inner);
                $ponPort = 'EPON0/' . ($slashParts[0] ?? '0');
                $onuIndex = $slashParts[1] ?? '0';
            } elseif (str_starts_with($onuId, 'Corelink:')) {
                // Format: Corelink:port/onuindex  e.g. Corelink:1/1
                $inner = substr($onuId, 9); // "1/1"
                $slashParts = explode('/', $inner);
                $ponPort = 'EPON0/' . ($slashParts[0] ?? '0');
                $onuIndex = $slashParts[1] ?? '0';
            } else {
                // Standard format: GPON0/1:2 or EPON0/1:2
                $parts = explode(':', $onuId);
                $ponPort = $parts[0] ?? 'Unknown';
                $onuIndex = $parts[1] ?? '0';
            }

            $mac = isset($resultData['mac'][$i]) ? $resultData['mac'][$i] : (isset($resultData['router_mac'][$i]) ? $resultData['router_mac'][$i] : 'Unknown');
            $status = isset($resultData['status'][$i]) ? $resultData['status'][$i] : 'Unknown';
            $rx = isset($resultData['rx'][$i]) ? $resultData['rx'][$i] : (isset($resultData['rx_power'][$i]) ? $resultData['rx_power'][$i] : 'Unknown');
            $distance = isset($resultData['distance'][$i]) ? $resultData['distance'][$i] : (isset($resultData['distances'][$i]) ? $resultData['distances'][$i] : 0);
            $reason = isset($resultData['reason'][$i]) ? $resultData['reason'][$i] : '';
            $desc = isset($resultData['des'][$i]) ? $resultData['des'][$i] : (isset($resultData['name'][$i]) ? $resultData['name'][$i] : (isset($resultData['description'][$i]) ? $resultData['description'][$i] : ''));
            $voltage  = isset($resultData['voltage'][$i])  ? $resultData['voltage'][$i]  : (isset($resultData['volts'][$i])       ? $resultData['volts'][$i]       : null);
            $temp     = isset($resultData['temp'][$i])     ? $resultData['temp'][$i]     : (isset($resultData['temperature'][$i]) ? $resultData['temperature'][$i] : null);
            $bias     = isset($resultData['bias'][$i])     ? $resultData['bias'][$i]     : null;
            $txPower  = isset($resultData['tx_power'][$i]) ? $resultData['tx_power'][$i] : null;
            $vendor   = isset($resultData['vendor'][$i])   ? $resultData['vendor'][$i]   : null;

            // Try to match customer details using MAC address
            $customerName = '';
            $companyName = '';
            $customerAddress = '';
            $customerMobile = '';
            $pppoeId = '';
            if ($mac !== 'Unknown') {
                $customer = $this->lookupCustomerByMac($mac);
                if ($customer) {
                    $customerName = $customer->name;
                    $customerAddress = $customer->address ?? '';
                    $customerMobile = $customer->mobile ?? '';
                    $pppoeId = $customer->pppoe_id ?? '';

                    // Get Reseller's organization name if they belong to a reseller/admin
                    if (!empty($customer->admin_id)) {
                        $org = getOrgById($customer->admin_id);
                        if ($org && !empty($org['organization_name'])) {
                            $companyName = $org['organization_name'];
                        }
                    }
                }
            }

            $description = !empty($desc) ? $desc : (!empty($customerName) ? $customerName : '');

            // Maintain splitter name if we had it before (by matching MAC address)
            $existingCache = $db->table('olt_synchronized_onus')
                ->where('olt_id', $oltId)
                ->where('mac_address', $mac)
                ->get()
                ->getRowArray();
            $splitterName = $existingCache['splitter_name'] ?? null;

            $insertData = [
                'olt_id'        => $oltId,
                'pon_port'      => $ponPort,
                'onu_index'     => $onuIndex,
                'mac_address'   => $mac,
                'status'        => $status,
                'rx_power'      => $rx,
                'distance'      => $distance,
                'description'   => $description,
                'splitter_name' => $splitterName,
                'customer_name' => $customerName ?: null,
                'company_name'  => $companyName ?: null,
                'address'       => $customerAddress ?: null,
                'mobile'        => $customerMobile ?: null,
                'pppoe_id'      => $pppoeId ?: null,
                'voltage'       => $voltage  ?: null,
                'temp'          => $temp     ?: null,
                'bias'          => $bias     ?: null,
                'tx_power'      => $txPower  ?: null,
                'vendor'        => $vendor   ?: null,
            ];

            $this->syncModel->insert($insertData);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Database transaction failed.']);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Successfully synchronized {$count} ONUs locally."
        ]);
    }

    public function getTopology($oltId)
    {
        session_write_close();
        $olt = $this->oltModel->find($oltId);
        if (!$olt) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'OLT not found.']);
        }

        $onus = $this->syncModel->where('olt_id', $oltId)->findAll();

        $ponPortFilter = $this->request->getGet('pon_port');
        $zoneFilter = $this->request->getGet('zone');
        $search = $this->request->getGet('search');

        // Stats calculation
        $totalOnus = count($onus);
        $onlineCount = 0;
        $offlineCount = 0;

        foreach ($onus as $o) {
            if (strtolower($o['status'] ?? '') === 'online') {
                $onlineCount++;
            } else {
                $offlineCount++;
            }
        }

        // Apply filters
        $filteredOnus = [];
        foreach ($onus as $o) {
            // Filter by PON Port
            if (!empty($ponPortFilter) && $ponPortFilter !== 'All' && ($o['pon_port'] ?? '') !== $ponPortFilter) {
                continue;
            }

            // Match customer for advanced filters (Zone, Search)
            $customer = $this->lookupCustomerByMac($o['mac_address'] ?? '');

            // Filter by Zone (Area)
            if (!empty($zoneFilter) && $zoneFilter !== 'All') {
                if (!$customer || ($customer->area_id ?? '') != $zoneFilter) {
                    continue;
                }
            }

            // Filter by Search (MAC, Description, Customer Name, PON Port, Company Name, Address, PPPoE ID, Mobile)
            if (!empty($search)) {
                $searchTerm = strtolower($search);
                $macMatch = str_contains(strtolower($o['mac_address'] ?? ''), $searchTerm);
                $descMatch = str_contains(strtolower($o['description'] ?? ''), $searchTerm);
                $ponMatch = str_contains(strtolower($o['pon_port'] ?? ''), $searchTerm);
                $custMatch = ($customer && !empty($customer->name)) ? str_contains(strtolower($customer->name), $searchTerm) : false;
                $custNameMatch = !empty($o['customer_name']) && str_contains(strtolower($o['customer_name']), $searchTerm);
                $compMatch = !empty($o['company_name']) && str_contains(strtolower($o['company_name']), $searchTerm);
                $addrMatch = !empty($o['address']) && str_contains(strtolower($o['address']), $searchTerm);
                $pppoeMatch = !empty($o['pppoe_id']) && str_contains(strtolower($o['pppoe_id']), $searchTerm);
                $mobMatch = !empty($o['mobile']) && str_contains(strtolower($o['mobile']), $searchTerm);

                if (!$macMatch && !$descMatch && !$ponMatch && !$custMatch && !$custNameMatch && !$compMatch && !$addrMatch && !$pppoeMatch && !$mobMatch) {
                    continue;
                }
            }

            // If splitter_name is not set but they want auto-generated splitters, let's group them or read them
            if (empty($o['splitter_name'])) {
                // Auto assign splitter by grouping every 8 ONUs or based on description
                $o['splitter_name'] = $this->autoAssignSplitter($o);
            }

            $filteredOnus[] = $o;
        }

        // Structure the hierarchical data for tree rendering:
        // OLT -> PON Ports -> Splitters -> ONUs/Users
        $ponPortsData = [];
        foreach ($filteredOnus as $onu) {
            $port = $onu['pon_port'] ?? 'Unknown';
            if (!isset($ponPortsData[$port])) {
                $ponPortsData[$port] = [
                    'name'     => $port,
                    'capacity' => 64, // GPON standard default capacity
                    'total'    => 0,
                    'online'   => 0,
                    'offline'  => 0,
                    'splitters'=> []
                ];
            }

            $splitter = $onu['splitter_name'] ?? 'Direct Connections';
            if (!isset($ponPortsData[$port]['splitters'][$splitter])) {
                $ponPortsData[$port]['splitters'][$splitter] = [
                    'name'     => $splitter,
                    'distance' => $onu['distance'] ?? 0,
                    'total'    => 0,
                    'online'   => 0,
                    'offline'  => 0,
                    'users'    => []
                ];
            }

            // Append user
            $userNode = [
                'id'            => $onu['id'],
                'onu_index'     => $onu['onu_index'] ?? '',
                'mac'           => $onu['mac_address'] ?? '',
                'label'         => !empty($onu['description']) ? $onu['description'] : ($onu['mac_address'] ?? ''),
                'status'        => $onu['status'] ?? 'Offline',
                'rx_power'      => $onu['rx_power'] ?? '',
                'distance'      => $onu['distance'] ?? '',
                'reason'        => $onu['reason'] ?? '',
                'customer_name' => $onu['customer_name'] ?? '',
                'company_name'  => $onu['company_name'] ?? '',
                'address'       => $onu['address'] ?? '',
                'mobile'        => $onu['mobile'] ?? '',
                'pppoe_id'      => $onu['pppoe_id'] ?? '',
                'voltage'       => $onu['voltage']   ?? '',
                'temp'          => $onu['temp']      ?? '',
                'bias'          => $onu['bias']      ?? '',
                'tx_power'      => $onu['tx_power']  ?? '',
                'vendor'        => $onu['vendor']    ?? '',
            ];

            $ponPortsData[$port]['splitters'][$splitter]['users'][] = $userNode;
            $ponPortsData[$port]['splitters'][$splitter]['total']++;
            if (strtolower($onu['status'] ?? '') === 'online') {
                $ponPortsData[$port]['splitters'][$splitter]['online']++;
                $ponPortsData[$port]['online']++;
            } else {
                $ponPortsData[$port]['splitters'][$splitter]['offline']++;
                $ponPortsData[$port]['offline']++;
            }
            $ponPortsData[$port]['total']++;
        }

        // Sort PON ports and splitters keys nicely
        ksort($ponPortsData);
        foreach ($ponPortsData as &$pData) {
            ksort($pData['splitters']);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'stats'  => [
                'total_onus' => $totalOnus,
                'online'     => $onlineCount,
                'offline'    => $offlineCount,
            ],
            'olt' => [
                'name'      => $olt['olt_name'],
                'brand'     => $olt['brand'],
                'ip'        => $olt['ip'],
                'port'      => $olt['port'],
                'total_pon' => count($ponPortsData)
            ],
            'tree' => array_values($ponPortsData)
        ]);
    }

    public function getPonPorts($oltId)
    {
        session_write_close();
        $ports = $this->syncModel->select('pon_port')
            ->where('olt_id', $oltId)
            ->distinct()
            ->findAll();

        $portsList = array_map(function($p) {
            return $p['pon_port'];
        }, $ports);

        sort($portsList);

        return $this->response->setJSON([
            'status' => 'success',
            'ports'  => $portsList
        ]);
    }

    public function updateSplitter()
    {
        if (!userHasPermission('network', 'update')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Permission denied.']);
        }
        session_write_close();

        $onuId = $this->request->getPost('id');
        $splitterName = $this->request->getPost('splitter_name');

        if (empty($onuId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing ONU ID.']);
        }

        $updateData = ['splitter_name' => !empty($splitterName) ? $splitterName : null];
        $this->syncModel->update($onuId, $updateData);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Splitter assignment updated.']);
    }

    private function lookupCustomerByMac($mac)
    {
        if (empty($mac) || $mac === 'Unknown') return null;
        $upperMac = strtoupper(trim($mac));
        $noColons = str_replace(':', '', $upperMac);
        
        $bindingModel = new UserBindingModel();
        $binding = $bindingModel->whereIn('mac_address', [$upperMac, $noColons])->first();
        if ($binding) {
            $user = $this->userModel->where('name', $binding['user_name'])->first();
            if ($user) {
                return $user;
            }
        }
        return null;
    }

    private function autoAssignSplitter($onu)
    {
        // Simple auto group: e.g. Splitter-01 (1x8) for index 1-8, Splitter-02 (1x8) for index 9-16, etc.
        $index = intval(preg_replace('/[^0-9]/', '', $onu['onu_index']));
        if ($index <= 0) $index = 1;
        $group = ceil($index / 8);
        return "Splitter-" . str_pad($group, 2, '0', STR_PAD_LEFT) . " (1x8)";
    }

    private function runPython($olt, $argument)
    {
        $scriptName = '';
        switch (strtolower($olt['brand'])) {
            case 'avies':       $scriptName = 'avies_olt.py'; break;
            case 'bdcom':       $scriptName = 'bdcom_olt.py'; break;
            case 'corelink':    $scriptName = 'corelink_olt.py'; break;
            case 'atop':        $scriptName = 'atop_olt.py'; break;
            case 'dbc':         $scriptName = 'dbc_olt.py'; break;
            case 'c_data':      $scriptName = 'cdata_olt.py'; break;
            case 'ecom':        $scriptName = 'ecom_olt.py'; break;
            case 'v_sol':
            case 'vsol':        $scriptName = 'vsol_olt.py'; break;
            case 'hsgq':        $scriptName = 'hsgq_olt.py'; break;
            case 'tbs_pothon':   $scriptName = 'tbs_pothon_olt.py'; break;
            case 'fucascom':    $scriptName = 'fucascom_olt.py'; break;
            default:
                throw new \Exception("Unsupported OLT brand: " . $olt['brand']);
        }

        $ip = $olt['ip'];
        $port = $olt['port'];
        $user = $olt['username'];
        $pass = base64_decode($olt['password']);
        $loginKey = $olt['login_key'] ?? '';

        if (PHP_OS_FAMILY === 'Windows') {
            $pythonPath = "C:\\Program Files\\Python313\\python.exe";
            $scriptDir = ROOTPATH . 'app/Views/olt_brands';
            $command = "cd /d \"$scriptDir\" && \"$pythonPath\" $scriptName "
                . escapeshellarg($argument) . " "
                . escapeshellarg($ip) . " "
                . escapeshellarg($port) . " "
                . escapeshellarg($user) . " "
                . escapeshellarg($pass) . " "
                . escapeshellarg($loginKey);
        } else {
            $pythonPath = "/home/isppaybd/virtualenv/isppaybd.com/3.11/bin/python";
            $scriptDir = "/home/isppaybd/isp-core/app/Views/olt_brands";
            $command = "cd $scriptDir && $pythonPath $scriptName "
                . escapeshellarg($argument) . " "
                . escapeshellarg($ip) . " "
                . escapeshellarg($port) . " "
                . escapeshellarg($user) . " "
                . escapeshellarg($pass) . " "
                . escapeshellarg($loginKey);
        }

        try {
            set_time_limit(90);
            $output = shell_exec($command);
            if (is_null($output) || $output === false) {
                return json_encode(["error" => "Command timed out or failed to execute."]);
            }
            return $output;
        } catch (\Exception $e) {
            return json_encode(["error" => "Critical Failure: " . $e->getMessage()]);
        }
    }
}
