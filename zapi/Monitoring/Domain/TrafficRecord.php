<?php

namespace Zapi\Monitoring\Domain;

final class TrafficRecord
{
    public static function make(array $payload): array
    {
        return [
            'request_id' => (string) ($payload['request_id'] ?? ''),
            'started_at' => (string) ($payload['started_at'] ?? gmdate('c')),
            'ended_at' => (string) ($payload['ended_at'] ?? gmdate('c')),
            'duration_ms' => (int) ($payload['duration_ms'] ?? 0),
            'path' => (string) ($payload['path'] ?? '/'),
            'endpoint_group' => (string) ($payload['endpoint_group'] ?? 'web'),
            'method' => strtoupper((string) ($payload['method'] ?? 'GET')),
            'status_code' => (int) ($payload['status_code'] ?? 0),
            'ip_address' => (string) ($payload['ip_address'] ?? ''),
            'user_agent' => (string) ($payload['user_agent'] ?? ''),
            'referer' => (string) ($payload['referer'] ?? ''),
            'is_api' => (bool) ($payload['is_api'] ?? false),
            'is_web' => (bool) ($payload['is_web'] ?? true),
            'client_source' => (string) ($payload['client_source'] ?? ((bool) ($payload['is_api'] ?? false) ? 'app' : 'web')),
            'device_type' => (string) ($payload['device_type'] ?? 'unknown'),
            'device_os' => (string) ($payload['device_os'] ?? 'unknown'),
            'device_browser' => (string) ($payload['device_browser'] ?? 'unknown'),
            'device_name' => (string) ($payload['device_name'] ?? 'Unknown Device'),
            'user_id' => isset($payload['user_id']) ? (int) $payload['user_id'] : null,
            'actor_type' => (string) ($payload['actor_type'] ?? ''),
            'actor_label' => (string) ($payload['actor_label'] ?? ''),
            'route_name' => (string) ($payload['route_name'] ?? ''),
            'year' => (string) ($payload['year'] ?? gmdate('Y')),
            'month' => (string) ($payload['month'] ?? gmdate('m')),
            'day' => (string) ($payload['day'] ?? gmdate('d')),
            'hour' => (string) ($payload['hour'] ?? gmdate('H')),
            'minute' => (string) ($payload['minute'] ?? gmdate('i')),
            'created_at' => (string) ($payload['created_at'] ?? gmdate('c')),
        ];
    }
}

