<?php

namespace App\Controllers;

use App\Libraries\UpstashRedisConfig;
use App\Services\JobQueue;
use Config\Database;

/**
 * Metrics — Phase O (O1/O3) Prometheus-compatible text-format endpoint.
 *
 *   GET /metrics  ->  200 text/plain; version=0.0.4
 *
 * Exposes the four hard-wall signals the Phase O dashboard and alert-triggers.sh
 * need, in Prometheus exposition format so any scraper (node_exporter push,
 * Grafana Alloy, VictoriaMetrics, etc.) can consume without adaptation.
 *
 * Protected: only reachable from loopback (127.x) or a configured scraper CIDR
 * (METRICS_ALLOWED_CIDR env var) to avoid leaking internal counters publicly.
 * Load balancers must not forward /metrics — configure an explicit deny rule.
 *
 * Metrics exposed:
 *   isp_db_up                    — 1 if SELECT 1 succeeds, 0 otherwise
 *   isp_db_latency_ms            — DB query latency in ms
 *   isp_cache_up                 — 1 if cache ping succeeds, 0 otherwise
 *   isp_redis_latency_ms         — Redis PING latency in ms (-1 if not configured)
 *   isp_queue_pending            — jobs table: pending row count
 *   isp_queue_processing         — jobs table: processing row count
 *   isp_queue_failed             — jobs table: failed row count
 *   isp_queue_dead               — jobs table: dead-letter row count
 *   isp_breaker_open_total       — number of router_down_* cache keys (open breakers)
 *   isp_fpm_active_procs         — FPM active process count (if fpm_get_status() available)
 *   isp_fpm_idle_procs           — FPM idle process count
 *   isp_fpm_listen_queue         — FPM listen queue depth (backlog)
 */
