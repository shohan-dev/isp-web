<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class LogoutController extends BaseController
{
    /**
     * Logout
     * @action: logout
     */
    public function index()
    {
        $session = session();

        // If impersonating, revert to the original admin / super_admin session
        if ($session->has('original_user')) {
            $original = $session->get('original_user');

            $session->set([
                'user_id'   => $original['user_id'],
                'user_role' => $original['user_role'],
                'admin_id'  => $original['admin_id'] ?? null,
            ]);

            $session->remove('original_user');

            $returnRoute = $original['return_route'] ?? 'route.reseller';
            if (!is_string($returnRoute) || $returnRoute === '') {
                $returnRoute = 'route.reseller';
            }

            return redirect()->route($returnRoute);
        }

        // If not impersonating, do a normal logout
        $session->destroy();
        return redirect()->route('route.auth.login');
    }
}
