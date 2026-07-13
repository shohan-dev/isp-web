<?php

namespace Zapi\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Zapi\Core\Support\Http\ApiResponse;

class ApiResponseEnvelopeFilter implements FilterInterface
{
    private function ensureLoginValidationDetails(RequestInterface $request, array $payload): array
    {
        $uri = trim($request->getUri()->getPath(), '/');
        if ($uri !== 'api/common/login') {
            return $payload;
        }

        $error = $payload['error'] ?? null;
        $code = (string) ($error['code'] ?? '');
        $details = $error['details'] ?? null;
        if ($code !== 'VALIDATION_ERROR' || !empty($details)) {
            return $payload;
        }

        $payload['error']['details'] = [
            'email' => 'Provide a valid email',
            'password' => 'Provide your password',
        ];

        return $payload;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $uri = trim($request->getUri()->getPath(), '/');
        if ($uri === '' || strpos($uri, 'api/') !== 0) {
            return null;
        }
        if ($uri === 'api/docs' || $uri === 'api/docs/' || strpos($uri, 'api/docs/') === 0) {
            return null;
        }

        $contentType = (string) $response->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') === false) {
            if ($response->getStatusCode() >= 400) {
                $enveloped = ApiResponse::error(
                    'API request failed.',
                    $response->getStatusCode(),
                    'REQUEST_FAILED',
                    ['issue' => 'Non-JSON error response from server']
                );
                return $response->setJSON($enveloped)->setStatusCode($enveloped['statusCode']);
            }
            return null;
        }

        $body = $response->getBody();
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return $response->setJSON(ApiResponse::error('Invalid JSON response payload.', 500, 'INVALID_JSON'));
        }

        if (array_key_exists('statusCode', $decoded)
            && array_key_exists('success', $decoded)
            && array_key_exists('data', $decoded)
            && array_key_exists('error', $decoded)) {
            if (is_array($decoded['data'])
                && array_key_exists('statusCode', $decoded['data'])
                && array_key_exists('success', $decoded['data'])
                && array_key_exists('data', $decoded['data'])
                && array_key_exists('error', $decoded['data'])) {
                $decoded['data'] = $decoded['data']['data'];
                $decoded = $this->ensureLoginValidationDetails($request, $decoded);
                return $response->setJSON($decoded)->setStatusCode((int) $decoded['statusCode']);
            }
            $decoded = $this->ensureLoginValidationDetails($request, $decoded);
            return $response->setJSON($decoded)->setStatusCode((int) ($decoded['statusCode'] ?? $response->getStatusCode()));
        }

        $statusCode = $response->getStatusCode();
        if (array_key_exists('status', $decoded) && !array_key_exists('success', $decoded)) {
            $legacyStatus = strtolower((string) $decoded['status']);
            if ($legacyStatus === 'success') {
                $payload = array_key_exists('data', $decoded) ? $decoded['data'] : $decoded;
                $enveloped = ApiResponse::success($payload, $statusCode < 400 ? $statusCode : 200);

                return $response->setJSON($enveloped)->setStatusCode($enveloped['statusCode']);
            }

            $message = $decoded['message'] ?? 'Request failed.';
            $details = is_array($decoded['errors'] ?? null) ? $decoded['errors'] : [];
            $enveloped = ApiResponse::error((string) $message, $statusCode >= 400 ? $statusCode : 400, 'REQUEST_FAILED', $details);

            return $response->setJSON($enveloped)->setStatusCode($enveloped['statusCode']);
        }

        if ($statusCode < 400) {
            $enveloped = ApiResponse::success($decoded, $statusCode);
        } else {
            $message = $decoded['message'] ?? 'Request failed.';
            $errorCode = $decoded['code'] ?? 'REQUEST_FAILED';
            $details = is_array($decoded['errors'] ?? null) ? $decoded['errors'] : [];
            $enveloped = ApiResponse::error((string) $message, $statusCode, (string) $errorCode, $details);
        }

        $enveloped = $this->ensureLoginValidationDetails($request, $enveloped);
        return $response->setJSON($enveloped)->setStatusCode($statusCode);
    }
}

