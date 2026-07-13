<?php

namespace App\Libraries;

use Config\Cache as CacheConfig;
use Predis\Client;
use Throwable;

/**
 * Read-only Redis key inspector for the ops/debug UI.
 * Scans keys lightly, then loads TYPE/TTL/value only for the current page.
 */
class RedisInspector
{
    private const MAX_KEYS = 5000;

    private const DISPLAY_LIMIT = 4000;

    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** @var list<string> */
    public const SORT_OPTIONS = ['key_desc', 'key_asc', 'ttl_desc', 'ttl_asc', 'category_asc'];

    private ?Client $client = null;

    private ?string $connectError = null;

    public function connect(): bool
    {
        if (! UpstashRedisConfig::enabled()) {
            $this->connectError = 'Redis is disabled (redis.enabled=false in .env).';

            return false;
        }

        try {
            $config = config(CacheConfig::class);
            $params = UpstashRedisConfig::normalizeCacheConfig($config->redis);

            $this->client = UpstashRedisConfig::createPredisClient([
                'scheme'   => $params['scheme'] ?? 'tcp',
                'host'     => $params['host'],
                'port'     => $params['port'],
                'password' => $params['password'] ?? null,
                'timeout'  => $params['timeout'] ?? 1.0,
            ]);
            $this->client->ping();

            return true;
        } catch (Throwable $e) {
            $this->connectError = $e->getMessage();

            return false;
        }
    }

    public function getConnectionError(): ?string
    {
        return $this->connectError;
    }

    /**
     * @return list<string>
     */
    public static function categoryOptions(): array
    {
        return [
            'Session',
            'Kill-switch flag',
            'Permission cache',
            'Settings cache',
            'JWT revocation',
            'Router traffic',
            'Dashboard cache',
            'Router circuit breaker',
            'Action cooldown',
            'App cache (other)',
            'Other',
        ];
    }

    /**
     * @param array{
     *   pattern?: string,
     *   search?: string,
     *   category?: string,
     *   page?: int,
     *   per_page?: int,
     *   sort?: string
     * } $options
     *
     * @return array<string, mixed>
     */
    public function inspect(array $options = []): array
    {
        $pattern  = trim((string) ($options['pattern'] ?? '*'));
        $pattern  = $pattern === '' ? '*' : $pattern;
        $search   = trim((string) ($options['search'] ?? ''));
        $category = trim((string) ($options['category'] ?? ''));
        $page     = max(1, (int) ($options['page'] ?? 1));
        $perPage  = (int) ($options['per_page'] ?? 25);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 25;
        }
        $sort = (string) ($options['sort'] ?? 'key_desc');
        if (! in_array($sort, self::SORT_OPTIONS, true)) {
            $sort = 'key_desc';
        }

        $config  = config(CacheConfig::class);
        $host    = (string) ($config->redis['host'] ?? '127.0.0.1');
        $handler = UpstashRedisConfig::enabled()
            ? (string) env('cache.handler', 'file')
            : 'file';

        $emptyPagination = [
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => 0,
            'total_pages' => 0,
            'from'        => 0,
            'to'          => 0,
            'sort'        => $sort,
        ];

        if ($this->client === null && ! $this->connect()) {
            return [
                'connected'   => false,
                'error'       => $this->connectError,
                'host'        => $host,
                'handler'     => $handler,
                'stats'       => [],
                'entries'     => [],
                'truncated'   => false,
                'pattern'     => $pattern,
                'search'      => $search,
                'category'    => $category,
                'pagination'  => $emptyPagination,
            ];
        }

        $scanned   = $this->scanKeys($pattern);
        $truncated = count($scanned) >= self::MAX_KEYS;
        $keys      = $this->filterKeys($scanned, $search, $category);
        $keys      = $this->sortKeys($keys, $sort);

