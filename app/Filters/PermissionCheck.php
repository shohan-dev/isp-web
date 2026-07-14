<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionCheck implements FilterInterface
{
    /**
     * Permission check filter
     * We'll check if a user has a specific permission or not
     */
    public function before(RequestInterface $request, $arguments = null)
    {

        $menu = $arguments[0];
        $sub_menu = $arguments[1] ?? null;
        $role = $arguments[2] ?? null;
        $user_id = $arguments[3] ?? null;

        if (!userHasPermission($menu, $sub_menu, $role, $user_id)) {
            $session = session();

            // During admin->reseller impersonation, redirect denied pages to dashboard
            // instead of showing 404 when user presses browser back.
            if (
                $session->has('original_user') &&
                $session->get('user_role') === 'resellerAdmin'
            ) {
                return redirect()->to(route_to('route.dashboard'));
            }

            /**
             * Show 404 page not found if user 
             * not have permission to view the page
             */
            show_404();
        }
    }

    /**
     * After login check filter
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
