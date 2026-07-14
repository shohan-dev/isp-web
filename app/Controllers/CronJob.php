<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\Config as DatabaseConfig;

use \RouterOS\Client;
use RouterOS\Query;
use Exception;
use DateTime;

class CronJob extends BaseController
{

    protected $user_model;

    private $radiusServerIp;
    private $radiusSecret;

    public function __construct()
    {
        date_default_timezone_set('Asia/Dhaka');
        $this->user_model = model('App\Models\User');
        helper('sms');
        helper('router');

        // Fetch Radius Settings
        $this->radiusServerIp = getSetting('radius_server_ip', '203.18.158.157');
        $this->radiusSecret = getSetting('radius_secret', 'ISP_Secret_123');
    }

    /**
     * Ensures the MikroTik router is configured to use RADIUS
     */
    private function ensureRadiusSetup($routerId)
    {
        static $checkedRouters = [];
        if (isset($checkedRouters[$routerId]))
            return;

        $client = routerClient($routerId);
        if (!$client || is_array($client))
            return;

        try {
            // Check if RADIUS is already enabled for PPP
            $pppAaa = $client->query('/ppp/aaa/print')->read();
            $useRadius = $pppAaa[0]['use-radius'] ?? 'no';

            if ($useRadius !== 'yes') {
                $this->cron_log("Configuring RADIUS on Router ID: $routerId...");

                // 1. Add RADIUS server if not exists
                $radiusServers = $client->query('/radius/print')->read();
                $exists = false;
                foreach ($radiusServers as $server) {
                    if (($server['address'] ?? '') === $this->radiusServerIp) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $client->query('/radius/add', [
                        'service' => 'ppp',
                        'address' => $this->radiusServerIp,
                        'secret' => $this->radiusSecret,
                        'comment' => 'Added by ISP Core'
                    ])->read();
                }

                // 2. Enable RADIUS for PPP
                $client->query('/ppp/aaa/set', ['use-radius' => 'yes'])->read();

                // 3. Enable Incoming requests
                $client->query('/radius/incoming/set', ['accept' => 'yes'])->read();

                $this->cron_log("RADIUS configured successfully on Router ID: $routerId");
            }
        } catch (\Throwable $e) {
            $this->cron_log("Failed to setup RADIUS on Router $routerId: " . $e->getMessage(), 'error');
        }

        $checkedRouters[$routerId] = true;
    }

    /**
     * Helper to write cron logs to custom folder + CI logs
     */
    private function cron_log($message, $level = 'info')
    {
        // Write to CI log (always safe, handled by framework)
        log_message($level, $message);

        // Prepare cron log folder
        $folder = WRITEPATH . 'cronJob';
        if (!is_dir($folder)) {
            @mkdir($folder, 0777, true);
        }

        // Daily log file
        $file = $folder . '/cron_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');

        $level = strtoupper($level);
        $line = "[$timestamp] [$level] $message" . PHP_EOL;

