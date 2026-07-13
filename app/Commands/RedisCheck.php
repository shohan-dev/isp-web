<?php

namespace App\Commands;

use App\Libraries\UpstashRedisConfig;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * redis:check — verify the live Redis/Upstash connection END-TO-END (Phase 2).
 *
 *   php spark redis:check
 *
 * Reads the SAME config the app uses from `.env` (cache.redis.host/port/password/
 * database/timeout) — nothing is hardcoded, so it follows whatever you set. It
 * connects with raw phpredis the way CI4's RedisHandler does (host carries the
 * `tls://` scheme for TLS), then exercises the exact commands the cache + session
 * handlers issue (AUTH, SELECT, hMSet/hMGet/expire/del, set/get) so a PASS means
 * the real app path works — not just that a socket opened.
 *
 * Why this matters: with cache.handler=redis the app SILENTLY falls back to the
 * file cache if Redis is unreachable (so a broken Upstash looks "fine" until you
 * notice every key is a file), and Redis SESSIONS just fatal. This command makes
 * the failure loud and tells you which precondition is missing.
 *
 * Exit code 0 = all green, 1 = a check failed.
 */
class RedisCheck extends BaseCommand
{
    protected $group       = 'Redis';
    protected $name        = 'redis:check';
    protected $description = 'Verify the live Redis/Upstash connection (TLS, auth, DB, cache+session commands).';
    protected $usage       = 'redis:check';

    private int $fails = 0;

