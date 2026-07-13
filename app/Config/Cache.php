<?php

namespace Config;

use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\Cache\Handlers\FileHandler;
use CodeIgniter\Cache\Handlers\MemcachedHandler;
use CodeIgniter\Cache\Handlers\PredisHandler;
use CodeIgniter\Cache\Handlers\RedisHandler;
use App\Libraries\UpstashRedisConfig;
use CodeIgniter\Cache\Handlers\WincacheHandler;
use CodeIgniter\Config\BaseConfig;

class Cache extends BaseConfig
{
    public function __construct()
    {
        parent::__construct();

        if (! UpstashRedisConfig::enabled()) {
            $this->handler       = 'file';
            $this->backupHandler = 'file';
        }
    }

    /**
     * --------------------------------------------------------------------------
     * Primary Handler
     * --------------------------------------------------------------------------
     *
     * The name of the preferred handler that should be used. If for some reason
     * it is not available, the $backupHandler will be used in its place.
     */
    public string $handler = 'file';

    /**
     * --------------------------------------------------------------------------
     * Backup Handler
     * --------------------------------------------------------------------------
     *
     * The name of the handler that will be used in case the first one is
     * unreachable. Often, 'file' is used here since the filesystem is
     * always available, though that's not always practical for the app.
     */
    // Phase 2 (MT-3): 'file' (was 'dummy') so that once the primary handler is
    // Redis, a Redis outage falls back to the file cache (degrade) instead of
    // silently caching nothing. Inert while $handler='file' (primary is always up).
    public string $backupHandler = 'file';

    /**
     * --------------------------------------------------------------------------
     * Cache Directory Path
     * --------------------------------------------------------------------------
     *
     * The path to where cache files should be stored, if using a file-based
     * system.
     *
     * @deprecated Use the driver-specific variant under $file
     */
    public string $storePath = WRITEPATH . 'cache/';

    /**
     * --------------------------------------------------------------------------
     * Cache Include Query String
     * --------------------------------------------------------------------------
     *
     * Whether to take the URL query string into consideration when generating
     * output cache files. Valid options are:
     *
     *    false      = Disabled
     *    true       = Enabled, take all query parameters into account.
     *                 Please be aware that this may result in numerous cache
     *                 files generated for the same page over and over again.
     *    array('q') = Enabled, but only take into account the specified list
     *                 of query parameters.
     *
     * @var bool|string[]
     */
    public $cacheQueryString = false;

    /**
     * --------------------------------------------------------------------------
     * Key Prefix
     * --------------------------------------------------------------------------
     *
     * This string is added to all cache item names to help avoid collisions
     * if you run multiple applications with the same cache engine.
     */
    // Phase 2: namespace EVERY cache key. With a managed/single-database Redis
    // (e.g. Upstash, which does not support SELECT to logical DB 1/2/3), the cache
    // and the sessions share one Redis DB — this prefix keeps cache keys
    // (`ispc:flag_*`, `ispc:jwt_revoke_after_*`, L2/throttler keys) cleanly
    // separated from session keys (`isp_sess:*`) and lets ops `SCAN MATCH ispc:*`
    // to flush only the cache without touching live sessions. The prefix is
    // exempt from reserved-char validation (only the key is checked), so the ':'
    // is safe. Override per-deploy with `cache.prefix` in `.env`.
    public string $prefix = 'ispc:';

    /**
     * --------------------------------------------------------------------------
     * Default TTL
     * --------------------------------------------------------------------------
     *
     * The default number of seconds to save items when none is specified.
     *
     * WARNING: This is not used by framework handlers where 60 seconds is
     * hard-coded, but may be useful to projects and modules. This will replace
     * the hard-coded value in a future release.
     */
    public int $ttl = 60;

    /**
     * --------------------------------------------------------------------------
     * Reserved Characters
     * --------------------------------------------------------------------------
     *
     * A string of reserved characters that will not be allowed in keys or tags.
     * Strings that violate this restriction will cause handlers to throw.
     * Default: {}()/\@:
     * Note: The default set is required for PSR-6 compliance.
     */
    public string $reservedCharacters = '{}()/\@:';

    /**
     * --------------------------------------------------------------------------
     * File settings
     * --------------------------------------------------------------------------
     * Your file storage preferences can be specified below, if you are using
     * the File driver.
     *
     * @var array<string, int|string|null>
     */
    public array $file = [
        'storePath' => WRITEPATH . 'cache/',
        'mode'      => 0640,
    ];

    /**
     * -------------------------------------------------------------------------
     * Memcached settings
     * -------------------------------------------------------------------------
     * Your Memcached servers can be specified below, if you are using
     * the Memcached drivers.
     *
     * @see https://codeigniter.com/user_guide/libraries/caching.html#memcached
     *
     * @var array<string, bool|int|string>
     */
    public array $memcached = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];

    /**
     * -------------------------------------------------------------------------
     * Redis settings
     * -------------------------------------------------------------------------
     * Your Redis server can be specified below, if you are using
     * the Redis or Predis drivers.
     *
     * @var array<string, int|string|null>
     */
    // Phase 2 (MT-3): finite connect timeout — NEVER 0. With 0 a stalled Redis
    // blocks the FPM worker indefinitely; 1.0s fails fast so a Redis hiccup
    // degrades (to $backupHandler='file') instead of taking the pool down.
    //
    // FULLY DYNAMIC (like the DB block): every key below binds from `.env` via
    // CI4 BaseConfig — `cache.handler=redis`, `cache.redis.host/port/password/
    // database/timeout`. No code change is needed to cut over; the values live in
    // `.env` next to `database.default.*`.
    //
    // MANAGED / EXTERNAL Redis (Upstash, etc.): set
    //   cache.redis.host = tls://<id>.upstash.io   (the `tls://` scheme makes
    //   phpredis connect over TLS — required by Upstash), cache.redis.password =
    //   <token>, cache.redis.database = 0. Upstash is SINGLE-DB only (SELECT to
    //   1/2/3 is unsupported), so keep database=0 and rely on key prefixes
    //   ($prefix above for cache, `isp_sess:` for sessions) for separation. Use a
    //   slightly looser timeout (e.g. 1.5s) for an off-box endpoint. Requires
    //   phpredis >= 5.3 built with OpenSSL on the server.
    public array $redis = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 1.0,
        'database' => 0,
    ];

    /**
     * --------------------------------------------------------------------------
     * Available Cache Handlers
     * --------------------------------------------------------------------------
     *
     * This is an array of cache engine alias' and class names. Only engines
     * that are listed here are allowed to be used.
     *
     * @var array<string, string>
     */
    public array $validHandlers = [
        'dummy'     => DummyHandler::class,
        'file'      => FileHandler::class,
        'memcached' => MemcachedHandler::class,
        'predis'    => \App\Cache\Handlers\UpstashPredisHandler::class,
        'redis'     => RedisHandler::class,
        'wincache'  => WincacheHandler::class,
    ];
}
