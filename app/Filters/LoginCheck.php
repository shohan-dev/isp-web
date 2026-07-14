<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class LoginCheck implements FilterInterface
{
    /**
     * Login check filter
     * @action: user, admin & employee login check
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('utility');

        if (!empty(getSession('user_role')) && !empty(getSession('user_id'))) {
            return redirect()->to(route_to('route.dashboard'));
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
