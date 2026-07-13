<?php

/**
 * Router Helper File
 */

/**
 * Get Router Name by Id
 */


if (!function_exists('getAreaNameById')) {
	function getAreaNameById($row)
	{
		$id = is_object($row) ? $row->area_id : $row['area_id'];
		$user_id = is_object($row) ? $row->id : $row['id'];

		$model = model('App\Models\User');
		$details = $model->where('id', $user_id)->first();
		$admin_id = $details ? $details->admin_id : null;

		// log_message('debug', 'Fetching Area Name for ID: ' . print_r($details, true));

		if (!$admin_id) {
			log_message('error', 'Admin ID not found for user ID: ' . $id);
			return null;
		}



		$areaModel = model('App\Models\Area');

		$area = $areaModel->where('id', $id)
			->where('user_id', $admin_id)
			->first();

		// log_message('debug', 'Retrieved Area Details: ' . print_r($area, true));

		return $area ? $area->area_name : null;
	}
}

if (!function_exists('getRouterById')) {
	function getRouterById($id)
	{

		$routerModel = model('App\Models\Router');

		$router = $routerModel->find($id);

		return $router ?? null;
	}
}

if (!function_exists('getresellerById')) {
	function getresellerById($id)
	{

		$routerModel = model('App\Models\Registration');

		$router = $routerModel->where(['userid' => $id])->first();

		// log_message('debug', 'Retrieved getresellerById Credentials: ' . print_r($router, true));

		return $router ?? null;
	}
}

if (!function_exists('getRouterPassById')) {
	function getRouterPassById($id)
	{

		// log_message('debug', 'System interface getRouterPassById  here: ');


		$userModel = model('App\Models\User');
		$routerDataModel = model('App\Models\UserRouterDataModel');

		$details = $userModel->where(['id' => $id, 'role' => 'user'])->first();

		if (!$details) {
			return null;
		}

		// Check if data is already available in the database
		$routerData = $routerDataModel->where('user_id', $id)->first();

		// log_message('debug', 'Retrieved routerData details: ' . print_r($details, true));

		if ($routerData && (time() - strtotime($routerData->last_updated))) { // 1-hour cache
			// log_message('debug', 'System interface getRouterPassById strtotime here: ');

			return [
				'router_password' => $routerData->router_password,
				'pppoe_secret' => $routerData->pppoe_secret
			];
		}

		// Retrieve password and name dynamically
		$router_client = routerClient($details->router_id);

		if (!is_array($router_client)) {

			$pppoe = getPPPoEUserUserId($router_client, $details->id);
			$pppoe_id = $pppoe[0]['.id'] ?? $details->pppoe_id ?? null;

			// log_message('info', "PPPoE ID for User ID {$details->id}: {$pppoe_id}");




			$user_ppp = getPPPoEUser($router_client, $pppoe_id);
			$password = $user_ppp[0]['password'] ?? 'No password available';
			$pppoe_name = $user_ppp[0]['name'] ?? 'No name available';

			// log_message('debug', 'System user_ppp Resources Data: ' . print_r($user_ppp, true));

			// Update or insert router data in the database
			if ($routerData) {
				$routerDataModel->update($routerData->id, [
					'router_password' => $password,
					'pppoe_secret' => $pppoe_name,
					'last_updated' => date('Y-m-d H:i:s')
				]);
			} else {
				$routerDataModel->insert([
					'user_id' => $id,
					'router_id' => $details->router_id,
					'router_password' => $password,
					'pppoe_secret' => $pppoe_name,
					'last_updated' => date('Y-m-d H:i:s')
				]);
			}
			// log_message('debug', 'System user_ppp Resources Data: ' . print_r($password, true));

			return [
				'router_password' => $password,
				'pppoe_secret' => $pppoe_name,
			];
		}

		return 'No router client available';
	}
}





/**
 * Router clients count
 */
if (!function_exists('routerClientCount')) {
	function routerClientCount($id)
	{

		$userModel = model('App\Models\User');

		$count = $userModel->where(['router_id' => $id])->countAllResults();

		return (int) $count;
	}
}

function customer_data_usages()
{
	ini_set('max_execution_time', 0);
	set_time_limit(0);

	$user_model = model('App\Models\User');
	$usage_model = model('App\Models\UserDataUsageModel');
	$routerDataModel = model('App\Models\UserRouterDataModel');
	$date = date('Y-m-d');

	log_message('info', 'customer_data_usages: job started at ' . date('Y-m-d H:i:s'));

	// Resume from last user if already processed today
	$lastData = $usage_model->orderBy('id', 'DESC')->limit(1)->first();
	$startFromId = 1;

	if ($lastData && $lastData['date'] === $date) {
		$startFromId = $lastData['admin_id'] + 1;
		log_message('info', "Resuming from admin_id $startFromId for today");
	}
	$lastUser = $user_model
		->orderBy('id', 'DESC') // highest ID = last created
		->first();

	if ($lastUser) {
		$lastUserId = (int) $lastUser->id - 100;
		log_message('info', "Last created user ID: $lastUserId");
	} else {
		$lastUserId = 0;
		log_message('warning', "No users found in database");
	}

	if ($startFromId > $lastUserId) {
		log_message('info', "All users processed for today. Exiting.");
		$startFromId = 1;
	}

	// Step 1: Get all active users with router & PPPoE
	$users = $user_model
		->whereIn('role', ['user'])
		->where('router_id IS NOT NULL')
		->where('pppoe_id IS NOT NULL')
		->findAll();

	$users = array_filter($users, fn($u) => (int) $u->id >= $startFromId);
	if (empty($users))
		return log_message('warning', 'No users to process.');

	// Step 2: Group users by router
	$usersByRouter = [];
	foreach ($users as $user)
		$usersByRouter[$user->router_id][] = $user;

	// Step 3: Process each router
	foreach ($usersByRouter as $router_id => $routerUsers) {
		log_message('info', "Processing router_id $router_id with " . count($routerUsers) . " users");

		$router_client = routerClient($router_id);
		if (!is_object($router_client)) {
			log_message('error', "Router connection failed for router_id $router_id");
			continue;
		}

		try {
			// Fetch interfaces & active PPPoE users once per router
			$allInterfaces = [];
			foreach ($router_client->query(new \RouterOS\Query('/interface/print'))->read() as $intf) {
				if (isset($intf['name']))
					$allInterfaces[$intf['name']] = $intf;
			}
			// log_message('debug', 'System allInterfaces Data: ' . print_r($allInterfaces, true));

			$allPPP = [];
			foreach ($router_client->query(new \RouterOS\Query('/ppp/active/print'))->read() as $ppp) {
				if (isset($ppp['name']))
					$allPPP[$ppp['name']] = $ppp;
			}
			// log_message('debug', 'System allPPP Data: ' . print_r($allPPP, true));
		} catch (\Throwable $e) {
			log_message('error', "Router query failed for router_id $router_id - " . $e->getMessage());
			continue;
		}

		// Fetch previous usage for all users in this router
		$prevUsages = $usage_model
			->whereIn('admin_id', array_map(fn($u) => $u->id, $routerUsers))
			->where('date <', $date)
			->orderBy('date', 'DESC')
			->findAll();

		$prevMap = [];
		foreach ($prevUsages as $prev) {
			if (!isset($prevMap[$prev['admin_id']]))
				$prevMap[$prev['admin_id']] = $prev;
		}

		$batchInsert = [];
		$batchUpdate = [];
		$counter = 0;

		foreach ($routerUsers as $user) {
			$admin_id = (int) $user->id;
			$pppoe_id = $user->pppoe_id;

			$routerData = $routerDataModel->where('user_id', $admin_id)->first();
			if (empty($routerData)) {
				log_message('warning', "No router data found for User ID: {$user->id}");
				continue;
			}
			$pppoe_id = is_array($routerData) ? $routerData['pppoe_secret'] : $routerData->pppoe_secret;

			if (!isset($allPPP[$pppoe_id])) {
				log_message('warning', "PPPoE user $pppoe_id not active (user_id $admin_id)");
				continue;
			}

			// Build correct interface key
			$interfaceName = "<pppoe-$pppoe_id>";

			if (!isset($allInterfaces[$interfaceName])) {
				log_message('warning', "Interface $interfaceName not found for user_id $admin_id");
				continue;
			}

			$foundInterface = $allInterfaces[$interfaceName];
			$rxMB = round($foundInterface['rx-byte'] / 1024 / 1024, 2);
			$txMB = round($foundInterface['tx-byte'] / 1024 / 1024, 2);

			log_message('debug', "User $pppoe_id (ID: $admin_id) - RX: {$rxMB} MB, TX: {$txMB} MB");

			// Calculate today's usage
			$prev = $prevMap[$admin_id] ?? null;
			if ($prev && ($rxMB >= $prev['rx_mb'] && $txMB >= $prev['tx_mb'])) {
				$rxToday = $rxMB - $prev['rx_mb'];
				$txToday = $txMB - $prev['tx_mb'];
			} else {
				$rxToday = $rxMB;
				$txToday = $txMB;
			}

			$data = [
				'admin_id' => $admin_id,
				'user_name' => $pppoe_id,
				'interface' => $interfaceName,
				'date' => $date,
				'rx_mb' => $rxMB,
				'tx_mb' => $txMB,
				'rx_today' => $rxToday,
				'tx_today' => $txToday,
			];

			$existing = $usage_model->where('user_name', $pppoe_id)->where('date', $date)->first();
			if ($existing) {
				$data['id'] = $existing['id']; // add primary key
				$batchUpdate[] = $data;
			} else {
				$batchInsert[] = $data;
			}



			$counter++;

			// Batch insert/update every 200 users
			if ($counter % 200 === 0) {
				try {
					if (!empty($batchUpdate)) {
						try {
							// Pass the primary key column name as second argument
							$usage_model->updateBatch($batchUpdate, 'id');
							log_message('info', "Batch updated " . count($batchUpdate) . " records for router_id $router_id");
							$batchUpdate = [];
						} catch (\Throwable $e) {
							log_message('error', "DB batch update failed - " . $e->getMessage());
						}
					}

					if (!empty($batchInsert)) {
						$usage_model->insertBatch($batchInsert);
						log_message('info', "Batch inserted " . count($batchInsert) . " records for router_id $router_id");
						$batchInsert = [];
					}
				} catch (\Throwable $e) {
					log_message('error', "DB batch failed for router_id $router_id - " . $e->getMessage());
				}
			}
		}

		// Final batch insert/update
		if (!empty($batchUpdate))
			$usage_model->updateBatch($batchUpdate, 'id');
		if (!empty($batchInsert))
			$usage_model->insertBatch($batchInsert);
	}

	log_message('info', 'customer_data_usages: job completed at ' . date('Y-m-d H:i:s'));
}


/**
 * Get Router Client
 */

if (!function_exists('routerClient')) {
	function routerClient($id, $debug = false)
	{
		static $clients = []; // per-request cache

		// Return from cache if available
		if (isset($clients[$id])) {
			log_message('info', "♻️ Using cached Router client for ID $id");
			return $clients[$id];
		}

		// Circuit breaker: if this router was just found unreachable, fail fast
		// (<1ms) instead of paying the ~11s connect-timeout penalty on EVERY
		// request. One offline MikroTik would otherwise exhaust the PHP-FPM pool.
		// Cross-request via the cache service (file today, Redis after Phase 2).
		// The key auto-expires (see below) so one request retries = half-open.
		if (cache("router_down_{$id}")) {
			log_message('warning', "⛔ Circuit OPEN for Router ID $id — skipping connect (recently unreachable)");
			return null;
		}

		// Fetch router info
		$router = getRouterById($id);
		if (!$router) {
			log_message('error', "Router not found for ID $id");
			return null;
		}

		$host = is_array($router) ? $router['host'] : $router->host;
		$user = is_array($router) ? $router['username'] : $router->username;
		$pass = is_array($router) ? $router['password'] : $router->password;
		$port = is_array($router) ? $router['port'] : $router->port;
		$port = !empty($port) ? (int) $port : 8728;

		$client = null;

		// ── Build port try-list: custom → 8728 (if different) → 8729 SSL ──────
		// Each entry: [port, ssl]
		$portQueue = [];
		$portQueue[] = ['port' => $port,  'ssl' => false]; // always try DB port first
		if ($port !== 8728) {
			$portQueue[] = ['port' => 8728,  'ssl' => false]; // try plain 8728 if DB port isn't 8728
		}
		if ($port !== 8729) {
			$portQueue[] = ['port' => 8729,  'ssl' => true];  // SSL fallback
		}

		foreach ($portQueue as $attempt) {
			$tryPort = $attempt['port'];
			$trySSL  = $attempt['ssl'];
			$label   = $trySSL ? "API:{$tryPort}-SSL" : "API:{$tryPort}";

			try {
				log_message('info', "[$label] Trying Router ID $id ({$host}:{$tryPort})...");
				$client = new RouterOS\Client([
					'host'           => $host,
					'user'           => $user,
					'pass'           => $pass,
					'port'           => $tryPort,
					'ssl'            => $trySSL,
					'timeout'        => 5,
					'socket_timeout' => 5,
					'attempts'       => 1,
				]);
				log_message('info', "✅ [$label] Connected to Router ID $id ({$host}:{$tryPort})");
				break; // connected — stop trying more ports
			} catch (\Throwable $e) {
				log_message('warning', "[$label] Failed for Router ID $id - " . $e->getMessage());
				$client = null;
			}
		}

		if (!$client) {
			log_message('error', "Router connection failed for ID $id (tried ports: " . implode(', ', array_column($portQueue, 'port')) . ")");
			cache()->save("router_down_{$id}", 1, 45);
			return null;
		}

		// Cache client for this request
		$clients[$id] = $client;
		// Close the circuit breaker on a successful connection.
		cache()->delete("router_down_{$id}");
		log_message('info', "🆕 New Router client cached for ID $id");

		return $client;
	}
}


