<?php

namespace Zapi\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Zapi\Core\Support\Auth\JwtToken;

class JwtAuthFilter implements FilterInterface
{
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

    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = (string) $request->getHeaderLine('Authorization');
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return $this->error(401, 'UNAUTHORIZED', 'Missing Bearer token.');
        }

        $token = $this->normalizeBearerToken($authHeader);
        if ($token === '') {
            return $this->error(401, 'UNAUTHORIZED', 'Invalid Bearer token.');
        }

        $jwtLeeway = (int) env('zapi.jwtLeeway', 5);
        $verification = JwtToken::verifyDetailed(
            $token,
            \Zapi\utils\JwtToken::secret(),
            $jwtLeeway
        );
        $payload = is_array($verification['payload'] ?? null) ? $verification['payload'] : null;
        if (($verification['valid'] ?? false) !== true || !is_array($payload)) {
            $errorCode = (string) ($verification['error'] ?? 'UNAUTHORIZED');
            if ($errorCode === 'TOKEN_EXPIRED') {
                return $this->error(401, 'TOKEN_EXPIRED', 'Access token expired.');
            }
            return $this->error(401, 'UNAUTHORIZED', 'Token is invalid.');
        }
        if (($payload['token_type'] ?? 'access') !== 'access') {
            return $this->error(401, 'UNAUTHORIZED', 'Access token required.');
        }

        // Token revocation (Phase 2): reject access tokens issued before the
        // user's revoke timestamp (set on password change/reset/account disable).
        // FAILS OPEN — no revoke entry, or no usable claims, means "allow".
        helper('token');
        $sub = $payload['sub'] ?? $payload['id'] ?? $payload['user_id'] ?? null;
        $iat = isset($payload['iat']) ? (int) $payload['iat'] : null;
        if ($sub !== null && $iat !== null) {
            $revokeAfter = tokensRevokedAfter($sub);
            if ($revokeAfter !== null && $iat < $revokeAfter) {
                return $this->error(401, 'TOKEN_REVOKED', 'Session ended. Please sign in again.');
            }
        }

        if ($sub !== null) {
            if (method_exists($request, 'setGlobal')) {
                $request->setGlobal('jwt_user_id', (int) $sub);
            } else {
                $request->globals['jwt_user_id'] = (int) $sub;
            }
        }

        return null;
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