    public function run(array $params)
    {
        if (! UpstashRedisConfig::enabled()) {
            CLI::write('Redis is disabled (redis.enabled=false in .env).', 'yellow');
            CLI::write('Cache and sessions are using file storage. No connection check needed.', 'green');
            return 0;
        }

        $host     = (string) env('cache.redis.host', '127.0.0.1');
        $port     = (int) env('cache.redis.port', 6379);
        $password = (string) (env('cache.redis.password', '') ?? '');
        $database = (int) env('cache.redis.database', 0);
        $timeout  = (float) env('cache.redis.timeout', 1.5);
        $handler  = (string) env('cache.handler', 'file');

        CLI::write('Redis / Upstash connection check', 'yellow');
        CLI::write('  host=' . $host . ' port=' . $port . ' db=' . $database
            . ' timeout=' . $timeout . 's auth=' . ($password !== '' ? 'yes' : 'no')
            . ' cache.handler=' . $handler);
        CLI::newLine();

        // 1) phpredis extension present (CI4's RedisHandler needs ext-redis; the
        //    cache silently falls back to file without it, sessions fatal).
        if (! extension_loaded('redis')) {
            $this->fail('phpredis (ext-redis) is NOT loaded — install it (pecl install redis) '
                . 'and enable it. Without it cache.handler=redis degrades to file and Redis sessions fatal.');
            return $this->summary();
        }
        $ver = phpversion('redis');
        $this->ok("phpredis loaded (v{$ver})");
        if (version_compare($ver, '5.3.0', '<')) {
            $this->warn("phpredis {$ver} < 5.3 — TLS (tls://) needs >= 5.3 built with OpenSSL.");
        }
        if (strpos($host, 'tls://') === 0) {
            CLI::write('  note: TLS endpoint — phpredis must be built with OpenSSL '
                . '(a non-TLS build fails tls:// silently).', 'dark_gray');
        }

        // 2) Connect (same call shape as CI4 RedisHandler: connect(host, port, timeout)).
        $redis = new \Redis();
        try {
            if (! $redis->connect($host, ($host[0] === '/' ? 0 : $port), $timeout)) {
                $this->fail('connect() returned false — host/port/timeout or TLS handshake failed.');
                return $this->summary();
            }
            $this->ok('connect() ok');
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $this->fail('connect() threw: ' . $msg);
            if (stripos($msg, 'certificate') !== false || stripos($msg, 'SSL') !== false || stripos($msg, 'verify') !== false) {
                CLI::write('  → TLS cert verification failed. Install ca-certificates and point '
                    . 'php.ini openssl.cafile/capath at the system bundle '
                    . '(e.g. /etc/ssl/certs/ca-certificates.crt).', 'red');
            }
            return $this->summary();
        }

        // 3) AUTH (single-arg, as the app does; Upstash accepts the token alone).
        if ($password !== '') {
            try {
                $redis->auth($password) ? $this->ok('auth() ok') : $this->fail('auth() returned false — wrong token?');
            } catch (Throwable $e) {
                $this->fail('auth() threw: ' . $e->getMessage());
                return $this->summary();
            }
        } else {
            $this->warn('no password set (cache.redis.password empty) — Upstash normally requires a token.');
        }

        // 4) SELECT (Upstash supports only DB 0).
        try {
            $redis->select($database) ? $this->ok("select({$database}) ok") : $this->fail("select({$database}) returned false.");
        } catch (Throwable $e) {
            $this->fail("select({$database}) threw: " . $e->getMessage()
                . ($database !== 0 ? ' — Upstash only supports database 0.' : ''));
        }

        // 5) PING.
        try {
            $pong = $redis->ping();
            (($pong === true) || $pong === '+PONG' || $pong === 'PONG')
                ? $this->ok('ping() ok') : $this->fail('ping() unexpected: ' . var_export($pong, true));
        } catch (Throwable $e) {
            $this->fail('ping() threw: ' . $e->getMessage());
        }

        // 6) Cache round-trip — the EXACT commands CI4's cache RedisHandler issues
        //    (hMSet + expire + hMGet + del), under the app's 'ispc:' prefix.
        $cacheKey = 'ispc:redis_check_probe';
        try {
            $redis->del($cacheKey);
            $redis->hMSet($cacheKey, ['__ci_type' => 'string', '__ci_value' => 'ok']);
            $redis->expire($cacheKey, 30);
            $got = $redis->hMGet($cacheKey, ['__ci_type', '__ci_value']);
            $ttl = $redis->ttl($cacheKey);
            $redis->del($cacheKey);
            (($got['__ci_value'] ?? null) === 'ok' && $ttl > 0)
                ? $this->ok("cache hMSet/hMGet/expire/del round-trip ok (ttl was {$ttl}s)")
                : $this->fail('cache round-trip mismatch: ' . json_encode($got) . " ttl={$ttl}");
        } catch (Throwable $e) {
            $this->fail('cache hash round-trip threw: ' . $e->getMessage());
        }

        // 7) Session-style round-trip (plain set/get under the session prefix).
        $sessKey = 'isp_sess:redis_check_probe';
        try {
            $redis->setex($sessKey, 30, 'ok');
            $val = $redis->get($sessKey);
            $redis->del($sessKey);
            ($val === 'ok') ? $this->ok('session setex/get/del round-trip ok')
                : $this->fail('session round-trip mismatch: ' . var_export($val, true));
        } catch (Throwable $e) {
            $this->fail('session round-trip threw: ' . $e->getMessage());
        }

        try {
            $redis->close();
        } catch (Throwable $e) {
            // ignore
        }

        return $this->summary();
    }

    private function ok(string $msg): void
    {
        CLI::write('  ✓ ' . $msg, 'green');
    }

    private function warn(string $msg): void
    {
        CLI::write('  ! ' . $msg, 'yellow');
    }

    private function fail(string $msg): void
    {
        $this->fails++;
        CLI::write('  ✗ ' . $msg, 'red');
    }

    private function summary(): int
    {
        CLI::newLine();
        if ($this->fails === 0) {
            CLI::write('ALL CHECKS PASSED — Redis/Upstash is live and the app path works.', 'green');
            return EXIT_SUCCESS;
        }
        CLI::write($this->fails . ' check(s) FAILED — fix the above before relying on Redis. '
            . 'Cache will silently degrade to file; Redis sessions will fatal.', 'red');
        return EXIT_ERROR;
    }
}