/**
 * Create PPPoE User
 */
// if (!function_exists('createPPPoEUser')) {
// 	function createPPPoEUser($client, $data)
// 	{

// 		$query = (new RouterOS\Query('/ppp/secret/add'))
// 			->equal('name', $data['pppoe_name'])
// 			->equal('password', $data['pppoe_password'])
// 			->equal('service', $data['pppoe_service'])
// 			->equal('profile', $data['pppoe_profile']);

// 		if (is_object($client)) {
// 			$response = $client->query($query)->read();
// 		} else {
// 			if (is_array($client)) {
// 				$client = (object) $client;

// 				$response = $client->query($query)->read();
// 			} else {
// 				return ['status' => 'error', 'error' => 'RouterOS client is not initialized properly'];
// 			}
// 		}

// 		// Process the response
// 		$router_error = $response['after']['message'] ?? null;
// 		$pppoe_id = $response['after']['ret'] ?? null;

// 		return !empty($router_error) ?
// 			['status' => 'error', 'error' => $router_error] : $pppoe_id;



// 		$router_error = $response['after']['message'] ?? null;

// 		$pppoe_id = $response['after']['ret'] ?? null;

// 		return !empty($router_error) ?
// 			['status' => 'error', 'error' => $router_error] :
// 			['status' => 'success', 'pppoe_id' => $pppoe_id];
// 	}
// }

if (!function_exists('createPPPoEUser')) {
	function createPPPoEUser($client, $data)
	{
		// Prepare RouterOS query
		$query = (new RouterOS\Query('/ppp/secret/add'))
			->equal('name', $data['pppoe_name'])
			->equal('password', $data['pppoe_password'])
			->equal('service', $data['pppoe_service']);

		if (!empty($data['pppoe_profile'])) {
			$query->equal('profile', $data['pppoe_profile']);
		}

		// Validate client object
		if (!is_object($client)) {
			if (is_array($client)) {
				$client = (object) $client; // Convert array to object
			} else {
				return ['status' => 'error', 'error' => 'RouterOS client is not initialized properly'];
			}
		}

		// Execute query
		$response = $client->query($query)->read();

		// Extract response data
		$router_error = $response['after']['message'] ?? null;
		$pppoe_id = $response['after']['ret'] ?? null;

		// Return structured response
		if (!empty($router_error)) {
			log_message('error', 'RouterOS error: ' . $router_error);
			return ['status' => 'error', 'error' => $router_error];
		}

		return ['status' => 'success', 'pppoe_id' => $pppoe_id];
	}
}


/**
 * Get single PPPoE User
 */
if (!function_exists('getPPPoEUser')) {
	function getPPPoEUser($client, $ppp_id)
	{
		if ($client === null) {
			// Log error or handle the fact that client connection failed
			log_message('error', 'RouterOS client is null in getPPPoEUser for PPP ID: ' . $ppp_id);
			// Return default "empty" response or false/null, depending on your app logic
			return [
				0 => [
					'name' => '',
					'password' => '',
					'service' => '',
					'profile' => '',
					'disabled' => 'true',
				]
			];
		}

		$query = (new RouterOS\Query('/ppp/secret/print'))
			->where('.id', $ppp_id);

		$response = $client->query($query)->read();

		// log_message('debug', 'getPPPoEUser response for PPP ID ' . $ppp_id . ': ' . print_r($response, true));

		if (!empty($response))
			return $response;

		return [
			0 => [
				'name' => '',
				'password' => '',
				'service' => '',
				'profile' => '',
				'disabled' => 'true',
			]
		];
	}
}

function getPPPoEUsersBatch($client, $ppp_ids)
{
	if ($client === null) {
		log_message('error', 'RouterOS client is null in getPPPoEUsersBatch');
		return createEmptyResponse($ppp_ids);
	}

	try {
		// Fetch ALL PPPoE users in one query
		$query = new RouterOS\Query('/ppp/secret/print');
		$allUsers = $client->query($query)->read();

		$result = [];

		// Create a lookup map for quick access
		$userMap = [];
		foreach ($allUsers as $user) {
			if (isset($user['.id'])) {
				$userMap[$user['.id']] = $user;
			}
		}

		// Return only the requested users
		foreach ($ppp_ids as $id) {
			$result[$id] = $userMap[$id] ?? createEmptyUserData();
		}

		return $result;
	} catch (\Throwable $e) {
		log_message('error', 'RouterOS batch query failed: ' . $e->getMessage());
		return createEmptyResponse($ppp_ids);
	}
}

// Helper function to create empty user data
function createEmptyUserData()
{
	return [
		'name' => '',
		'password' => '',
		'service' => 'pppoe',
		'profile' => '',
		'disabled' => 'true',
		'.id' => ''
	];
}

// Helper function for empty response
function createEmptyResponse($ppp_ids)
{
	$result = [];
	foreach ((array) $ppp_ids as $id) {
		$result[$id] = createEmptyUserData();
	}
	return $result;
}

if (!function_exists('getPPPoEUserUserId')) {
	function getPPPoEUserUserId($client, $user_id)
	{
		if ($client === null || empty($user_id)) {
			log_message('error', 'RouterOS client is null or user_id is empty in getPPPoEUserUserId for User ID: ' . $user_id);
			return [
				0 => [
					'id' => '',
					'password' => '',
					'service' => '',
					'profile' => '',
					'disabled' => 'true',
				]
			];
		}

		$user_model = model('App\Models\UserRouterDataModel');
		$user = $user_model->where(['user_id' => $user_id])->first();

		$ppp_name = !empty($user) ? $user->pppoe_secret : null;

		if (empty($ppp_name)) {
			return [
				0 => [
					'id' => '',
					'password' => '',
					'service' => '',
					'profile' => '',
					'disabled' => 'true',
				]
			];
		}

		$query = (new RouterOS\Query('/ppp/secret/print'))
			->where('name', $ppp_name);

		$response = $client->query($query)->read();

		// log_message('info', 'getPPPoEUserUserId response for User ID ' . $user_id . ': ' . print_r($response, true));

		if (!empty($response))
			return $response;
		else
			return [
				0 => [
					'id' => '',
					'password' => '',
					'service' => '',
					'profile' => '',
					'disabled' => 'true',
				]
			];
	}
}


if (!function_exists('getPPPoEUserByName')) {
	function getPPPoEUserByName($client, $ppp_name)
	{
		if (empty($ppp_name) || $client === null) {
			return [
				0 => [
					'id' => '',
					'password' => '',
					'service' => '',
					'profile' => '',
					'disabled' => 'true',
				]
			];
		}

		$query = (new RouterOS\Query('/ppp/secret/print'))
			->where('name', $ppp_name);

		$response = $client->query($query)->read();

		if (!empty($response))

			return $response;
		else
			return [
				0 => [
					'id' => '',
					'password' => '',
					'service' => '',
					'profile' => '',
					'disabled' => 'true',
				]
			];
	}
}



function getPPPoEUserBandwidth($client, $pppoeUsername)
{
	// Step 1: Get active PPPoE user by name
// $activeUserQuery = (new RouterOS\Query('/ppp/active/print'))->equal('name', $pppoeUsername);
// $activeUsers = $client->query($activeUserQuery)->read();

	// if (empty($activeUsers) || empty($activeUsers[0]['interface'])) {
//     // No active user or no interface assigned
//     return [
//         'interface' => null,
//         'speed' => ['rx_mbps' => 0, 'tx_mbps' => 0],
//         'total_usage' => ['rx_bytes' => 0, 'tx_bytes' => 0],
//         'checked_at' => gmdate(DATE_ISO8601),
//     ];
// }

	// $interface = $activeUsers[0]['interface'];

	// // Step 2: Get traffic for the interface
// $trafficQuery = (new RouterOS\Query('/interface/monitor-traffic'))
//     ->equal('interface', $interface)
//     ->equal('once');

	// $traffic = $client->query($trafficQuery)->read();

	// $rxMbps = !empty($traffic[0]['rx-bits-per-second']) ? round($traffic[0]['rx-bits-per-second'] / 1_000_000, 2) : 0;
// $txMbps = !empty($traffic[0]['tx-bits-per-second']) ? round($traffic[0]['tx-bits-per-second'] / 1_000_000, 2) : 0;

	// return [
//     'interface' => $interface,
//     'speed' => [
//         'rx_mbps' => $rxMbps,
//         'tx_mbps' => $txMbps,
//     ],
//     'total_usage' => [
//         'rx_bytes' => 0,
//         'tx_bytes' => 0,
//     ],
//     'checked_at' => gmdate(DATE_ISO8601),
// ];
// Example: dynamic PPPoE name
// $pppoeName = 'Fatima@18@';

	// // Query active PPP sessions for this user
// $query = (new RouterOS\Query('/ppp/active'))
//     ->where('name', $pppoeName);

	// try {
//     $activeData = $client->query($query)->read();

	//     if (!empty($activeData)) {
//         $session = $activeData[0];

	//         $rxBytes = isset($session['limit-bytes-in']) ? (int)$session['limit-bytes-in'] : 0;
//         $txBytes = isset($session['limit-bytes-out']) ? (int)$session['limit-bytes-out'] : 0;

	//         $rxMB = round($rxBytes / 1024 / 1024, 2);
//         $txMB = round($txBytes / 1024 / 1024, 2);

	//         log_message('debug', "System active user session R...X: {$rxMB} MB");
//         log_message('debug', "System active user session TX: {$txMB} MB");
//     } else {
//         log_message('debug', "No active PPP session found for user: $pppoeName");
//     }
// } catch (\Throwable $e) {
//     log_message('error', 'Error fetching PPP active session data: ' . $e->getMessage());
// }

}



/**
 * Get all PPPoE Users
 */
if (!function_exists('getAllPPPoEUsers')) {
	function getAllPPPoEUsers($client)
	{
		// Guard: client may be null if router connection failed
		if (!$client || !is_object($client)) {
			return [];
		}

		$query = (new RouterOS\Query('/ppp/secret/print'));

		try {
			$response = $client->query($query)->read();
		} catch (\Throwable $e) {
			log_message('error', 'getAllPPPoEUsers failed: ' . $e->getMessage());
			return [];
		}

		if (!empty($response))

			return $response;
		else
			return [
				0 => [
					'name' => '',
					'password' => '',
					'service' => '',
					'profile' => '',
					'disabled' => 'true',
				]
			];
	}
}

/**
 * Get PPPoE Profiles
 */
if (!function_exists('getPPPoEProfiles')) {
	function getPPPoEProfiles($client)
	{
		if ($client === null) {
			log_message('error', 'RouterOS client is null in getPPPoEProfiles');
			return []; // Return empty array if client connection failed
		}

		$profiles = [];

		$query = (new RouterOS\Query('/ppp/profile/print'));

		$ppp_profiles = $client->query($query)->read();

		foreach ($ppp_profiles as $ppp_profile) {
			if (isset($ppp_profile['name'])) {
				$profiles[] = $ppp_profile['name'];
			}
		}

		return $profiles;
	}
}


/**
 * Enable PPPoE User
 */