        // Safely write to custom log file only if permitted, to avoid permission crash
        if (is_writable($folder) && (!file_exists($file) || is_writable($file))) {
            @file_put_contents($file, $line, FILE_APPEND);
            if (file_exists($file)) {
                @chmod($file, 0666); // Ensure both web server and cron CLI can write to it
            }
        }
    }

    public function customer_data_usages()
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $user_model      = model('App\Models\User');
        $routerDataModel = model('App\Models\UserRouterDataModel');
        $date            = date('Y-m-d');
        $buffer          = new \App\Services\UsageBufferService();

        $this->cron_log('customer_data_usages: job started at ' . date('Y-m-d H:i:s'));

        // Get all unique routers.
        $routers = $user_model->select('router_id')->distinct()
            ->where('router_id IS NOT NULL')
            ->findAll();

        $this->cron_log('customer_data_usages: Found ' . count($routers) . ' routers to process.');

        foreach ($routers as $r) {
            $router_id = $r->router_id;

            $routerUsers = $user_model->where('router_id', $router_id)
                ->whereIn('role', ['user'])
                ->where('pppoe_id IS NOT NULL')
                ->findAll();

            if (empty($routerUsers)) {
                continue;
            }

            $this->cron_log("customer_data_usages: router_id $router_id - processing " . count($routerUsers) . " users");

            // F4/BUG-12: load pppoe_secrets for THIS router's users only — not the whole
            // table. At 20k users a global findAll() materialises the full table on every
            // cron tick; scoping to router_users keeps memory O(router-batch-size).
            $routerUserIds = array_column(
                array_map(fn($u) => is_object($u) ? ['id' => $u->id] : $u, $routerUsers),
                'id'
            );
            $routerSecretRows = $routerDataModel->whereIn('user_id', $routerUserIds)->findAll();
            $secretByUser = [];
            foreach ($routerSecretRows as $rd) {
                $uid = (int) (is_object($rd) ? $rd->user_id : $rd['user_id']);
                $sec = (string) (is_object($rd) ? ($rd->pppoe_secret ?? '') : ($rd['pppoe_secret'] ?? ''));
                if ($uid > 0 && $sec !== '') {
                    $secretByUser[$uid] = $sec;
                }
            }
            unset($routerSecretRows);

            $router_client = routerClient($router_id);
            if (!is_object($router_client)) {
                $this->cron_log("customer_data_usages: router_id $router_id - connection failed", 'error');
                continue;
            }

            try {
                // ONE MikroTik query per router — batch fetch all PPPoE interfaces.
                $allInterfacesRaw = $router_client->query(new \RouterOS\Query('/interface/print'))->read();
            } catch (\Throwable $e) {
                $this->cron_log("customer_data_usages: router_id $router_id - interface query failed: " . $e->getMessage(), 'error');
                continue;
            }

            $allInterfaces = [];
            foreach ($allInterfacesRaw as $intf) {
                if (isset($intf['name'])) {
                    $allInterfaces[$intf['name']] = $intf;
                }
            }

            $buffered     = 0;
            $dbFallback   = [];

            foreach ($routerUsers as $user) {
                $user_id    = (int) $user->id;
                $pppoe_name = $secretByUser[$user_id] ?? $user->pppoe_id ?? null;

                if (!$pppoe_name) {
                    continue;
                }

                $interfaceBase  = "pppoe-{$pppoe_name}";
                $interfaceQuery = "<{$interfaceBase}>";
                $foundInterface = $allInterfaces[$interfaceQuery] ?? $allInterfaces[$interfaceBase] ?? null;

                if (!$foundInterface) {
                    continue;
                }

                $rxMB = round(($foundInterface['rx-byte'] ?? 0) / 1048576, 2);
                $txMB = round(($foundInterface['tx-byte'] ?? 0) / 1048576, 2);

                // Prev-day baseline: Redis first (0 DB queries on warm cache),
                // falls back to a single DB query only on the first poll of the day.
                $prev     = $buffer->getPrevBaseline($user_id, $date);
                $rxPrev   = $prev['rx_mb'] ?? 0.0;
                $txPrev   = $prev['tx_mb'] ?? 0.0;

                if ($prev !== null && $rxMB >= $rxPrev && $txMB >= $txPrev) {
                    $rxToday = $rxMB - $rxPrev;
                    $txToday = $txMB - $txPrev;
                } else {
                    // Router reboot or first entry of the day.
                    $rxToday = $rxMB;
                    $txToday = $txMB;
                }

                $row = [
                    'admin_id'  => $user_id,
                    'user_name' => $pppoe_name,
                    'interface' => $interfaceBase,
                    'date'      => $date,
                    'rx_mb'     => $rxMB,
                    'tx_mb'     => $txMB,
                    'rx_today'  => $rxToday,
                    'tx_today'  => $txToday,
                ];

                // Try to buffer in Redis (no DB write here).
                try {
                    $buffer->bufferRow($row);
                    $buffered++;
                } catch (\Throwable $e) {
                    // Redis unavailable — collect for direct DB write (fail-safe).
                    $dbFallback[] = $row;
                }
            }

            $this->cron_log("customer_data_usages: router_id $router_id - buffered {$buffered} rows in Redis.");

            // Fail-safe: write directly if Redis was unavailable for any rows.
            if (!empty($dbFallback)) {
                $this->cron_log("customer_data_usages: router_id $router_id - DB fallback for " . count($dbFallback) . " rows.", 'warning');
                $this->_directDbWrite($dbFallback, $date, $router_id);
            }
        }

        $this->cron_log('customer_data_usages: job completed at ' . date('Y-m-d H:i:s'));
    }

    /**
     * Fail-safe direct DB upsert used when Redis is unavailable.
     * Mirrors the old batch-insert/update logic exactly.
     */
    private function _directDbWrite(array $rows, string $date, $routerId): void
    {
        $usage_model     = model('App\Models\UserDataUsageModel');
        $userIds         = array_column($rows, 'admin_id');
        $existingRecords = [];

        try {
            $existingData = $usage_model->where('date', $date)
                ->whereIn('admin_id', $userIds)
                ->findAll();
            foreach ($existingData as $row) {
                $existingRecords[(int) (is_object($row) ? $row->admin_id : $row['admin_id'])] =
                    (int) (is_object($row) ? $row->id : $row['id']);
            }
        } catch (\Throwable $e) {
            $this->cron_log("customer_data_usages: router $routerId - DB read for fallback failed: " . $e->getMessage(), 'error');
        }

        $toInsert = [];
        $toUpdate = [];

        foreach ($rows as $row) {
            $uid = (int) $row['admin_id'];
            if (isset($existingRecords[$uid])) {
                $row['id'] = $existingRecords[$uid];
                $toUpdate[] = $row;
            } else {
                $toInsert[] = $row;
            }
        }

        if (!empty($toInsert)) {
            try {
                $usage_model->insertBatch($toInsert);
            } catch (\Throwable $e) {
                $this->cron_log("customer_data_usages: router $routerId - DB insert fallback failed: " . $e->getMessage(), 'error');
            }
        }
        if (!empty($toUpdate)) {
            try {
                $usage_model->updateBatch($toUpdate, 'id');
            } catch (\Throwable $e) {
                $this->cron_log("customer_data_usages: router $routerId - DB update fallback failed: " . $e->getMessage(), 'error');
            }
        }
    }






    /**
     * Flush Redis-buffered usage data for today (and optionally yesterday) to DB.
     * Registered as 'usage-flush' in CronRun so it runs under a singleton lock.
     * Schedule: every hour, e.g. "0 * * * * php spark cron:run usage-flush".
     */
    public function flushUsage(): void
    {
        $buffer  = new \App\Services\UsageBufferService();
        $today   = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        foreach ([$today, $yesterday] as $date) {
            $result = $buffer->flushToDb($date);
            $this->cron_log(sprintf(
                'usage:flush date=%s flushed=%d skipped=%d errors=%d',
                $date,
                $result['flushed'],
                $result['skipped'],
                $result['errors']
            ), $result['errors'] > 0 ? 'error' : 'info');
        }
    }

    /**
     * Purge recycle_bin rows past expires_at in bounded batches.
     * Registered as 'purge-trash' in CronRun. Schedule daily.
     */
    public function purgeTrash(): void
    {
        $service = new \App\Services\TrashService();
        $total   = 0;

        do {
            $batch = $service->purgeExpired(1000);
            $total += $batch;
        } while ($batch === 1000);

        $this->cron_log("purge-trash: purged {$total} expired recycle_bin row(s)", 'info');
    }

    function backupDatabaseAndSendEmail()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $this->cron_log('→ Starting database backup and email send');

        $dbConfig = new DatabaseConfig();
        $db = db_connect();

        $database = $db->database;
        $fileName = $database . '_' . date('Ymd_His') . '.sql';

        // Define public and writable backup paths
        $publicBackupDir = FCPATH . 'backups';
        $writableBackupDir = WRITEPATH . 'backups';

        // Ensure both directories exist
        if (!is_dir($publicBackupDir)) {
            mkdir($publicBackupDir, 0755, true);
        }
        if (!is_dir($writableBackupDir)) {
            mkdir($writableBackupDir, 0755, true);
        }

        // DB credentials from .env
        $creds = parse_ini_file(ROOTPATH . '.env');
        $host = $creds['database.default.hostname'] ?? 'localhost';
        $user = $creds['database.default.username'] ?? '';
        $pass = $creds['database.default.password'] ?? '';
        $port = $creds['database.default.port'] ?? '3306';

        // Determine mysqldump path (Handle XAMPP on F: or C: drive)
        $dumpPath = 'mysqldump';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Check current drive (F: or C:)
            $projectDrive = substr(ROOTPATH, 0, 3); // e.g. "f:\"
            $xamppPath = $projectDrive . 'Xampp\\mysql\\bin\\mysqldump.exe';

            if (file_exists($xamppPath)) {
                $dumpPath = '"' . $xamppPath . '"';
            } else {
                // Fallback to C: if F: fails
                $fallbackPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
                if (file_exists($fallbackPath)) {
                    $dumpPath = '"' . $fallbackPath . '"';
                }
            }
        }

        // Build command (Handle empty password)
        $passwordPart = !empty($pass) ? "--password=" . escapeshellarg($pass) : "";

        // --- Try saving in writable first ---
        $primaryBackupFile = $writableBackupDir . DIRECTORY_SEPARATOR . $fileName;
        $cmd = "{$dumpPath} --user=" . escapeshellarg($user) . " {$passwordPart} --host=" . escapeshellarg($host) . " --port=" . escapeshellarg($port) . " " . escapeshellarg($database) . ' > ' . escapeshellarg($primaryBackupFile);

        system($cmd, $status);

        if ($status === 0 && file_exists($primaryBackupFile)) {
            $this->cron_log('✔️ Backup saved to writable: ' . $primaryBackupFile);
            $finalFile = $primaryBackupFile;
        } else {
            $this->cron_log('⚠️ Writable backup failed, trying public folder...', 'warning');

            // --- Try saving directly in public as fallback ---
            $publicBackupFile = $publicBackupDir . DIRECTORY_SEPARATOR . $fileName;
            $cmd = "{$dumpPath} --user=" . escapeshellarg($user) . " {$passwordPart} --host=" . escapeshellarg($host) . " --port=" . escapeshellarg($port) . " " . escapeshellarg($database) . ' > ' . escapeshellarg($publicBackupFile);

            system($cmd, $status);

            if ($status === 0 && file_exists($publicBackupFile)) {
                $this->cron_log('✔️ Backup saved to public: ' . $publicBackupFile);
                $finalFile = $publicBackupFile;
            } else {
                $this->cron_log('✖️ Database backup failed in both writable and public', 'error');
                return false;
            }
        }

        // --- NEW: Send to Telegram Bot ---
        $this->sendBackupToTelegram($finalFile, $fileName);
    }

    /**
     * Sends the backup file to a Telegram Bot
     */
    private function sendBackupToTelegram($filePath, $fileName)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId   = env('TELEGRAM_CHAT_ID');
        if (!$botToken || !$chatId) {
            $this->cron_log('✖️ Telegram credentials not configured (TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID)', 'error');
            return;
        }

        $this->cron_log("→ Sending backup to Telegram Bot...");

        $url = "https://api.telegram.org/bot{$botToken}/sendDocument";

        $postFields = [
            'chat_id' => $chatId,
            'caption' => "🚀 Database Backup: " . date('Y-m-d H:i:s'),
            'document' => new \CURLFile(realpath($filePath), 'application/octet-stream', $fileName)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // backup upload may be large
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->cron_log("✖️ Telegram Error: " . $err, 'error');
        } else {
            $res = json_decode($result, true);
            if (isset($res['ok']) && $res['ok']) {
                $this->cron_log("✔️ Backup sent to Telegram successfully!");
                // Security: Delete the local file after sending
                if (file_exists($filePath)) {
                    unlink($filePath);
                    $this->cron_log("🗑️ Local backup file deleted for security.");
                }
            } else {
                $this->cron_log("✖️ Telegram failed: " . ($res['description'] ?? 'Unknown error'), 'error');
            }
        }
    }





    /**
     * CronJob
     * @action: Disable User on Overdue
     */

    public function backupDatabaseFromEnv()
    {
        // Load .env file
        $env = parse_ini_file(ROOTPATH . '.env');
        $savePath = '/path/to/save';
        $host = $env['database.default.hostname'] ?? 'localhost';
        $username = $env['database.default.username'] ?? 'root';
        $password = $env['database.default.password'] ?? '';
        $database = $env['database.default.database'] ?? '';

        $this->cron_log('Database Host: ' . $host);
        if (empty($database)) {
            echo "Database name is missing!";
            return;
        }

        // Generate file name
        //  $fileName = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
        //  $fullPath = rtrim($savePath, '/') . '/' . $fileName;

        //  // Build command
        //  $command = "mysqldump --user={$username} --password=\"{$password}\" --host={$host} {$database} > {$fullPath}";

        //  system($command, $output);

        //  if ($output === 0) {
        //      echo "Database backup successful: " . $fullPath;
        //  } else {
        //      echo "Database backup failed.";
        //  }
    }

    // Usage Example
    //  backupDatabaseFromEnv('/path/to/save'); // like '/var/www/html/backups'

    function updateUser_activity()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $today = date('Y-m-d H:i:s');
        $user_model = model('App\Models\User');

        $users = $user_model
            ->where('subscription_status', 'active')
            ->where('conn_status', 'conn')
            ->where('will_expire >', $today)
            ->where("activity", 'inactive')
            // ->like('last_renewed', $today)  // matches today
            ->findAll();
        $this->cron_log('Found ' . count($users) . ' users to enable PPPoE.');
        // $this->cron_log('dataa ' . print_r($users, true));

        $usersByRouter = [];
        foreach ($users as $user) {
            $usersByRouter[$user->router_id][] = $user;
        }

        foreach ($usersByRouter as $router_id => $routerUsers) {
            $this->cron_log("updateUser_activity: Processing " . count($routerUsers) . " users for Router ID: $router_id");

            $router_client = routerClient($router_id);
            if (empty($router_client) || is_array($router_client)) {
                // Fallback for missing client
                try {
                    foreach ($routerUsers as $user) {
                        $pppoe = getPPPoEUserUserId($router_client, $user->id);
                        $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;
                        if ($pppoe_id && enablePPPoEUserFsock($user->router_id, $pppoe_id)) {
                            $user_model->update($user->id, ['activity' => 'active']);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->cron_log("Error on router $router_id (fsock fallback): " . $e->getMessage(), 'error');
                }
                continue;
            }

            try {
                // Batch fetch secrets
                $secrets = $router_client->query(new \RouterOS\Query('/ppp/secret/print'))->read();
                $secretsByComment = [];
                foreach ($secrets as $s) {
                    if (isset($s['comment']))
                        $secretsByComment[$s['comment']] = $s;
                }

                foreach ($routerUsers as $user) {
                    $pppoe_id = $secretsByComment[$user->id]['.id'] ?? null;
                    if (!$pppoe_id) {
                        $pppoe = getPPPoEUserUserId($router_client, $user->id);
                        $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;
                    }

                    if ($pppoe_id && enablePPPoEUser($router_client, $pppoe_id)) {
                        $user_model->update($user->id, ['activity' => 'active']);
                        $this->cron_log("Successfully enabled PPPoE user for User ID {$user->id}");
                    } else {
                        // Fallback by secret name
                        $router_model = model('App\Models\UserRouterDataModel');
                        $data = $router_model->where('user_id', $user->id)->first();
                        if ($data) {
                            $pppoe_secret = $data ? (is_array($data) ? ($data['pppoe_secret'] ?? null) : ($data->pppoe_secret ?? null)) : null;
                            if (enablePPPoEUser_by_pppoe_secret($router_client, $pppoe_secret)) {
                                $user_model->update($user->id, ['activity' => 'active']);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->cron_log("Error on router $router_id: " . $e->getMessage(), 'error');
            }
        }
    }




    public function index()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $currentTime = date('Y-m-d H:i:s');
        $timezone = date_default_timezone_get();

        $this->cron_log("Starting automatic user disable cronjob. Time: {$currentTime}, Timezone: {$timezone}");

        $failedRouters = [];
        $counts = [
            'total_expired' => 0,
            'disabled_success' => 0,
            'case2' => 0,
            'case4' => 0,
        ];

        // CASE 1 & 3: Fetch all expired active users in memory first
        $allExpiredActive = $this->user_model
            ->where('will_expire <=', $currentTime)
            ->where('subscription_status', 'active')
            ->where('role', 'user')
            ->findAll();

        $counts['total_expired'] = count($allExpiredActive);

        if ($counts['total_expired'] > 0) {
            $this->cron_log("Batch updating {$counts['total_expired']} expired users in database to inactive...");
            $db = \Config\Database::connect();
            $db->query("
                UPDATE users 
                SET subscription_status = 'inactive', status = 'inactive' 
                WHERE will_expire <= ? 
                  AND subscription_status = 'active' 
                  AND role = 'user'
            ", [$currentTime]);
        }

        foreach ($allExpiredActive as $user) {
            // Reset timeout countdown to 30 seconds for this user
            set_time_limit(30);

            // Ensure RADIUS is setup on this router (ONLY if enabled in settings)
            if (getSetting('enable_radius', 'no') === 'yes') {
                $this->ensureRadiusSetup($user->router_id);
            }

            // Log for debugging
            $this->cron_log("Checking expired user ID: {$user->id} | Name: {$user->name} | Auto-Disconnect: '{$user->auto_disconnect}'");

            $routerDisabled = false;
            $shouldDisconnect = ($user->auto_disconnect === 'yes');

            if ($shouldDisconnect) {
                if (isset($failedRouters[$user->router_id])) {
                    $this->cron_log("Skipping Mikrotik disconnect for user {$user->id} because router {$user->router_id} is currently marked as failed.");
                } else {
                    // Try to disable in MikroTik
                    $routerDisabled = $this->disableRouterConnection($user, $failedRouters);
                }
            } else {
                $this->cron_log("Bypassing Mikrotik disconnect for user {$user->id} because auto_disconnect is NOT 'yes' (Value is: '{$user->auto_disconnect}')");
            }

            // Update connection status if MikroTik disconnect succeeded
            if ($routerDisabled) {
                $this->user_model->update($user->id, ['conn_status' => 'expired']);
                $this->cron_log("Updated conn_status to expired for user {$user->id} on Mikrotik.");
            }
            $counts['disabled_success']++;

            // Handle notifications and payments
            $this->handleNotifications($user);
            $this->createPaymentRecord($user);
        }

        // CASE 2: Inactive in DB but potentially still connected in MikroTik (Cleanup Case)
        $case2Users = $this->user_model
            ->where('auto_disconnect', 'yes')
            ->where('subscription_status', 'inactive')
            ->groupStart()
            ->where('conn_status !=', 'disconn')
            ->where('conn_status !=', 'expired')
            ->orWhere('activity', 'active')
            ->groupEnd()
            ->where('role', 'user')
            ->findAll();

        // --- DIAGNOSTIC LOGGING FOR CASE 2 ---
        $allInactiveConnected = $this->user_model
            ->where('subscription_status', 'inactive')
            ->groupStart()
            ->where('conn_status !=', 'disconn')
            ->where('conn_status !=', 'expired')
            ->orWhere('activity', 'active')
            ->groupEnd()
            ->where('role', 'user')
            ->findAll();

        foreach ($allInactiveConnected as $pe) {
            if ($pe->auto_disconnect !== 'yes') {
                $this->cron_log("⚠️ SKIPPED from Case 2 (Inactive but still connected/active): ID: {$pe->id} | Name: {$pe->name} | Auto Disconnect: '{$pe->auto_disconnect}' | Router ID: {$pe->router_id}", 'warning');
            }
        }
        // ---------------------------

        $counts['case2'] = count($case2Users);
        foreach ($case2Users as $user) {
            // Reset timeout countdown to 30 seconds for this user
            set_time_limit(30);

            if (isset($failedRouters[$user->router_id]))
                continue;
            $this->disableFromMikrotik($user, $failedRouters);
        }

        // CASE 4: sAdmin users - Update subscription status only
        $case4Users = $this->user_model
            ->where('will_expire <', $currentTime)
            ->where('subscription_status', 'active')
            ->where('role', 'admin')
            ->findAll();

        $counts['case4'] = count($case4Users);
        $this->cron_log("Found {$counts['case4']} sAdmin users to update.");
        foreach ($case4Users as $user) {
            $this->updateSAdminStatus($user);
        }

        $this->cron_log("Cronjob Summary: TotalExpiredFound: {$counts['total_expired']}, DisabledSuccess: {$counts['disabled_success']}, InactiveButConnected: {$counts['case2']}, Case4: {$counts['case4']}");
        $this->cron_log('Cronjob completed successfully');

        if (!empty($failedRouters)) {
            $this->cron_log("Failed routers (skipped): " . implode(', ', array_keys($failedRouters)), 'warning');
        }
    }

    private function disableActiveConnectedUser($user, &$failedRouters)
    {
        $this->cron_log("CASE 1: Disabling ACTIVE+CONNECTED user {$user->id} from Mikrotik");

        // Disable router connection first
        $routerDisabled = $this->disableRouterConnection($user, $failedRouters);

        // ALWAYS update subscription status for expired users, even if router is down
        $updateData = [
            'subscription_status' => 'inactive'
        ];

        if ($routerDisabled) {
            $updateData['conn_status'] = 'expired';
        }

        $this->user_model->update($user->id, $updateData);

        if ($routerDisabled) {
            $this->cron_log("Successfully disabled active+connected user {$user->id} from Mikrotik and updated status");
        } else {
            // Check if router was marked as failed due to authentication
            if (isset($failedRouters[$user->router_id])) {
                $this->cron_log("🔐 Router {$user->router_id} AUTH FAIL. User {$user->id} marked INACTIVE in DB anyway.", 'warning');
            } else {
                $this->cron_log("⚠️ User {$user->id} marked INACTIVE in DB, but Mikrotik disable failed. Cleanup required later.", 'warning');
            }
        }

        // Handle notifications and payments
        $this->handleNotifications($user);
        $this->createPaymentRecord($user);

        return $routerDisabled;
    }

    private function disableFromMikrotik($user, &$failedRouters)
    {
        $this->cron_log("CASE 2: Disabling INACTIVE+CONNECTED user {$user->id} from Mikrotik");

        // Disable router connection
        $routerDisabled = $this->disableRouterConnection($user, $failedRouters);

        if ($routerDisabled) {
            // Update connection status and account status (subscription already inactive)
            $this->user_model->update($user->id, [
                'conn_status' => 'expired',
                'status' => 'inactive'
            ]);

            $this->cron_log("Successfully disabled inactive+connected user {$user->id} from Mikrotik");

            // Handle notifications and payments
            $this->handleNotifications($user);
            $this->createPaymentRecord($user);
            return true;
        } else {
            // Check if router was marked as failed due to authentication
            if (isset($failedRouters[$user->router_id])) {
                $this->cron_log("Failed to disable user {$user->id} due to router authentication issues", 'error');
            } else {
                $this->cron_log("Failed to disable inactive+connected user {$user->id} from Mikrotik (non-auth error)", 'error');
            }
            return false;
        }
    }

    private function disableRouterConnection($user, &$failedRouters)
    {
        // Skip if router already marked as failed
        if (isset($failedRouters[$user->router_id])) {
            $this->cron_log("Skipping router {$user->router_id} - marked as failed due to authentication issues", 'warning');
            return false;
        }

        $maxRetries = 2;
        $retryCount = 0;

        $this->cron_log("Attempting to disable router connection for user {$user->id} on router {$user->router_id}");

        while ($retryCount <= $maxRetries) {
            try {
                // Try API client method first
                $this->cron_log("API Client: Initializing connection to Router ID {$user->router_id}...");
                $router_client = routerClient($user->router_id);

                if ($router_client) {
                    $this->cron_log("API Client: Connected successfully. Fetching PPPoE ID for user {$user->id}...");
                } else {
                    $this->cron_log("API Client: Connection failed (returned empty/null).");
                }

                // Fetch username from DB for fallback/extra reliability
                $routerDataModel = model('App\Models\UserRouterDataModel');
                $routerData = $routerDataModel->where('user_id', $user->id)->first();
                $ppp_name = $routerData ? $routerData->pppoe_secret : null;

                $pppoe_id = $user->pppoe_id ?? null;
                if ($router_client && !is_array($router_client)) {
                    $pppoe = getPPPoEUserUserId($router_client, $user->id);
                    $pppoe_id = $pppoe[0]['.id'] ?? $pppoe_id;
                    $this->cron_log("API Client: PPPoE ID resolved to '{$pppoe_id}', secret: '{$ppp_name}'");
                }

                log_message('info', "PPPoE ID for User ID {$user->id}: {$pppoe_id}, Name: {$ppp_name}");

                if (!is_array($router_client) && !empty($router_client)) {
                    $this->cron_log("API Client: Disabling PPPoE user {$user->id}...");
                    $disabled = disablePPPoEUser($router_client, $pppoe_id, $ppp_name);
                    if ($disabled) {
                        $this->cron_log("Successfully disabled and dropped user {$user->id} via API client");
                        return true;
                    }
                    $this->cron_log("API Client: Failed to disable PPPoE user via API client.");
                }

                $this->cron_log("Fsock: Initializing fsock connection to Router ID {$user->router_id}...");
                $disabled = disablePPPoEUserFsock($user->router_id, $pppoe_id);
                if ($disabled) {
                    $this->cron_log("Successfully disabled user {$user->id} via fsock");
                    return true;
                }
                $this->cron_log("Fsock: Failed to disable PPPoE user.");
            } catch (\Throwable $e) {
                $errorMessage = $e->getMessage();
                $this->cron_log("Router disable attempt {$retryCount} failed for user {$user->id}: " . $errorMessage, 'error');

                // ✅ ONLY mark router as failed for "Invalid username/password" errors
                if ($this->isAuthenticationError($errorMessage)) {
                    $this->cron_log("🔐 AUTHENTICATION FAILURE detected for router {$user->router_id} - marking as failed", 'error');
                    $failedRouters[$user->router_id] = true;
                    return false; // Stop immediately and mark router as failed
                }

                // ❌ For all other errors, just log and continue (don't mark router as failed)
                $this->cron_log("Non-authentication error for router {$user->router_id} - will retry other users", 'warning');
                // Continue to retry or return false without marking router as failed
            }

            $retryCount++;
            if ($retryCount <= $maxRetries) {
                sleep(1);
            }
        }

        $this->cron_log("Failed to disable router connection for user {$user->id} after {$maxRetries} attempts", 'error');

        // Cache connection failure so we skip trying this router for subsequent users in this run
        $this->cron_log("❌ Router ID {$user->router_id} connection failed completely. Marking as failed to skip subsequent users.", 'error');
        $failedRouters[$user->router_id] = true;

        return false;
    }

    private function isAuthenticationError($errorMessage)
    {
        $authErrors = [
            'invalid user name or password',
            '!trap","=message=invalid user name or password',
            'authentication failed',
            'login failed'
        ];

        foreach ($authErrors as $authError) {
            if (stripos($errorMessage, $authError) !== false) {
                return true;
            }
        }

        return false;
    }


    // The other methods (updateDatabaseOnly, updateSAdminStatus, handleNotifications, createPaymentRecord) remain the same
    private function updateDatabaseOnly($user)
    {
        $this->cron_log("CASE 3: Updating database for user {$user->id} (active but already disconnected)");

        // Just update subscription status in database
        $this->user_model->update($user->id, [
            'subscription_status' => 'inactive'
        ]);

        $this->cron_log("Updated user {$user->id} subscription status to inactive");

        // Handle notifications and payments
        $this->handleNotifications($user);
        $this->createPaymentRecord($user);
    }

    private function updateSAdminStatus($user)
    {
        helper('subscription');
        if (hasPendingPackageChange($user)) {
            $this->cron_log("CASE 4: Skipping sAdmin {$user->id} — pending package change awaiting payment");

            return;
        }

        // Pay-As-You-Go tenants are owned by the wallet billing engine:
        // charge the wallet (extends the subscription) or walk the
        // grace → suspension path instead of blind deactivation.
        try {
            $billing = new \App\Services\PaygBillingService();
            if ($billing->isPaygUser($user)) {
                $result = $billing->runCycle($user);
                $this->cron_log("CASE 4: PAYG billing cycle for sAdmin {$user->id}: " . json_encode($result));
                return;
            }
        } catch (\Throwable $e) {
            $this->cron_log("CASE 4: PAYG billing failed for sAdmin {$user->id}: " . $e->getMessage(), 'error');
            return; // don't blind-suspend a PAYG tenant on engine error
        }

        $this->cron_log("CASE 4: Updating sAdmin user {$user->id} subscription status to inactive");

        // Just update subscription status for sAdmin
        $this->user_model->update($user->id, [
            'subscription_status' => 'inactive'
        ]);

        $this->cron_log("Updated sAdmin user {$user->id} subscription status to inactive");
    }

    /**
     * Pay-As-You-Go tenant billing (cron action `payg-billing`, daily).
     *
     * 1. Charges every PAYG tenant whose cycle is due (will_expire passed):
     *    wallet debit → extend 30 days, or grace → suspension when short.
     * 2. Sends the once-per-cycle low-balance alert when the next charge is
     *    within 7 days and the wallet can't cover it.
     *
     * CASE 4 of index() delegates expired PAYG tenants here too, so billing
     * works even if only the manage-user cron is scheduled.
     */
    public function paygBilling()
    {
        $this->cron_log('PAYG billing run started');

        $billing = new \App\Services\PaygBillingService();
        $paygPlan = $billing->paygPlan();

        if (empty($paygPlan)) {
            $this->cron_log('PAYG billing: no active PAYG plan configured, nothing to do');
            return requestResponse('success', 'No PAYG plan configured', 200);
        }

        $paygPlanId = (int) (is_object($paygPlan) ? $paygPlan->id : $paygPlan['id']);

        $tenants = $this->user_model
            ->where('role', 'admin')
            ->where('package_id', $paygPlanId)
            ->findAll();

        $counts = ['charged' => 0, 'grace' => 0, 'suspended' => 0, 'alerted' => 0, 'skipped' => 0];

        foreach ($tenants as $tenant) {
            set_time_limit(30);

            try {
                $result = $billing->runCycle($tenant);

                switch ($result['status']) {
                    case 'charged':
                        $counts['charged']++;
                        $this->cron_log("PAYG charged tenant {$tenant->id}: BDT {$result['charge']}, next {$result['next_expire']}");
                        break;
                    case 'insufficient_grace':
                        $counts['grace']++;
                        break;
                    case 'suspended':
                    case 'insufficient_waiting':
                        $counts['suspended'] += $result['status'] === 'suspended' ? 1 : 0;
                        break;
                    default:
                        $counts['skipped']++;
                }

                // Low-balance pre-alert for tenants whose charge is coming up.
                if ($result['status'] === 'not_due' && $billing->maybeSendLowBalanceAlert($tenant)) {
                    $counts['alerted']++;
                }
            } catch (\Throwable $e) {
                $this->cron_log("PAYG billing error for tenant {$tenant->id}: " . $e->getMessage(), 'error');
            }
        }

        $summary = "PAYG billing done. Charged: {$counts['charged']}, Grace: {$counts['grace']}, "
            . "Suspended: {$counts['suspended']}, Low-balance alerts: {$counts['alerted']}, Skipped: {$counts['skipped']}";
        $this->cron_log($summary);

        return requestResponse('success', $summary, 200);
    }

    // handleNotifications and createPaymentRecord methods remain the same as your original
    private function handleNotifications($user)
    {
        // Only send notifications for regular users, not sAdmin
        if ($user->role !== 'user') {
            return;
        }

        $this->cron_log("Sending notifications for user {$user->id}");

        $data = [
            'user' => $user->name,
            'id' => $user->id,
            'expire_date' => date("d M Y, h:i a", strtotime($user->will_expire)),
        ];

        // Send SMS notification
        try {
            // event: user_expired | default template: 12 (customer payment due)
            sendEventSms('user_expired', $user, (int) ($user->admin_id ?? 0), 12);
            $this->cron_log("SMS sent successfully to user {$user->id}");
        } catch (\Throwable $e) {
            $this->cron_log("SMS sending failed for user {$user->id}: " . $e->getMessage(), 'error');
        }

        // Send email notification
        try {
            sendMail(
                $user->email,
                getSetting('app_name', '', $user->id) . ' | Subscription Expired',
                view('emails/subscription-expired', $data),
            );
            $this->cron_log("Email sent successfully to user {$user->id}");
        } catch (\Throwable $e) {
            $this->cron_log("Email sending failed for user {$user->id}: " . $e->getMessage(), 'error');
        }
    }

    private function createPaymentRecord($user)
    {
        // Only create payment records for regular users, not sAdmin
        if ($user->role !== 'user') {
            return;
        }

        $this->cron_log("Creating payment record for user {$user->id}");

        // Get package price
        if ($user->created_by === 'resellerAdmin') {
            $price = ResellerPackagePrice($user->package_id);
        } else {
            $package = getUserPackage($user->id);
            $price = is_array($package) ? $package['price'] : (is_object($package) ? $package->price : 0);
        }

        $currentMonth = date('F');
        $payment_model = model('App\Models\Payment');

        // Check if a successful payment already exists for this user and month
        $anyPaidPayment = $payment_model->where([
            'user_id' => $user->id,
            'month' => $currentMonth,
            'status' => 'successful'
        ])->first();

        if ($anyPaidPayment) {
            $this->cron_log("Payment for user {$user->id} for {$currentMonth} is already successful. Skipping.");
            return;
        }

        $paydata = [
            'user_id' => $user->id,
            'user_type' => 'user',
            'admin_id' => $user->admin_id ?? "",
            'invoice' => 'INV-' . random_int(100000, 999999),
            'amount' => $price ?? 0,
            'month' => $currentMonth,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'pending',
        ];

        // Check if a pending/non-successful payment already exists to update
        $existing = $payment_model->where([
            'user_id' => $user->id,
            'month' => $currentMonth,
            'status !=' => 'successful'
        ])->first();

        try {
            if ($existing) {
                $payment_model->update($existing->id, $paydata);
                $this->cron_log("Updated existing pending payment record for user {$user->id}");
            } else {
                $payment_model->insert($paydata);
                $this->cron_log("Created new payment record for user {$user->id}");
            }
        } catch (\Throwable $e) {
            $this->cron_log("Payment record creation failed for user {$user->id}: " . $e->getMessage(), 'error');
        }
    }
























    function usersactivity()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $this->cron_log('users activity : executing automatically');

        $user_model = model('App\Models\User');
        $router_model = model('App\Models\Router');
        $routerDataModel = model('App\Models\UserRouterDataModel'); // move out of loop for efficiency

        // Step 1: Get all active routers
        $routers = $router_model->where('status', 'active')->findAll();

        foreach ($routers as $router) {
            $this->cron_log("Processing Router ID: {$router->id} - {$router->name}");
            try {
                $router_client = routerClient($router->id);
                if (!$router_client) {
                    $this->cron_log("Router connection failed for Router ID: {$router->id}", 'error');
                    continue; // skip this router
                }

                // Step 2: Get all active users from this router
                $users = $user_model->where('router_id', $router->id)->where('role', 'user')->findAll();
                $this->cron_log("Found " . count($users) . " users for Router ID: {$router->id}");
                if (empty($users))
                    continue;

                // Step 3: Get active PPPoE users from the router
                $active_user_data = getactive_user($router_client);
                $active_ids = array_column($active_user_data['data']['activeusers'] ?? [], 'name');

                // 2. Fetch existing router data for these users to avoid duplicates
                $routerData = $routerDataModel->whereIn('user_id', array_column($users, 'id'))
                    ->orderBy('id', 'desc') // Process newest first
                    ->findAll();

                $credsMap = [];
                foreach ($routerData as $rd) {
                    if (!isset($credsMap[$rd->user_id])) {
                        $credsMap[$rd->user_id] = $rd;
                    } else {
                        // Duplicate record found! Clean it up to keep only the newest one.
                        $routerDataModel->delete($rd->id);
                        $this->cron_log("DELETED DUPLICATE: Removed extra router_data record (ID {$rd->id}) for User ID {$rd->user_id}");
                    }
                }

                // Step 5: Prepare bulk update
                $updateData = [];

                foreach ($users as $user) {
                    $routerData = $credsMap[$user->id] ?? null;
                    if (!$routerData) {
                        // fallback to user model pppoe_id if missing router data
                        $pppoe_id = $user->pppoe_id;
                    } else {
                        $pppoe_id = is_array($routerData) ? $routerData['pppoe_secret'] : $routerData->pppoe_secret;
                    }

                    if (!$pppoe_id) {
                        $this->cron_log("No pppoe identifier found for User ID: {$user->id}", 'warning');
                        continue;
                    }

                    $isActive = in_array($pppoe_id, $active_ids, true);

                    // Only add to update list if status actually changed (Optimization to reduce DB writes)
                    if ($user->activity !== ($isActive ? 'active' : 'inactive')) {
                        $updateData[] = [
                            'id' => $user->id,
                            'activity' => $isActive ? 'active' : 'inactive'
                        ];
                    }
                }

                // Step 5: Bulk update
                if (!empty($updateData)) {
                    $user_model->updateBatch($updateData, 'id');
                    $this->cron_log("Bulk update completed for Router ID {$router->id} (" . count($updateData) . " users).");
                }
            } catch (\Throwable $e) {
                $this->cron_log("Error processing Router ID {$router->id}: " . $e->getMessage(), 'error');
            }
        }

        $this->cron_log('Users activity update completed.');
    }




    /**
     * CronJob
     * @action: Send Notification 
     *          to the User about the subscription expired
     */

    // public function sendNotification()
    // {

    //     $now = strtotime(date('Y-m-d'));

    //     $this->cron_log('users users Data: sendNotification executing autometicly in 1 day' );


    //     $users = $this->user_model->where(['role' => 'user', 'subscription_status' => 'active', 'status' => 'active'])
    //         ->findAll();

    //     if (!empty($users)) {

    //         foreach ($users as $user) {

    //             $before_day = strtotime(date('Y-m-d', strtotime('-' . getSetting("notify_user_subscription_expire_before_days") . ' days',  strtotime($user->will_expire))));

    //             $before_day = date('Y-m-d, h:i a', $before_day);
    //             $now = date('Y-m-d, h:i a', $now);
    //             // if ($now >= $before_day) {
    //             if ($now == $before_day) {


    //                 $data = [
    //                     'user' => $user->name,
    //                     'expire_date' => date("d M Y, h:i a", strtotime($user->will_expire)),
    //                 ];

    //                 $this->cron_log('sendNotification Successfully data : ' . print_r($data, true));
    //                 $this->cron_log('sendNotification Successfully before_day : ' . print_r($before_day, true));


    //                 //send sms notification
    //                 sendSms($user->mobile, view('sms/templates/expire-notice', $data));

    //                 //send email notification
    //                 sendMail(
    //                     $user->email,
    //                     getSetting('app_name') . ' | Subscription Expiry Notice',
    //                     view('emails/subscription-will-expire', $data),
    //                 );
    //             }
    //         }
    //     }
    // }

    public function sendNotification()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $now = date('Y-m-d');

        $this->cron_log('users users Data: sendNotification executing automatically');

        // Fetch users in chunks
        $this->user_model->where(['role' => 'user', 'subscription_status' => 'active', 'status' => 'active'])
            ->chunk(100, function ($users) use ($now) {
                // Cache admin settings to avoid redundant lookups within the chunk
                $adminSettings = [];
                $uniqueAdminIds = array_unique(array_column($users, 'admin_id'));
                foreach ($uniqueAdminIds as $adminId) {
                    $days = getSetting("notify_user_subscription_expire_before_days", "", (int) $adminId);
                    $adminSettings[$adminId] = array_filter(array_map('trim', explode(',', $days)));
                }

                foreach ($users as $user) {
                    if (empty($user->will_expire))
                        continue;

                    $notify_days_array = $adminSettings[$user->admin_id] ?? [];
                    if (empty($notify_days_array))
                        continue;

                    foreach ($notify_days_array as $notify_day) {
                        // Calculate the target date for notification
                        $before_day_timestamp = strtotime('-' . $notify_day . ' days', strtotime($user->will_expire));
                        if ($before_day_timestamp === false)
                            continue;

                        $before_day = date('Y-m-d', $before_day_timestamp);

                        // Compare the dates
                        if ($now === $before_day) {
                            $data = [
                                'user' => $user->name,
                                'expire_date' => date("d M Y, h:i a", strtotime($user->will_expire)),
                            ];

                            $this->cron_log("Sending expiry notice to {$user->name} ({$user->id}) for {$data['expire_date']}");

                            // Send SMS and email notifications using the user's admin gateway
                            // event: expiry_notice | default template: 12 (customer payment due)
                            try {
                                sendEventSms('expiry_notice', $user, (int) ($user->admin_id ?? 0), 12);
                            } catch (\Throwable $e) {
                                $this->cron_log("SMS sending failed for user {$user->id}: " . $e->getMessage(), 'error');
                            }

                            try {
                                sendMail(
                                    $user->email,
                                    getSetting('app_name', '', $user->id) . ' | Subscription Expiry Notice',
                                    view('emails/subscription-will-expire', $data)
                                );
                            } catch (\Throwable $e) {
                                $this->cron_log("Email sending failed for user {$user->id}: " . $e->getMessage(), 'error');
                            }
                        }
                    }
                }
            });
    }


    public function deleteWriteAbleLogs()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $this->cron_log('users deleteWriteAbleLogs : executing automatically every day');

        $writableLogDir = WRITEPATH . 'logs';
        $writablecronJobDir = WRITEPATH . 'cronJob';
        $writableBackupsDir = WRITEPATH . 'backups';
        $writableSessionDir = WRITEPATH . 'session';

        $logKeepDays = 3;
        $backupKeepDays = 10;
        $sessionKeepDays = 7;

        if (is_dir($writablecronJobDir)) {
            $logFiles = glob($writablecronJobDir . '/cron_*.log');
            $logCutoffDate = new DateTime("-{$logKeepDays} days");

            foreach ($logFiles as $file) {
                if (is_file($file) && preg_match('/cron_(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                    $fileDate = DateTime::createFromFormat('Y-m-d', $matches[1]);

                    if ($fileDate && $fileDate < $logCutoffDate) {
                        $this->cron_log('Deleting old log file: ' . $file);
                        unlink($file);
                    } else {
                        $this->cron_log('Keeping recent log file: ' . $file);
                    }
                }
            }

            $this->cron_log('Log cleanup completed.');
        } else {
            $this->cron_log('Logs directory does not exist: ' . $writablecronJobDir, 'error');
        }


        // --- Delete old log files ---
        if (is_dir($writableLogDir)) {
            $logFiles = glob($writableLogDir . '/log-*.log');
            $logCutoffDate = new DateTime("-{$logKeepDays} days");

            foreach ($logFiles as $file) {
                if (is_file($file) && preg_match('/log-(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                    $fileDate = DateTime::createFromFormat('Y-m-d', $matches[1]);

                    if ($fileDate && $fileDate < $logCutoffDate) {
                        $this->cron_log('Deleting old log file: ' . $file);
                        unlink($file); // Uncomment to delete
                    } else {
                        $this->cron_log('Keeping recent log file: ' . $file);
                    }
                }
            }

            $this->cron_log('Log cleanup completed.');
        } else {
            $this->cron_log('Logs directory does not exist: ' . $writableLogDir, 'error');
        }

        // --- Delete old session files ---
        if (is_dir($writableSessionDir)) {
            $sessionFiles = glob($writableSessionDir . '/*');
            $sessionCutoffDate = new DateTime("-{$sessionKeepDays} days");

            foreach ($sessionFiles as $file) {
                if (is_file($file)) {
                    $fileMtime = filemtime($file);
                    $fileDate = (new DateTime())->setTimestamp($fileMtime);

                    if ($fileDate < $sessionCutoffDate) {
                        $this->cron_log('Deleting old session file: ' . basename($file));
                        unlink($file);
                    }
                }
            }
            $this->cron_log('Session cleanup completed.');
        } else {
            $this->cron_log('Session directory does not exist: ' . $writableSessionDir, 'error');
        }

        // --- Delete old backup files ---
        if (is_dir($writableBackupsDir)) {
            $backupFiles = glob($writableBackupsDir . '/*.sql');
            $backupCutoffDate = new DateTime("-{$backupKeepDays} days");

            foreach ($backupFiles as $file) {
                // Match pattern: [dbname]_[Ymd]_[His].sql
                if (is_file($file) && preg_match('/.*_(\d{8})_\d{6}\.sql$/', basename($file), $matches)) {
                    $fileDate = DateTime::createFromFormat('Ymd', $matches[1]);

                    if ($fileDate && $fileDate < $backupCutoffDate) {
                        $this->cron_log('Deleting old backup file: ' . $file);
                        unlink($file);
                    } else {
                        $this->cron_log('Keeping recent backup file: ' . $file);
                    }
                }
            }

            $this->cron_log('Backup cleanup completed.');
        } else {
            $this->cron_log('Backups directory does not exist: ' . $writableBackupsDir, 'error');
        }
    }
    public function daily_payment_generate()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M'); // Phase-F2: bound memory; -1 risks OOM at 20k

        $this->cron_log('Starting daily payment generation process...');
        $userModel = model('App\Models\User');
        $transactionModel = model('App\Models\ResellerTransactions');

        // Get all active resellers
        $resellers = $userModel->where('role', 'resellerAdmin')
            ->where('status', 'active')
            ->findAll();

        if (empty($resellers)) {
            $this->cron_log('No active resellers found.');
            return;
        }

        // Loop through each reseller once
        foreach ($resellers as $reseller) {
            $resellerId = $reseller->id;
            $adminId = $reseller->admin_id;
            $fund = (float) ($reseller->fund ?? 0);
            $initialFund = $fund; // snapshot so the end-of-run write can be an ATOMIC relative deduct
            $billingType = $reseller->billing_type ?? 'postpaid';
            $this->cron_log("Processing reseller ID: {$resellerId} ({$billingType}) with initial fund: {$fund}");

            if ($billingType === 'prepaid' && $fund <= 0) {
                $this->cron_log("Prepaid reseller: {$resellerId} has no fund. Skipping.");
                continue;
            }

            // Check custom permission only if reseller is prepaid (skip for postpaid)
            if ($billingType === 'prepaid') {
                $customAccessModel = model('App\Models\CustomAccess');
                $customPermission = $customAccessModel->where([
                    'user_id' => $resellerId,
                    'status' => 'active'
                ])->first();

                $hasDailyPermission = false;
                if ($customPermission && !empty($customPermission->permissions)) {
                    $permissions = json_decode($customPermission->permissions, true);
                    // Check both possible keys (Resellers or reseller)
                    if (
                        (isset($permissions['Resellers']) && in_array('daily_payment_generate', $permissions['Resellers'])) ||
                        (isset($permissions['reseller']) && in_array('daily_payment_generate', $permissions['reseller']))
                    ) {
                        $hasDailyPermission = true;
                    }
                }

                if (!$hasDailyPermission) {
                    $this->cron_log("Custom daily payment permission not found for prepaid reseller: {$resellerId}. Skipping.");
                    continue;
                }
            }

            $this->cron_log("Processing reseller: {$resellerId}");

            // Cache current reseller ID for ResellerPackagePrice()
            cache()->save('current_reseller_id', $resellerId, 3600);

            $today = date('Y-m-d 23:59:59');

            // Phase-F1: chunked query — 500 users/batch keeps PHP memory bounded.
            // $packagePriceCache and $fund persist across chunks; $totalDeducted
            // accumulates per-user deductions for the single atomic DB write at the end.
            $chunkSize         = 500;
            $offset            = 0;
            $totalDeducted     = 0.0;
            $packagePriceCache = [];
            $hasUsers          = false;

            do {
                $users = $userModel->select('id, package_id, will_expire')
                    ->where([
                        'role'     => 'user',
                        'admin_id' => $resellerId,
                    ])
                    ->groupStart()
                        ->where('conn_status !=', 'disconn')
                        ->orWhere('conn_status', null)
                    ->groupEnd()
                    ->where("DATE(last_renewed) !=", date('Y-m-d'))
                    ->findAll($chunkSize, $offset);

                if (empty($users)) break;
                $hasUsers = true;

                $userUpdates        = [];
                $transactionInserts = [];

                foreach ($users as $user) {
                    $packageId = $user->package_id;
                    $this->cron_log("Processing user: {$user->id} with package: {$packageId}");

                    if (empty($user->will_expire)) {
                        $this->cron_log("User {$user->id} has no expiration date set. Skipping.");
                        continue;
                    }

                    $todayStart   = date('Y-m-d 00:00:00');
                    $todayEnd     = date('Y-m-d 23:59:59');
                    $willExpireTs = strtotime($user->will_expire);
                    $todayStartTs = strtotime($todayStart);
                    $todayEndTs   = strtotime($todayEnd);

                    if ($willExpireTs < $todayStartTs) {
                        $this->cron_log("User {$user->id} is already expired (Expired on {$user->will_expire}). Skipping daily billing.");
                        continue;
                    }

                    if ($willExpireTs > $todayEndTs) {
                        $this->cron_log("User {$user->id} has future prepaid validity (expires on {$user->will_expire}). Skipping daily billing.");
                        continue;
                    }

                    if (!isset($packagePriceCache[$packageId])) {
                        $packagePriceCache[$packageId] = (int) ResellerPackagePrice($packageId, null, $resellerId, "resellerAdmin");
                    }
                    $tprice     = $packagePriceCache[$packageId];
                    $dailyPrice = round($tprice / 30, 2);

                    if ($dailyPrice <= 0) {
                        continue;
                    }

                    if ($billingType === 'postpaid' || $fund >= $dailyPrice) {
                        if ($billingType === 'prepaid') {
                            $fund          -= $dailyPrice;
                            $totalDeducted += $dailyPrice;
                        }

                        $baseDate   = !empty($user->will_expire) ? $user->will_expire : date('Y-m-d H:i:s');
                        $willExpire = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($baseDate)));

                        $updateFields = [
                            'id'           => $user->id,
                            'last_renewed' => date('Y-m-d H:i:s'),
                            'will_expire'  => $willExpire,
                        ];

                        if (strtotime($willExpire) > time()) {
                            $updateFields['subscription_status'] = 'active';
                            $updateFields['status']              = 'active';
                            $updateFields['conn_status']         = 'conn';
                        }

                        $userUpdates[] = $updateFields;

                        $transactionInserts[] = [
                            'customer'      => $user->id,
                            'admin_id'      => $resellerId,
                            'amount'        => $dailyPrice,
                            'package_price' => $tprice,
                            'active_for'    => '1',
                            'comments'      => 'Daily payment auto-deducted',
                        ];
                    } else {
                        $this->cron_log("Insufficient fund for reseller: {$resellerId}, expiring subscription.");
                        $userUpdates[] = [
                            'id'                  => $user->id,
                            'will_expire'         => date('Y-m-d H:i:s'),
                            'subscription_status' => 'inactive',
                            'status'              => 'inactive',
                        ];
                    }
                }

                // Flush this chunk — keeps working-set small rather than accumulating all rows.
                if (!empty($userUpdates)) {
                    $this->cron_log('Updating users chunk offset ' . $offset . ': ' . count($userUpdates) . ' rows');
                    $userModel->updateBatch($userUpdates, 'id');
                }
                if (!empty($transactionInserts)) {
                    $this->cron_log('Inserting transactions chunk offset ' . $offset . ': ' . count($transactionInserts) . ' rows');
                    $transactionModel->insertBatch($transactionInserts);
                }

                unset($users, $userUpdates, $transactionInserts);
                gc_collect_cycles();
                $offset += $chunkSize;

            } while (true);

            if (! $hasUsers) continue;

            // Update reseller fund ONCE — single ATOMIC RELATIVE deduct (3.3c).
            // FundService::deduct() runs `fund = fund - ? WHERE fund >= ?` so
            // concurrent credits are preserved and the balance never goes negative.
            $totalDeducted = round($totalDeducted, 2);
            if ($billingType === 'prepaid' && $totalDeducted > 0) {
                $ok = (new \App\Services\FundService())->deduct((int) $resellerId, $totalDeducted);
                if (! $ok) {
                    $row  = $userModel->find($resellerId);
                    $live = (float) ($row->fund ?? 0);
                    $take = round(min($totalDeducted, max(0.0, $live)), 2);
                    if ($take > 0) {
                        (new \App\Services\FundService())->deduct((int) $resellerId, $take);
                    }
                    $this->cron_log("Reseller {$resellerId}: full deduct {$totalDeducted} refused (concurrent change); deducted coverable {$take} atomically instead");
                } else {
                    $this->cron_log("Reseller {$resellerId}: atomically deducted {$totalDeducted}, remaining ~{$fund}");
                }
            } else {
                $this->cron_log("Reseller {$resellerId}: nothing deducted this run.");
            }
        }

        $this->cron_log('Daily payment generation process completed.');
    }



    /**
     * Master Sync: sAdmin -> Routers -> PPPoE Credentials
     * Ensures pppoe_id (User Model) and pppoe_secret/pass (RouterData Model) are identical to the MikroTik.
     */
    public function sync_all_credentials()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $this->cron_log('--- Starting Master Credential Sync Process ---');

        $userModel = model('App\Models\User');
        $routerModel = model('App\Models\Router');
        $routerDataModel = model('App\Models\UserRouterDataModel');
        $now = date('Y-m-d H:i:s');

        // 1. Fetch all Admins (sAdmin and resellerAdmin) who are Active
        $admins = $userModel->whereIn('role', ['admin', 'resellerAdmin'])
            ->where('status', 'active')
            ->findAll();

        $this->cron_log("Found " . count($admins) . " active admins to process.");

        foreach ($admins as $admin) {
            $this->cron_log("Processing Admin: {$admin->name} (ID: {$admin->id}, Role: {$admin->role})");

            // 2. Fetch Active Routers belonging to this Admin
            $routers = $routerModel->where([
                'user_id' => $admin->id,
                'status' => 'active'
            ])->findAll();

            foreach ($routers as $router) {
                $this->cron_log("Connecting to Router: {$router->name} ({$router->host})");

                $router_client = routerClient($router->id);
                if (!is_object($router_client)) {
                    $this->cron_log("FAIL: Connection failed for router {$router->id}", 'error');
                    continue;
                }

                try {
                    // 3. Fetch ALL Secrets from MikroTik in one go
                    $mktSecrets = $router_client->query(new \RouterOS\Query('/ppp/secret/print'))->read();

                    // Index secrets by Name and ID for O(1) matching
                    $mktByName = [];
                    $mktById = [];
                    foreach ($mktSecrets as $s) {
                        if (isset($s['name'])) {
                            $mktByName[$s['name']] = $s;
                        }
                        if (isset($s['.id'])) {
                            $mktById[$s['.id']] = $s;
                        }
                    }

                    // 4. Fetch all users assigned to this router in our DB
                    $dbUsers = $userModel->where('router_id', $router->id)
                        ->where('role', 'user')
                        ->findAll();

                    if (empty($dbUsers))
                        continue;

                    // Batch fetch credentials for all these users for THIS router
                    $credsList = $routerDataModel->whereIn('user_id', array_column($dbUsers, 'id'))
                        ->where('router_id', $router->id)
                        ->orderBy('id', 'desc')
                        ->findAll();
                    $credsMap = [];
                    foreach ($credsList as $c) {
                        if (!isset($credsMap[$c->user_id])) {
                            $credsMap[$c->user_id] = $c;
                        } else {
                            // Deduplicate: If multiple records exist for same user/router, delete older ones
                            $routerDataModel->delete($c->id);
                            $this->cron_log("CLEANUP: Deleted duplicate record (ID {$c->id}) for user {$c->user_id} on router {$router->id}");
                        }
                    }

                    foreach ($dbUsers as $user) {
                        $cred = $credsMap[$user->id] ?? null;
                        $mktData = null;

                        // Priority 1: Match by Secret Name from Database (User's request)
                        if ($cred && $cred->pppoe_secret && isset($mktByName[$cred->pppoe_secret])) {
                            $tempMkt = $mktByName[$cred->pppoe_secret];
                            // Validation: If name matches but password/ID is wildly different, it might be a collision.
                            // We only accept the name match if it's a strong candidate.
                            if ($cred->router_password === ($tempMkt['password'] ?? '') || $user->pppoe_id === ($tempMkt['.id'] ?? '')) {
                                $mktData = $tempMkt;
                            }
                        }

                        // Priority 2: Match by MikroTik Internal ID (Stable Backup)
                        if (!$mktData && $user->pppoe_id && $user->pppoe_id !== '--' && isset($mktById[$user->pppoe_id])) {
                            $mktData = $mktById[$user->pppoe_id];
                        }

                        // Priority 3: Try searching by Email Prefix (e.g. '110selim' from '110selim@gmail.com')
                        if (!$mktData && !empty($user->email)) {
                            $emailPrefix = explode('@', $user->email)[0];
                            if (isset($mktByName[$emailPrefix])) {
                                $mktData = $mktByName[$emailPrefix];
                            }
                        }

                        // Priority 4: Try searching by Full Name
                        if (!$mktData && !empty($user->name) && isset($mktByName[$user->name])) {
                            $mktData = $mktByName[$user->name];
                        }

                        // Priority 5: Try searching by expected name format (ID-{user_id})
                        if (!$mktData && isset($mktByName["ID-{$user->id}"])) {
                            $mktData = $mktByName["ID-{$user->id}"];
                            $this->cron_log("MATCHED: User ID {$user->id} found by ID Pattern (ID-{$user->id})");
                        }

                        // Priority 6: Try pppoe_id as name (common fallback)
                        if (!$mktData && $user->pppoe_id && $user->pppoe_id !== '--' && isset($mktByName[$user->pppoe_id])) {
                            $mktData = $mktByName[$user->pppoe_id];
                            $this->cron_log("MATCHED: User ID {$user->id} found by pppoe_id name fallback");
                        }

                        if (!$mktData) {
                            continue;
                        }

                        $mktInternalId = $mktData['.id'];
                        $mktSecretName = $mktData['name'];
                        $mktPassword = $mktData['password'] ?? '';
                        $mktProfile = $mktData['profile'] ?? '';

                        // A. Sync User Model (pppoe_id)
                        if ($user->pppoe_id !== $mktInternalId) {
                            $userModel->update($user->id, ['pppoe_id' => $mktInternalId]);
                            $this->cron_log("FIXED ID: User ID {$user->id} linked to MikroTik ID {$mktInternalId}");
                        }

                        // B. Sync RouterData Model (Password, Secret & Profile)
                        if ($cred) {
                            $needsUpdate = (
                                $cred->router_password !== $mktPassword ||
                                $cred->pppoe_secret !== $mktSecretName ||
                                ($cred->pppoe_profile ?? '') !== $mktProfile ||
                                $cred->router_id != $router->id // CRITICAL: Ensure router_id is correct for the join
                            );

                            if ($needsUpdate) {
                                $routerDataModel->update($cred->id, [
                                    'router_id' => $router->id, // Fixed: Ensure consistency
                                    'pppoe_secret' => $mktSecretName,
                                    'router_password' => $mktPassword,
                                    'pppoe_profile' => $mktProfile,
                                    'last_updated' => $now
                                ]);
                                $this->cron_log("FIXED DATA: User ID {$user->id} credentials synced (Secret: {$mktSecretName}, Profile: {$mktProfile})");
                            }
                        } else {
                            // C. ADD MISSING: If user exists but table Record is missing, create it.
                            $routerDataModel->insert([
                                'user_id' => $user->id,
                                'router_id' => $router->id,
                                'pppoe_secret' => $mktSecretName,
                                'router_password' => $mktPassword,
                                'pppoe_profile' => $mktProfile,
                                'last_updated' => $now
                            ]);
                            $this->cron_log("SYNCED: Created missing credential record for {$mktSecretName}.");
                        }
                    }
                } catch (\Throwable $e) {
                    $this->cron_log("ERROR: syncing router {$router->id}: " . $e->getMessage(), 'error');
                }
            }
        }
        $this->cron_log('--- Master Sync Completed ---');
    }



}
