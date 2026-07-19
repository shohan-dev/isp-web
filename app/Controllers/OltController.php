<?php

namespace App\Controllers;

use App\Models\OltModel;
use App\Models\User;

class OltController extends BaseController
{
    protected $oltModel;
    protected $userModel;
    protected $olt;

    public function __construct()
    {
        $this->oltModel = new OltModel(); // auto table check
        $this->userModel = new User();
    }

    // Show all OLTs
    public function index()
    {
        //log_message('info', 'OLTController index accessed');

        $userId = session()->get('user_id');

        $olts = [];
        $currentUserId = $userId;
        $visited = [];

        while (!empty($currentUserId) && !in_array($currentUserId, $visited)) {
            $visited[] = $currentUserId;

            $olts = $this->oltModel->where('user_id', $currentUserId)->findAll();
            if (!empty($olts)) {
                break;
            }

            $userRecord = $this->userModel->find($currentUserId);
            if ($userRecord && $userRecord->role !== 'admin' && !empty($userRecord->admin_id)) {
                $currentUserId = $userRecord->admin_id;
            } else {
                break;
            }
        }

        $data['olts'] = $olts;

        return view('olt/index', $data);
    }

    // Save new OLT
    public function store()
    {
        //log_message('info', 'Storing new OLT: ' . print_r($this->request->getPost(), true));

        $userId = session()->get('user_id');


        $data = [
            'user_id' => $userId,
            'olt_name' => $this->request->getPost('olt_name'),
            'brand' => $this->request->getPost('brand'),
            'ip' => $this->request->getPost('ip'),
            'port' => $this->request->getPost('port'),
            'protocol' => $this->request->getPost('protocol'),
            'username' => $this->request->getPost('username'),
            'password' => base64_encode($this->request->getPost('password')),
            'login_key' => $this->request->getPost('login_key'),
            'status' => 1
        ];

        $this->oltModel->save($data);

        return redirect()->back()->with('success', 'OLT Router Added Successfully');
    }

    public function update()
    {
        $oltId = $this->request->getPost('olt_id');

        // 1. Prepare data
        $data = [
            'id' => $oltId, // Providing the ID tells the model to UPDATE
            'olt_name' => $this->request->getPost('olt_name'),
            'brand' => $this->request->getPost('brand'),
            'ip' => $this->request->getPost('ip'),
            'port' => $this->request->getPost('port'),
            'protocol' => $this->request->getPost('protocol'),
            'username' => $this->request->getPost('username'),
            'login_key' => $this->request->getPost('login_key'),
        ];

        // 2. Only update password if a new one was provided
        $newPassword = $this->request->getPost('password');
        if (!empty($newPassword)) {
            $data['password'] = base64_encode($newPassword);
        }

        // 3. Save (This handles the update because 'id' is present)
        if ($this->oltModel->save($data)) {
            return redirect()->back()->with('success', 'OLT Configuration Updated Successfully');
        } else {
            return redirect()->back()->with('error', 'Failed to update OLT');
        }
    }