if (!function_exists('enablePPPoEUser')) {
	function enablePPPoEUser($client, $ppp_id)
	{
		// A null/empty ppp id can never enable a secret. Without this guard the
		// function returned true for a missing id, so a failed lookup registered as a
		// successful enable (paid customer never connected, nothing retried). Empty id
		// => return false so the caller's secret fallback runs. Valid-id path unchanged.
		if (empty($ppp_id)) {
			log_message('warning', 'enablePPPoEUser called with empty ppp_id — treating as failure.');
			return false;
		}
		try {
			// Create the RouterOS enable command
			$query = (new RouterOS\Query('/ppp/secret/enable'))
				->equal('numbers', $ppp_id);

			// Execute the query
			$response = $client->query($query)->read();

			// Log the response for debugging
			log_message('info', 'PPPoE Enable Command Sent: ' . $ppp_id);
			// log_message('info', 'Router Response: ' . json_encode($response));

			// Optionally, check for success
			if (is_array($response) && count($response) > 0) {
				log_message('info', "PPPoE user '{$ppp_id}' enabled successfully.");
			} else {
				log_message('warning', "PPPoE enable response empty for '{$ppp_id}'.");
			}

			return true;
		} catch (\Throwable $e) {
			// Log any error
			log_message('error', "Failed to enable PPPoE user '{$ppp_id}': " . $e->getMessage());
			return false;
		}
	}
}


if (!function_exists('enablePPPoEUser_by_pppoe_secret')) {
	function enablePPPoEUser_by_pppoe_secret($client, $pppoe_secret)
	{
		// A null client (routerClient() returns null on circuit-open / not-found /
		// connect-failure) or empty secret can never enable anything. Without this,
		// null->query() throws \Error which the catch (Exception) below MISSES,
		// surfacing as a fatal on the secret-fallback path of every gateway + webhook.
		if ($client === null || empty($pppoe_secret)) {
			log_message('warning', 'enablePPPoEUser_by_pppoe_secret called with null client or empty secret — treating as failure.');
			return false;
		}
		try {
			$pppoe_name = $pppoe_secret;

			$query = (new RouterOS\Query('/ppp/secret/print'))
				->where('name', $pppoe_name);

			$response = $client->query($query)->read();

			$ppp_id = $response[0]['.id'] ?? null;


			$querys = (new RouterOS\Query('/ppp/secret/enable'))
				->equal('numbers', $ppp_id);

			// Execute the query
			$response = $client->query($querys)->read();

			// Log the response for debugging
			log_message('info', 'PPPoE Enable Command Sent: ' . $ppp_id);
			log_message('info', 'enablePPPoEUser_by_pppoe_secret Response: ' . json_encode($response));

			$checkQuery = (new RouterOS\Query('/ppp/secret/print'))
				->where('.id', $ppp_id);

			$check = $client->query($checkQuery)->read();

			$status = $check[0]['disabled'] ?? 'unknown';
			if ($status === 'false') {
				log_message('info', "PPPoE user '{$pppoe_secret}' is now enabled.");
				log_message('info', 'Status after enable: ' . json_encode($check));
			}


			// Optionally, check for success
			if (is_array($response) && count($response) > 0) {
				log_message('info', "PPPoE user '{$pppoe_secret}' enabled successfully.");
			} else {
				log_message('warning', "PPPoE enable response empty for '{$pppoe_secret}'.");
			}

			return true;
		} catch (\Throwable $e) {
			// Log any error
			log_message('error', "Failed to enable PPPoE user '{$pppoe_secret}': " . $e->getMessage());
			return false;
		}
	}
}

function getHotspotUserProfiles($router_id)
{
	$router_client = routerClient($router_id);

	/* ===============================
	 * 1️⃣ RouterOS\Client (API)
	 * =============================== */
	if ($router_client instanceof \RouterOS\Client) {
		try {
			$response = $router_client
				->query('/ip/hotspot/user/profile/print')
				->read();

			if (!empty($response)) {
				return $response;
			}
		} catch (\Throwable $e) {
			log_message('error', 'Hotspot profile API error: ' . $e->getMessage());
		}
	}

	/* ===============================
	 * 2️⃣ fsocket fallback
	 * =============================== */
	$fp = connect_using_Fsocket($router_id);
	if (!$fp) {
		return false;
	}

	$result = getHotspotProfilesFsock($fp);
	fclose($fp);
	return $result;
}

function getHotspotProfilesFsock($fp)
{
	if (!$fp)
		return false;

	// send command
	fwrite($fp, "/ip/hotspot/user/profile/print\n");
	fwrite($fp, "\n");

	$result = [];
	while (!feof($fp)) {
		$rawLine = fgets($fp, 4096);
		if ($rawLine === false) {
			break;
		}
		$line = trim($rawLine);

		if ($line === '!done') {
			break;
		}

		if (str_starts_with($line, '=')) {
			[$key, $value] = explode('=', substr($line, 1), 2);
			$result[$key] = $value;
		}

		if ($line === '!re') {
			if (!empty($result)) {
				$profiles[] = $result;
				$result = [];
			}
		}
	}

	return $profiles ?? [];
}

function getSimpleQueues($router_id)
{
	$router_client = routerClient($router_id);

	/* ===============================
	 * 1️⃣ RouterOS\Client (API)
	 * =============================== */
	if ($router_client instanceof \RouterOS\Client) {
		try {
			return $router_client->query(
				'/queue/simple/print',
				['?dynamic' => 'false']
			)->read();
		} catch (\Throwable $e) {
			log_message('error', 'Queue API error: ' . $e->getMessage());
		}
	}
	/* ===============================
	 * 2️⃣ fsocket fallback
	 * =============================== */
	$fp = connect_using_Fsocket($router_id);
	if (!$fp) {
		return false;
	}

	$result = getSimpleQueuesFsock($fp);
	fclose($fp);
	return $result;
}
function getSimpleQueuesFsock($fp)
{
	if (!$fp)
		return false;

	fwrite($fp, "/queue/simple/print\n");
	fwrite($fp, "?dynamic=false\n");
	fwrite($fp, "\n");

	return readRouterResponseFsock($fp);
}

function getIpPools($router_id)
{
	$router_client = routerClient($router_id);

	/* ===============================
	 * 1️⃣ RouterOS\Client (API)
	 * =============================== */
	if ($router_client instanceof \RouterOS\Client) {
		try {
			return $router_client
				->query('/ip/pool/print')
				->read();
		} catch (\Throwable $e) {
			log_message('error', 'IP Pool API error: ' . $e->getMessage());
		}
	}

	/* ===============================
	 * 2️⃣ fsocket fallback
	 * =============================== */
	$fp = connect_using_Fsocket($router_id);
	if (!$fp) {
		return false;
	}

	$result = getIpPoolsFsock($fp);
	fclose($fp);
	return $result;
}
function getIpPoolsFsock($fp)
{
	if (!$fp)
		return false;

	fwrite($fp, "/ip/pool/print\n");
	fwrite($fp, "\n");

	return readRouterResponseFsock($fp);
}

function readRouterResponseFsock($fp)
{
	$data = [];
	$row = [];

	stream_set_timeout($fp, 5);

	while (true) {
		$line = fgets($fp, 4096);
		if ($line === false)
			break;

		$line = trim($line);

		if ($line === '!done') {
			if (!empty($row))
				$data[] = $row;
			break;
		}

		if ($line === '!re') {
			if (!empty($row)) {
				$data[] = $row;
				$row = [];
			}
			continue;
		}

		if ($line !== '' && $line[0] === '=') {
			[$key, $value] = explode('=', substr($line, 1), 2);
			$row[$key] = $value;
		}
	}

	return $data;
}

// Helper function for fsocket method
function addHotspotUserFsock($fp, $server, $name, $password, $profile, $mac_address, $timelimit, $datalimit, $comment)
{
	try {
		$commands = [
			"/ip/hotspot/user/add",
			"=server=$server",
			"=name=$name",
			"=password=$password",
			"=profile=$profile",
			"=disabled=no",
			"=limit-uptime=$timelimit",
			"=limit-bytes-total=$datalimit",
			"=comment=$comment",
		];

		if (!empty($mac_address)) {
			$commands[] = "=mac-address=$mac_address";
		}

		// Send commands
		foreach ($commands as $cmd) {
			fputs($fp, $cmd . chr(0));
		}

		fputs($fp, chr(0));

		// Read response
		$response = '';
		while (!feof($fp)) {
			$line = fgets($fp, 128);
			if ($line === false) {
				break;
			}
			$response .= $line;
		}

		fclose($fp);

		// Check if successful
		return strpos($response, '!done') !== false;
	} catch (\Throwable $e) {
		log_message('error', 'Fsocket error: ' . $e->getMessage());
		if ($fp)
			fclose($fp);
		return false;
	}
}


// Helper function for fsocket - Get Users
function getFsockUsers($fp)
{
	$users = [];

	try {
		fputs($fp, "/ip/hotspot/user/print" . chr(0));
		fputs($fp, chr(0));

		$response = '';
		while (!feof($fp)) {
			$line = fgets($fp, 128);
			if ($line === false) {
				break;
			}
			$response .= $line;
		}

		// Parse response into array
		$lines = explode("\n", $response);
		$currentUser = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if (strpos($line, '!re') === 0) {
				if (!empty($currentUser)) {
					$users[] = $currentUser;
				}
				$currentUser = [];
			} elseif (strpos($line, '=') !== false) {
				list($key, $value) = explode('=', $line, 2);
				$currentUser[$key] = $value;
			} elseif (strpos($line, '.id') === 0) {
				$currentUser['.id'] = substr($line, 4);
			}
		}

		if (!empty($currentUser)) {
			$users[] = $currentUser;
		}
	} catch (\Throwable $e) {
		log_message('error', 'Fsocket get users error: ' . $e->getMessage());
	}

	return $users;
}

// Helper function for fsocket - Get Profiles
function getFsockProfiles($fp)
{
	$profiles = [];

	try {
		fputs($fp, "/ip/hotspot/user/profile/print" . chr(0));
		fputs($fp, chr(0));

		$response = '';
		while (!feof($fp)) {
			$line = fgets($fp, 128);
			if ($line === false) {
				break;
			}
			$response .= $line;
		}

		// Parse response
		$lines = explode("\n", $response);
		$currentProfile = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if (strpos($line, '!re') === 0) {
				if (!empty($currentProfile)) {
					$profiles[] = $currentProfile;
				}
				$currentProfile = [];
			} elseif (strpos($line, '=') !== false) {
				list($key, $value) = explode('=', $line, 2);
				$currentProfile[$key] = $value;
			} elseif (strpos($line, '.id') === 0) {
				$currentProfile['.id'] = substr($line, 4);
			}
		}

		if (!empty($currentProfile)) {
			$profiles[] = $currentProfile;
		}
	} catch (\Throwable $e) {
		log_message('error', 'Fsocket get profiles error: ' . $e->getMessage());
	}

	return $profiles;
}

// Helper function for fsocket - Get Servers
function getFsockServers($fp)
{
	$servers = [];

	try {
		fputs($fp, "/ip/hotspot/print" . chr(0));
		fputs($fp, chr(0));

		$response = '';
		while (!feof($fp)) {
			$line = fgets($fp, 128);
			if ($line === false) {
				break;
			}
			$response .= $line;
		}

		// Parse response
		$lines = explode("\n", $response);
		$currentServer = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if (strpos($line, '!re') === 0) {
				if (!empty($currentServer)) {
					$servers[] = $currentServer;
				}
				$currentServer = [];
			} elseif (strpos($line, '=') !== false) {
				list($key, $value) = explode('=', $line, 2);
				$currentServer[$key] = $value;
			} elseif (strpos($line, '.id') === 0) {
				$currentServer['.id'] = substr($line, 4);
			}
		}

		if (!empty($currentServer)) {
			$servers[] = $currentServer;
		}
	} catch (\Throwable $e) {
		log_message('error', 'Fsocket get servers error: ' . $e->getMessage());
	}

	return $servers;
}

// function buildHotspotOnLoginScript($mode, $price, $sprice, $validity, $lock)
// {
// 	if ($mode === '0') {
// 		return $price !== ''
// 			? ':put (",,' . $price . ',,,noexp,,' . '")' . $lock
// 			: '';
// 	}

// 	return ':put (",' . $mode . ',' . $price . ',' . $validity . ',' . $sprice . ',,'$lock',' . '")' . $lock;
// }

function buildHotspotBackgroundService($profile, $mode)
{
	if ($mode === '0')
		return '';

	return ':local dateint do={:local montharray ( "jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec" );:local days [ :pick $d 4 6 ];:local month [ :pick $d 0 3 ];:local year [ :pick $d 7 11 ];:local monthint ([ :find $montharray $month]);:local month ($monthint + 1);:if ( [len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}}; :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; }; :local date [ /system clock get date ]; :local time [ /system clock get time ]; :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ; :foreach i in [ /ip hotspot user find where profile="' . $profile . '" ] do={ :local comment [ /ip hotspot user get $i comment]; :local name [ /ip hotspot user get $i name]; :local gettime [:pic $comment 12 20]; :if ([:pic $comment 3] = "/" and [:pic $comment 6] = "/") do={:local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ; :if (($expd < $today and $expt < $curtime) or ($expd < $today and $expt > $curtime) or ($expd = $today and $expt < $curtime)) do={ [ /ip hotspot user ' . $mode . ' $i ]; [ /ip hotspot active remove [find where user=$name] ];}}}';
}


