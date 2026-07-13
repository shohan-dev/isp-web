<?php

use App\Services\JobQueue;

/**
 * queue helper — thin facade over App\Services\JobQueue so call sites can
 * offload heavy external I/O with one call:
 *
 *   enqueue('sms', ['to' => $mobile, 'text' => $msg]);
 *   enqueue('router_enable', ['router_id' => $rid, 'user_id' => $uid]);
 *
 * Drained by `php spark queue:work`.
 */
if (! function_exists('enqueue')) {
    /**
     * Enqueue a background job. Returns the new job id.
     */
    function enqueue(string $type, array $payload = [], string $queue = 'default', int $delaySeconds = 0, int $maxAttempts = 3): int
    {
        return (new JobQueue())->push($type, $payload, $queue, $delaySeconds, $maxAttempts);
    }
}
