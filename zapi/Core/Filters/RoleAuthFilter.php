<?php

namespace Zapi\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Zapi\Core\Support\Auth\JwtToken;

class RoleAuthFilter implements FilterInterface
{
    private function normalizeBearerToken(string $authHeader): string
    {
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return '';
        }

        $token = trim(substr($authHeader, 7));
        // Accept accidental "Bearer bearer <jwt>" from clients, matching
        // JwtAuthFilter so a request that passes zapijwt does not then 401 here.
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        return $token;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $requiredRole = strtolower((string) ($arguments[0] ?? ''));
        if ($requiredRole === '') {
            return null;
        }

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

        $actualRole = strtolower((string) ($payload['role'] ?? ''));
        if (!$this->isAllowed($requiredRole, $actualRole)) {
            return $this->error(403, 'FORBIDDEN', 'Role does not have access to this endpoint.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function isAllowed(string $requiredRole, string $actualRole): bool
    {
        if ($requiredRole === 'customer') {
            return in_array($actualRole, ['user'], true);
        }
        if ($requiredRole === 'reseller') {
            return in_array($actualRole, ['reselleradmin', 'admin', 'super_admin', 'employee'], true);
        }

        return $actualRole === $requiredRole;
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

