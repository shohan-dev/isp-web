<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RequestTimingFilter — Phase O structured request-timing log.
 *
 * Writes one JSON log line per request to the CI4 `timing` channel so that
 * centralized log aggregators (Loki, Cloudwatch, ELK) can alert on p95 latency
 * or surface slow admin endpoints without per-query profiling overhead.
 *
 * Wire in app/Config/Filters.php globals or per-route:
 *   'globals' => ['after' => ['App\Filters\RequestTimingFilter']]
 *
 * Log line shape:
 *   {"method":"GET","path":"/api/customers","status":200,"ms":42.3,
 *    "mem_kb":1024,"ip":"1.2.3.4","ts":"2026-06-24T12:00:00+00:00"}
 */
class RequestTimingFilter implements FilterInterface
{
    // Paths that generate very high-frequency noise; skip to keep log volume sane.
    private const SKIP_PATHS = ['/healthz', '/favicon.ico'];

    public function before(RequestInterface $request, $arguments = null)
    {
        // Store start time in a request attribute so after() can read it.
        // $_SERVER['REQUEST_TIME_FLOAT'] is set by PHP before any app code runs,
        // so it already captures bootstrap time — no need to stamp here.
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $path = $request->getUri()->getPath();

        foreach (self::SKIP_PATHS as $skip) {
            if ($path === $skip) {
                return;
            }
        }

        $startFloat = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $ms         = round((microtime(true) - $startFloat) * 1000, 2);
        $memKb      = (int) ceil(memory_get_peak_usage(true) / 1024);

        log_message('info', json_encode([
            'method'  => $request->getMethod(),
            'path'    => $path,
            'status'  => $response->getStatusCode(),
            'ms'      => $ms,
            'mem_kb'  => $memKb,
            'ip'      => $request->getIPAddress(),
            'ts'      => date('c'),
        ], JSON_UNESCAPED_SLASHES), [], 'timing');

        return null;
    }
}