    public function delete($id = null)
    {
        $model = new \App\Models\OltModel();

        // Check if the OLT exists first
        if ($model->find($id)) {
            $model->delete($id);

            // If it's an AJAX request
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'success', 'message' => 'OLT deleted']);
            }

            return redirect()->to('/olts')->with('success', 'OLT deleted successfully');
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Record not found'], 404);
    }

    // Assign OLT to user
    public function assignToUser()
    {
        $userId = $this->request->getPost('user_id');
        $oltId = $this->request->getPost('olt_id');

        $userModel = new User();
        $userModel->update($userId, ['olt_id' => $oltId]);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * MAIN: Run OLT command by user
     * Route: /olt/run/{userId}/{action}
     */
    public function run($userId, $action = 'status')
    {
        try {
            $result = $this->runOlt($userId, $action); // reuse internal method
            return $this->response->setJSON($result);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getOnuByMac($mac = null, $userId = null, $router_id = null)
    {
        $userId = $userId ?? session()->get('user_id');
        // log_message('info', "Searching ONU by MAC: $mac for User $userId");

        $targetMac = strtoupper($mac ?? "A2:8F:01:0C:26:30");

        $nonmatchedOnu = [
            'name' => 'Not Found',
            'onu_id' => 'Not Found',
            'mac' => 'Not Found',
            'status' => 'Not Found',
            'rx' => 'Not Found',
            'reason' => 'Not Found',
            'last_seen' => 'Not Found',
            'description' => 'Not Found',
            'olt_name' => 'Not Found'
        ];
        // $macAddresses = [
        //     "8C:DE:F9:39:68:76",
        //     "C8:3A:35:02:68:D0",
        //     "98:BA:5F:05:8B:F5"
        // ];

        log_message('info', "Searching ONU by MAC: $targetMac for User $userId");

        // 🔹 Resolve OLT ownership by traversing up the admin tree recursively
        $olts = [];
        $currentUserId = $userId;
        $visited = []; // Prevent infinite loop in case of circular reference

        while (!empty($currentUserId) && !in_array($currentUserId, $visited)) {
            $visited[] = $currentUserId;

            // Try to find OLTs for the current user
            if (!empty($router_id)) {
                $olt = $this->oltModel
                    ->where('user_id', $currentUserId)
                    ->where('id', $router_id)
                    ->first();
                $olts = $olt ? [$olt] : [];
            } else {
                $olts = $this->oltModel
                    ->where('user_id', $currentUserId)
                    ->findAll();
            }

            // If OLTs are found, we stop!
            if (!empty($olts)) {
                break;
            }

            // Otherwise, move up to the parent admin
            $userRecord = $this->userModel->find($currentUserId);
            if ($userRecord && $userRecord->role !== 'admin' && !empty($userRecord->admin_id)) {
                $currentUserId = $userRecord->admin_id;
            } else {
                break;
            }
        }

        if (empty($olts)) {
            log_message('info', "No OLTs found for User $userId (visited: " . implode(" -> ", $visited) . ")");
            return $nonmatchedOnu;
        }
        foreach ($olts as $olt) {
            try {
                log_message('info', "=== Start OLT Search for target MAC: $targetMac on OLT: {$olt['olt_name']} (Brand: {$olt['brand']}, ID: {$olt['id']}) ===");

                // 🔹 Step 1: Get MAC list
                $macResult = $this->runOlt($olt['id'], 'mac');
                if (!$macResult) {
                    log_message('warning', "⚠️ OLT {$olt['olt_name']} (ID: {$olt['id']}) runOlt returned empty/false result.");
                    continue;
                }

                $macData = json_decode($macResult['result'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    log_message('error', "❌ Failed to decode OLT MAC result JSON. Error: " . json_last_error_msg() . ". Raw result: " . substr($macResult['result'], 0, 500));
                    continue;
                }

                if (!isset($macData['router_mac']) || !is_array($macData['router_mac'])) {
                    log_message('warning', "⚠️ OLT {$olt['olt_name']} (ID: {$olt['id']}) MAC list does not contain 'router_mac' or is not an array.");
                    continue;
                }

                $normalizedTarget = strtoupper(preg_replace('/[^A-F0-9]/i', '', $targetMac));
                log_message('info', "Searching " . count($macData['router_mac']) . " MAC addresses for normalized target: $normalizedTarget");

                $matchedIndex = null;
                $bestMatchLevel = 0; // 0: No match, 1: First 6 match, 2: Full/Contains match

                foreach ($macData['router_mac'] as $index => $foundMac) {
                    $normalizedFound = strtoupper(preg_replace('/[^A-F0-9]/i', '', $foundMac));

                    // 1. Full or Contains match (Highest priority)
                    if ($normalizedFound === $normalizedTarget || (!empty($normalizedTarget) && str_contains($normalizedFound, $normalizedTarget))) {
                        $matchedIndex = $index;
                        $bestMatchLevel = 2;
                        break; // Exit loop immediately on best match
                    }
                }

                // ❌ MAC not found in this OLT
                if ($matchedIndex === null) {
                    log_message('info', "❌ Target MAC $targetMac ($normalizedTarget) not found in OLT {$olt['olt_name']}'s learned MAC table.");
                    continue;
                }

                $matchType = ($bestMatchLevel === 2) ? "Full/Contains" : "First 6 (OUI)";
                $foundOnuPort = $macData['onu_id'][$matchedIndex] ?? null;
                log_message('info', "✅ $matchType Match found in OLT {$olt['olt_name']} (ID: {$olt['id']}) at index $matchedIndex. Found MAC: {$macData['router_mac'][$matchedIndex]} on Port: $foundOnuPort");

                $index = $matchedIndex;

                // 🔹 Step 2: Get Status list
                $statusResult = $this->runOlt($olt['id'], 'status');
                if (!$statusResult) {
                    log_message('warning', "⚠️ OLT {$olt['olt_name']} (ID: {$olt['id']}) runOlt status returned empty/false result.");
                    continue;
                }

                $statusData = json_decode($statusResult['result'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    log_message('error', "❌ Failed to decode OLT status result JSON. Error: " . json_last_error_msg() . ". Raw: " . substr($statusResult['result'], 0, 500));
                    continue;
                }

                if (!isset($statusData['onu_id']) || !is_array($statusData['onu_id'])) {
                    log_message('warning', "⚠️ OLT {$olt['olt_name']} status list does not contain 'onu_id' or is not an array.");
                    continue;
                }

                if (!$foundOnuPort) {
                    log_message('warning', "⚠️ No ONU Port was resolved from MAC table for matched index $matchedIndex.");
                    continue;
                }

                // 🔹 Step 3: Find this Port/ONU in the Status List
                $statusIndex = array_search($foundOnuPort, $statusData['onu_id']);
                if ($statusIndex === false) {
                    log_message('warning', "⚠️ ONU Port $foundOnuPort found in MAC table but NOT in status list of OLT {$olt['olt_name']} (ID: {$olt['id']}). Status list ports: " . implode(', ', array_slice($statusData['onu_id'], 0, 10)) . "...");
                    continue;
                }

                $onu_id = $statusData['onu_id'][$statusIndex] ?? $foundOnuPort;
                $matchedIndex = $statusIndex; // Use status index for remaining data lookup

                // 🔹 Step 4: Get RX (only if needed)
                $rx = null;
                if (!empty($onu_id) && ($olt['brand'] == 'BDCOM' || $olt['brand'] == 'DBC' || strtolower($olt['brand']) == 'v_sol' || strtolower($olt['brand']) == 'vsol')) {
                    $rxResult = $this->runOlt($olt['id'], "rx:$onu_id");
                    $rxData = json_decode($rxResult['result'] ?? null, true);
                    $rx = $rxData['rx'] ?? ($rxResult['result'] ?? null);
                }

                // 🔹 Build final response
                $matchedOnu = [
                    'name' => $statusData['olt'][$matchedIndex] ?? $olt['olt_name'] ?? 'Unknown OLT',
                    'onu_id' => $onu_id,
                    'mac' => $statusData['mac'][$matchedIndex] ?? 'Unknown',
                    'call_id' => $targetMac ?? null,
                    'matched_id' => $macData['router_mac'][$index],
                    'status' => $statusData['status'][$matchedIndex] ?? 'Unknown',
                    'rx' => $rx
                        ?? $statusData['rx'][$matchedIndex]
                        ?? $statusData['rx_power'][$matchedIndex]
                        ?? 'Unknown',
                    'reason' => $statusData['reason'][$matchedIndex] ?? null,
                    'last_seen' => $statusData['last_deregister'][$matchedIndex]
                        ?? $statusData['last_seen'][$matchedIndex]
                        ?? $statusData['last_register'][$matchedIndex]
                        ?? null,
                    'description' => $statusData['des'][$matchedIndex] ?? null,
                    'olt_name' => $statusData['olt'][$matchedIndex] ?? $olt['olt_name'] ?? 'Unknown OLT'
                ];

                //log_message('info', "Matched ONU Details: " . print_r($matchedOnu, true));

                return $matchedOnu; // ✅ return immediately after match
            } catch (\Exception $e) {
                log_message('error', "Error searching OLT ID {$olt['id']}: " . $e->getMessage());
                continue; // 🔹 Move to next OLT if this one fails
            }
        }

        return $nonmatchedOnu;
    }



    private function runOlt($userId, $action = 'status')
    {
        //log_message('info', "Running OLT action '$action' for user ID: $userId");
        $this->loadUserOlt($userId); // load user’s assigned OLT id
        $output = $this->connect($action); // run Python script
        // log_message('info', "OLT Output: " . print_r($output, true));
        // 🔹 Check if output is valid JSON
        $isJson = is_string($output) && is_array(json_decode($output, true)) && (json_last_error() == JSON_ERROR_NONE);

        if ($isJson) {
            $resultData = json_decode($output, true);
            
            // Get MAC addresses from response
            if (isset($resultData['mac']) && is_array($resultData['mac'])) {
                $macs = $resultData['mac'];
            } elseif (isset($resultData['router_mac']) && is_array($resultData['router_mac'])) {
                $macs = $resultData['router_mac'];
            } else {
                $macs = [];
            }
            
            if (!empty($macs)) {
                $searchMacs = [];
                foreach ($macs as $m) {
                    if (empty($m)) continue;
                    $upper = strtoupper(trim($m));
                    $searchMacs[] = $upper;
                    
                    $noColons = str_replace(':', '', $upper);
                    $searchMacs[] = $noColons;
                }
                $searchMacs = array_unique(array_filter($searchMacs));
                
                if (!empty($searchMacs)) {
                    $bindingModel = new \App\Models\UserBindingModel();
                    $bindings = $bindingModel->whereIn('mac_address', $searchMacs)->findAll();
                    
                    $macToUserMap = [];
                    foreach ($bindings as $binding) {
                        $dbMac = strtoupper(trim($binding['mac_address']));
                        $dbMacNoColons = str_replace(':', '', $dbMac);
                        
                        $macToUserMap[$dbMac] = $binding['user_name'];
                        $macToUserMap[$dbMacNoColons] = $binding['user_name'];
                    }
                    $resultData['mac_to_user'] = $macToUserMap;
                }
            }
            $output = json_encode($resultData);
        }

        return [
            'status' => $isJson ? 'success' : 'error',
            'olt' => $this->olt['olt_name'] ?? 'Unknown',
            'result' => $output
        ];
    }


    private function loadUserOlt($userId)
    {
        // $userModel = new User();
        // $user = $userModel->find($userId);

        // if (!$user || empty($user['olt_id'])) {
        //     throw new \Exception("No OLT assigned to this user");
        // }

        $this->olt = $this->oltModel->find($userId);

        //log_message('info', "Loaded OLT for user $userId: " . print_r($this->olt, true));

        if (!$this->olt) {
            throw new \Exception("OLT not found in database");
        }
    }

    private function connect($action)
    {
        switch (strtolower($this->olt['brand'])) {
            case 'avies':
                return $this->runPython('avies_olt.py', $action);

            case 'bdcom':
                return $this->runPython('bdcom_olt.py', $action);

            case 'corelink':
                return $this->runPython('corelink_olt.py', $action);

            case 'atop':
                return $this->runPython('atop_olt.py', $action);

            case 'dbc':
                return $this->runPython('dbc_olt.py', $action);

            // New Cases Added Below
            case 'c_data':
                return $this->runPython('cdata_olt.py', $action);

            case 'ecom':
                return $this->runPython('ecom_olt.py', $action);

            case 'v_sol':
            case 'vsol':
                return $this->runPython('vsol_olt.py', $action);

            case 'hsgq':
                return $this->runPython('hsgq_olt.py', $action);

            case 'tbs_pothon':
                return $this->runPython('tbs_pothon_olt.py', $action);

            case 'fucascom':
                return $this->runPython('fucascom_olt.py', $action);

            default:
                throw new \Exception("Unsupported OLT brand: " . $this->olt['brand']);
        }
    }

    /**
     * 🐍 Runs Python script per OLT brand
     */
    private function runPython($scriptName, $argument)
    {
        // $pythonPath = "C:\\Program Files\\Python313\\python.exe";
        $ip = $this->olt['ip'];
        $port = $this->olt['port'];
        $user = $this->olt['username'];
        $pass = base64_decode($this->olt['password']);
        $loginKey = $this->olt['login_key'] ?? '';

        if (PHP_OS_FAMILY === 'Windows') {
            // $pythonPath = "F:\\Python\\python.exe";
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

        log_message('info', "Executing OLT Command [" . PHP_OS_FAMILY . "]: $command");

        try {
            // 1. Give PHP more time to breathe
            set_time_limit(90);

            $output = shell_exec($command);

            // 2. shell_exec returns NULL if it fails or produces no output
            if (is_null($output) || $output === false) {
                log_message('error', "OLT Script failed or timed out.");
                return json_encode(["error" => "Command timed out or failed to execute."]);
            }

            return $output;
        } catch (\Exception $e) {
            log_message('critical', $e->getMessage());
            return json_encode(["error" => "Critical Failure: " . $e->getMessage()]);
        }
    }
}
