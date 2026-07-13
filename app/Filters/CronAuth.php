<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CronAuth
 *
 * Guards the HTTP-triggerable /cron/* routes with a shared secret so they can
 * no longer be invoked anonymously (mass-disconnect, fund mutation, DB dump).
 *
 * - CLI invocations (`php spark` / cron `php index.php cron/...`) are trusted and skipped.
 * - HTTP invocations must present the secret via `?secret=`, POST `secret`, or
 *   the `X-Cron-Secret` header, matching `cron.secret` in the environment.
 * - Fails CLOSED: if `cron.secret` is unset, every HTTP call is rejected.
 *
 * Deploy note: set `cron.secret` in the server .env and append it to the cron
 * URLs/commands, OR migrate the jobs to `php spark` CLI commands (Phase 3).
 */
class CronAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Trust CLI/cron invocations; this guard is only for HTTP triggers.
        if (is_cli()) {
            return;
        }

        $expected = (string) env('cron.secret', '');

        $provided = (string) (
            $request->getGet('secret')
            ?? $request->getPost('secret')
            ?? $request->getHeaderLine('X-Cron-Secret')
        );

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