if (!function_exists('hotspot_user_js')) {
	function hotspot_user_js()
	{
		return '<script>
            window.HOTSPOT_USER_ID = ' . (int) session()->get('user_id') . ';
        </script>';
	}
}


function handleProfileSchedulerAPI(
	\RouterOS\Client $client,
	string $name,
	string $bgservice,
	string $start,
	string $interval,
	string $mode
) {
	// No expiration → remove scheduler if exists
	if ($mode === '0') {
		$client->query(
			(new \RouterOS\Query('/system/scheduler/remove'))
				->where('name', $name)
		)->read();
		return;
	}

	try {
		// Remove old scheduler first (important!)
		$client->query(
			(new \RouterOS\Query('/system/scheduler/remove'))
				->where('name', $name)
		)->read();

		// Add scheduler
		$query = new \RouterOS\Query('/system/scheduler/add');
		$query->equal('name', $name);
		$query->equal('start-time', $start);
		$query->equal('interval', $interval);
		$query->equal('on-event', $bgservice);
		$query->equal('disabled', 'no');
		$query->equal('comment', "Monitor Profile $name");

		// 🔥 THIS IS REQUIRED
		$response = $client->query($query)->read();

		log_message('debug', 'Scheduler created for profile: ' . $name);
		log_message('debug', 'Scheduler response: ' . print_r($response, true));
	} catch (\Throwable $e) {
		log_message('error', 'Scheduler add API error: ' . $e->getMessage());
	}
}

function addHotspotProfileFsock(
	$fp,
	$name,
	$pool,
	$ratelimit,
	$shared,
	$onlogin,
	$parent,
	$bgservice,
	$start,
	$interval,
	$mode
) {
	fwrite($fp, "/ip/hotspot/user/profile/add\n");
	fwrite($fp, "=name=$name\n");
	fwrite($fp, "=address-pool=$pool\n");
	fwrite($fp, "=rate-limit=$ratelimit\n");
	fwrite($fp, "=shared-users=$shared\n");
	fwrite($fp, "=status-autorefresh=1m\n");
	fwrite($fp, "=on-login=$onlogin\n");
	fwrite($fp, "=parent-queue=$parent\n\n");
	readRouterResponseFsock($fp);

	if ($mode !== '0') {
		fwrite($fp, "/system/scheduler/add\n");
		fwrite($fp, "=name=$name\n");
		fwrite($fp, "=start-time=$start\n");
		fwrite($fp, "=interval=$interval\n");
		fwrite($fp, "=on-event=$bgservice\n");
		fwrite($fp, "=disabled=no\n\n");
		readRouterResponseFsock($fp);
	} else {
		fwrite($fp, "/system/scheduler/remove\n");
		fwrite($fp, "?name=$name\n\n");
		readRouterResponseFsock($fp);
	}

	return true;
}

function updateHotspotProfileFsock(
	$fp,
	$profileId,
	$name,
	$pool,
	$ratelimit,
	$shared,
	$onlogin,
	$parent,
	$bgservice,
	$start,
	$interval,
	$mode
) {
	/* ===============================
	 * UPDATE PROFILE
	 * =============================== */
	fwrite($fp, "/ip/hotspot/user/profile/set\n");
	fwrite($fp, "=.id=$profileId\n");
	fwrite($fp, "=name=$name\n");
	fwrite($fp, "=address-pool=$pool\n");
	fwrite($fp, "=rate-limit=$ratelimit\n");
	fwrite($fp, "=shared-users=$shared\n");
	fwrite($fp, "=status-autorefresh=1m\n");
	fwrite($fp, "=on-login=$onlogin\n");

	if ($parent !== 'none' && !empty($parent)) {
		fwrite($fp, "=parent-queue=$parent\n");
	} else {
		fwrite($fp, "=parent-queue=\n");
	}

	fwrite($fp, "\n");
	readRouterResponseFsock($fp);

	/* ===============================
	 * SCHEDULER HANDLING
	 * =============================== */
	if ($mode !== '0') {
		// Remove old scheduler (if exists)
		fwrite($fp, "/system/scheduler/remove\n");
		fwrite($fp, "?name=$name\n\n");
		readRouterResponseFsock($fp);

		// Add new scheduler
		fwrite($fp, "/system/scheduler/add\n");
		fwrite($fp, "=name=$name\n");
		fwrite($fp, "=start-time=$start\n");
		fwrite($fp, "=interval=$interval\n");
		fwrite($fp, "=on-event=$bgservice\n");
		fwrite($fp, "=disabled=no\n\n");
		readRouterResponseFsock($fp);
	} else {
		// Expiration disabled → remove scheduler
		fwrite($fp, "/system/scheduler/remove\n");
		fwrite($fp, "?name=$name\n\n");
		readRouterResponseFsock($fp);
	}

	return true;
}

















function lengthEncode($len)
{
	if ($len < 0x80)
		return chr($len);
	elseif ($len < 0x4000)
		return chr(($len >> 8) | 0x80) . chr($len & 0xFF);
	elseif ($len < 0x200000)
		return chr(($len >> 16) | 0xC0) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
	elseif ($len < 0x10000000)
		return chr(($len >> 24) | 0xE0) . chr(($len >> 16) & 0xFF)
			. chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
	else
		return chr(0xF0) . chr(($len >> 24) & 0xFF)
			. chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF)
			. chr($len & 0xFF);
}

function writeWord($fp, $word)
{
	fwrite($fp, lengthEncode(strlen($word)) . $word);
}

function writeSentence($fp, $words = [])
{
	foreach ($words as $w) {
		writeWord($fp, $w);
	}
	writeWord($fp, ""); // sentence end
}

function readWord($fp)
{
	$len = ord(fread($fp, 1));
	if ($len & 0x80) {
		if ($len & 0x40) {
			if ($len & 0x20) {
				$len &= 0x1F;
				$len = ($len << 8) + ord(fread($fp, 1));
				$len = ($len << 8) + ord(fread($fp, 1));
				$len = ($len << 8) + ord(fread($fp, 1));
			} else {
				$len &= 0x3F;
				$len = ($len << 8) + ord(fread($fp, 1));
				$len = ($len << 8) + ord(fread($fp, 1));
			}
		} else {
			$len &= 0x7F;
			$len = ($len << 8) + ord(fread($fp, 1));
		}
	}
	if ($len > 0) {
		return fread($fp, $len);
	}
	return "";
}

function readSentence($fp)
{
	$sentence = [];
	while (true) {
		$word = readWord($fp);
		if ($word == "")
			break;
		$sentence[] = $word;
	}
	return $sentence;
}

function connect_using_Fsocket($id)
{
	$router = getRouterById($id);

	if (!$router) {
		log_message('error', "❌ Router not found for ID $id");
		return null;
	}

	$host = is_array($router) ? $router['host'] : $router->host;
	$user = is_array($router) ? $router['username'] : $router->username;
	$pass = is_array($router) ? $router['password'] : $router->password;
	$port = is_array($router) ? $router['port'] : $router->port;
	$port = !empty($port) ? (int) $port : 8728;

	// ── Build port try-list: custom → 8728 (if different) → 8729 ──────────
	$portsToTry = array_unique(array_filter([$port, ($port !== 8728 ? 8728 : null), 8729]));

	foreach ($portsToTry as $tryPort) {
		$errno  = 0;
		$errstr = '';
		$fp = @fsockopen($host, $tryPort, $errno, $errstr, 3);

		if (!$fp || !is_resource($fp)) {
			log_message('warning', "[Fsock:$tryPort] Connection failed to Router ID $id ($host:$tryPort) → $errstr (Code: $errno)");
			continue; // try next port
		}

		stream_set_timeout($fp, 10);
		log_message('info', "✅ [Fsock:$tryPort] Connected to Router ID $id ({$host}:{$tryPort})");

		// Login
		writeSentence($fp, ["/login", "=name=$user", "=password=$pass"]);
		$loginResponse = readSentence($fp);
		log_message('debug', "[Fsock:$tryPort] Login Response: " . json_encode($loginResponse));

		if (empty($loginResponse) || $loginResponse[0] !== '!done') {
			log_message('error', "[Fsock:$tryPort] Login failed for Router ID $id ({$host})");
			fclose($fp);
			continue; // try next port
		}

		return $fp; // ✅ success
	}

	log_message('error', "❌ Fsock: All ports failed for Router ID $id");
	return null;
}


function enablePPPoEUserFsock($id, $ppp_id)
{
	// Empty ppp id can't enable anything — guard before opening a socket so a missing
	// id is never sent as `=numbers=` (and never reads as a false success upstream).
	if (empty($ppp_id)) {
		log_message('warning', 'enablePPPoEUserFsock called with empty ppp_id — treating as failure.');
		return false;
	}
	$fp = connect_using_Fsocket($id);
	if (!$fp) {
		log_message('error', "FSOCK connection not established for Router ID $id");
		return false;
	}
	// ✅ Send enable command
	writeSentence($fp, [
		"/ppp/secret/enable",
		"=numbers=$ppp_id"
	]);

	log_message('debug', "Sending ENABLE command for PPP ID: $ppp_id");

	while (true) {
		$resp = readSentence($fp);

		// Log raw response
		log_message('debug', "Enable Response: " . json_encode($resp));

		if (count($resp) == 0) {
			log_message('error', "FSOCK connection closed unexpectedly while enabling PPPoE user.");
			fclose($fp);
			return false;
		}

		// RouterOS finished
		if ($resp[0] == "!done") {
			log_message('info', "PPPoE user '$ppp_id' ENABLED successfully.");

			fclose($fp);
			return true;
		}

		// RouterOS error !trap
		if ($resp[0] == "!trap") {
			log_message('error', "Enable FAILED for '$ppp_id': " . json_encode($resp));

			fclose($fp);
			return false;
		}
	}
}

function disablePPPoEUserFsock($id, $ppp_id)
{
	// ── Layer 1: Fsock (port 8728 → 8729 tried internally) ────────────────
	$fp = connect_using_Fsocket($id);
	if ($fp) {
		writeSentence($fp, ["/ppp/secret/disable", "=numbers=$ppp_id"]);
		log_message('debug', "[Fsock] Sending DISABLE for PPP ID: $ppp_id");

		while (true) {
			$resp = readSentence($fp);
			log_message('debug', "[Fsock] Disable Response: " . json_encode($resp));

			if (count($resp) == 0) {
				log_message('error', "[Fsock] Connection closed unexpectedly while disabling PPPoE user.");
				fclose($fp);
				break; // fall through to SSH
			}
			if ($resp[0] === '!done') {
				log_message('info', "[Fsock] PPPoE user '$ppp_id' DISABLED successfully.");
				fclose($fp);
				return true;
			}
			if ($resp[0] === '!trap') {
				log_message('error', "[Fsock] Disable FAILED for '$ppp_id': " . json_encode($resp));
				fclose($fp);
				break; // fall through to SSH
			}
		}
	} else {
		log_message('warning', "[Fsock] Connection not established for Router ID $id — trying SSH fallback.");
	}

	// ── Layer 2: SSH fallback via phpseclib ────────────────────────────────
	return disablePPPoEUserSSH($id, $ppp_id);
}

/**
 * Disable a PPPoE user via SSH (phpseclib3) — Layer 2 fallback.
 * Uses port 22. Requires phpseclib/phpseclib ^3.0 (already in composer.json).
 */
function disablePPPoEUserSSH($id, $ppp_id)
{
	if (!class_exists('\phpseclib3\Net\SSH2')) {
		log_message('error', "[SSH] phpseclib not available. Cannot use SSH fallback for Router ID $id.");
		return false;
	}

	$router = getRouterById($id);
	if (!$router) {
		log_message('error', "[SSH] Router not found for ID $id");
		return false;
	}

	$host = is_array($router) ? $router['host']     : $router->host;
	$user = is_array($router) ? $router['username']  : $router->username;
	$pass = is_array($router) ? $router['password']  : $router->password;

	log_message('info', "[SSH] Attempting SSH connection to Router ID $id ({$host}:22)...");

	try {
		$ssh = new \phpseclib3\Net\SSH2($host, 22, 5); // 5s timeout

		if (!$ssh->login($user, $pass)) {
			log_message('error', "[SSH] Login failed for Router ID $id ({$host})");
			return false;
		}

		log_message('info', "[SSH] ✅ Logged in to Router ID $id ({$host}:22)");

		// Disable the PPPoE secret by name/id
		$cmd    = "/ppp secret disable numbers=\"$ppp_id\"";
		$output = $ssh->exec($cmd);
		log_message('info', "[SSH] DISABLE command sent: $cmd");
		log_message('debug', "[SSH] Response: " . trim($output));

		// Also try to drop the active session so user is kicked immediately
		$activeOut = $ssh->exec("/ppp active remove [find name=\"$ppp_id\"]");
		log_message('debug', "[SSH] Active session remove response: " . trim($activeOut));

		log_message('info', "[SSH] ✅ PPPoE user '$ppp_id' disabled via SSH on Router ID $id");
		return true;

	} catch (\Exception $e) {
		log_message('error', "[SSH] Exception for Router ID $id: " . $e->getMessage());
		return false;
	}
}

