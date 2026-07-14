<?php

namespace App\Libraries;

use Exception;
use RouterOS\Client;

class RouterService
{
    /**
     * Holds cached RouterOS\Client instances per request
     *
     * @var array<int, Client>
     */
    private static array $clients = [];

    /**
     * File to store cached router connection info for 1 minute
     */
    private static string $cacheFile = WRITEPATH . 'router_client_cache.json';


    /**
     * Get (or create) a cached router client
     *
     * @param int|array|object $router Router ID, or router record (array/object)
     * @return Client|null
     */
    public static function getClient($router): ?Client
    {
        // If ID was passed, fetch full router info
        if (is_numeric($router)) {
            $router = getRouterById((int) $router);
            if (!$router) {
                log_message('error', "❌ Invalid router ID: {$router}");
                return null;
            }
        }

        // Normalize router ID
        $id = is_array($router) ? $router['id'] : $router->id;

        // Check if client exists in memory for this request
        if (isset(self::$clients[$id])) {
            log_message('info', "♻️ Reusing Router client from memory for ID {$id}");
            return self::$clients[$id];
        }

        // Load cached connection info
        $cache = [];
        if (file_exists(self::$cacheFile)) {
            $cache = json_decode(file_get_contents(self::$cacheFile), true);
        }

        $useCache = false;
        if (isset($cache[$id])) {
            $diff = time() - $cache[$id]['timestamp'];
            if ($diff < 60) {
                // Cache is valid
                $host = $cache[$id]['host'];
                $user = $cache[$id]['user'];
                $pass = $cache[$id]['pass'];
                $port = $cache[$id]['port'];
                $useCache = true;
            }
        }

        // If no valid cache, extract connection details from router
        if (!$useCache) {
            $host = is_array($router) ? $router['host'] : $router->host;
            $user = is_array($router) ? $router['username'] : $router->username;
            $pass = is_array($router) ? $router['password'] : $router->password;
            $port = is_array($router) ? $router['port'] : $router->port;
            $port = !empty($port) ? (int)$port : 8728; // default API port
            
            // Save connection info for 1 minute
            $cache[$id] = [
                'timestamp' => time(),
                'host'      => $host,
                'user'      => $user,
                'pass'      => $pass,
                'port'      => $port,
            ];
            file_put_contents(self::$cacheFile, json_encode($cache));
        }

        // Try connecting quickly
        $attempts = 0;
        $maxAttempts = 1;
        $client = null;

        while ($attempts < $maxAttempts) {
            try {
                // Recreate the client quickly using cached info
                $client = new Client([
                    'host' => $cache[$id]['host'],
                    'user' => $cache[$id]['user'],
                    'pass' => $cache[$id]['pass'],
                    'port' => $cache[$id]['port'],
                    'timeout' => 3,
                    'socket_timeout' => 3,
                    'attempts' => 1
                ]);

                self::$clients[$id] = $client;
                log_message('debug', "🆕 New Router client created and cached for ID {$id}");
                break;
            } catch (\Throwable $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    log_message(
                        'error',
                        "Router connection failed for ID {$id}: " . $e->getMessage()
                    );
                }
            }
        }

        return $client;
    }
}
