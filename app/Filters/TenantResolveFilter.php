<?php

namespace App\Filters;

use App\Libraries\TenantContext;
use App\Models\TenantModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Resolve tenant from HTTP Host on every request.
 * Platform apex → no tenant. Subdomain → tenants.slug.
 */
class TenantResolveFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('tenant');

        TenantContext::reset();

        $host = normalizeRequestHost($request->getServer('HTTP_HOST') ?? $request->getUri()->getHost());
        $path = trim($request->getUri()->getPath(), '/');

        // Never block infrastructure / static paths.
        if ($this->isExemptPath($path)) {
            TenantContext::markResolved(TenantContext::MODE_PLATFORM, $host);
            $this->applyBaseUrl($host);

            return null;
        }

        if (isPlatformHost($host)) {
            TenantContext::markResolved(TenantContext::MODE_PLATFORM, $host);
            $this->applyBaseUrl($host);

            return null;
        }

        $slug = extractTenantSlugFromHost($host);
        if ($slug === null || $slug === '') {
            TenantContext::markResolved(TenantContext::MODE_UNKNOWN, $host);
            $this->applyBaseUrl($host);

            return $this->portalResponse($request, 'not_found');
        }

        try {
            $tenantModel = model(TenantModel::class);
            $tenant      = $tenantModel->findBySlug($slug);
        } catch (\Throwable $e) {
            log_message('error', 'TenantResolve: ' . $e->getMessage());
            TenantContext::markResolved(TenantContext::MODE_UNKNOWN, $host, $slug);
            $this->applyBaseUrl($host);

            return $this->portalResponse($request, 'not_found');
        }

        if (empty($tenant)) {
            TenantContext::markResolved(TenantContext::MODE_UNKNOWN, $host, $slug);
            $this->applyBaseUrl($host);

            return $this->portalResponse($request, 'not_found');
        }

        TenantContext::markResolved(TenantContext::MODE_TENANT, $host, $slug, $tenant);
        $this->applyBaseUrl($host);

        $status = strtolower((string) ($tenant->status ?? 'active'));
        if ($status === 'suspended') {
            // Allow login page assets only; block app usage.
            if (!$this->isPublicAuthPath($path)) {
                return $this->portalResponse($request, 'suspended', $tenant);
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }

    private function applyBaseUrl(string $host): void
    {
        if ($host === '') {
            return;
        }

        $scheme = 'http';
        try {
            $uriScheme = service('request')->getUri()->getScheme();
            if (is_string($uriScheme) && $uriScheme !== '') {
                $scheme = $uriScheme;
            }
        } catch (\Throwable $e) {
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                $scheme = 'https';
            }
        }

        $baseUrl = $scheme . '://' . $host . '/';
        $config  = config('App');
        if ($config) {
            $config->baseURL = $baseUrl;
            // Allow this host even if it differs from .env baseURL.
            if (!in_array($host, $config->allowedHostnames, true)) {
                $config->allowedHostnames[] = $host;
            }
        }
    }

    private function isExemptPath(string $path): bool
    {
        if ($path === '' ) {
            return false;
        }

        $exact = ['healthz', 'metrics', 'favicon.ico', 'robots.txt', 'sitemap.xml'];
        if (in_array($path, $exact, true)) {
            return true;
        }

        $prefixes = ['assets/', 'api/', 'zapi/', 'cron/', 'payment/gateway/'];
        foreach ($prefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    private function isPublicAuthPath(string $path): bool
    {
        $exact = ['auth/login', 'login', 'logout', 'auth/forgot', 'auth/forgot/validate'];
        if (in_array($path, $exact, true)) {
            return true;
        }

        $prefixes = ['auth/login/', 'auth/forgot/'];
        foreach ($prefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    private function portalResponse(RequestInterface $request, string $reason, $tenant = null)
    {
        $accept = (string) ($request->getHeaderLine('Accept') ?? '');
        $isJson = strpos($accept, 'application/json') !== false
            || strpos($request->getUri()->getPath(), '/api/') !== false;

        if ($isJson) {
            $message = $reason === 'suspended'
                ? 'This portal is suspended.'
                : 'Portal not found.';

            return service('response')
                ->setStatusCode($reason === 'suspended' ? 403 : 404)
                ->setJSON([
                    'status'  => 'error',
                    'success' => false,
                    'message' => $message,
                ]);
        }

        $view = $reason === 'suspended' ? 'tenants/portal_suspended' : 'tenants/portal_not_found';

        return service('response')
            ->setStatusCode($reason === 'suspended' ? 403 : 404)
            ->setBody(view($view, [
                'tenant' => $tenant,
                'host'   => TenantContext::host(),
                'slug'   => TenantContext::slug(),
            ]));
    }
}