/**
 * Enable a PPPoE user via SSH (phpseclib3) — SSH fallback for enable.
 * Uses port 22. Requires phpseclib/phpseclib ^3.0.
 */
function enablePPPoEUserSSH($id, $ppp_id)
{
	if (!class_exists('\phpseclib3\Net\SSH2')) {
		log_message('error', "[SSH-Enable] phpseclib not available. Cannot use SSH fallback for Router ID $id.");
		return false;
	}

	$router = getRouterById($id);
	if (!$router) {
		log_message('error', "[SSH-Enable] Router not found for ID $id");
		return false;
	}

	$host = is_array($router) ? $router['host']    : $router->host;
	$user = is_array($router) ? $router['username'] : $router->username;
	$pass = is_array($router) ? $router['password'] : $router->password;

	log_message('info', "[SSH-Enable] Attempting SSH connection to Router ID $id ({$host}:22)...");

	try {
		$ssh = new \phpseclib3\Net\SSH2($host, 22, 5); // 5s timeout

		if (!$ssh->login($user, $pass)) {
			log_message('error', "[SSH-Enable] Login failed for Router ID $id ({$host})");
			return false;
		}

		log_message('info', "[SSH-Enable] ✅ Logged in to Router ID $id ({$host}:22)");

		// Enable the PPPoE secret by id/name
		$cmd    = "/ppp secret enable numbers=\"$ppp_id\"";
		$output = $ssh->exec($cmd);
		log_message('info', "[SSH-Enable] ENABLE command sent: $cmd");
		log_message('debug', "[SSH-Enable] Response: " . trim($output));

		log_message('info', "[SSH-Enable] ✅ PPPoE user '$ppp_id' enabled via SSH on Router ID $id");
		return true;

	} catch (\Exception $e) {
		log_message('error', "[SSH-Enable] Exception for Router ID $id: " . $e->getMessage());
		return false;
	}
}

function getPPPoEIdFsock($fp, $pppoe_secret)
{
	if (!$fp) {
		log_message('error', "FSOCK is not initialized");
		return null;
	}

	$pppoe_secret = is_array($pppoe_secret) ? $pppoe_secret['pppoe_secret'] : $pppoe_secret;
	$pppoe_secret = trim($pppoe_secret);

	// Send command to print PPP secrets
	writeSentence($fp, ["/ppp/secret/print", "?name=$pppoe_secret"]);

	$pppoe_id = null;

	while (true) {
		$resp = readSentence($fp);

		if (count($resp) === 0) {
			log_message('error', "FSOCK connection closed unexpectedly while getting PPPoE ID.");
			fclose($fp);
			return null;
		}

		if ($resp[0] === '!done') {
			break; // finished reading
		}

		if ($resp[0] === '!re') {
			$entry = [];
			foreach ($resp as $word) {
				if (strpos($word, '=') === 0) {
					list($k, $v) = explode("=", substr($word, 1), 2);
					$entry[$k] = $v;
				}
			}

			// Check if this is the PPPoE secret we are looking for
			if (isset($entry['name']) && $entry['name'] === $pppoe_secret) {
				$pppoe_id = $entry['.id'] ?? null;
				break; // found it
			}
		}

		if ($resp[0] === '!trap') {
			log_message('error', "FSOCK error while searching for PPPoE secret '$pppoe_secret': " . json_encode($resp));
			break;
		}
	}

	return $pppoe_id; // returns null if not found
}


function updateUser()
{
	log_message('info', 'updateUser: job started at ' . date('Y-m-d H:i:s'));

	$id = 10;
	$fp = connect_using_Fsocket($id);
	if (!$fp) {
		log_message('error', "FSOCK connection not established for Router ID $id");
		return false;
	}
	// $router = getRouterById($id);

	// if (!$router) {
	// 	log_message('error', "❌ Router not found for ID $id");
	// 	return null;
	// }

	// $host = is_array($router) ? $router['host'] : $router->host;
	// $user = is_array($router) ? $router['username'] : $router->username;
	// $pass = is_array($router) ? $router['password'] : $router->password;
	// $port = is_array($router) ? $router['port'] : $router->port;
	// $port = !empty($port) ? (int)$port : 8728;

	// $errno = 0;
	// $errstr = '';

	// // ✅ Attempt connection
	// $fp = @fsockopen($host, $port, $errno, $errstr, 5);

	// if (!$fp || !is_resource($fp)) {
	// 	log_message('error', "❌ Connection failed to Router ID $id ($host:$port) → $errstr (Code: $errno)");
	// 	log_message('info', 'updateUser: job ended unsuccessfully at ' . date('Y-m-d H:i:s'));
	// 	return null;
	// }

	// log_message('info', "✅ Connected to Router ID $id ({$host}:{$port})");

	// // ✅ Login to MikroTik
	// writeSentence($fp, ["/login", "=name=$user", "=password=$pass"]);
	// $loginResponse = readSentence($fp);
	// log_message('debug', 'MikroTik Login Response: ' . json_encode($loginResponse));

	// if (empty($loginResponse) || $loginResponse[0] !== '!done') {
	// 	log_message('error', "❌ Login failed for Router ID $id ({$host})");
	// 	fclose($fp);
	// 	log_message('info', 'updateUser: job ended unsuccessfully at ' . date('Y-m-d H:i:s'));
	// 	return null;
	// }

	// ✅ Verify actual connection by fetching router identity
	writeSentence($fp, ["/system/identity/print"]);
	$identityResp = readSentence($fp);

	$routerIdentity = null;
	foreach ($identityResp as $word) {
		if (strpos($word, "=name=") === 0) {
			$routerIdentity = substr($word, 6);
			break;
		}
	}

	if ($routerIdentity) {
		log_message('info', "🟢 Verified connection to MikroTik Router: {$routerIdentity}");
	} else {
		log_message('warning', "⚠️ Connection established but identity could not be verified.");
	}

	// ✅ Example PPPoE check
	$pppoe_secret = 'sb744rony';
	$ppp_id = getPPPoEIdFsock($fp, $pppoe_secret);
	log_message('info', "🔁 Retrying enablePPPoEUserFsock for PPPoE Secret '{$pppoe_secret}' using PPP ID {$ppp_id}");

	// ✅ Fetch active PPP users
	writeSentence($fp, ["/ppp/active/print"]);

	$active = [];

	while (true) {
		$resp = readSentence($fp);

		if (empty($resp)) {
			log_message('error', "FSOCK connection closed unexpectedly during updateUser check.");
			break;
		}
		if ($resp[0] === '!done')
			break;

		if ($resp[0] === '!re') {
			$entry = [];
			foreach ($resp as $word) {
				if (strpos($word, '=') === 0) {
					list($k, $v) = explode('=', substr($word, 1), 2);
					$entry[$k] = $v;
				}
			}
			$active[] = $entry;
		}
	}

	fclose($fp);

	if (empty($active)) {
		log_message('info', 'ℹ️ No active PPPoE users found.');
	} else {
		log_message('info', '✅ Active PPPoE Users: ' . json_encode($active, JSON_PRETTY_PRINT));
	}

	log_message('info', 'updateUser: job ended successfully at ' . date('Y-m-d H:i:s'));
	return $active;
}




// if (!function_exists('enablePPPoEUser')) {
//     function enablePPPoEUser($client, $ppp_id)
//     {
//         log_message('info', "Enabling PPPoE User: $ppp_id");

//         // Enable the PPPoE secret
//         $query = (new RouterOS\Query('/ppp/secret/enable'))->equal('numbers', $ppp_id);
//         $client->query($query)->read();
//         log_message('info', "PPP Secret Enabled for User: $ppp_id");

//         // Remove any firewall blocking rule for this user
//         $firewallQuery = (new RouterOS\Query('/ip/firewall/filter/print'))->where('src-address', $ppp_id);
//         $firewallRules = $client->query($firewallQuery)->read();

//         if (!empty($firewallRules)) {
//             foreach ($firewallRules as $rule) {
//                 if (isset($rule['.id'])) {
//                     $removeFirewall = (new RouterOS\Query('/ip/firewall/filter/remove'))->equal('.id', $rule['.id']);
//                     $client->query($removeFirewall)->read();
//                     log_message('info', "Removed Firewall Block for User: $ppp_id");
//                 }
//             }
//         }

//         log_message('info', "User $ppp_id can now access the internet.");

//         return true;
//     }
// }



/**
 * Disable PPPoE User
 */
// if (!function_exists('disablePPPoEUser')) {
// 	function disablePPPoEUser($client, $ppp_id)
// 	{

// 		$query = (new RouterOS\Query('/ppp/secret/disable'))->equal('numbers', $ppp_id);

// 		$client->query($query)->read();

// 		return true;
// 	}
// }


if (!function_exists('disablePPPoEUser')) {
	function disablePPPoEUser($client, $ppp_id, $username = null)
	{
		log_message('info', "Starting to disable PPPoE user. ID: $ppp_id, Username: $username");

		// 🔍 1. Identify the username if not provided
		if (empty($username)) {
			$user = getPPPoEUser($client, $ppp_id);
			$username = $user[0]['name'] ?? null;
		}

		if (empty($username)) {
			log_message('error', "No username found to disable for PPPoE ID: $ppp_id");
			// If we still have an ID, try to disable by ID at least
			if (!empty($ppp_id)) {
				try {
					$client->query((new RouterOS\Query('/ppp/secret/disable'))->equal('.id', $ppp_id))->read();
					return true;
				} catch (\Throwable $e) {
					return false;
				}
			}
			return false;
		}

		log_message('info', "Identified user: $username for disabling.");

		try {
			// 🔐 2. Disable local secret (if exists)
			try {
				$client->query((new RouterOS\Query('/ppp/secret/disable'))->equal('numbers', $username))->read();
				log_message('info', "PPP Secret Disabled for: $username");
			} catch (\Throwable $secretEx) {
				log_message('warning', "Could not disable local secret for $username (likely a RADIUS-only user): " . $secretEx->getMessage());
			}

			// 🔌 3. Remove active session (Force Disconnect)
			$activeQuery = (new RouterOS\Query('/ppp/active/print'))->where('name', $username);
			$activeUsers = $client->query($activeQuery)->read();

			if (!empty($activeUsers)) {
				foreach ($activeUsers as $session) {
					if (isset($session['.id'])) {
						$client->query((new RouterOS\Query('/ppp/active/remove'))->equal('.id', $session['.id']))->read();
						log_message('info', "Dropped active session for: $username (Session ID: {$session['.id']})");
					}
				}
			} else {
				log_message('info', "No active sessions found for: $username");
			}

			return true;
		} catch (\Throwable $e) {
			log_message('error', "Error while disabling user $username: " . $e->getMessage());
			return false;
		}
	}
}



// if (!function_exists('disablePPPoEUser')) {
// 	function disablePPPoEUser($client, $ppp_id)
// 	{
// 		log_message('info', "disablePPPoEUser: switching PPPoE user ID: $ppp_id to Expired_Profile");

// 		// Get user details from router
// 		$user = getPPPoEUser($client, $ppp_id);
// 		$username = $user[0]['name'] ?? null;
// 		$secretId = $user[0]['.id'] ?? null;
// 		$currentProfile = $user[0]['profile'] ?? '';
// 		$currentComment = $user[0]['comment'] ?? '';

// 		if (empty($username) || empty($secretId)) {
// 			log_message('error', "disablePPPoEUser: No user found for PPPoE ID: $ppp_id");
// 			return false;
// 		}

// 		log_message('info', "disablePPPoEUser: Found user=$username profile=$currentProfile");

// 		// If already expired, just kick active session — avoid double-processing
// 		if ($currentProfile === 'Expired_Profile') {
// 			log_message('info', "disablePPPoEUser: $username already on Expired_Profile, kicking session only");
// 		}
// 		else {
// 			// Save original profile in comment so we can restore after payment
// 			$marker = 'orig_profile=';
// 			if (strpos($currentComment, $marker) === false) {
// 				$newComment = $marker . $currentProfile;
// 				if (!empty($currentComment)) {
// 					$newComment .= ' | ' . $currentComment;
// 				}

