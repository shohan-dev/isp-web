<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Roles;

/**
 * Restrict routes to one or more role strings (e.g. role:super_admin).
 */
class RoleGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('user');

        $required = is_array($arguments) ? $arguments : [];
        if ($required === []) {
            return null;
        }

        $actual = (string) (getSession('user_role') ?? '');
        if ($actual === '' || ! in_array($actual, $required, true)) {
            $isAjax = $request->isAJAX()
                || strtolower((string) $request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

            if ($isAjax) {
                return service('response')->setStatusCode(403)->setJSON([
                    'status'   => 'error',
                    'response' => 'You do not have permission to access this resource.',
                ]);
            }

            return redirect()->to(route_to('route.dashboard'))
                ->with('error', 'You do not have permission to access this resource.');
        }

        // Platform-only routes must not be served on tenant hosts.
        if (in_array(Roles::PLATFORM, $required, true)
            && function_exists('isTenantRequest')
            && isTenantRequest()) {
            $isAjax = $request->isAJAX()
                || strtolower((string) $request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

            if ($isAjax) {
                return service('response')->setStatusCode(403)->setJSON([
                    'status'   => 'error',
                    'response' => 'Manage this area from the main platform domain.',
                ]);
            }

            return redirect()->to(route_to('route.dashboard'))
                ->with('error', 'Manage this area from the main platform domain.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
