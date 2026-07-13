<?php

namespace Zapi\Core\Base;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class BaseApiController extends BaseController
{
    protected int $defaultPerPage = 10;
    protected int $maxPerPage = 100;

    protected function respondPayload(array $payload, int $code = 200): ResponseInterface
    {
        if (array_key_exists('statusCode', $payload)
            && array_key_exists('success', $payload)
            && array_key_exists('data', $payload)
            && array_key_exists('error', $payload)) {
            return $this->response->setStatusCode((int) $payload['statusCode'])->setJSON($payload);
        }

        if (array_key_exists('status', $payload) && !array_key_exists('success', $payload)) {
            $legacyStatus = strtolower((string) $payload['status']);
            if ($legacyStatus === 'success') {
                $data = array_key_exists('data', $payload) ? $payload['data'] : $payload;
                return $this->respondSuccess($data, $code < 400 ? $code : 200);
            }

            return $this->respondError(
                (string) ($payload['message'] ?? 'Request failed.'),
                $code >= 400 ? $code : 400,
                'REQUEST_FAILED',
                is_array($payload['errors'] ?? null) ? $payload['errors'] : []
            );
        }

        if ($code >= 400) {
            return $this->respondError(
                (string) ($payload['message'] ?? 'Request failed.'),
                $code,
                (string) ($payload['code'] ?? 'REQUEST_FAILED'),
                is_array($payload['errors'] ?? null) ? $payload['errors'] : []
            );
        }

        return $this->respondSuccess($payload, $code);
    }

    protected function respondSuccess($data = null, int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'statusCode' => $code,
            'success' => true,
            'data' => $data,
            'error' => null,
        ]);
    }

    protected function respondError(
        string $message,
        int $code = 400,
        string $errorCode = 'GENERAL_ERROR',
        array $details = []
    ): ResponseInterface {
        $path = trim((string) $this->request->getUri()->getPath(), '/');
        if ($errorCode === 'VALIDATION_ERROR' && empty($details) && $path === 'api/common/login') {
            $details = [
                'email' => 'Enter a valid email address',
                'password' => 'Enter your password',
            ];
        }

        return $this->response->setStatusCode($code)->setJSON([
            'statusCode' => $code,
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details,
            ],
        ]);
    }

    protected function getPaginationParams(): array
    {
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $requestedLimit = (int) ($this->request->getGet('limit') ?? 0);
        $requestedPerPage = (int) ($this->request->getGet('per_page') ?? 0);
        $limit = $requestedLimit > 0 ? $requestedLimit : $requestedPerPage;
        $limit = $limit > 0 ? min($limit, $this->maxPerPage) : $this->defaultPerPage;
        $offset = ($currentPage - 1) * $limit;

        return [
            'page' => $currentPage,
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $currentPage,
            'per_page' => $limit,
        ];
    }

    protected function buildPaginationMeta(int $totalFound, int $currentPage, int $perPage, int $pageCount): array
    {
        $totalPages = max(1, (int) ceil($totalFound / $perPage));
        $currentPage = min($currentPage, $totalPages);

        return [
            'page' => $currentPage,
            'limit' => $perPage,
            'total' => $totalFound,
            'totalPages' => $totalPages,
            'hasNext' => $currentPage < $totalPages,
            'hasPrev' => $currentPage > 1,
            'total_found' => $totalFound,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'page_count' => $pageCount,
            'has_next_page' => $currentPage < $totalPages,
            'has_prev_page' => $currentPage > 1,
        ];
    }

    protected function respondPaginatedSuccess(array $rows, int $totalFound, int $currentPage, int $perPage, int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'statusCode' => $code,
            'success' => true,
            'data' => $rows,
            'pagination' => $this->buildPaginationMeta($totalFound, $currentPage, $perPage, count($rows)),
            'error' => null,
        ]);
    }
}
