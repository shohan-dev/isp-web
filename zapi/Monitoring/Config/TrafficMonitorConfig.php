<?php

namespace Zapi\Monitoring\Config;

final class TrafficMonitorConfig
{
    // Static cache: arrays built once per process, not on every request.
    private static ?bool $enabled = null;
    /** @var array<int,string>|null */
    private static ?array $skipPrefixes = null;
    /** @var array<int,string>|null */
    private static ?array $skipContains = null;

    /**
     * Master on/off switch.  Set `zapi.monitor.enabled = false` in .env to
     * disable all traffic recording with zero per-request overhead.
     * Default ON so existing deployments are unaffected.
     */
    public static function enabled(): bool
    {
        if (self::$enabled === null) {
            $val = env('zapi.monitor.enabled', 'true');
            self::$enabled = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false;
        }
        return self::$enabled;
    }

    public static function dailyMaxBytes(): int
    {
        $gb = (int) env('zapi.monitor.dailyMaxGB', 10);
        return max(1, $gb) * 1024 * 1024 * 1024;
    }

    public static function rawRetentionDays(): int
    {
        return max(1, (int) env('zapi.monitor.rawRetentionDays', 7));
    }

    public static function summaryRetentionDays(): int
    {
        return max(1, (int) env('zapi.monitor.summaryRetentionDays', 365 * 50));
    }

    /**
     * Shared secret for the maintenance routes (flush-queue, maintain-queue,
     * retention-cleanup), supplied as query `key` or header `X-Monitor-Cron-Key`.
     * Fails CLOSED: while this is empty, those routes reject every request (403).
     */
    public static function cronSecret(): string
    {
        return (string) env('zapi.monitor.cronSecret', '');
    }

    /**
     * Process-level cached skip-prefix list (never rebuilt after first call).
     *
     * @return array<int,string>
     */
    public static function skipPrefixes(): array
    {
        if (self::$skipPrefixes === null) {
            self::$skipPrefixes = [
                'assets/',
                'public/',
                'favicon.ico',
                'api/docs',
                'api/docs/',
                'api/docs/swagger-ui/',
                'api/monitor/',
                'api/customer/users-load-traffic/',
                'api/customer/routers/load-traffic/',
                'api/customer/ping-user',
                'api/common/generate_204',
                'api/common/hotspot-detect.html',
                'api/common/connecttest.txt',
                'api/common/ncsi.txt',
                'api/common/favicon.ico',
                '/',
                '/customers/get-pppoe-status',
            ];
        }
        return self::$skipPrefixes;
    }

    /**
     * Process-level cached skip-contains list.
     *
     * @return array<int,string>
     */
    public static function skipContains(): array
    {
        if (self::$skipContains === null) {
            self::$skipContains = [
                'load-traffic',
                'health',
                'heartbeat',
            ];
        }
        return self::$skipContains;
    }
}

