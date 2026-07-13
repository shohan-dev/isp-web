<?php

namespace Zapi\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Zapi\Core\Support\Auth\JwtToken;

/**
 * Per-endpoint permission gate for the zapi reseller surface.
 *
 * Ports the web portal's Permission / CustomAccess model to the mobile API so
 * that a reselleradmin / admin / employee whose portal permission row withholds
 * an action (delete, funding, bulk-recharge, ...) cannot perform it from the app
 * either. Reuses the SAME decision function the portal uses
 * (App\Helpers\user_helper::userHasPermission) so the two surfaces can never drift.
 *
 * Usage (route filter arg): 'zapipermission:MENU,SUB' -> $arguments = ['MENU','SUB'].
 *
 * FAIL CLOSED: empty menu, unresolvable subject, missing users row, empty role,
 * a helper `false`, or ANY Throwable all deny (403). Only an explicit `=== true`
 * from the helper allows. super_admin is granted by the helper itself (no special
 * casing here).
 *
 * Self-contained: it re-decodes the bearer token exactly like RoleAuthFilter, so
 * it is correct regardless of filter ordering and independent of the jwt_user_id
 * request global set by JwtAuthFilter.
 */
class PermissionAuthFilter implements FilterInterface
{
    private function normalizeBearerToken(string $authHeader): string
    {
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return '';
        }

        $token = trim(substr($authHeader, 7));
        // Accept accidental "Bearer bearer <jwt>" from clients, matching
        // JwtAuthFilter / RoleAuthFilter so a request that passes zapijwt does
        // not then 401 here.
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        return $token;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Required (menu, submenu). Unknown mapping = fail closed.
        $menu = (string) ($arguments[0] ?? '');
        if ($menu === '') {
            return $this->error(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
        }
        $submenu = (isset($arguments[1]) && $arguments[1] !== '') ? (string) $arguments[1] : null;

        // 2. Re-decode the bearer token (defense in depth behind zapijwt/zapirole).
        $authHeader = (string) $request->getHeaderLine('Authorization');
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return $this->error(401, 'UNAUTHORIZED', 'Missing Bearer token.');
        }
        $token = $this->normalizeBearerToken($authHeader);
        if ($token === '') {
            return $this->error(401, 'UNAUTHORIZED', 'Invalid Bearer token.');
        }
        $payload = JwtToken::verify($token, \Zapi\utils\JwtToken::secret());
        if (!is_array($payload)) {
            return $this->error(401, 'UNAUTHORIZED', 'Token is invalid or expired.');
        }
        if (($payload['token_type'] ?? 'access') !== 'access') {
            return $this->error(401, 'UNAUTHORIZED', 'Access token required.');
        }

        // 3. Subject.
        $userId = (int) ($payload['sub'] ?? $payload['id'] ?? $payload['user_id'] ?? 0);
        if ($userId <= 0) {
            return $this->error(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
        }

        // 4. Load helpers. user_helper provides userHasPermission / getUserById /
        //    getSAdminIdForUser / permissionCacheVersion; utility provides
        //    getSession(), which userHasPermission calls unconditionally.
        helper(['user', 'utility']);

        try {
            // 5. Resolve the AUTHORITATIVE, ORIGINAL-CASE role from the users table
            //    by JWT subject. This is the exact string permissions.user_type is
            //    compared against (admin | resellerAdmin | employee | user |
            //    super_admin). Passing the lowercased JWT role would make the
            //    default-access lookup find nothing and deny everything (CASE TRAP).
            //    Missing / unloadable row => DENY (never fall back to the token role).
            if (!function_exists('userHasPermission') || !function_exists('getUserById')) {
                return $this->error(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
            }

            $user = getUserById($userId);
            if (empty($user)) {
                return $this->error(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
            }
            $role = is_object($user) ? (string) ($user->role ?? '') : (string) ($user['role'] ?? '');
            if ($role === '') {
                return $this->error(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
            }

            // admin_id is decision-irrelevant (only mixed into the helper cache key);
            // pass the token's for clean keys.
            $adminId = $payload['admin_id'] ?? null;

            // 6. The portal decision. super_admin returns true inside the helper.
            $allowed = userHasPermission($menu, $submenu, $role, $userId, $adminId);
            if ($allowed === true) {
                return null;
            }

            return $this->error(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
        } catch (\Throwable $e) {
            // Any error, missing row, or unexpected state => DENY.
            return $this->error(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function error(int $status, string $code, string $message): ResponseInterface
    {
        return Services::response()
            ->setStatusCode($status)
            ->setJSON([
                'statusCode' => $status,
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'details' => [],
                ],
            ]);
    }
}
