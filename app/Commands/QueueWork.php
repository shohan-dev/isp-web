<?php

namespace App\Commands;

use App\Services\JobQueue;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * queue:work — supervised worker that drains the jobs queue (Phase 3 / MT-2).
 *
 * Run under supervisor/systemd:
 *   php spark queue:work default
 *
 * Options:
 *   --once        process at most one job then exit (useful for cron/tests)
 *   --sleep=N     seconds to idle when the queue is empty (default 3)
 *   --max=N       exit after handling N jobs (worker recycling; default 0 = unlimited)
 *
 * Wire real handlers in dispatch() as call sites start enqueuing.
 */
class QueueWork extends BaseCommand
{
    protected $group       = 'Queue';
    protected $name        = 'queue:work';
    protected $description = 'Drain the jobs queue: reserve -> dispatch -> complete/fail (retry+dead-letter).';
    protected $usage       = 'queue:work [queue] [--once] [--sleep=3] [--max=0]';

    public function run(array $params)
    {
        $queue    = $params[0] ?? 'default';
        $once     = (bool) CLI::getOption('once');
        $sleep    = max(1, (int) (CLI::getOption('sleep') ?? 3));
        $max      = (int) (CLI::getOption('max') ?? 0);
        $workerId = gethostname() . ':' . getmypid();

        $q        = new JobQueue();
        $handled  = 0;

        CLI::write("queue:work draining '{$queue}' as {$workerId}" . ($once ? ' (--once)' : ''), 'green');

        helper('flag');

        while (true) {
            // Kill-switch honor-point (Phase 6): when the operator sheds load,
            // pause draining — jobs stay safely queued (no requeue churn) and no
            // outbound SMS/email/router I/O fires. Fail-safe: degrade_mode reads
            // the cache and defaults OFF, so normal draining is unchanged; a cache
            // outage degrades to "not degraded" (keeps working).
            if (flag('degrade_mode')) {
                if ($once) {
                    break; // don't busy-spin a one-shot run
                }
                CLI::write('  ⏸ degrade_mode active — pausing queue drain', 'yellow');
                sleep($sleep);
                continue;
            }

            $job = $q->reserve($queue, $workerId);

            if ($job === null) {
                if ($once) {
                    break;
                }
                sleep($sleep);
                continue;
            }

            // BUG-13 (worker variant): reset per-request static caches before each
            // job so a stale $__sadminCache / $__settingCache from job N never bleeds
            // into job N+1. FPM resets these on every HTTP request via REQUEST_TIME_FLOAT;
            // the queue worker is a long-lived process and needs an explicit stamp bump.
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

            try {
                $this->dispatch($job);
                $q->complete((int) $job->id);
                CLI::write("  ✓ #{$job->id} {$job->type} done", 'green');
            } catch (Throwable $e) {
                $q->fail((int) $job->id, $e->getMessage());
                CLI::write("  ✗ #{$job->id} {$job->type} failed: " . $e->getMessage(), 'red');
            }

            $handled++;
            if ($once || ($max > 0 && $handled >= $max)) {
                break;
            }
        }

        return EXIT_SUCCESS;
    }

    /**
     * Route a job to its handler. Throwing requeues/dead-letters it via fail().
     */
    protected function dispatch(object $job): void
    {
        $payload = json_decode($job->payload ?? '[]', true) ?: [];

        switch ($job->type) {
            case 'noop': // smoke-test handler
                return;

            case 'sms':
                helper(['user', 'sms']);
                $user = $payload['user'] ?? null;
                if (! is_array($user)) {
                    throw new \RuntimeException('sms job missing user payload');
                }
                // CLI-safe (traced 2026-06-21): Send_SMs() resolves the gateway via
                // sendSms($mobile, $msg, $userId) → getSetting('default_sms_gateway','',$userId)
                // → getSettingPrefixForUser($userId), all keyed on the EXPLICIT customer id in
                // the payload (getSetting uses `$id ?? session`, and the prefix fn try/catches the
                // missing session and walks the tenant tree from $userId). The owner-logging id
                // also falls back to $data['admin_id'] before the session. So no session is
                // required here — only a real staging SMS is left to confirm delivery before
                // flipping queue.smsEnabled. Throwing requeues/dead-letters the job.
                $result = Send_SMs([$user], null, null, null, $payload['content'] ?? '', $payload['sms_log_id'] ?? null);
                if (empty($result['status']) || $result['status'] !== 'success') {
                    throw new \RuntimeException('SMS send failed: ' . json_encode($result));
                }
                return;

            case 'router_enable':
                // Async provisioning offloaded from the bKash webhook (Phase 3.5 / M2).
                // routerClient() takes an explicit router_id and reads no session, so
                // this is CLI-safe. Throwing requeues with backoff, then dead-letters.
                $detailsId = (int) ($payload['details_id'] ?? $payload['user_id'] ?? 0);
                if ($detailsId <= 0) {
                    throw new \RuntimeException('router_enable job missing details_id/user_id');
                }
                helper('router');
                if (! $this->enableRouterForUser($detailsId, $payload)) {
                    throw new \RuntimeException("router_enable: could not enable connection for user {$detailsId}");
                }
                return;

            // case 'email':  // wire when an inline mail call site is converted to enqueue()
            //     return;

            default:
                throw new \RuntimeException("No handler registered for job type '{$job->type}'");
        }
    }

    /**
     * Enable a user's PPPoE connection from the worker. Mirrors the bKash webhook's
     * synchronous enable: try the API client, then fall back to fsock, each with a
     * pppoe_secret retry. CLI-safe (routerClient() takes an explicit router_id and
     * reads no session). Returns true on success; returning false lets dispatch()
     * throw so the queue retries with backoff and finally dead-letters.
     */
    private function enableRouterForUser(int $detailsId, array $payload): bool
    {
        $routerId = $payload['router_id'] ?? null;
        if ($routerId === null) {
            $user     = model('App\Models\User')->find($detailsId);
            $routerId = $user->router_id ?? null;
        }
        if ($routerId === null) {
            throw new \RuntimeException("router_enable: no router_id for user {$detailsId}");
        }

        $client = routerClient($routerId);

        if (! empty($client) && ! is_array($client)) {
            // API path
            $pppoe   = getPPPoEUserUserId($client, $detailsId);
            $pppoeId = $pppoe[0]['.id'] ?? $payload['pppoe_id'] ?? null;
            if (enablePPPoEUser($client, $pppoeId)) {
                return true;
            }
            $secret = $this->pppoeSecret($detailsId);
            return $secret ? (bool) enablePPPoEUser_by_pppoe_secret($client, $secret) : false;
        }

        // fsock path
        $pppoeId = $payload['pppoe_id'] ?? null;
        if ($pppoeId && enablePPPoEUserFsock($routerId, $pppoeId)) {
            return true;
        }
        $secret = $this->pppoeSecret($detailsId);
        if (! $secret) {
            return false;
        }
        $fp   = connect_using_Fsocket($routerId);
        $ppId = null;
        if ($fp) {
            $ppId = getPPPoEIdFsock($fp, $secret);
            if (is_resource($fp)) {
                fclose($fp);
            }
        }
        return (bool) enablePPPoEUserFsock($routerId, $ppId);
    }

    /** BUG-22: delegate to the shared helper in router_helper.php. */
    private function pppoeSecret(int $detailsId): ?string
    {
        return resolvePppoeSecret($detailsId);
    }
}
