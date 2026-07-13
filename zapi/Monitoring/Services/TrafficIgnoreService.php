<?php

namespace Zapi\Monitoring\Services;

use CodeIgniter\HTTP\RequestInterface;
use Zapi\Monitoring\Config\TrafficMonitorConfig;

class TrafficIgnoreService
{
    // $_SERVER key that caches the skip decision for the current request so
    // TrafficStartFilter and TrafficEndFilter never repeat the prefix scan.
    private const CACHE_KEY = '__zapi_monitor_skip';

    public function shouldSkip(RequestInterface $request): bool
    {
        if (isset($_SERVER[self::CACHE_KEY])) {
            return (bool) $_SERVER[self::CACHE_KEY];
        }

        $result = $this->compute($request);
        $_SERVER[self::CACHE_KEY] = $result;
        return $result;
    }

    public function isNoise(RequestInterface $request): bool
    {
        return $this->shouldSkip($request);
    }

    private function compute(RequestInterface $request): bool
    {
        $path = trim($request->getUri()->getPath(), '/');
        if ($path === '') {
            return false;
        }
        $normalized = strtolower($path);

        foreach (TrafficMonitorConfig::skipPrefixes() as $prefix) {
            $prefix = strtolower(trim($prefix, '/'));
            if ($prefix === '') {
                continue;
            }
            if ($normalized === $prefix || strpos($normalized, $prefix . '/') === 0) {
                return true;
            }
        }

        foreach (TrafficMonitorConfig::skipContains() as $contains) {
            $contains = strtolower(trim($contains));
            if ($contains !== '' && strpos($normalized, $contains) !== false) {
                return true;
            }
        }

        return false;
    }
}
