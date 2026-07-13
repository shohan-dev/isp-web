<?php

namespace Zapi\Modules\Customer\User\Controllers;

use App\Libraries\RouterService;
use Zapi\Core\Base\BaseApiController;
use Zapi\utils\JwtToken;

class RouterTrafficController extends BaseApiController
{
    protected $user_model;
    protected static $router_client;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
    }

    private function getJwtClaims(): array
    {
        $authHeader = (string) $this->request->getHeaderLine('Authorization');
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return [];
        }
        $token = trim(substr($authHeader, 7));
        $payload = JwtToken::verify($token, \Zapi\utils\JwtToken::secret());
        return is_array($payload) ? $payload : [];
    }

    public function loadTraffic($id)
    {
        log_message('debug', 'System interface its here load: ' . $id);
        self::$router_client = routerClient($id);

        /* Identity comes from the verified JWT only. These used to read
           ?user_id= / ?user_role= FIRST and fall back to the claims, so any
           authenticated customer could call
           ...?user_role=admin and skip the `$userRole !== 'admin'` branch below,
           receiving every subscriber's pppoe_id and live session data on the
           router instead of just their own. A caller must never be able to name
           their own identity or role. */
        $claims = $this->getJwtClaims();
        $userId = $claims['sub'] ?? null;
        $userRole = $claims['role'] ?? null;
        log_message('debug', 'User ID: ' . $userId);
        log_message('debug', 'User Role: ' . $userRole);

        $pppoeIds = [];

        if ($userRole !== 'admin') {
            $details = $this->user_model
                ->select('pppoe_id')
                ->where(['admin_id' => $userId])
                ->findAll();

            $pppoeIds = array_filter(array_map(function ($item) {
                return trim((string) ($item->pppoe_id ?? ''));
            }, $details));

            log_message('debug', 'Filtered PPPoE IDs: ' . print_r($pppoeIds, true));
        }

        if (!is_array(self::$router_client)) {
            $interface = getGetInput('interface') ?? null;

            // If interface is specified, use it. Otherwise try to find a sensible default.
            if (empty($interface) || $interface === 'WAN') {
                $interfaces = getInterface(self::$router_client);
                if (is_array($interfaces)) {
                    foreach ($interfaces as $iface) {
                        if (
                            stripos($iface['name'], 'wan') !== false
                            || stripos($iface['name'], 'sfp') !== false
                            || stripos($iface['name'], 'ether1') !== false
                        ) {
                            $interface = $iface['name'];
                            break;
                        }
                    }
                    if ((empty($interface) || $interface === 'WAN') && !empty($interfaces[0]['name'])) {
                        $interface = $interfaces[0]['name'];
                    }
                }
            }

            $data = getSystemResources(self::$router_client, $interface);
            $payload = is_array($data['data'] ?? null) ? $data['data'] : [];

            if (!empty($payload['traffic'])) {
                $payload['active_interface'] = $interface;
            }

            if (
                isset($payload['allusers'])
                && is_array($payload['allusers'])
                && $userRole !== 'admin'
            ) {
                $filteredUsers = [];
                $filteredActiveUsers = [];
                $validNames = [];

                foreach ($payload['allusers'] as $user) {
                    $itemId = trim((string) ($user['.id'] ?? ''));
                    $itemName = trim((string) ($user['name'] ?? ''));

                    if (in_array($itemId, $pppoeIds, true)) {
                        $filteredUsers[] = $user;
                        if ($itemName !== '') {
                            $validNames[] = $itemName;
                        }
                    }
                }

                if (isset($payload['activeusers']) && is_array($payload['activeusers'])) {
                    foreach ($payload['activeusers'] as $activeUser) {
                        $activeName = trim((string) ($activeUser['name'] ?? ''));

                        if (in_array($activeName, $validNames, true)) {
                            $filteredActiveUsers[] = $activeUser;
                        } else {
                            log_message('debug', "Did NOT match active user name: $activeName");
                        }
                    }
                }

                $payload['allusers'] = $filteredUsers;
                $payload['activeusers'] = $filteredActiveUsers;
                $payload['users'] = count($filteredUsers);
                $payload['active'] = count($filteredActiveUsers);
            }

            return $this->respondSuccess($payload);
        }

        log_message('error', 'RouterOS client is not initialized.');
        $errorMsg = is_array(self::$router_client)
            ? (self::$router_client['error'] ?? 'Connection failed')
            : 'Connection failed';

        return $this->respondError($errorMsg, 503, 'ROUTER_CONNECTION_FAILED', [
            'users' => 0,
            'active' => 0,
        ]);
    }

    public function UsersloadTraffic($id)
    {
        self::$router_client = RouterService::getClient($id);

        if (is_array(self::$router_client)) {
            $errorMsg = self::$router_client['error'] ?? 'Connection failed';
            log_message('error', 'UsersloadTraffic router connection failed: ' . $errorMsg);
            return $this->respondError($errorMsg, 503, 'ROUTER_CONNECTION_FAILED');
        }

        $pppoeName = trim((string) ($this->request->getGet('pppoe_name') ?? ''));
        $normalized = strtolower($pppoeName);
        $invalidPppoe = $pppoeName === '' || in_array($normalized, ['--', 'null', 'n/a', 'na', 'none'], true);
        if ($invalidPppoe) {
            // Keep traffic endpoint safe for callers that may still have placeholder PPPoE.
            // Returning success with zero traffic avoids hard client failures.
            return $this->respondSuccess([
                'rxbyte' => 0.0,
                'txbyte' => 0.0,
                'date' => gmdate(DATE_ISO8601),
                'unit' => 'Mbps',
                'unavailable' => true,
                'reason' => 'INVALID_PPPOE_NAME',
            ]);
        }

        $interface = '';
        $resources = getusersSystemResources(self::$router_client, $pppoeName, $interface);

        $traffic = is_array($resources['data']['traffic'] ?? null)
            ? $resources['data']['traffic']
            : null;

        if ($traffic === null) {
            log_message('debug', 'UsersloadTraffic: traffic data unavailable for pppoe ' . $pppoeName);
            return $this->respondError('Traffic data unavailable', 503, 'TRAFFIC_UNAVAILABLE', [
                'rxbyte' => 0,
                'txbyte' => 0,
            ]);
        }

        $traffic['rxbyte'] = isset($traffic['rxbyte']) ? (float) $traffic['rxbyte'] : 0.0;
        $traffic['txbyte'] = isset($traffic['txbyte']) ? (float) $traffic['txbyte'] : 0.0;
        $traffic['date'] = $traffic['date'] ?? gmdate(DATE_ISO8601);

        return $this->respondSuccess($traffic);
    }

    public function UsersloadTraffic_api($id)
    {
        return $this->UsersloadTraffic($id);
    }
}
