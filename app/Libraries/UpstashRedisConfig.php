<?php

namespace App\Libraries;

use CodeIgniter\Session\Exceptions\SessionException;

/**
 * Normalizes Upstash / TLS Redis DSNs for Predis (pure PHP — no phpredis ext).
 */
class UpstashRedisConfig
{
    private const DEFAULT_PORT     = 6379;
    private const DEFAULT_PROTOCOL = 'tcp';

    /**
     * Master Redis toggle from `.env` (`redis.enabled`).
     * When false, cache + session are forced to file handlers (no Redis I/O).
     */
    public static function enabled(): bool
    {
        $val = env('redis.enabled', true);

        if (is_bool($val)) {
            return $val;
        }

        $parsed = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? true;
    }

    /**
     * Parse CI4 session savePath DSN (same format as RedisHandler).
     *
     * @return array{scheme: string, host: string, port: int, password: ?string, database: int, timeout: float, prefix: string}
     */
    public static function parseSessionSavePath(string $savePath): array
    {
        if ($savePath === '') {
            throw SessionException::forEmptySavepath();
        }

        if (! preg_match('#(?:(tcp|tls)://)?([^:?]+)(?:\:(\d+))?(\?.+)?#', $savePath, $matches)) {
            throw SessionException::forInvalidSavePathFormat($savePath);
        }

        $query = $matches[4] ?? '';

        $prefix = 'ci_session:';
        if (preg_match('#prefix=([^\s&]+)#', $query, $match)) {
            $prefix = $match[1];
        }

        return [
            'scheme'   => ! empty($matches[1]) ? $matches[1] : self::DEFAULT_PROTOCOL,
            'host'     => $matches[2],
            'port'     => empty($matches[3]) ? self::DEFAULT_PORT : (int) $matches[3],
            'password' => preg_match('#auth=([^\s&]+)#', $query, $match) ? $match[1] : null,
            'database' => preg_match('#database=(\d+)#', $query, $match) ? (int) $match[1] : 0,
            'timeout'  => preg_match('#timeout=(\d+\.\d+|\d+)#', $query, $match) ? (float) $match[1] : 1.5,
            'prefix'   => $prefix,
        ];
    }

    /**
     * @param array<string, mixed> $redis Config\Cache::$redis block from .env
     *
     * @return array<string, mixed> Predis Client parameters
     */
    public static function normalizeCacheConfig(array $redis): array
    {
        $host   = (string) ($redis['host'] ?? '127.0.0.1');
        $scheme = 'tcp';

        if (str_starts_with($host, 'tls://')) {
            $scheme = 'tls';
            $host   = substr($host, 6);
        } elseif (str_starts_with($host, 'tcp://')) {
            $host = substr($host, 6);
        }

        $config = [
            'scheme'   => $scheme,
            'host'     => $host,
            'port'     => (int) ($redis['port'] ?? 6379),
            'password' => $redis['password'] ?? null,
            'timeout'  => (float) ($redis['timeout'] ?? 1.5),
        ];

        // Upstash does not support SELECT — keys are separated by prefix only.
        unset($redis['database']);

        return $config;
    }

    /**
     * @param array{scheme: string, host: string, port: int, password: ?string, timeout: float} $config
     */
    public static function createPredisClient(array $config): \Predis\Client
    {
        $parameters = [
            'scheme'   => $config['scheme'],
            'host'     => $config['host'],
            'port'     => $config['port'],
            'password' => $config['password'],
        ];

        // Phase-C / BUG-05,06,10,15 fix: disable Predis 2.x exception throwing so that
        // a Redis blip after connect returns falsy instead of throwing ConnectionException,
        // which CI4 does not catch at runtime and which was 500-ing the payment webhook.
        // All callers already check return values; this makes the check actually useful.
        $options = ['exceptions' => false];
        if (($config['timeout'] ?? 0) > 0) {
            $options['parameters'] = ['timeout' => (string) $config['timeout']];
        }

        return new \Predis\Client($parameters, $options);
    }
}