// 				// Store original profile into the PPPoE secret comment
// 				$client->query(
// 					(new RouterOS\Query('/ppp/secret/set'))
// 					->equal('.id', $secretId)
// 					->equal('comment', $newComment)
// 				)->read();
// 			}

// 			// Switch profile to Expired_Profile
// 			// User can still connect — but gets 10.10.10.x IP and captive portal
// 			$client->query(
// 				(new RouterOS\Query('/ppp/secret/set'))
// 				->equal('.id', $secretId)
// 				->equal('profile', 'Expired_Profile')
// 			)->read();

// 			log_message('info', "disablePPPoEUser: Switched $username → Expired_Profile (was: $currentProfile)");
// 		}

// 		// Kick active session — forces user to reconnect with new profile immediately
// 		$activeQuery = (new RouterOS\Query('/ppp/active/print'))->where('name', $username);
// 		$activeUsers = $client->query($activeQuery)->read();

// 		if (!empty($activeUsers)) {
// 			foreach ($activeUsers as $session) {
// 				if (isset($session['.id'])) {
// 					$client->query(
// 						(new RouterOS\Query('/ppp/active/remove'))
// 						->equal('.id', $session['.id'])
// 					)->read();
// 					log_message('info', "disablePPPoEUser: Kicked session for $username → will reconnect to Expired_Profile");
// 				}
// 			}
// 		}
// 		else {
// 			log_message('info', "disablePPPoEUser: No active sessions found for $username");
// 		}

// 		return true;
// 	}
// }



/**
 * Remove PPPoE User
 */
if (!function_exists('removePPPoEUser')) {
	function removePPPoEUser($client, $ppp_id)
	{

		$query = (new RouterOS\Query('/ppp/secret/remove'))->equal('numbers', $ppp_id);

		$client->query($query)->read();

		return true;
	}
}

/**
 * Update PPPoE User
 */

if (!function_exists('updatePPPoEUser')) {
	function updatePPPoEUser($client, $data)
	{

		if (!empty(getPPPoEUser($client, $data['pppoe_id'])[0]['name'])) {

			$query = (new RouterOS\Query('/ppp/secret/set'))
				->equal('name', $data['pppoe_name'])
				->equal('password', $data['pppoe_password'])
				->equal('service', $data['pppoe_service'])
				->equal('profile', $data['pppoe_profile'])
				//->equal('local-address', '104.105.87.200')
				//->equal('remote-address', '104.105.87.200')
				->equal('.id', $data['pppoe_id']);

			$client->query($query)->read();
		} else {

			unset($data["pppoe_id"]);

			$action = createPPPoEUser($client, $data);

			return $action;
		}

		return [
			'status' => 'success',
			'pppoe_id' => $data['pppoe_id']
		];
	}
}


// Helper method to check if a user is active
function checkIfUserIsActiveArray($routerId, $pppoe_id)
{

	$router_client = routerClient($routerId);

	if (!is_array($router_client)) {
		$user_ppp = getPPPoEUser($router_client, $pppoe_id);
		$ppoe = $user_ppp[0]['disabled'] ?? '--';
		// log_message('info', 'ppoe status activity: ' . $ppoe);

		return $ppoe;
	}
	$ppoe = 'true';

	return $ppoe;
}

function usersactivity()
{
	log_message('info', 'users activity : executing automatically');

	$user_model = model('App\Models\User');
	$router_model = model('App\Models\Router');
	$routerDataModel = model('App\Models\UserRouterDataModel'); // move out of loop for efficiency

	// Step 1: Get all active routers
	$routers = $router_model->where('status', 'active')->findAll();

	foreach ($routers as $router) {
		log_message('info', "Processing Router ID: {$router->id} - {$router->name}");
		try {
			$router_client = routerClient($router->id);
			if (!$router_client) {
				log_message('error', "Router connection failed for Router ID: {$router->id}");
				continue; // skip this router
			}

			// Step 2: Get all active users from this router
			$users = $user_model->where('router_id', $router->id)->where('role', 'user')->findAll();
			log_message('info', "Found " . count($users) . " users for Router ID: {$router->id}");
			if (empty($users))
				continue;

			// Step 3: Get active PPPoE users from the router
			$active_user_data = getactive_user($router_client);
			$active_ids = array_column($active_user_data['data']['activeusers'] ?? [], 'name');

			// Step 4: Prepare bulk update
			$updateData = [];

			foreach ($users as $user) {
				$routerData = $routerDataModel->where('user_id', $user->id)->first();
				if (empty($routerData)) {
					log_message('warning', "No router data found for User ID: {$user->id}");
					continue;
				}

				// routerData might be an array, access accordingly
				$pppoe_id = is_array($routerData) ? $routerData['pppoe_secret'] : $routerData->pppoe_secret;

				$isActive = in_array($pppoe_id, $active_ids, true);
				$updateData[] = [
					'id' => $user->id, // primary key
					'activity' => $isActive ? 'active' : 'inactive'
				];

				log_message('info', "User ID {$user->id} (Router: {$router->id}) Activity: " . ($isActive ? 'active' : 'inactive'));
			}

			// Step 5: Bulk update
			if (!empty($updateData)) {
				$user_model->updateBatch($updateData, 'id');
				log_message('info', "Bulk update completed for Router ID {$router->id} (" . count($updateData) . " users).");
			}
		} catch (\Throwable $e) {
			log_message('error', "Error processing Router ID {$router->id}: " . $e->getMessage());
		}
	}

	log_message('info', 'Users activity update completed.');
}

function checkIfUserIsActive($user)
{
	// $userModel = model('App\Models\User');
	// $details = $userModel->where(['id' => $id, 'role' => 'user'])->first();
	if (is_object($user)) {
		$routerId = $user->router_id;
		$pppoe_id = $user->pppoe_id;
	}
	// Check if $user is an array
	elseif (is_array($user)) {
		$routerId = $user['router_id'];
		$pppoe_id = $user['pppoe_id'];
	}
	// Handle unexpected $user type
	else {
		// log_message('error', 'Expected object or array, got: ' . gettype($user));
		return false; // Return false if neither an array nor object
	}

	$router_client = routerClient($routerId);

	if (!is_array($router_client)) {
		$user_ppp = getPPPoEUser($router_client, $pppoe_id);
		$ppoe = $user_ppp[0]['disabled'] ?? '--';
		// log_message('info', 'ppoe status activity: ' . $ppoe);

		return $ppoe;
	}
	$ppoe = 'true';

	return $ppoe;
}

/**
 * Get Interface
 */
if (!function_exists('getInterface')) {
	function getInterface($client)
	{
		// Check if the $client is valid
		if ($client === null) {
			log_message('error', 'RouterOS client is not initialized.');
			return 'n/a'; // Return some fallback value or handle the error
		}

		// Proceed if the $client is initialized correctly
		$query = (new RouterOS\Query('/interface/print'));

		try {
			$interfaces = $client->query($query)->read() ?? 'n/a';
		} catch (\Throwable $e) {
			log_message('error', 'Error while fetching interfaces: ' . $e->getMessage());
			return 'n/a';
		}

		return $interfaces ?? null;
	}
}


if (!function_exists('getactive_user')) {
	function getactive_user($client)
	{


		$active_user_query = (new RouterOS\Query('/ppp/active/print'));
		// $total_user_query = (new RouterOS\Query('/ppp/secret/print'));

		// $traffic_query = new RouterOS\Query('/interface/monitor-traffic');

		// if(!empty($interface)) {

		// 	$traffic_query->equal('interface', $interface);
		// }

		// $traffic_query->equal('once');

		$active_users = $client->query($active_user_query)->read();
		// $total_users = $client->query($total_user_query)->read();



		// $traffic_data = getTrafficByIpOrMac($client, $address, $caller_id);
		// log_message('debug', 'System active_users ' . print_r($active_users, true));

		return [
			"data" => [

				'activeusers' => $active_users,
				// 'allusers' => $total_users,

			],
		];
	}
}

/**
 * Get system resources
 */
if (!function_exists('getSystemResourcess')) {
	function getSystemResourcess($client, $interface = null, $ppoe = null)
	{


		$active_user_query = (new RouterOS\Query('/ppp/active/print'));
		$total_user_query = (new RouterOS\Query('/ppp/secret/print'));

		if (!empty($ppoe)) {
			$active_user_query->where('name', $ppoe);
			$total_user_query->where('name', $ppoe);
		}

		$traffic_query = new RouterOS\Query('/interface/monitor-traffic');

		if (!empty($interface)) {

			$traffic_query->equal('interface', $interface);
		}

		$traffic_query->equal('once');

		if ($client !== null) {
			$active_users = $client->query($active_user_query)->read();
			$total_users = $client->query($total_user_query)->read();
		} else {
			log_message('error', 'Router client is null. Cannot query active/total users.');
			$active_users = [];
			$total_users = [];
		}


		// $traffic_data = getTrafficByIpOrMac($client, $address, $caller_id);
		//log_message('debug', 'System traffic_data Resources Data: ' . print_r($traffic_data, true));

		return [
			"data" => [

				'activeusers' => $active_users,
				'allusers' => $total_users,

			],
		];
	}
}






