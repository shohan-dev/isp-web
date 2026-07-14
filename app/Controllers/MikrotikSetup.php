<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use RouterOS\Query;

class MikrotikSetup extends BaseController
{
    public function setupExpiredProfile($router_id)
    {
        set_time_limit(300);
        helper('router');

        $router_client = routerClient($router_id);
        if (!$router_client) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cannot connect to router']);
        }

        //   $this->removeExpiredRules($router_client);
//         // Get router IP dynamically
//         $routerIp = null;
//         $ipAddresses = $router_client->query(new Query('/ip/address/print'))->read();
//         foreach ($ipAddresses as $addr) {
//             if (isset($addr['address'])) {
//                 $ip = explode('/', $addr['address'])[0];
//                 if (str_starts_with($ip, '10.72.')) {
//                     $routerIp = $ip;
//                     break;
//                 }
//             }
//         }

//         log_message('info', "MIKROTIK SETUP: Detected router IP: {$routerIp}");

//         if (!$routerIp) {
//             return $this->response->setJSON(['status' => 'error', 'message' => 'Could not detect router LAN IP']);
//         }

//         $terminalCommands = "
// /ppp profile
// :if ([:len [find name=expired]] = 0) do={ add name=expired address-list=expired }

// /ip firewall filter
// add action=accept chain=forward comment=Expired dst-port=53 protocol=udp src-address-list=expired
// add action=accept chain=forward comment=Expired dst-address-list=domain src-address-list=expired
// add action=accept chain=forward comment=Expired dst-address-list=payment src-address-list=expired
// add action=accept chain=forward comment=Expired dst-address-list=chat src-address-list=expired
// add action=reject chain=forward comment=Expired dst-port=80,443 protocol=tcp reject-with=icmp-network-unreachable src-address-list=expired
// add action=drop chain=forward comment=Expired src-address-list=expired
// add action=passthrough chain=unused-hs-chain comment=Expired disabled=yes

// /ip firewall nat
// add action=redirect chain=dstnat comment=Expired dst-port=80 protocol=tcp src-address-list=expired to-ports=8090
// ";

//         try {
//             // Cleanup existing rules first to avoid duplicates
//             $this->removeExpiredRules($router_client);

//             $scriptName = 'setup_expired_temp';

//             // Remove script if exists
//             try {
//                 $router_client->query((new Query('/system/script/remove'))->equal('.id', $scriptName))->read();
//             } catch (\Exception $e) {
//             }

//             // Add script
//             $addResult = $router_client->query(
//                 (new Query('/system/script/add'))
//                     ->equal('name', $scriptName)
//                     ->equal('source', $terminalCommands)
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Script add result: " . json_encode($addResult));

//             // Run script
//             $runResult = $router_client->query(
//                 (new Query('/system/script/run'))->equal('.id', $scriptName)
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Script run result: " . json_encode($runResult));

//             // Remove script
//             $router_client->query(
//                 (new Query('/system/script/remove'))->equal('.id', $scriptName)
//             )->read();

//             // Enable proxy via API
//             $router_client->query(
//                 (new Query('/ip/proxy/set'))
//                     ->equal('enabled', 'yes')
//                     ->equal('max-cache-size', 'none')
//                     ->equal('parent-proxy', '0.0.0.0')
//                     ->equal('cache-administrator', 'super_admin')
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Proxy enabled via API");

//             // Proxy access rules via API
//             $router_client->query(
//                 (new Query('/ip/proxy/access/add'))
//                     ->equal('comment', 'Expired')
//                     ->equal('src-address', '0.0.0.0/0')
//                     ->equal('dst-port', '8090,443')
//             )->read();

//             $router_client->query(
//                 (new Query('/ip/proxy/access/add'))
//                     ->equal('action', 'deny')
//                     ->equal('comment', 'Expired')
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Proxy access rules added via API");

//             // Verify filter rules were created
//             $filters = $router_client->query(
//                 (new Query('/ip/firewall/filter/print'))->where('comment', 'Expired')
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Expired filter rules found: " . json_encode($filters));

//             // Verify NAT rules
//             $nats = $router_client->query(
//                 (new Query('/ip/firewall/nat/print'))->where('comment', 'Expired')
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Expired NAT rules found: " . json_encode($nats));

//             // Verify PPP profile
//             $profiles = $router_client->query(
//                 (new Query('/ppp/profile/print'))->where('name', 'expired')
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Expired PPP profile found: " . json_encode($profiles));

//             // Verify proxy
//             $proxy = $router_client->query(new Query('/ip/proxy/print'))->read();
//             log_message('info', "MIKROTIK SETUP: Proxy status: " . json_encode($proxy));

//             // Verify proxy access rules
//             $proxyAccess = $router_client->query(
//                 (new Query('/ip/proxy/access/print'))->where('comment', 'Expired')
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Proxy access rules found: " . json_encode($proxyAccess));

//             // Create web-proxy folder and placeholder file via script
//             log_message('info', "MIKROTIK SETUP: Creating web-proxy directory...");
//             $fileScript = "/file add name=\"web-proxy\" type=directory";
//             $fileScriptName = 'create_webproxy_temp';

//             try {
//                 $router_client->query((new Query('/system/script/remove'))->equal('.id', $fileScriptName))->read();
//             } catch (\Exception $e) {
//             }

//             $router_client->query(
//                 (new Query('/system/script/add'))
//                     ->equal('name', $fileScriptName)
//                     ->equal('source', $fileScript)
//             )->read();

//             $fileScriptResult = $router_client->query(
//                 (new Query('/system/script/run'))->equal('.id', $fileScriptName)
//             )->read();
//             log_message('info', "MIKROTIK SETUP: Create directory result: " . json_encode($fileScriptResult));

//             $router_client->query(
//                 (new Query('/system/script/remove'))->equal('.id', $fileScriptName)
//             )->read();

//             // Upload error.html
//             log_message('info', "MIKROTIK SETUP: Uploading error.html...");
//             $errorHtml = '<!doctype html>
// <html lang="bn">
// <head>
// <meta charset="utf-8">
// <meta name="viewport" content="width=device-width,initial-scale=1">
// <title>মেয়াদ শেষ - রিচার্জ করুন</title>
// <style>
//     :root{
//     --bg:#eef2f9;
//     --card:#ffffff;
//     --accent:#0b69ff;
//     --text:#0b1726;
//     --muted:#556079;
//     --danger:#e63946;
//     }
//     html,body{height:100%;}
//     body{
//     margin:0;
//     font-family: -apple-system, BlinkMacSystemFont, "Noto Sans Bengali", Arial, sans-serif;
//     background:linear-gradient(180deg, var(--bg), #dbe7ff);
//     display:flex;
//     align-items:center;
//     justify-content:center;
//     padding:24px;
//     color:var(--text);
//     }
//     .card{
//     width:100%;
//     max-width:720px;
//     background:var(--card);
//     border-radius:20px;
//     box-shadow:0 12px 36px rgba(11,22,38,0.12);
//     padding:36px;
//     text-align:center;
//     animation: fadeIn 0.8s ease-in-out;
//     }
//     .logo{
//     font-weight:700;
//     color:var(--accent);
//     font-size:24px;
//     margin-bottom:12px;
//     }
//     .time{
//     font-size:16px;
//     font-weight:500;
//     color:var(--muted);
//     margin-bottom:20px;
//     }
//     h1{
//     margin:8px 0 12px;
//     font-size:24px;
//     color:var(--danger);
//     }
//     p{
//     margin:0 0 20px;
//     color:var(--muted);
//     line-height:1.6;
//     font-size:17px;
//     }
//     .btn{
//     display:inline-block;
//     padding:12px 22px;
//     border-radius:12px;
//     text-decoration:none;
//     font-weight:600;
//     background:var(--accent);
//     color:#fff;
//     box-shadow: 0 6px 18px rgba(11,105,255,0.18);
//     transition: all 0.2s ease-in-out;
//     margin:8px;
//     }
//     .btn:hover{
//     background:#084bcc;
//     transform: translateY(-2px);
//     }
//     .small{
//     margin-top:20px;
//     font-size:14px;
//     color:#8a95a8;
//     }
//     @media (max-width:420px){
//     .card{padding:20px;border-radius:14px;}
//     h1{font-size:20px;}
//     p{font-size:15px;}
//     }
//     @keyframes fadeIn {
//     from {opacity:0; transform:translateY(20px);}
//     to {opacity:1; transform:translateY(0);}
//     }
// </style>
// </head>
// <body>
// <div class="card" role="main" aria-labelledby="title">
//     <div class="logo">রাফি নেট এন্ড ওয়াইফাই</div>
//     <div id="time" class="time">Loading time...</div>
//     <h1 id="title">সম্মানিত গ্রাহক</h1>
//     <h1 id="title">আপনার ব্রড ব্যান্ড ইন্টারনেটের মেয়াদ শেষ হয়েছে।</h1>
//     <p>দয়া করে রিচার্জ করুন এবং পুনরায় সংযোগ উপভোগ করুন।</p>
//     <a class="btn" href="https://isppaybd.com/auth/login" target="_blank">💳 রিচার্জ করুন</a>
//     <h1 id="title"></h1>
//     <a class="btn" href="https://isppaybd.com/auth/login" target="_blank">বিকাশ এজেন্ট নাম্বার 01866297444</a>
//     <a class="btn" href="tel:+8801766238343">বিকাশ এজেন্ট নাম্বার 01866297444</a>
//     <a class="btn" href="tel:+8809649238343">📞 কল করুন 09649238343</a>
//     <a class="btn" href="tel:+8801766238343">📞 কল করুন 01766238343</a>
//     <a class="btn" href="https://wa.me/8801766238343" target="_blank">💬 WhatsApp 01766238343</a>
//     <h1 id="title">আপনার ব্রড ব্যান্ড ইন্টারনেটের মেয়াদ শেষ হয়েছে।</h1>
//     <div class="small">কোনো সমস্যা থাকলে আমাদের কাস্টমার কেয়ার-এ যোগাযোগ করুন।</div>
// </div>
// <script>
//     function updateTime(){
//     const now = new Date();
//     const options = { weekday:\'long\', year:\'numeric\', month:\'long\', day:\'numeric\', hour:\'2-digit\', minute:\'2-digit\', second:\'2-digit\' };
//     document.getElementById("time").innerText = now.toLocaleDateString("bn-BD", options);
//     }
//     setInterval(updateTime,1000);
//     updateTime();
// </script>
// </body>
// </html>';

//             // Create web-proxy folder
//             $fileScript = "/file add name=\"web-proxy\" type=directory";
//             $fileScriptName = 'create_webproxy_temp';

//             try {
//                 $router_client->query((new Query('/system/script/remove'))->equal('.id', $fileScriptName))->read();
//             } catch (\Exception $e) {
//             }

//             $router_client->query(
//                 (new Query('/system/script/add'))
//                     ->equal('name', $fileScriptName)
//                     ->equal('source', $fileScript)
//             )->read();

//             $router_client->query(
//                 (new Query('/system/script/run'))->equal('.id', $fileScriptName)
//             )->read();

//             $router_client->query(
//                 (new Query('/system/script/remove'))->equal('.id', $fileScriptName)
//             )->read();

//             log_message('info', "MIKROTIK SETUP: web-proxy directory created");

//             // Wait 2 seconds for directory to be ready
//             sleep(2);

//             // Upload error.html directly to web-proxy/error.html
//             try {
//                 $router_client->query(
//                     (new Query('/file/set'))
//                         ->equal('.id', 'web-proxy/error.html')
//                         ->equal('contents', $errorHtml)
//                 )->read();
//                 log_message('info', "MIKROTIK SETUP: Uploaded error.html to web-proxy/error.html");
//             } catch (\Exception $e) {
//                 log_message('error', "MIKROTIK SETUP: set failed, trying add: " . $e->getMessage());
//                 try {
//                     $router_client->query(
//                         (new Query('/file/add'))
//                             ->equal('name', 'web-proxy/error.html')
//                             ->equal('contents', $errorHtml)
//                     )->read();
//                     log_message('info', "MIKROTIK SETUP: Created error.html at web-proxy/error.html");
//                 } catch (\Exception $ex) {
//                     log_message('error', "MIKROTIK SETUP: Failed web-proxy/error.html: " . $ex->getMessage());
//                 }
//             }

//             // Also upload to root as fallback
//             try {
//                 $router_client->query(
//                     (new Query('/file/set'))
//                         ->equal('.id', 'error.html')
//                         ->equal('contents', $errorHtml)
//                 )->read();
//                 log_message('info', "MIKROTIK SETUP: Uploaded error.html to root");
//             } catch (\Exception $e) {
//                 try {
//                     $router_client->query(
//                         (new Query('/file/add'))
//                             ->equal('name', 'error.html')
//                             ->equal('contents', $errorHtml)
//                     )->read();
//                     log_message('info', "MIKROTIK SETUP: Created error.html at root");
//                 } catch (\Exception $ex) {
//                     log_message('error', "MIKROTIK SETUP: Failed root error.html: " . $ex->getMessage());
//                 }
//             }

//             // Verify ONLY the specific file - not full file list
//             try {
//                 $fileCheck = $router_client->query(
//                     (new Query('/file/print'))->where('name', 'web-proxy/error.html')
//                 )->read();
//                 log_message('info', "MIKROTIK SETUP: web-proxy/error.html check: " . json_encode($fileCheck));
//             } catch (\Exception $e) {
//                 log_message('error', "MIKROTIK SETUP: File check failed: " . $e->getMessage());
//             }

//             return $this->response->setJSON(['status' => 'success', 'message' => 'Setup completed successfully']);
//         } catch (\Exception $e) {
//             log_message('error', "MIKROTIK SETUP FAILED: " . $e->getMessage());
//             return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
//         }
    }

    public function removeExpiredProfile($router_id)
    {
        set_time_limit(300);
        helper('router');

        $router_client = routerClient($router_id);
        if (!$router_client) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cannot connect to router']);
        }

        try {
            $this->removeExpiredRules($router_client);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Removed successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function removeExpiredRules($router_client)
    {
        // Remove Filter Rules with "Expired" comment
        $filters = $router_client->query(
            (new Query('/ip/firewall/filter/print'))->where('comment', 'Expired')
        )->read();
        foreach ($filters as $f) {
            if (isset($f['.id'])) {
                $router_client->query(
                    (new Query('/ip/firewall/filter/remove'))->equal('.id', $f['.id'])
                )->read();
            }
        }
        log_message('info', "MIKROTIK REMOVE: Filter rules removed");

        // Remove NAT Rules with "Expired" comment
        $nats = $router_client->query(
            (new Query('/ip/firewall/nat/print'))->where('comment', 'Expired')
        )->read();
        foreach ($nats as $n) {
            if (isset($n['.id'])) {
                $router_client->query(
                    (new Query('/ip/firewall/nat/remove'))->equal('.id', $n['.id'])
                )->read();
            }
        }
        log_message('info', "MIKROTIK REMOVE: NAT rules removed");

        // Remove only proxy access rules with "Expired" comment
        $proxyAccess = $router_client->query(
            (new Query('/ip/proxy/access/print'))->where('comment', 'Expired')
        )->read();
        foreach ($proxyAccess as $p) {
            if (isset($p['.id'])) {
                $router_client->query(
                    (new Query('/ip/proxy/access/remove'))->equal('.id', $p['.id'])
                )->read();
            }
        }
        log_message('info', "MIKROTIK REMOVE: Proxy access rules removed");

        // Remove expired PPPoE profile
        try {
            $profiles = $router_client->query(
                (new Query('/ppp/profile/print'))->where('name', 'expired')
            )->read();
            foreach ($profiles as $profile) {
                if (isset($profile['.id'])) {
                    $router_client->query(
                        (new Query('/ppp/profile/remove'))->equal('.id', $profile['.id'])
                    )->read();
                }
            }
            log_message('info', "MIKROTIK REMOVE: PPP profile removed");
        } catch (\Exception $e) {
            log_message('info', "MIKROTIK REMOVE: PPP profile not found, skipping");
        }

        // Disable proxy
        $router_client->query((new Query('/ip/proxy/set'))->equal('enabled', 'no'))->read();
        log_message('info', "MIKROTIK REMOVE: Proxy disabled");
    }

    public function setupRadius($routerId)
    {
        if (getSetting('enable_radius', 'no') !== 'yes') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'RADIUS Integration is currently disabled in Software Settings. Please enable it first.'
            ]);
        }
        
        set_time_limit(300);
        helper('router');

        $radiusIp = getSetting('radius_server_ip', '203.18.158.157');
        $radiusSecret = getSetting('radius_secret', 'ISP_Secret_123');

        $client = routerClient($routerId);
        if (!$client || is_array($client)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Cannot connect to router: ' . ($client['error'] ?? 'Unknown error')
            ]);
        }

        try {
            $logs = [];
            $logs[] = "Starting RADIUS configuration for Router ID: $routerId";

            // 1. Add RADIUS server if not exists
            $radiusServers = $client->query('/radius/print')->read();
            $exists = false;
            foreach ($radiusServers as $server) {
                if (($server['address'] ?? '') === $radiusIp) {
                    $exists = true;
                    // Update secret if it's different
                    if (($server['secret'] ?? '') !== $radiusSecret) {
                        $client->query('/radius/set', [
                            '.id' => $server['.id'],
                            'secret' => $radiusSecret
                        ])->read();
                        $logs[] = "Updated existing RADIUS server secret.";
                    } else {
                        $logs[] = "RADIUS server already configured with correct IP and secret.";
                    }
                    break;
                }
            }

            if (!$exists) {
                $client->query('/radius/add', [
                    'service' => 'ppp',
                    'address' => $radiusIp,
                    'secret' => $radiusSecret,
                    'comment' => 'Added by ISP Core UI'
                ])->read();
                $logs[] = "Added new RADIUS server: $radiusIp";
            }

            // 2. Enable RADIUS for PPP
            $client->query('/ppp/aaa/set', ['use-radius' => 'yes'])->read();
            $logs[] = "Enabled RADIUS for PPP.";

            // 3. Enable Incoming requests (CoA)
            $client->query('/radius/incoming/set', ['accept' => 'yes'])->read();
            $logs[] = "Enabled RADIUS incoming requests (RFC 3576).";

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'RADIUS configured successfully',
                'details' => $logs
            ]);
        } catch (\Exception $e) {
            log_message('error', "RADIUS SETUP FAILED for Router $routerId: " . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to configure RADIUS: ' . $e->getMessage()
            ]);
        }
    }
}