class Metrics extends BaseController
{
    public function index()
    {
        if (! $this->isAllowedScraper()) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        $lines = [];

        $this->appendDbMetrics($lines);
        $this->appendCacheMetrics($lines);
        $this->appendRedisMetrics($lines);
        $this->appendQueueMetrics($lines);
        $this->appendPaymentMetrics($lines);
        $this->appendBreakerMetrics($lines);
        $this->appendFpmMetrics($lines);

        $body = implode("\n", $lines) . "\n";

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
            ->setBody($body);
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function isAllowedScraper(): bool
    {
        $ip      = $this->request->getIPAddress();
        $allowed = $_ENV['METRICS_ALLOWED_CIDR'] ?? '127.0.0.1';

        foreach (explode(',', $allowed) as $cidr) {
            $cidr = trim($cidr);
            if ($cidr === $ip) {
                return true;
            }
            if (str_contains($cidr, '/') && $this->cidrMatch($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits   = (int) $bits;
        $ip4    = ip2long($ip);
        $sub4   = ip2long($subnet);
        if ($ip4 === false || $sub4 === false) {
            return false;
        }
        $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));
        return ($ip4 & $mask) === ($sub4 & $mask);
    }

    private function gauge(array &$lines, string $name, string $help, $value, string $type = 'gauge'): void
    {
        $lines[] = "# HELP {$name} {$help}";
        $lines[] = "# TYPE {$name} {$type}";
        $lines[] = "{$name} {$value}";
    }

    private function appendDbMetrics(array &$lines): void
    {
        try {
            $t0  = microtime(true);
            Database::connect()->query('SELECT 1');
            $ms  = round((microtime(true) - $t0) * 1000, 2);
            $this->gauge($lines, 'isp_db_up', '1 if the primary DB responds to SELECT 1', 1);
            $this->gauge($lines, 'isp_db_latency_ms', 'DB SELECT 1 round-trip latency in milliseconds', $ms);
        } catch (\Throwable) {
            $this->gauge($lines, 'isp_db_up', '1 if the primary DB responds to SELECT 1', 0);
            $this->gauge($lines, 'isp_db_latency_ms', 'DB SELECT 1 round-trip latency in milliseconds', -1);
        }
    }

    private function appendCacheMetrics(array &$lines): void
    {
        try {
            cache()->save('metrics_ping', '1', 5);
            $ok = cache('metrics_ping') === '1' ? 1 : 0;
        } catch (\Throwable) {
            $ok = 0;
        }
        $this->gauge($lines, 'isp_cache_up', '1 if the cache layer (Redis/file) is writable', $ok);
    }

    private function appendRedisMetrics(array &$lines): void
    {
        if (! UpstashRedisConfig::enabled()) {
            $this->gauge($lines, 'isp_redis_latency_ms', 'Redis PING latency ms; -1 if not configured', -1);

            return;
        }

        try {
            $config = config('UpstashRedisConfig');
            if (! $config || empty($config->url)) {
                $this->gauge($lines, 'isp_redis_latency_ms', 'Redis PING latency ms; -1 if not configured', -1);
                return;
            }
            $t0     = microtime(true);
            $client = new \Predis\Client($config->url, ['exceptions' => false]);
            $pong   = $client->ping();
            $ms     = round((microtime(true) - $t0) * 1000, 2);
            $ok     = $pong instanceof \Predis\Response\Status && (string) $pong === 'PONG';
            $this->gauge($lines, 'isp_redis_latency_ms', 'Redis PING latency ms; -1 if not configured', $ok ? $ms : -1);
        } catch (\Throwable) {
            $this->gauge($lines, 'isp_redis_latency_ms', 'Redis PING latency ms; -1 if not configured', -1);
        }
    }

    private function appendQueueMetrics(array &$lines): void
    {
        try {
            $counts = (new JobQueue())->counts();
            $this->gauge($lines, 'isp_queue_pending',    'Jobs in the pending state', (int) ($counts['pending'] ?? 0));
            $this->gauge($lines, 'isp_queue_processing', 'Jobs currently being processed', (int) ($counts['processing'] ?? 0));
            $this->gauge($lines, 'isp_queue_failed',     'Jobs in the failed (retryable) state', (int) ($counts['failed'] ?? 0));
            $this->gauge($lines, 'isp_queue_dead',       'Jobs in the dead-letter state', (int) ($counts['dead'] ?? 0));
        } catch (\Throwable) {
            foreach (['pending', 'processing', 'failed', 'dead'] as $state) {
                $this->gauge($lines, "isp_queue_{$state}", "Jobs in the {$state} state", -1);
            }
        }
    }

    // Phase O (O3): payment success/fail rate — last 24h window from payments table.
    // Uses the read group if available (Phase I1) so this read doesn't compete with writes.
    private function appendPaymentMetrics(array &$lines): void
    {
        try {
            $db      = \Config\Database::connect('read') ?? \Config\Database::connect();
            $since   = date('Y-m-d H:i:s', strtotime('-24 hours'));

            $row = $db->query(
                "SELECT
                    SUM(status = 'successful') AS success_count,
                    SUM(status = 'failed')     AS fail_count,
                    SUM(status = 'pending')    AS pending_count,
                    COUNT(*)                   AS total_count
                 FROM payments
                 WHERE created_at >= ?",
                [$since]
            )->getRow();

            $this->gauge($lines, 'isp_payment_success_24h', 'Payment rows with status=successful in last 24h', (int) ($row->success_count ?? 0));
            $this->gauge($lines, 'isp_payment_failed_24h',  'Payment rows with status=failed in last 24h', (int) ($row->fail_count ?? 0));
            $this->gauge($lines, 'isp_payment_pending_24h', 'Payment rows with status=pending in last 24h', (int) ($row->pending_count ?? 0));
            $this->gauge($lines, 'isp_payment_total_24h',   'Total payment rows created in last 24h', (int) ($row->total_count ?? 0));
        } catch (\Throwable) {
            foreach (['success', 'failed', 'pending', 'total'] as $s) {
                $this->gauge($lines, "isp_payment_{$s}_24h", "Payment {$s} count (last 24h)", -1);
            }
        }
    }

    private function appendBreakerMetrics(array &$lines): void
    {
        try {
            // Count router_down_* cache keys = number of open circuit breakers.
            // This is a best-effort scan; Predis scan() returns cursor+items per call.
            $config = config('UpstashRedisConfig');
            $open   = 0;
            if ($config && ! empty($config->url)) {
                $client = new \Predis\Client($config->url, ['exceptions' => false]);
                $cursor = '0';
                do {
                    $result = $client->scan($cursor, ['match' => 'ispc:router_down_*', 'count' => 100]);
                    if (! is_array($result) || count($result) < 2) {
                        break;
                    }
                    $cursor = (string) $result[0];
                    $open  += count((array) $result[1]);
                } while ($cursor !== '0');
            }
            $this->gauge($lines, 'isp_breaker_open_total', 'Number of router circuit breakers currently open', $open);
        } catch (\Throwable) {
            $this->gauge($lines, 'isp_breaker_open_total', 'Number of router circuit breakers currently open', -1);
        }
    }

    private function appendFpmMetrics(array &$lines): void
    {
        if (! function_exists('fpm_get_status')) {
            $this->gauge($lines, 'isp_fpm_active_procs',  'PHP-FPM active worker processes (-1 if unavailable)', -1);
            $this->gauge($lines, 'isp_fpm_idle_procs',    'PHP-FPM idle worker processes (-1 if unavailable)', -1);
            $this->gauge($lines, 'isp_fpm_listen_queue',  'PHP-FPM listen queue depth (-1 if unavailable)', -1);
            return;
        }
        try {
            $s = fpm_get_status();
            if (! is_array($s)) {
                throw new \RuntimeException('fpm_get_status returned non-array');
            }
            $this->gauge($lines, 'isp_fpm_active_procs',  'PHP-FPM active worker processes', (int) ($s['active processes'] ?? 0));
            $this->gauge($lines, 'isp_fpm_idle_procs',    'PHP-FPM idle worker processes', (int) ($s['idle processes'] ?? 0));
            $this->gauge($lines, 'isp_fpm_listen_queue',  'PHP-FPM listen queue depth', (int) ($s['listen queue'] ?? 0));
        } catch (\Throwable) {
            $this->gauge($lines, 'isp_fpm_active_procs',  'PHP-FPM active worker processes (-1 if unavailable)', -1);
            $this->gauge($lines, 'isp_fpm_idle_procs',    'PHP-FPM idle worker processes (-1 if unavailable)', -1);
            $this->gauge($lines, 'isp_fpm_listen_queue',  'PHP-FPM listen queue depth (-1 if unavailable)', -1);
        }
    }
}