if (!function_exists('getSystemResources')) {
	function getSystemResources($client, $interface = null)
	{
		// Guard: null or invalid client
		if (!$client || !is_object($client)) {
			return [
				'data' => [
					'active' => 0,
					'users' => 0,
					'activeusers' => [],
					'allusers' => [],
					'traffic' => ['rxbyte' => 0, 'txbyte' => 0, 'date' => gmdate(DATE_ISO8601)],
				],
			];
		}

		// --- 1. System Resources ---
		$resources = [];
		try {
			$resources = $client->query(new RouterOS\Query('/system/resource/print'))->read();
		} catch (\Throwable $e) {
			log_message('warning', 'getSystemResources: /system/resource/print failed — ' . $e->getMessage());
		}
		$res0 = $resources[0] ?? [];

		// --- 2. Active PPPoE Users ---
		$active_users = [];
		try {
			$active_users = $client->query(new RouterOS\Query('/ppp/active/print'))->read();
			if (!is_array($active_users)) {
				$active_users = [];
			}
		} catch (\Throwable $e) {
			log_message('warning', 'getSystemResources: /ppp/active/print failed — ' . $e->getMessage());
		}

		// --- 2b. Active Hotspot Users ---
		try {
			$active_hotspot = $client->query(new RouterOS\Query('/ip/hotspot/active/print'))->read();
			if (is_array($active_hotspot)) {
				foreach ($active_hotspot as $ah) {
					$active_users[] = [
						'.id' => $ah['.id'] ?? '',
						'name' => $ah['user'] ?? $ah['name'] ?? '',
						'address' => $ah['address'] ?? '',
						'mac-address' => $ah['mac-address'] ?? '',
						'uptime' => $ah['uptime'] ?? '',
						'type' => 'hotspot'
					];
				}
			}
		} catch (\Exception $e) {
			log_message('warning', 'getSystemResources: /ip/hotspot/active/print failed — ' . $e->getMessage());
		}

		// --- 3. Total PPPoE Secrets ---
		$total_users = [];
		try {
			$total_users = $client->query(new RouterOS\Query('/ppp/secret/print'))->read();
			if (!is_array($total_users)) {
				$total_users = [];
			}
		} catch (\Throwable $e) {
			log_message('warning', 'getSystemResources: /ppp/secret/print failed — ' . $e->getMessage());
		}

		// --- 3b. Total Hotspot Users ---
		try {
			$total_hotspot = $client->query(new RouterOS\Query('/ip/hotspot/user/print'))->read();
			if (is_array($total_hotspot)) {
				foreach ($total_hotspot as $th) {
					$total_users[] = [
						'.id' => $th['.id'] ?? '',
						'name' => $th['name'] ?? '',
						'profile' => $th['profile'] ?? '',
						'type' => 'hotspot'
					];
				}
			}
		} catch (\Exception $e) {
			log_message('warning', 'getSystemResources: /ip/hotspot/user/print failed — ' . $e->getMessage());
		}

		// --- 4. Interface Traffic (most likely to hang — isolated) ---
		$traffic = [];
		try {
			$traffic_query = new RouterOS\Query('/interface/monitor-traffic');
			if (!empty($interface)) {
				$traffic_query->equal('interface', $interface);
			}
			$traffic_query->equal('once');
			$traffic = $client->query($traffic_query)->read();
		} catch (\Throwable $e) {
			log_message('warning', 'getSystemResources: /interface/monitor-traffic failed — ' . $e->getMessage());
		}

		// --- 5. Routerboard info ---
		$routerboard = [];
		try {
			$routerboard = $client->query(new RouterOS\Query('/system/routerboard/print'))->read();
		} catch (\Throwable $e) {
			log_message('warning', 'getSystemResources: /system/routerboard/print failed — ' . $e->getMessage());
		}

		// --- 6. License info ---
		$license = [];
		try {
			$license = $client->query(new RouterOS\Query('/system/license/print'))->read();
		} catch (\Throwable $e) {
			log_message('warning', 'getSystemResources: /system/license/print failed — ' . $e->getMessage());
		}

		return [
			"data" => [
				'clock' => [
					'date' => date("d/m/Y"),
					'time' => date('h:i:s a'),
				],
				'resource' => [
					'up-time' => $res0['uptime'] ?? '--',
					'free-memory' => isset($res0['free-memory']) ? round($res0['free-memory'] / 1048576) . " MB" : '--',
					'total-memory' => isset($res0['total-memory']) ? round($res0['total-memory'] / 1048576) . " MB" : '--',
					'free-hdd-space' => isset($res0['free-hdd-space']) ? round($res0['free-hdd-space'] / 1048576) . " MB" : '--',
					'total-hdd-space' => isset($res0['total-hdd-space']) ? round($res0['total-hdd-space'] / 1048576) . " MB" : '--',
					'cpu-load' => $res0['cpu-load'] ?? '--',
					'version' => $res0['version'] ?? '--',
					'board-name' => $res0['board-name'] ?? '--',
					'cpu' => $res0['cpu'] ?? '--',
					'cpu-count' => $res0['cpu-count'] ?? '1',
					'architecture-name' => $res0['architecture-name'] ?? '--',
					'serial-number' => $routerboard[0]['serial-number'] ?? '--',
					'firmware' => $routerboard[0]['current-firmware'] ?? '--',
					'software-id' => $license[0]['software-id'] ?? '--',
					'cpu-frequency' => $res0['cpu-frequency'] ?? '--',
					'build-time' => $res0['build-time'] ?? '--',
				],
				'active' => count($active_users),
				'users' => count($total_users),
				'activeusers' => $active_users,
				'allusers' => $total_users,
				'traffic' => [
					'rxbyte' => !empty($traffic[0]) ? ($traffic[0]['rx-bits-per-second'] / 1000000) : 0,
					'txbyte' => !empty($traffic[0]) ? ($traffic[0]['tx-bits-per-second'] / 1000000) : 0,
					'date' => gmdate(DATE_ISO8601),
				],
			],
		];
	}
}
if (!function_exists('getusersSystemResources')) {
	function getusersSystemResources($client, $pppoeName, $interface)
	{

		$res_query = (new RouterOS\Query('/system/resource/print'));

		$active_user_query = (new RouterOS\Query('/ppp/active/print'));
		$total_user_query = (new RouterOS\Query('/ppp/secret/print'));

		$traffic_query = new RouterOS\Query('/interface/monitor-traffic');
		// log_message('debug', 'System pppoeName my Data: ' . print_r($pppoeName, true));

		$interface = '<pppoe-' . $pppoeName . '>';
		// log_message('debug', 'System interface name : ' . print_r($interface, true));

		if (!empty($pppoeName)) {

			$traffic_query->equal('interface', $interface);
		} else {
			return null;
		}

		$traffic_query->equal('once');


		if (!$client) {
			return []; // or handle it gracefully
		}

		$traffic_data = $client->query($traffic_query)->read();





		if (empty($traffic_data[0]['name'])) {
			log_message('debug', 'System trafic empty : ');
			// return requestResponse('error','my error', 500);
			return ["data" => ['active' => 'error']];
		}

		// MikroTik DNS cache query
		$query = new RouterOS\Query('/ip/dns/cache/print');

		try {

			$dnsCache = $client->query($query)->read();
			// log_message('info', 'System MikroTik DNS cache query results Data: ' . print_r($dnsCache, true));
			// Format results: just domain and IP address
			$results = array_map(function ($entry) {

				return [
					'name' => $entry['name'] ?? 'unknown',
					'data' => $entry['data'] ?? 'N/A',
					'ttl' => $entry['ttl'] ?? '',
					'static' => $entry['static'] ?? false,
				];
			}, $dnsCache);
		} catch (\Throwable $e) {
			// log_message('error', 'System MikroTik DNS cache query results Data: ' . print_r($e->getMessage(), true));

		}
		// log_message('debug', 'System MikroTik DNS cache query results Data: ' . print_r($results, true));

		return [
			"data" => [

				'results' => $results,
				'traffic' => [
					'rxbyte' => $traffic_data[0]['rx-bits-per-second'] / 1000000 ?? 0.00,
					'txbyte' => $traffic_data[0]['tx-bits-per-second'] / 1000000 ?? 0.00,
					'date' => $traffic_data['traffic']['date'] ?? gmdate(DATE_ISO8601),
				],




			],
		];
	}
}

function getTrafficByIpOrMac($client, $address = null, $caller_id = null, $interface = null)
{
	// Log the initial state of the client, address, and interface
	// log_message('debug', 'System client Resources Data: ' . print_r($client, true));
	// log_message('debug', 'System address Resources Data: ' . $address);
	// log_message('debug', 'System interface Resources Data: ' . $interface);

	// Create a traffic query
	$traffic_query = new RouterOS\Query('/interface/monitor-traffic');

	// Apply the interface filter
	if (!empty($interface)) {
		$traffic_query->equal('interface', $interface);
	}



	// Apply filtering by address or MAC
	if (!empty($address)) {
		// log_message('debug', 'Applying address filter: ' . $address);
		$traffic_query->where('address', $address);
	} elseif (!empty($caller_id)) {
		// log_message('debug', 'Applying MAC filter: ' . $caller_id);
		$traffic_query->where('mac-address', $caller_id);
	} else {
		// log_message('error', 'No address or caller ID provided');
		return ['error' => 'No address or caller ID provided'];
	}

	// Log the query after applying the address or MAC filters
	// log_message('debug', 'Traffic Query after applying address or MAC filter: ' . $traffic_query->getQuery());

	// Apply the 'once' condition to the query
	$traffic_query->equal('once');

	// Execute the query after applying filters
	$traffic = $client->query($traffic_query)->read();

	// Log the traffic data retrieved after applying the filter
	// log_message('debug', 'Traffic data after applying address or MAC filter: ' . print_r($traffic, true));

	// Return the traffic data in Mbps
	return [
		'traffic' => [
			'rxbyte' => !empty($traffic[0]) ? $traffic[0]['rx-bits-per-second'] / 1000000 : 0,
			'txbyte' => !empty($traffic[0]) ? $traffic[0]['tx-bits-per-second'] / 1000000 : 0,
			'date' => gmdate(DATE_ISO8601),
		]
	];
}


/**
 * Set router identity
 */

if (!function_exists('setRouterIdentity')) {
	function setRouterIdentity($client, $name)
	{

		$query = (new RouterOS\Query('/system/identity/set'))->equal('name', $name);

		$result = $client->query($query)->read();

		return $result;
	}
}


/**
 * Get Logs
 */
