<?php

namespace Zapi\Modules\Customer\Security\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class SecurityService extends CustomerBaseService
{
    public function reportFraud($userId, $description, $type)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $reportId = $this->createFraudReport((int) $userId, (string) $description, (string) $type);

        $this->notifySecurityTeam((int) $userId, $reportId);

        return $this->respondSuccess([
            'user_id'   => (int) $userId,
            'report_id' => $reportId,
            'ticket_id' => $reportId,
            'status'    => 'submitted',
            'message'   => 'Fraud report submitted successfully. Our security team will investigate.',
        ]);
    }

    public function checkSuspiciousLogins($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $logins    = $this->getRecentLogins((int) $userId);
        $suspicious = $this->analyzeSuspiciousLogins($logins);

        return $this->respondSuccess([
            'user_id'            => (int) $userId,
            'total_recent_logins'=> count($logins),
            'suspicious_count'   => count($suspicious),
            'suspicious_logins'  => $suspicious,
            'recommendations'    => $this->getSecurityRecommendations($suspicious),
        ]);
    }

    public function manageIPWhitelist($userId, $ipAddress, $action)
    {
        if (!$userId || !$ipAddress) {
            return $this->respondError('User ID and IP address are required', 400);
        }

        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return $this->respondError('Invalid IP address format', 400);
        }

        if ($action === 'add') {
            $this->addToWhitelist((int) $userId, $ipAddress);
            $message = "IP address {$ipAddress} has been whitelisted.";
        } else {
            $this->removeFromWhitelist((int) $userId, $ipAddress);
            $message = "IP address {$ipAddress} has been removed from whitelist.";
        }

        return $this->respondSuccess([
            'user_id'    => (int) $userId,
            'ip_address' => $ipAddress,
            'action'     => $action,
            'whitelist'  => $this->getWhitelist((int) $userId),
            'message'    => $message,
        ]);
    }

    public function toggleDeviceTrust($userId, $macAddress, $trusted)
    {
        if (!$userId || !$macAddress) {
            return $this->respondError('User ID and MAC address are required', 400);
        }

        $this->setDeviceTrust((int) $userId, $macAddress, (bool) $trusted);

        return $this->respondSuccess([
            'user_id'     => (int) $userId,
            'mac_address' => $macAddress,
            'trusted'     => (bool) $trusted,
            'message'     => $trusted ? 'Device marked as trusted' : 'Device trust removed',
        ]);
    }

    // ── private helpers ────────────────────────────────────────────────────────

    private function createFraudReport(int $userId, string $description, string $type): string
    {
        $user    = $this->user_model->find($userId);
        $adminId = $user ? (int) ($user->admin_id ?? 0) : 0;

        $data = [
            'user_id'    => $userId,
            'admin_ids'  => (string) $adminId,
            'subject'    => 'Fraud Report: ' . ucfirst($type ?: 'general'),
            'category'   => 'security',
            'priority'   => 'high',
            'details'    => $description ?: 'No details provided.',
            'datetime'   => date('Y-m-d H:i:s'),
            'remarks'    => null,
            'viewed'     => 0,
            'status'     => 'open',
        ];

        try {
            $id = $this->ticket_model->insert($data, true);
            return $id ? (string) $id : ('FR-' . time());
        } catch (\Throwable $e) {
            log_message('error', 'SecurityService: ticket insert failed: ' . $e->getMessage());
            return 'FR-' . time();
        }
    }

    private function notifySecurityTeam(int $userId, string $reportId): void
    {
        try {
            $user    = $this->user_model->find($userId);
            $adminId = $user ? (int) ($user->admin_id ?? 0) : 0;
            if ($adminId <= 0) {
                return;
            }

            $admin  = $this->user_model->find($adminId);
            $mobile = $admin ? trim((string) ($admin->mobile ?? '')) : '';
            if ($mobile === '') {
                return;
            }

            helper('sms');
            $msg = "Security Alert: A fraud report (#{$reportId}) has been submitted by user #{$userId}. Please review immediately.";
            Send_SMs([['mobile' => $mobile]], null, null, null, $msg);
        } catch (\Throwable $e) {
            log_message('error', 'SecurityService: security-team notify failed: ' . $e->getMessage());
        }
    }

    private function getRecentLogins(int $userId): array
    {
        try {
            $audit  = model('App\Models\AuditLogModel');
            $rows   = $audit
                ->where('user_id', $userId)
                ->whereIn('action', ['login', 'login_failed', 'login_success'])
                ->orderBy('created_at', 'DESC')
                ->limit(20)
                ->findAll();

            return array_map(static function ($r) {
                $row = is_object($r) ? $r : (object) $r;
                return [
                    'time'     => $row->created_at ?? null,
                    'ip'       => $row->ip_address ?? 'unknown',
                    'location' => 'Bangladesh',
                    'device'   => $row->user_agent ?? 'Unknown',
                    'action'   => $row->action ?? 'login',
                    'status'   => str_contains((string) ($row->action ?? ''), 'failed') ? 'failed' : 'success',
                ];
            }, $rows);
        } catch (\Throwable $e) {
            log_message('info', 'SecurityService: audit log fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    private function analyzeSuspiciousLogins(array $logins): array
    {
        $suspicious = [];
        foreach ($logins as $login) {
            if ($login['status'] === 'failed' || $this->isForeignIP($login['ip'])) {
                $suspicious[] = $login;
            }
        }
        return $suspicious;
    }

    private function isForeignIP(string $ip): bool
    {
        // Bangladesh ISP blocks are predominantly 103.x and 202.x;
        // flag anything outside as foreign for basic heuristic.
        $bdPrefixes = ['103.', '202.', '113.', '27.', '192.168.', '10.', '172.'];
        foreach ($bdPrefixes as $prefix) {
            if (str_starts_with($ip, $prefix)) {
                return false;
            }
        }
        return true;
    }

    private function getSecurityRecommendations(array $suspicious): array
    {
        if (count($suspicious) === 0) {
            return ['No suspicious activity detected on your account.'];
        }
        return [
            'We detected ' . count($suspicious) . ' suspicious login(s). Consider changing your password.',
            'Enable two-factor authentication for additional security.',
        ];
    }

    // ── cache-backed IP whitelist & device trust ──────────────────────────────

    private function getWhitelistKey(int $userId): string
    {
        return "security_whitelist_{$userId}";
    }

    private function getTrustKey(int $userId): string
    {
        return "security_trusted_devices_{$userId}";
    }

    private function getWhitelist(int $userId): array
    {
        try {
            $data = \Config\Services::cache()->get($this->getWhitelistKey($userId));
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function addToWhitelist(int $userId, string $ip): void
    {
        try {
            $cache = \Config\Services::cache();
            $list  = $this->getWhitelist($userId);
            if (!in_array($ip, $list, true)) {
                $list[] = $ip;
            }
            $cache->save($this->getWhitelistKey($userId), $list, 86400 * 365);
        } catch (\Throwable $e) {
            log_message('error', 'SecurityService: whitelist add failed: ' . $e->getMessage());
        }
    }

    private function removeFromWhitelist(int $userId, string $ip): void
    {
        try {
            $cache = \Config\Services::cache();
            $list  = array_values(array_filter($this->getWhitelist($userId), static fn ($v) => $v !== $ip));
            $cache->save($this->getWhitelistKey($userId), $list, 86400 * 365);
        } catch (\Throwable $e) {
            log_message('error', 'SecurityService: whitelist remove failed: ' . $e->getMessage());
        }
    }

    private function setDeviceTrust(int $userId, string $mac, bool $trusted): void
    {
        try {
            $cache   = \Config\Services::cache();
            $key     = $this->getTrustKey($userId);
            $devices = $cache->get($key);
            $devices = is_array($devices) ? $devices : [];

            if ($trusted) {
                $devices[$mac] = ['mac' => $mac, 'trusted_at' => date('Y-m-d H:i:s')];
            } else {
                unset($devices[$mac]);
            }

            $cache->save($key, $devices, 86400 * 365);
        } catch (\Throwable $e) {
            log_message('error', 'SecurityService: device trust failed: ' . $e->getMessage());
        }
    }
}
