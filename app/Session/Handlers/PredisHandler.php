<?php

namespace App\Session\Handlers;

use App\Libraries\UpstashRedisConfig;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Handlers\BaseHandler;
use Config\App as AppConfig;
use Config\Session as SessionConfig;
use Predis\Client;
use ReturnTypeWillChange;
use Throwable;

/**
 * Session handler via Predis — remote Upstash Redis without phpredis extension.
 *
 * savePath DSN (same as CI4 RedisHandler):
 *   tls://host:6379?timeout=1.5&auth=TOKEN&database=0&prefix=isp_sess:
 */
class PredisHandler extends BaseHandler
{
    /** @var Client|null */
    protected $redis;

    protected string $keyPrefix = 'ci_session:';

    protected ?string $lockKey = null;

    protected bool $keyExists = false;

    protected int $sessionExpiration = 7200;

    /** @var array<string, mixed> */
    protected array $connection = [];

    public function __construct(AppConfig $config, string $ipAddress)
    {
        parent::__construct($config, $ipAddress);

        /** @var SessionConfig|null $session */
        $session = config(SessionConfig::class);

        if ($session instanceof SessionConfig) {
            $this->sessionExpiration = empty($session->expiration)
                ? (int) ini_get('session.gc_maxlifetime')
                : $session->expiration;
            $this->keyPrefix .= $session->cookieName . ':';
        } else {
            $this->sessionExpiration = empty($config->sessionExpiration)
                ? (int) ini_get('session.gc_maxlifetime')
                : $config->sessionExpiration;
            $this->keyPrefix .= $config->sessionCookieName . ':';
        }

        $this->parseSavePath();

        if ($this->matchIP === true) {
            $this->keyPrefix .= $this->ipAddress . ':';
        }
    }

    protected function parseSavePath(): void
    {
        $parsed = UpstashRedisConfig::parseSessionSavePath((string) $this->savePath);

        $this->connection = $parsed;
        $this->keyPrefix  = $parsed['prefix'];
    }

    public function open($path, $name): bool
    {
        try {
            $this->redis = UpstashRedisConfig::createPredisClient($this->connection);
            // exceptions=false: ping() returns false on error instead of throwing.
            $ping = $this->redis->ping();
            if ($ping === false || (is_object($ping) && method_exists($ping, 'getPayload') && $ping->getPayload() !== 'PONG')) {
                // Phase-C2: Redis unreachable — log and fail open() cleanly so CI4
                // can fall back to a different handler at the session-service level.
                $this->logger->error('Session: Redis ping failed (Predis). Sessions unavailable.');
                $this->redis = null;
                return false;
            }
        } catch (Throwable $e) {
            $this->logger->error('Session: Unable to connect to Redis (Predis): ' . $e->getMessage());
            $this->redis = null;
            return false;
        }

        return true;
    }

    #[ReturnTypeWillChange]
    public function read($id)
    {
        if (isset($this->redis) && $this->lockSession($id)) {
            if (! isset($this->sessionID)) {
                $this->sessionID = $id;
            }

            $data = $this->redis->get($this->keyPrefix . $id);

            if (is_string($data)) {
                $this->keyExists = true;
            } else {
                $data = '';
            }

            $this->fingerprint = md5($data);

            return $data;
        }

        return '';
    }

    public function write($id, $data): bool
    {
        if (! isset($this->redis)) {
            return false;
        }

        if ($this->sessionID !== $id) {
            // BUG-17 fix: reset lock state even when releaseLock() fails so
            // write() doesn't use a stale lockKey pointing at the old session.
            $this->releaseLock();
            $this->lockKey    = null;
            $this->lock       = false;
            if (! $this->lockSession($id)) {
                return false;
            }

            $this->keyExists = false;
            $this->sessionID = $id;
        }

        if ($this->lockKey === null) {
            return false;
        }

        // BUG-10 fix: guard the lock-refresh; a Predis throw here (exceptions=false
        // now makes it return false instead) must not abort the rest of the write.
        $this->redis->expire($this->lockKey, 300);

        if ($this->fingerprint !== ($fingerprint = md5($data)) || $this->keyExists === false) {
            $result = $this->redis->setex($this->keyPrefix . $id, $this->sessionExpiration, $data);
            if ($result) {
                $this->fingerprint = $fingerprint;
                $this->keyExists   = true;

                return true;
            }

            return false;
        }

        return (bool) $this->redis->expire($this->keyPrefix . $id, $this->sessionExpiration);
    }

    public function close(): bool
    {
        if (! isset($this->redis)) {
            return true;
        }

        try {
            $this->redis->ping();

            if ($this->lockKey !== null) {
                $this->redis->del([$this->lockKey]);
            }

            $this->redis->disconnect();
        } catch (Throwable $e) {
            $this->logger->error('Session: Predis close error: ' . $e->getMessage());
        }

        $this->redis = null;

        return true;
    }

    public function destroy($id): bool
    {
        if (isset($this->redis) && $this->lockKey !== null) {
            $this->redis->del([$this->keyPrefix . $id]);

            return $this->destroyCookie();
        }

        return false;
    }

    #[ReturnTypeWillChange]
    public function gc($max_lifetime)
    {
        return 1;
    }

    protected function lockSession(string $sessionID): bool
    {
        if (! isset($this->redis)) {
            return false;
        }

        $lockKey = $this->keyPrefix . $sessionID . ':lock';

        if ($this->lockKey === $lockKey) {
            return (bool) $this->redis->expire($this->lockKey, 300);
        }

        // Phase-C3 fix: exponential backoff with jitter instead of a flat 1s × 30 spin.
        // Total budget ≈ 10 s max — well under request_terminate_timeout (60 s) —
        // while reducing thundering-herd on contended sessions.
        $attempt  = 0;
        $maxWaitUs = 10_000_000; // 10 s hard ceiling
        $elapsed   = 0;

        do {
            $ttl = (int) $this->redis->ttl($lockKey);

            if ($ttl > 0) {
                // backoff: 50 ms * 2^attempt + up to 25 ms jitter, capped at 1 s
                $backoffUs = min(50_000 * (2 ** $attempt), 1_000_000) + random_int(0, 25_000);
                usleep($backoffUs);
                $elapsed += $backoffUs;

                if ($elapsed >= $maxWaitUs) {
                    break;
                }

                continue;
            }

            if (! $this->redis->setex($lockKey, 300, (string) Time::now()->getTimestamp())) {
                $this->logger->error('Session: Error while trying to obtain lock for ' . $this->keyPrefix . $sessionID);

                return false;
            }

            $this->lockKey = $lockKey;
            $this->lock    = true;

            return true;
        } while (++$attempt < 15); // 15 attempts × backing off ≈ ~10 s total

        log_message('error', 'Session: Unable to obtain lock for ' . $this->keyPrefix . $sessionID . ' after ' . $attempt . ' attempts, aborting.');

        return false;
    }

    protected function releaseLock(): bool
    {
        if (isset($this->redis) && $this->lockKey !== null && $this->lock) {
            if (! $this->redis->del([$this->lockKey])) {
                $this->logger->error('Session: Error while trying to free lock for ' . $this->lockKey);

                return false;
            }

            $this->lockKey = null;
            $this->lock    = false;
        }

        return true;
    }
}