if (!function_exists('getLogs')) {
	function getLogs($client)
	{
		// If the client is not initialized, handle gracefully
		if (!$client) {
			log_message('error', 'RouterOS client is not initialized.');
			return null; // or return an empty array to avoid breaking the caller
		}

		try {
			$query = (new RouterOS\Query('/log/print'));
			$logs = $client->query($query)->read();
			return $logs ?? null;
		} catch (\Throwable $e) {
			log_message('critical', 'Failed to get logs: ' . $e->getMessage());
			return null;
		}
	}

	if (!function_exists('pingUser')) {
		function pingUser($router_id, $name, $count = 25)
		{
			$router_client = routerClient($router_id);

			if (!$router_client) {
				return [
					'status' => 'error',
					'message' => 'Router client is not initialized.'
				];
			}

			// Get active users and locate target IP
			$activeUsers = $router_client->query('/ppp/active/print')->read();
			$target_ip = null;

			foreach ($activeUsers as $u) {
				if ($u['name'] === $name) {
					$target_ip = $u['address'];
					break;
				}
			}

			if (!$target_ip) {
				return [
					'status' => 'error',
					'message' => "User $name is not active or has no IP assigned"
				];
			}

			try {
				$query = (new RouterOS\Query('/ping'))
					->equal('address', $target_ip)
					->equal('count', $count);

				$results = $router_client->query($query)->read();

				// Extract latency times
				$times = array_column($results, 'time');
				$latency = array_map(fn($t) => (int) preg_replace('/[^0-9]/', '', $t), $times);
				$avg = count($latency) ? array_sum($latency) / count($latency) : null;

				// Extract ping statistics
				$sent = 0;
				$received = 0;
				$loss = 0;

				foreach ($results as $r) {
					if (isset($r['sent'])) {
						$sent = (int) $r['sent'];
					}
					if (isset($r['received'])) {
						$received = (int) $r['received'];
					}
					if (isset($r['packet-loss'])) {
						$loss = (int) $r['packet-loss'];
					}
				}

				log_message('info', "Pinged $target_ip: Sent=$sent, Received=$received, Loss=$loss%, Avg Latency=" . ($avg ? $avg . ' ms' : 'N/A'));
				return [
					'status' => 'success',
					'data' => $results,
					'average_latency' => $avg ? $avg . ' ms' : 'N/A',
					'packets' => [
						'sent' => $sent,
						'received' => $received,
						'loss' => $loss . '%'
					]
				];
			} catch (\Throwable $e) {
				return [
					'status' => 'error',
					'message' => $e->getMessage()
				];
			}
		}
	}

	function isCDNIP($ip)
	{
		$ranges = [
			['start' => '31.13.0.0', 'end' => '31.13.255.255'], // Facebook CDN
			['start' => '157.240.0.0', 'end' => '157.240.255.255'], // Meta (Facebook/Instagram)
			['start' => '104.16.0.0', 'end' => '104.31.255.255'], // Cloudflare CDN
			['start' => '172.217.0.0', 'end' => '172.217.255.255'], // Google CDN
			['start' => '13.224.0.0', 'end' => '13.227.255.255'], // Amazon CloudFront
			// Add more ranges as needed
		];

		$ipLong = ip2long($ip);
		if ($ipLong === false) {
			return false;
		}

		foreach ($ranges as $range) {
			$start = ip2long($range['start']);
			$end = ip2long($range['end']);
			if ($ipLong >= $start && $ipLong <= $end) {
				return true;
			}
		}
		return false;
	}


	function userTrafficLog()
	{
		$pppoeName = '150jahagir'; // Replace with actual PPPoE username 34006632
		// Assuming $router_client is already connected
		$router_client = routerClient(10); // Replace 1 with actual router ID or parameter

		try {
			$query = (new RouterOS\Query('/ppp/active/print'))->where('name', $pppoeName);
			$activeUsers = $router_client->query($query)->read();

			if (!empty($activeUsers)) {
				$userIp = $activeUsers[0]['address'];
				log_message('info', "PPPoE User {$pppoeName} IP: {$userIp}");

				$connQuery = (new RouterOS\Query('/ip/firewall/connection/print'))->where('src-address', $userIp);
				$connections = $router_client->query($connQuery)->read();

				foreach ($connections as $conn) {
					$dstIp = $conn['dst-address'] ?? '-';
					$dstPort = $conn['dst-port'] ?? '-';
					$protocol = $conn['protocol'] ?? '-';

					log_message('info', "PPPoE {$pppoeName} -> Dest IP: {$dstIp}, Port: {$dstPort}, Protocol: {$protocol}");

					if (in_array($dstPort, ['80', '443']) && isCDNIP($dstIp)) {
						log_message('info', "Image/media data - {$pppoeName} -> {$dstIp}:{$dstPort} ({$protocol})");
					}
				}
			} else {
				log_message('info', "PPPoE user {$pppoeName} is not active or not found.");
			}
		} catch (\Throwable $e) {
			log_message('error', "Error fetching traffic for {$pppoeName}: " . $e->getMessage());
		}
	}












	function getMacFromRouter($user)
	{
		$router_client = routerClient($user->router_id);

		// -----------------------
		// 1️⃣ Try using PPPoE ID first (API)
		// -----------------------
		if (!empty($user->pppoe_id) && $router_client instanceof \RouterOS\Client) {
			try {
				$query = new \RouterOS\Query('/ppp/secret/print');
				$activeUsers = $router_client->query($query)->read();

				foreach ($activeUsers as $entry) {
					if (isset($entry['.id']) && $entry['.id'] == $user->pppoe_id) {
						return $entry['last-caller-id'] ?? null;
					}
				}
			} catch (\Throwable $e) {
				// API failed → fallback
			}
		}

		// -----------------------
		// 2️⃣ If not found, fallback to PPPoE secret from database
		// -----------------------
		$router_model = model('App\Models\UserRouterDataModel');
		$data = $router_model->where('user_id', $user->id)->first();

		if (!$data) {
			return null; // No data found in DB
		}

		$pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;

		// -----------------------
		// 3️⃣ API METHOD using secret
		// -----------------------
		if ($router_client instanceof \RouterOS\Client) {
			try {
				$query = new \RouterOS\Query('/ppp/secret/print');
				$activeUsers = $router_client->query($query)->read();

				foreach ($activeUsers as $entry) {
					if (isset($entry['name']) && $entry['name'] == $pppoe_secret) {
						return $entry['last-caller-id'] ?? null;
					}
				}
			} catch (\Throwable $e) {
				// API failed → fallback
			}
		}

		// -----------------------
		// 4️⃣ FSOCK METHOD fallback
		// -----------------------
		$fp = connect_using_Fsocket($user->router_id);
		if ($fp) {
			$cmd = "/ppp/active/print";
			$response = write_and_read_fsock($fp, $cmd);
			$lines = explode("\n", $response);

			foreach ($lines as $line) {
				if (
					strpos($line, "name=\"{$pppoe_secret}\"") !== false ||
					(!empty($user->pppoe_id) && strpos($line, ".id={$user->pppoe_id}") !== false)
				) {
					if (preg_match('/caller-id="([0-9A-F:]+)"/i', $line, $match)) {
						if (is_resource($fp)) fclose($fp);
						return $match[1];
					}
				}
			}
			if (is_resource($fp)) fclose($fp);
		}

		return null; // MAC not found
	}


	/**
	 * Check if a user has MAC bound
	 *
	 * @param object $user User object containing at least router_id and pppoe_id
	 * @return array ['status' => true/false, 'mac' => 'xx:xx:xx:xx:xx:xx' or null]
	 */
	function isMacBound($user_id)
	{
		$user_model = model('App\Models\User');
		$user = $user_model->where('id', $user_id)->first();

		if (!$user) {
			log_message('error', "User ID {$user_id} not found");
			return ['status' => false, 'mac' => null];
		}

		$router_client = routerClient($user->router_id);
		log_message('info', "Checking MAC bind for User ID {$user_id} on Router ID {$user->router_id}");

		// -----------------------
		// 1️⃣ Try using PPPoE ID first (API)
		// -----------------------
		if (!empty($user->pppoe_id) && $router_client instanceof \RouterOS\Client) {
			log_message('info', "Trying to check MAC via PPPoE ID: {$user->pppoe_id}");
			try {
				$query = new \RouterOS\Query('/ppp/secret/print');
				$activeUsers = $router_client->query($query)->read();

				foreach ($activeUsers as $entry) {
					if (isset($entry['.id']) && $entry['.id'] == $user->pppoe_id) {
						$mac = $entry['caller-id'] ?? null;
						log_message('info', "MAC found via PPPoE ID: " . ($mac ?? 'not set'));
						return ['status' => !empty($mac), 'mac' => $mac];
					}
				}
				log_message('info', "MAC not found via PPPoE ID, fallback to secret");
			} catch (\Throwable $e) {
				log_message('error', "Router API check via PPPoE ID failed: " . $e->getMessage());
			}
		}

		// -----------------------
		// 2️⃣ Fallback: Get PPPoE secret from DB
		// -----------------------
		$router_model = model('App\Models\UserRouterDataModel');
		$data = $router_model->where('user_id', $user->id)->first();

		if (!$data) {
			log_message('error', "No PPPoE secret found in DB for User ID {$user_id}");
			return ['status' => false, 'mac' => null];
		}

		$pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;
		log_message('info', "Using PPPoE secret from DB: {$pppoe_secret}");

		// -----------------------
		// 3️⃣ Check via API using secret
		// -----------------------
		if ($router_client instanceof \RouterOS\Client) {
			try {
				$query = new \RouterOS\Query('/ppp/secret/print');
				$query->equal('name', $pppoe_secret);
				$result = $router_client->query($query)->read();

				if (!empty($result[0]['caller-id'])) {
					log_message('info', "MAC found via PPPoE secret API: " . $result[0]['caller-id']);
					return ['status' => true, 'mac' => $result[0]['caller-id']];
				}
				log_message('info', "MAC not set via PPPoE secret API");
			} catch (\Throwable $e) {
				log_message('error', "Router API check via secret failed: " . $e->getMessage());
			}
		}

		// -----------------------
		// 4️⃣ FSOCK fallback
		// -----------------------
		$fp = connect_using_Fsocket($user->router_id);
		if ($fp) {
			log_message('info', "Checking MAC via FSOCK for secret: {$pppoe_secret}");
			$cmd = "/ppp/secret/print where name=$pppoe_secret";
			$response = write_and_read_fsock($fp, $cmd);
			$lines = explode("\n", $response);

			foreach ($lines as $line) {
				if (strpos($line, "name=\"$pppoe_secret\"") !== false) {
					if (preg_match('/caller-id="([0-9A-F:]+)"/i', $line, $match)) {
						log_message('info', "MAC found via FSOCK: " . $match[1]);
						fclose($fp);
						return ['status' => true, 'mac' => $match[1]];
					}
				}
			}
			log_message('info', "MAC not set via FSOCK for secret: {$pppoe_secret}");
			fclose($fp);
		}

		// Not found
		log_message('info', "MAC not bound for User ID {$user_id}");
		return ['status' => false, 'mac' => null];
	}

	function unbindMacAPI($router_client, $pppoe_id = null, $pppoe_secret = null)
	{
		if (!$router_client instanceof \RouterOS\Client)
			return false;

		try {
			$query = new \RouterOS\Query('/ppp/secret/set');

			if ($pppoe_id) {
				$query->equal('.id', $pppoe_id)->equal('caller-id', '');
			} elseif ($pppoe_secret) {
				$query->equal('name', $pppoe_secret)->equal('caller-id', '');
			} else {
				return false; // Nothing to unbind
			}

			$router_client->query($query)->read();
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}
	function unbindMacFsock($fp, $pppoe_id = null)
	{
		if (!$fp || !$pppoe_id)
			return false;

		$cmd = "/ppp/secret/set .id=$pppoe_id caller-id="; // empty caller-id removes bind
		$response = write_and_read_fsock($fp, $cmd);

		return (strpos($response, "!done") !== false);
	}
	function removeMacBind($user_id)
	{
		$user_model = model('App\Models\User');
		$user = $user_model->where('id', $user_id)->first();
		if (!$user)
			return false;

		$router_client = routerClient($user->router_id);

		// Try API first
		if ($router_client instanceof \RouterOS\Client) {
			$success = unbindMacAPI($router_client, $user->pppoe_id);
			if ($success)
				return true;

			// Fallback: get secret from DB
			$router_model = model('App\Models\UserRouterDataModel');
			$data = $router_model->where('user_id', $user->id)->first();
			$pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;

			return unbindMacAPI($router_client, null, $pppoe_secret);
		}

		// FSOCK fallback
		$fp = connect_using_Fsocket($user->router_id);
		if ($fp) {
			$result = unbindMacFsock($fp, $user->pppoe_id);
			if (is_resource($fp)) fclose($fp);
			return $result;
		}

		return false;
	}




	function bindMacForUser($user_id)
	{

		$user_model = model('App\Models\User');
		$user_details = $user_model->where('id', $user_id)->first();

		log_message('info', "Starting MAC bind process for User ID: {$user_id}");
		// Get router client
		$router_client = routerClient($user_details->router_id);
		$user = getUserById($user_details->id);

		$pppoe = getPPPoEUserUserId($router_client, $user->id);
		$pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;

		log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}");



		// Get MAC from database (or however you store it)
		$macAddress = getMacFromRouter($user_details);
		log_message('info', "Binding MAC for User ID {$user_id}: Found MAC = {$macAddress}");
		if (!$macAddress) {
			return ['status' => false, 'message' => 'MAC not found from router (User must be online)'];
		}



		// -----------------------
		// CASE 1: Use RouterOS\Client (API)
		// -----------------------
		if ($router_client instanceof \RouterOS\Client) {

			// FIRST TRY: Bind using pppoe_id
			if (!empty($user->pppoe_id)) {


				$result = bindMacAPI($router_client, $pppoe_id, $macAddress);
				if ($result)
					return ['status' => true, 'message' => 'MAC Bind Successful (API)'];
			}

			// FALLBACK: Lookup PPPoE Secret if pppoe_id missing
			$router_model = model('App\Models\UserRouterDataModel');
			$data = $router_model->where('user_id', $user->id)->first();
			$pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;

			// Try binding using secret name
			$result = bindMacAPI_bySecret($router_client, $pppoe_secret, $macAddress);

			if ($result) {
				return ['status' => true, 'message' => 'MAC Bind Successful (API by Secret)'];
			}
		}

		// -----------------------
		// CASE 2: Use FSOCK (fallback)
		// -----------------------

		// Connect via fsock
		$fp = connect_using_Fsocket($user_details->router_id);

		if (!$fp) {
			return ['status' => false, 'message' => 'Router connection failed (Fsock)'];
		}

		// If PPPoE ID available
		if (!empty($user_details->pppoe_id)) {
			$result = bindMacFsock($fp, $pppoe_id, $macAddress);
			if ($result) {
				if (is_resource($fp)) fclose($fp);
				return ['status' => true, 'message' => 'MAC Bind Successful (Fsock)'];
			}
		}

		// Fallback: Get PPPoE ID using secret
		$router_model = model('App\Models\UserRouterDataModel');
		$data = $router_model->where('user_details_id', $user_details->id)->first();
		$pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;

		$ppp_id = getPPPoEIdFsock_test($fp, $pppoe_secret);

		// Final attempt
		$result = bindMacFsock($fp, $ppp_id, $macAddress);

		if ($result) {
			if (is_resource($fp)) fclose($fp);
			return ['status' => true, 'message' => 'MAC Bind Successful (Fsock by Secret)'];
		}

		if (is_resource($fp)) fclose($fp);
		return ['status' => false, 'message' => 'MAC Bind Failed'];
	}

	function bindMacAPI($client, $pppoe_id, $mac)
	{
		try {
			$query = (new \RouterOS\Query('/ppp/secret/set'))
				->equal('.id', $pppoe_id)
				->equal('caller-id', $mac);

			$client->query($query)->read();
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}
	function bindMacAPI_bySecret($client, $secret_name, $mac)
	{
		try {
			$query = (new \RouterOS\Query('/ppp/secret/set'))
				->equal('name', $secret_name)
				->equal('caller-id', $mac);

			$client->query($query)->read();
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}

	function bindMacFsock($fp, $ppp_id, $mac)
	{
		if (!$ppp_id || !$mac)
			return false;

		$cmd = "/ppp/secret/set .id=$ppp_id caller-id=$mac";
		$response = write_and_read_fsock($fp, $cmd);

		return (strpos($response, "!done") !== false);
	}

	function getPPPoEIdFsock_test($fp, $secret_name)
	{
		$cmd = "/ppp/secret/print where name=$secret_name";
		$response = write_and_read_fsock($fp, $cmd);

		preg_match('/\*([0-9A-F]+)/', $response, $match);

		return $match[1] ?? null;
	}

	function write_and_read_fsock($fp, $cmd)
	{
		if (!$fp)
			return false;

		fwrite($fp, $cmd . "\n");

		$response = "";
		$startTime = microtime(true);

		stream_set_blocking($fp, false); // important: no blocking

		while (true) {
			if (microtime(true) - $startTime > 3) {
				break; // 3-second timeout
			}

			$chunk = fread($fp, 1024);

			if ($chunk !== false && $chunk !== "") {
				$response .= $chunk;

				if (strpos($response, '!done') !== false) {
					break;
				}
			}

			usleep(20000);
		}

		return $response;
	}

	// Helper function for random string from custom charset
	function generateRandomString($length, $chars)
	{
		$result = '';
		$charsLength = strlen($chars);
		if ($charsLength === 0)
			return '';
		for ($i = 0; $i < $length; $i++) {
			$result .= $chars[rand(0, $charsLength - 1)];
		}
		return $result;
	}
}

/**
 * BUG-22: pppoe_secret extraction was copy-pasted verbatim across Bkash_webhook,
 * CustomerPayment, DeviceService, DiagnosticsService, and QueueWork.
 * Single source of truth — returns null if no row or no secret is stored.
 */
if (!function_exists('resolvePppoeSecret')) {
    function resolvePppoeSecret(int $userId): ?string
    {
        $rd = model('App\Models\UserRouterDataModel')->where('user_id', $userId)->first();
        if (!$rd) return null;
        return is_array($rd) ? ($rd['pppoe_secret'] ?? null) : ($rd->pppoe_secret ?? null);
    }
}
