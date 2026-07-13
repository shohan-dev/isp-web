<?php

namespace Zapi\Modules\Customer\Device\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

/**
 * Customer "connected devices" view.
 *
 * IMPORTANT (device-class reality): subscribers connect via PPPoE from their
 * own home router. The ISP MikroTik therefore only sees the subscriber's
 * SESSION (one WAN link), not the phones/laptops on the customer's home LAN —
 * those sit behind the home router's NAT and are invisible to our backend.
 * Returning the router's full DHCP/ARP table would leak OTHER customers'
 * devices, so we never do that. The full home-device list requires reaching
 * the home router itself (TR-069 in a later phase, or the in-app WebView that
 * opens the router's admin page on the customer's LAN).
 *
 * Block / unblock / bandwidth-limit are not yet implemented on this transport;
 * they return an honest `pending` rather than a fake success.
 */
class DeviceService extends CustomerBaseService
{
    /** Default LAN gateway guess for the WebView fallback (user-confirmable in the app). */
    private const DEFAULT_WEB_ADMIN_URL = 'http://192.168.0.1';

    public function getConnectedDevices($userId)
    {
        helper('router');
        $userId = (int) $userId;
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }
        if (!$this->canPerformAction($userId, 'view_devices')) {
            return $this->respondError('You are not allowed to perform this action', 403);
        }

        $routerId = $user->router_id ?? null;

        // Resolve PPPoE name from UserRouterDataModel and look up the active session.
        $session = null;
        if ($routerId) {
            helper('router');
            $pppoe = resolvePppoeSecret((int) $userId); // BUG-22: shared helper
            if ($pppoe) {
                $client = routerClient($routerId);
                if ($client && !is_array($client)) {
                    try {
                        $activeData = getactive_user($client);
                        foreach ($activeData['data']['activeusers'] ?? [] as $sess) {
                            if (($sess['name'] ?? '') === $pppoe) {
                                $session = $sess;
                                break;
                            }
                        }
                    } catch (\Throwable $e) {
                        log_message('info', 'DeviceService: MikroTik session lookup failed: ' . $e->getMessage());
                    }
                }
            }
        }

        $connection = [
            'online' => $session !== null,
            'ip'     => $session['address'] ?? null,
            'mac'    => $session['caller-id'] ?? null,
            'uptime' => $session['uptime'] ?? null,
        ];

        $this->logAction($userId, 'view_devices', 'Connected-devices view requested', ['router' => $routerId]);

        return $this->respondSuccess([
            'user_id'                => $userId,
            'connection'             => $connection,
            // ISP side cannot enumerate home-LAN devices on a PPPoE link.
            'devices'                => [],
            'total_devices'          => 0,
            'home_devices_supported' => false,
            'web_admin_url'          => self::DEFAULT_WEB_ADMIN_URL,
            'message'                => $connection['online']
                ? 'আপনার সংযোগ সচল আছে। বাসার ওয়াইফাইয়ে যুক্ত ডিভাইসগুলো দেখতে রাউটারের অ্যাডমিন পেজ খুলুন।'
                : 'এই মুহূর্তে আপনার সংযোগ সক্রিয় দেখা যাচ্ছে না। বাসার ওয়াইফাইয়ে যুক্ত ডিভাইস দেখতে রাউটারের অ্যাডমিন পেজ খুলুন।',
        ]);
    }

    public function getDeviceInfo($userId, $macAddress)
    {
        $userId = (int) $userId;
        if (!$userId || !$macAddress) {
            return $this->respondError('User ID and MAC address are required', 400);
        }

        // Per-home-device detail is not available from the ISP side on PPPoE.
        return $this->respondSuccess([
            'user_id'       => $userId,
            'mac_address'   => $macAddress,
            'status'        => 'pending',
            'web_admin_url' => self::DEFAULT_WEB_ADMIN_URL,
            'message'       => 'ডিভাইসের বিস্তারিত তথ্য রাউটারের অ্যাডমিন পেজ থেকে দেখা যাবে।',
        ]);
    }

    public function blockDevice($userId, $macAddress)
    {
        return $this->notYetAvailable((int) $userId, $macAddress, 'block_device');
    }

    public function unblockDevice($userId, $macAddress)
    {
        return $this->notYetAvailable((int) $userId, $macAddress, 'unblock_device');
    }

    public function limitBandwidth($userId, $macAddress, $limit)
    {
        return $this->notYetAvailable((int) $userId, $macAddress, 'limit_bandwidth');
    }

    /**
     * Honest placeholder for per-device controls that require home-router access
     * (TR-069 / vendor API), planned for a later phase.
     */
    private function notYetAvailable(int $userId, $macAddress, string $action)
    {
        if (!$userId || !$macAddress) {
            return $this->respondError('User ID and MAC address are required', 400);
        }

        return $this->respondSuccess([
            'user_id'       => $userId,
            'mac_address'   => $macAddress,
            'action'        => $action,
            'status'        => 'pending',
            'web_admin_url' => self::DEFAULT_WEB_ADMIN_URL,
            'message'       => 'এই সুবিধাটি শীঘ্রই আসছে। আপাতত রাউটারের অ্যাডমিন পেজ থেকে ডিভাইস নিয়ন্ত্রণ করা যাবে।',
        ]);
    }
}
