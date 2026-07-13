<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Roles;

/**
 * Global maintenance gate — returns 503 for non-exempt traffic when enabled.
 */
class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['flag', 'user']);

        if (! isMaintenanceMode()) {
            return null;
        }

        $path = $this->requestPath($request);

        if ($this->isExemptPath($path) || $this->isBypassed($request)) {
            return null;
        }

        $this->forceLogoutWebSession();

        return $this->maintenanceResponse($request);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function isExemptPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        $exact = ['healthz', 'metrics', 'favicon.ico', 'robots.txt', 'sitemap.xml'];
        if (in_array($path, $exact, true)) {
            return true;
        }

        // 'api/common/bkash/' + 'api/bkash/' cover the public, unauthenticated bKash
        // SMS-relay/IPN webhook routes (zapi/config/routes/common_routes.php) that the
        // field Android SMS-relay app and bKash itself POST to — must survive maintenance
        // or real payment confirmations get 503'd and dropped.
        $prefixes = ['assets/', 'cron/', 'payment/gateway/', 'api/common/bkash/', 'api/bkash/'];
        foreach ($prefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return true;
            }
        }

        if ($this->isPublicAuthPath($path)) {
            return true;
        }

        return false;
    }

    private function isPublicAuthPath(string $path): bool
    {
        $exact = [
            'auth/login',
            'login',
            'logout',
            'auth/forgot-password',
            'auth/forgot-password/validate',
            'auth/forgot-password/reset',
            'api/common/login',
            'api/common/refresh',
            'api/common/check-user',
            'api/common/register',
        ];
        if (in_array($path, $exact, true)) {
            return true;
        }

        $prefixes = ['auth/login/', 'auth/forgot-password/'];
        foreach ($prefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    private function isBypassed(RequestInterface $request): bool
    {
        $ip = (string) ($request->getIPAddress() ?? '');
        if ($ip !== '' && in_array($ip, maintenanceAllowIps(), true)) {
            return true;
        }

        if (function_exists('getSession') && getSession('user_role') === Roles::PLATFORM) {
            return true;
        }

        return false;
    }

    private function forceLogoutWebSession(): void
    {
        if (! function_exists('getSession') || getSession('user_id') === null) {
            return;
        }

        if (getSession('user_role') === Roles::PLATFORM) {
            return;
        }

        try {
            session()->destroy();
        } catch (\Throwable $e) {
            log_message('error', 'MaintenanceFilter session destroy failed: ' . $e->getMessage());
        }
    }

    private function maintenanceResponse(RequestInterface $request): ResponseInterface
    {
        $accept = (string) ($request->getHeaderLine('Accept') ?? '');
        $path = $this->requestPath($request);
        $isJson = strpos($accept, 'application/json') !== false
            || strpos($path, 'api/') === 0
            || strpos($path, 'zapi/') === 0;

        $response = service('response')->setStatusCode(503)->setHeader('Retry-After', '300');

        if ($isJson) {
            return $response->setJSON([
                'statusCode'  => 503,
                'success'     => false,
                'maintenance' => true,
                'data'        => null,
                'error'       => [
                    'code'    => 'MAINTENANCE',
                    'message' => 'The platform is temporarily down for maintenance. Please try again shortly.',
                    'details' => [],
                ],
            ]);
        }

        return $response->setBody(view('errors/maintenance'));
    }

    private function requestPath(RequestInterface $request): string
    {
        $path = trim($request->getPath(), '/');
        if ($path !== '') {
            return $path;
        }

        $path = trim($request->getUri()->getPath(), '/');
        if ($path !== '') {
            return $path;
        }

        $segments = $request->getUri()->getSegments();

        return $segments !== [] ? implode('/', $segments) : '';
    }
}
