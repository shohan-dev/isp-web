<?php

namespace App\Controllers;

use App\Libraries\UpstashRedisConfig;
use App\Services\JobQueue;
use Config\Database;

/**
 * Health — liveness/readiness probe for load balancers & uptime checks (Phase O).
 *
 *   GET /healthz  ->  200 {"status":"ok", ...}   when the DB is reachable
 *                     503 {"status":"fail", ...} when it is not
 *
 * Each check is wrapped so the endpoint itself never 500s — it always returns a
 * JSON snapshot (db, cache, queue depth, redis latency, fpm pool). The DB is the
 * hard readiness gate; cache/redis/fpm degradation is non-fatal. No auth/session.
 */
class Health extends BaseController
{
    public function index()
    {
        $db    = $this->checkDb();
        $cache = $this->checkCache();
        $redis = $this->checkRedisLatency();
        $fpm   = $this->checkFpmPool();
        $queue = $this->queueDepth();

        $ok = $db['ok'] === true;

        return $this->response
            ->setStatusCode($ok ? 200 : 503)
            ->setJSON([
                'status' => $ok ? 'ok' : 'fail',
                'checks' => [
                    'db'    => $db,
                    'cache' => $cache,
                    'redis' => $redis,
                    'fpm'   => $fpm,
                ],
                'queue' => $queue,
                'time'  => date('c'),
            ]);
    }

    private function checkDb(): array
    {
        try {
            $t0 = microtime(true);
            Database::connect()->query('SELECT 1');
            return ['ok' => true, 'latency_ms' => round((microtime(true) - $t0) * 1000, 2)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            cache()->save('healthz_ping', '1', 5);

            return ['ok' => cache('healthz_ping') === '1'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    // Phase O: measure Redis PING latency as an independent check.
    // Uses the same Predis client as the rest of the app; exceptions=false
    // means a down Redis returns false instead of throwing, so we surface
    // 'unreachable' without a 500.
    private function checkRedisLatency(): array
    {
        if (! UpstashRedisConfig::enabled()) {
            return ['ok' => null, 'note' => 'disabled'];
        }

        try {
            $config = config('UpstashRedisConfig');
            if (! $config || empty($config->url)) {
                return ['ok' => null, 'note' => 'not_configured'];
            }

            $t0     = microtime(true);
            $client = new \Predis\Client($config->url, ['exceptions' => false]);
            $pong   = $client->ping();
            $ms     = round((microtime(true) - $t0) * 1000, 2);

            $ok = $pong instanceof \Predis\Response\Status && (string) $pong === 'PONG';

            return ['ok' => $ok, 'latency_ms' => $ms];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    // Phase O: report PHP-FPM pool status when available.
    // fpm_get_status() is a PHP 8.0+ built-in that reads the FastCGI status
    // without an HTTP round-trip. Returns null if running under CLI/Apache/
    // mod_php so we gracefully skip it.
    private function checkFpmPool(): array
    {
        try {
            if (! function_exists('fpm_get_status')) {
                return ['ok' => null, 'note' => 'not_fpm'];
            }
            $status = fpm_get_status();
            if (! is_array($status)) {
                return ['ok' => false, 'note' => 'fpm_get_status_returned_null'];
            }
            return [
                'ok'              => true,
                'pool'            => $status['pool'] ?? null,
                'active_procs'    => $status['active processes'] ?? null,
                'idle_procs'      => $status['idle processes'] ?? null,
                'total_procs'     => $status['total processes'] ?? null,
                'listen_queue'    => $status['listen queue'] ?? null,
                'max_listen_q'    => $status['max listen queue'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function queueDepth(): array
    {
        try {
            return (new JobQueue())->counts();
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
