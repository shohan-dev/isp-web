<?php

namespace Zapi\Modules\Common\Auth\Controllers;

use Throwable;
use Zapi\Core\Base\BaseApiController;
use Zapi\utils\JwtToken;

class AuthController extends BaseApiController
{
    private const SESSION_LIFETIME_SECONDS = 604800; // 7 days

    private function normalizeBearerToken(string $authHeader): string
    {
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return '';
        }

        $token = trim(substr($authHeader, 7));
        // Accept accidental "Bearer bearer <jwt>" from clients.
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        return $token;
    }

    private function getAccessPayload(): ?array
    {
        $authHeader = (string) $this->request->getHeaderLine('Authorization');
        $token = $this->normalizeBearerToken($authHeader);
        if ($token === '') {
            return null;
        }

        $payload = JwtToken::verify($token, \Zapi\utils\JwtToken::secret());
        if (!is_array($payload) || (($payload['token_type'] ?? 'access') !== 'access')) {
            return null;
        }

        return $payload;
    }

    private function issueTokens(int $userId, string $role, $adminId): array
    {
        $secret = \Zapi\utils\JwtToken::secret();
        $accessTtl = (int) env('zapi.jwtTtl', 3600);
        $refreshTtl = (int) env('zapi.jwtRefreshTtl', 2592000);
        $issuedAtMs = time() * 1000;

        return [
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
            'issued_at' => $issuedAtMs,
            'session_expires_at' => $issuedAtMs + (self::SESSION_LIFETIME_SECONDS * 1000),
            'access_token' => JwtToken::issue([
                'sub' => $userId,
                'role' => $role,
                'admin_id' => $adminId,
                'token_type' => 'access',
            ], $secret, $accessTtl),
            'refresh_expires_in' => $refreshTtl,
            'refresh_token' => JwtToken::issue([
                'sub' => $userId,
                'role' => $role,
                'admin_id' => $adminId,
                'token_type' => 'refresh',
            ], $secret, $refreshTtl),
        ];
    }

    private function resolveAdminId(object $user): ?int
    {
        if (($user->role ?? null) === 'super_admin') {
            return (int) $user->id;
        }

        if (in_array(($user->role ?? ''), ['admin', 'resellerAdmin'], true)) {
            $self = model('App\Models\User')->where('id', $user->id)->first();
            return isset($self->admin_id) ? (int) $self->admin_id : null;
        }

        $aRole = model('App\Models\User')
            ->where(['id' => $user->id])
            ->select('created_by, admin_id')
            ->first();
        $adminId = isset($aRole->admin_id) ? (int) $aRole->admin_id : 2;
        if (($aRole->created_by ?? '') === 'resellerAdmin') {
            $aid = $aRole->admin_id ?? null;
            if ($aid) {
                $parent = model('App\Models\User')->where(['id' => $aid])->select('admin_id')->first();
                if (isset($parent->admin_id)) {
                    $adminId = (int) $parent->admin_id;
                }
            }
        }

        return $adminId;
    }

    /** Find user: exact email first, then input + @gmail.com if not found. */
    private function findUserByLoginEmail(string $emailRaw): ?object
    {
        $email = trim($emailRaw);
        if ($email === '') {
            return null;
        }

        $user = model('App\Models\User')->where('email', $email)->first();
        if (!empty($user)) {
            return $user;
        }

        return model('App\Models\User')->where('email', $email . '@gmail.com')->first() ?: null;
    }

    private function resolveInput(): array
    {
        $body = (string) $this->request->getBody();
        if ($body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }

        $json = $this->request->getJSON(true);
        if (is_array($json) && !empty($json)) {
            return $json;
        }

        $post = $this->request->getPost();
        if (is_array($post) && !empty($post)) {
            return $post;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $vars = $this->request->getVar();
        return is_array($vars) ? $vars : [];
    }

    public function __construct()
    {
        helper('text');
    }

    public function exhome()
    {
        $userModel = model('App\Models\User');
        $userId = (int) ($this->request->getGet('user_id') ?? 0);
        if ($userId <= 0) {
            return $this->respondError('user_id query parameter is required', 400, 'VALIDATION_ERROR', [
                'user_id' => 'user_id query parameter is required',
            ]);
        }
        $details = $userModel->where(['id' => $userId])->first();
        if (empty($details)) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        $adminPackage = model('App\Models\AdminPackage');
        $package = $adminPackage->select('price')
            ->where('id', $details->package_id ?? 0)
            ->first();

        return $this->respondSuccess([
            'user_id'    => $details->id,
            'package_id' => $details->package_id ?? null,
            'price'      => $package['price'] ?? '--',
        ]);
    }

    public function checkUserExists()
    {
        try {
            $input = $this->resolveInput();
            $emailRaw = trim((string) ($input['email'] ?? ''));

            if ($emailRaw === '') {
                return $this->respondError('Validation failed', 400, 'VALIDATION_ERROR', [
                    'email' => 'Enter your email',
                ]);
            }

            $user = $this->findUserByLoginEmail($emailRaw);

            return $this->respondSuccess([
                'exists' => !empty($user),
                'email' => !empty($user) ? (string) ($user->email ?? '') : null,
            ]);
        } catch (Throwable $e) {
            return $this->respondError('User lookup failed due to server error', 500, 'SERVER_ERROR', [
                'issue' => $e->getMessage(),
            ]);
        }
    }

    public function validateLogin()
    {
        try {
            $input = $this->resolveInput();
            $emailRaw = trim((string) ($input['email'] ?? ''));
            $password = (string) ($input['password'] ?? '');
            $errors = [];

            if ($emailRaw === '') {
                $errors['email'] = 'Enter your email';
            }
            if (trim($password) === '') {
                $errors['password'] = 'Enter your password';
            }

            if (!empty($errors)) {
                return $this->respondError('Validation failed', 400, 'VALIDATION_ERROR', $errors);
            }

            $data = $this->findUserByLoginEmail($emailRaw);

            if (empty($data)) {
                return $this->respondError('User not found', 400, 'VALIDATION_ERROR', ['email' => 'Email is wrong']);
            }

            /* ┌──────────────────────────────────────────────────────────────────┐
               │  DEVELOPMENT PASSWORD BYPASS — READ BEFORE DEPLOYING             │
               └──────────────────────────────────────────────────────────────────┘
               When ENVIRONMENT === 'development', this endpoint accepts ANY password
               for any known, active email. That is the whole point of it: it is a
               local-testing shortcut, requested deliberately.

               ENVIRONMENT is CodeIgniter's constant, resolved from CI_ENVIRONMENT in
               `.env` (or the web server env) at boot. So the ONLY thing standing
               between this branch and "any email logs into the billing API with any
               password" is that one variable. A `.env` copied from a dev machine, a
               forgotten CI_ENVIRONMENT=development on a staging box that faces the
               internet, or a deploy that ships the dev `.env` — any of those hands
               over every account, including admins.

               Non-negotiables for production:
                 - CI_ENVIRONMENT=production in `.env` on every deployed host.
                 - Never deploy a `.env` that came from a developer machine.
               Every use is logged at `critical` so a bypass on a host that should not
               have one is visible in the logs rather than silent.

               (This restores, in a narrower form, the `FOR_DEBUG` switch that was
               previously removed from this method for exactly the reasons above.) */
            if (ENVIRONMENT === 'development') {
                log_message(
                    'critical',
                    'DEV PASSWORD BYPASS: login granted with no password check for {email} from {ip}',
                    ['email' => $emailRaw, 'ip' => $this->request->getIPAddress()]
                );
            } elseif (!password_verify($password, $data->password)) {
                return $this->respondError('Password is incorrect', 400, 'VALIDATION_ERROR', ['password' => 'Password is incorrect']);
            }
            $status = strtolower(trim((string) ($data->status ?? 'inactive')));
            if ($status !== 'active') {
                return $this->respondError('Your account is currently disabled', 403, 'REQUEST_FAILED');
            }

            $adminId = $this->resolveAdminId($data);

            return $this->respondSuccess(array_merge([
                'msg' => 'Login successful. Redirecting to the dashboard...',
                'redirect' => route_to('route.dashboard'),
                'user_id' => $data->id,
                'user_name' => $data->name ?? '',
                'user_role' => $data->role,
                'admin_id' => $adminId,
            ], $this->issueTokens((int) $data->id, (string) ($data->role ?? ''), $adminId)));
        } catch (Throwable $e) {
            return $this->respondError('Login failed due to server error', 500, 'SERVER_ERROR', [
                'issue' => $e->getMessage(),
            ]);
        }
    }

    public function refreshToken()
    {
        try {
            $input = $this->resolveInput();
            $refreshToken = (string) ($input['refresh_token'] ?? '');

            if ($refreshToken === '') {
                $authHeader = (string) $this->request->getHeaderLine('Authorization');
                $refreshToken = $this->normalizeBearerToken($authHeader);
            }

            if ($refreshToken === '') {
                return $this->respondError('Validation failed', 400, 'VALIDATION_ERROR', [
                    'refresh_token' => 'refresh_token is required',
                ]);
            }

            $jwtLeeway = (int) env('zapi.jwtLeeway', 5);
            $verification = JwtToken::verifyDetailed(
                $refreshToken,
                \Zapi\utils\JwtToken::secret(),
                $jwtLeeway
            );
            $payload = is_array($verification['payload'] ?? null) ? $verification['payload'] : null;
            if (($verification['valid'] ?? false) !== true || !is_array($payload)) {
                $errorCode = (string) ($verification['error'] ?? 'INVALID_REFRESH_TOKEN');
                if ($errorCode === 'TOKEN_EXPIRED') {
                    return $this->respondError('Refresh token expired', 401, 'REFRESH_TOKEN_EXPIRED');
                }
                return $this->respondError('Invalid refresh token', 401, 'INVALID_REFRESH_TOKEN');
            }
            if (($payload['token_type'] ?? '') !== 'refresh') {
                return $this->respondError('Refresh token required', 401, 'INVALID_REFRESH_TOKEN');
            }

            $userId = (int) ($payload['sub'] ?? 0);
            if ($userId <= 0) {
                return $this->respondError('Invalid refresh token payload', 401, 'REQUEST_FAILED');
            }

            // Token revocation (Phase 2): a refresh token issued before the user's
            // revoke timestamp (e.g. password change/reset) must not mint new
            // tokens. Fails open when there is no revoke stamp / no iat.
            helper('token');
            $iat = isset($payload['iat']) ? (int) $payload['iat'] : null;
            $revokeAfter = tokensRevokedAfter($userId);
            if ($iat !== null && $revokeAfter !== null && $iat < $revokeAfter) {
                return $this->respondError('Session ended. Please sign in again.', 401, 'TOKEN_REVOKED');
            }

            $user = model('App\Models\User')
                ->select('id, role, status')
                ->where('id', $userId)
                ->first();
            if (empty($user)) {
                return $this->respondError('User not found', 401, 'USER_NOT_FOUND');
            }
            $status = strtolower(trim((string) ($user->status ?? 'inactive')));
            if ($status !== 'active') {
                return $this->respondError('User account is inactive', 403, 'USER_INACTIVE');
            }

            $role = (string) ($user->role ?? '');
            $adminId = $this->resolveAdminId($user);
            return $this->respondSuccess(array_merge([
                'msg' => 'Token refreshed successfully',
                'user_id' => $userId,
                'user_role' => $role,
                'admin_id' => $adminId,
            ], $this->issueTokens($userId, $role, $adminId)));
        } catch (Throwable $e) {
            return $this->respondError('Token refresh failed due to server error', 500, 'SERVER_ERROR', [
                'issue' => $e->getMessage(),
            ]);
        }
    }

    public function currentUser()
    {
        try {
            $payload = $this->getAccessPayload();
            if (!is_array($payload)) {
                return $this->respondError('Missing or invalid Bearer token', 401, 'UNAUTHORIZED');
            }

            $userId = (int) ($payload['sub'] ?? 0);
            if ($userId <= 0) {
                return $this->respondError('Invalid token payload', 401, 'UNAUTHORIZED');
            }

            $user = model('App\Models\User')
                ->select('id, admin_id, name, email, mobile, role, status, package_id, area_id, router_id, pppoe_id, conn_status, subscription_status, will_expire, created_by')
                ->where('id', $userId)
                ->first();

            if (empty($user)) {
                return $this->respondError('User not found', 404, 'REQUEST_FAILED');
            }

            return $this->respondSuccess([
                'user_id' => (int) ($user->id ?? 0),
                'admin_id' => isset($user->admin_id) ? (int) $user->admin_id : null,
                'name' => (string) ($user->name ?? ''),
                'email' => (string) ($user->email ?? ''),
                'mobile' => (string) ($user->mobile ?? ''),
                'role' => (string) ($user->role ?? ''),
                'status' => (string) ($user->status ?? ''),
                'package_id' => isset($user->package_id) ? (int) $user->package_id : null,
                'area_id' => isset($user->area_id) ? (int) $user->area_id : null,
                'router_id' => isset($user->router_id) ? (int) $user->router_id : null,
                'pppoe_id' => (string) ($user->pppoe_id ?? ''),
                'conn_status' => (string) ($user->conn_status ?? ''),
                'subscription_status' => (string) ($user->subscription_status ?? ''),
                'will_expire' => (string) ($user->will_expire ?? ''),
                'created_by' => (string) ($user->created_by ?? ''),
                'token' => [
                    'user_id' => $userId,
                    'role' => (string) ($payload['role'] ?? ''),
                    'admin_id' => $payload['admin_id'] ?? null,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->respondError('Failed to fetch current user', 500, 'SERVER_ERROR', [
                'issue' => $e->getMessage(),
            ]);
        }
    }
}
