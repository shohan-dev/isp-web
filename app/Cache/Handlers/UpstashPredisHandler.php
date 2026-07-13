<?php

namespace App\Cache\Handlers;

use App\Libraries\UpstashRedisConfig;
use CodeIgniter\Cache\Handlers\PredisHandler as CIPredisHandler;
use Config\Cache;

/**
 * Predis cache handler with tls:// host normalization for Upstash.
 */
class UpstashPredisHandler extends CIPredisHandler
{
    public function __construct(Cache $config)
    {
        $config->redis = UpstashRedisConfig::normalizeCacheConfig($config->redis);

        parent::__construct($config);
    }
}
