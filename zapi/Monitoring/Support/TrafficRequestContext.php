<?php

namespace Zapi\Monitoring\Support;

final class TrafficRequestContext
{
    private const KEY_START = '__zapi_monitor_start_time';
    private const KEY_REQUEST_ID = '__zapi_monitor_request_id';

    public static function markStart(): void
    {
        $_SERVER[self::KEY_START] = microtime(true);
        $_SERVER[self::KEY_REQUEST_ID] = self::buildRequestId();
    }

    public static function getStartTime(): float
    {
        $value = $_SERVER[self::KEY_START] ?? null;
        return is_numeric($value) ? (float) $value : microtime(true);
    }

    public static function getRequestId(): string
    {
        $value = $_SERVER[self::KEY_REQUEST_ID] ?? '';
        if ($value !== '') {
            return (string) $value;
        }

        $generated = self::buildRequestId();
        $_SERVER[self::KEY_REQUEST_ID] = $generated;
        return $generated;
    }

    private static function buildRequestId(): string
    {
        try {
            return bin2hex(random_bytes(8)) . '-' . dechex((int) (microtime(true) * 1000000));
        } catch (\Throwable $e) {
            return uniqid('req_', true);
        }
    }
}

