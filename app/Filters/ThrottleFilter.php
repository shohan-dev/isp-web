<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * ThrottleFilter — login brute-force back-pressure (Phase 2 / T6).
 *
 * Phase-G3: ON by default (flag default changed to true). Operator can disable
 * with setFlag('login_throttle', false) if CGNAT false-positives require tuning.
 * Many ISP customers share a NAT/CGNAT public IP — monitor 429s on first deploy
 * and bump the capacity argument (throttle:15,60) if needed.
 * Backed by CI4's cache-based Throttler (file now, Redis once cache.handler is
 * switched — then the bucket is shared across nodes).
 *
 * Usage (Config\Filters):
 *   'throttle' => ['before' => ['auth/login/validate']]          // 8/60s default
 *   // or tune per route: throttle:5,300  (5 attempts per 300s)
 */
class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('flag');
        if (! flag('login_throttle', true)) {
            return; // operator can disable with setFlag('login_throttle', false)
        }

        $capacity = isset($arguments[0]) ? max(1, (int) $arguments[0]) : 8;
        $seconds  = isset($arguments[1]) ? max(1, (int) $arguments[1]) : 60;

        $throttler = Services::throttler();
        // CI4 cache forbids reserved chars {}()/\@: — IPv6 addresses contain ':'.
        // Hash the IP so the throttler key is always a valid cache key.
        $ip  = (string) ($request->getIPAddress() ?: 'unknown');
        $key = 'login_' . md5($ip);

        if ($throttler->check($key, $capacity, $seconds) === false) {
            $retryAfter = $throttler->getTokenTime();

            return Services::response()
                ->setStatusCode(429, 'Too Many Requests')
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Too many login attempts. Please wait ' . $retryAfter . ' second(s) and try again.',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