        $total       = count($keys);
        $totalPages  = $total > 0 ? (int) ceil($total / $perPage) : 0;
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }
        $offset      = ($page - 1) * $perPage;
        $pageKeys    = array_slice($keys, $offset, $perPage);
        $entries     = [];

        foreach ($pageKeys as $key) {
            $entries[] = $this->inspectKey($key);
        }

        $from = $total > 0 ? $offset + 1 : 0;
        $to   = $total > 0 ? min($offset + count($entries), $total) : 0;

        return [
            'connected'  => true,
            'error'      => null,
            'host'       => $host,
            'handler'    => $handler,
            'stats'      => $this->serverStats($total, count($entries)),
            'entries'    => $entries,
            'truncated'  => $truncated,
            'pattern'    => $pattern,
            'search'     => $search,
            'category'   => $category,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
                'from'        => $from,
                'to'          => $to,
                'sort'        => $sort,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function scanKeys(string $pattern): array
    {
        $keys   = [];
        $cursor = '0';

        do {
            $result = $this->client->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 200]);
            $cursor = (string) ($result[0] ?? '0');
            $batch  = $result[1] ?? [];

            foreach ($batch as $key) {
                $keys[] = (string) $key;
                if (count($keys) >= self::MAX_KEYS) {
                    return $keys;
                }
            }
        } while ($cursor !== '0');

        return $keys;
    }

    /**
     * @param list<string> $keys
     *
     * @return list<string>
     */
    private function filterKeys(array $keys, string $search, string $category): array
    {
        $searchLower = strtolower($search);

        return array_values(array_filter($keys, function (string $key) use ($searchLower, $category): bool {
            if ($category !== '' && $this->categorize($key) !== $category) {
                return false;
            }
            if ($searchLower !== '' && strpos(strtolower($key), $searchLower) === false) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param list<string> $keys
     *
     * @return list<string>
     */
    private function sortKeys(array $keys, string $sort): array
    {
        if ($sort === 'ttl_desc' || $sort === 'ttl_asc') {
            $ttlMap = $this->fetchTtlMap($keys);
            usort($keys, static function (string $a, string $b) use ($ttlMap, $sort): int {
                $ta = $ttlMap[$a] ?? -2;
                $tb = $ttlMap[$b] ?? -2;
                $cmp = $tb <=> $ta;
                if ($cmp !== 0) {
                    return $sort === 'ttl_desc' ? $cmp : -$cmp;
                }

                return strcmp($b, $a);
            });

            return $keys;
        }

        if ($sort === 'category_asc') {
            usort($keys, function (string $a, string $b): int {
                $cat = strcmp($this->categorize($a), $this->categorize($b));
                if ($cat !== 0) {
                    return $cat;
                }

                return strcmp($b, $a);
            });

            return $keys;
        }

        if ($sort === 'key_asc') {
            sort($keys, SORT_STRING);

            return $keys;
        }

        // Default: newest / latest keys first (reverse alphabetical).
        rsort($keys, SORT_STRING);

        return $keys;
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, int>
     */
    private function fetchTtlMap(array $keys): array
    {
        $map = [];
        foreach ($keys as $key) {
            try {
                $map[$key] = (int) $this->unwrapRedisValue($this->client->ttl($key));
            } catch (Throwable $e) {
                $map[$key] = -2;
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function inspectKey(string $key): array
    {
        $type  = $this->normalizeType($this->client->type($key));
        $ttl   = (int) $this->unwrapRedisValue($this->client->ttl($key));
        $size  = $this->estimateSize($key, $type);
        $value = $this->readValue($key, $type);

        return [
            'key'         => $key,
            'category'    => $this->categorize($key),
            'type'        => strtoupper($type),
            'ttl'         => $ttl,
            'ttl_label'   => $this->formatTtl($ttl),
            'size_bytes'  => $size,
            'size_label'  => $this->formatBytes($size),
            'value'       => $value['display'],
            'value_full'  => $value['full'],
            'truncated'   => $value['truncated'],
        ];
    }

    private function normalizeType(mixed $type): string
    {
        $type = $this->unwrapRedisValue($type);

        if (is_string($type)) {
            return strtolower($type);
        }

        if (is_int($type)) {
            return match ($type) {
                1       => 'string',
                2       => 'set',
                3       => 'list',
                4       => 'zset',
                5       => 'hash',
                6       => 'stream',
                default => 'unknown',
            };
        }

        return 'unknown';
    }

    /**
     * Predis wraps many Redis replies (TYPE, TTL, GET, etc.) in response objects.
     */
    private function unwrapRedisValue(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_object($value)) {
            if (method_exists($value, 'getPayload')) {
                return $value->getPayload();
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
        }

        return $value;
    }

    private function categorize(string $key): string
    {
        if (str_starts_with($key, 'isp_sess:')) {
            return 'Session';
        }
        if (str_starts_with($key, 'ispc:flag_')) {
            return 'Kill-switch flag';
        }
        if (str_starts_with($key, 'ispc:perm_')) {
            return 'Permission cache';
        }
        if (str_starts_with($key, 'ispc:set2_')) {
            return 'Settings cache';
        }
        if (str_starts_with($key, 'ispc:jwt_revoke_after_')) {
            return 'JWT revocation';
        }
        if (str_starts_with($key, 'ispc:traffic_')) {
            return 'Router traffic';
        }
        if (str_starts_with($key, 'ispc:dash_')) {
            return 'Dashboard cache';
        }
        if (str_starts_with($key, 'ispc:router_down_')) {
            return 'Router circuit breaker';
        }
        if (str_starts_with($key, 'ispc:rc_cooldown_')) {
            return 'Action cooldown';
        }
        if (str_starts_with($key, 'ispc:')) {
            return 'App cache (other)';
        }

        return 'Other';
    }

    private function formatTtl(int $ttl): string
    {
        if ($ttl === -2) {
            return 'missing';
        }
        if ($ttl === -1) {
            return 'no expiry';
        }
        if ($ttl < 60) {
            return $ttl . 's';
        }
        if ($ttl < 3600) {
            return intdiv($ttl, 60) . 'm ' . ($ttl % 60) . 's';
        }

        $hours   = intdiv($ttl, 3600);
        $minutes = intdiv($ttl % 3600, 60);

        return $hours . 'h ' . $minutes . 'm';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 2) . ' MB';
    }

    /**
     * @return array{display: string, full: string, truncated: bool}
     */
    private function readValue(string $key, string $type): array
    {
        try {
            $raw = match ($type) {
                'string' => (string) ($this->unwrapRedisValue($this->client->get($key)) ?? ''),
                'hash'   => $this->formatHashValue($this->client->hGetAll($key)),
                'list'   => json_encode($this->client->lRange($key, 0, 49), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'set'    => json_encode($this->client->sMembers($key), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'zset'   => json_encode($this->client->zRange($key, 0, 49, ['WITHSCORES' => true]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                default  => '(unsupported type — key metadata only)',
            };
        } catch (Throwable $e) {
            $raw = '(read error: ' . $e->getMessage() . ')';
        }

        $full      = (string) $raw;
        $truncated = strlen($full) > self::DISPLAY_LIMIT;
        $display   = $truncated ? substr($full, 0, self::DISPLAY_LIMIT) . '…' : $full;

        return [
            'display'   => $display,
            'full'      => $full,
            'truncated' => $truncated,
        ];
    }

    /**
     * @param array<string, string> $hash
     */
    private function formatHashValue(array $hash): string
    {
        if (isset($hash['__ci_type'], $hash['__ci_value'])) {
            $decoded = $this->decodeCiCacheValue((string) $hash['__ci_type'], (string) $hash['__ci_value']);

            return json_encode([
                'cache_type' => $hash['__ci_type'],
                'value'      => $decoded,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        return json_encode($hash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function decodeCiCacheValue(string $type, string $value): mixed
    {
        if ($type === 'string') {
            return $value;
        }
        if ($type === 'integer') {
            return (int) $value;
        }
        if ($type === 'double') {
            return (float) $value;
        }
        if ($type === 'boolean') {
            return $value === '1' || strtolower($value) === 'true';
        }
        if ($type === 'NULL' || $type === 'null') {
            return null;
        }
        if (in_array($type, ['array', 'object'], true)) {
            $unserialized = @unserialize($value, ['allowed_classes' => false]);

            return $unserialized !== false || $value === 'b:0;' ? $unserialized : $value;
        }

        return $value;
    }

    private function estimateSize(string $key, string $type): int
    {
        try {
            return match ($type) {
                'string' => strlen((string) ($this->unwrapRedisValue($this->client->get($key)) ?? '')),
                'hash'   => strlen(json_encode($this->client->hGetAll($key)) ?: ''),
                'list'   => strlen(json_encode($this->client->lRange($key, 0, -1)) ?: ''),
                'set'    => strlen(json_encode($this->client->sMembers($key)) ?: ''),
                'zset'   => strlen(json_encode($this->client->zRange($key, 0, -1, ['WITHSCORES' => true])) ?: ''),
                default  => 0,
            };
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serverStats(int $matchedKeys, int $loadedKeys): array
    {
        $stats = [
            'matched_keys' => $matchedKeys,
            'loaded_keys'  => $loadedKeys,
        ];

        try {
            $info = $this->client->info();
            if (is_array($info)) {
                $stats['redis_version']     = $info['redis_version'] ?? $info['Server']['redis_version'] ?? null;
                $stats['used_memory']        = $info['used_memory_human'] ?? $info['Memory']['used_memory_human'] ?? null;
                $stats['connected_clients']  = $info['connected_clients'] ?? $info['Clients']['connected_clients'] ?? null;
                $dbSize                      = $this->unwrapRedisValue($this->client->dbSize());
                $stats['total_keys']         = is_numeric($dbSize) ? (int) $dbSize : null;
            }
        } catch (Throwable $e) {
            $stats['info_error'] = $e->getMessage();
        }

        return $stats;
    }
}
