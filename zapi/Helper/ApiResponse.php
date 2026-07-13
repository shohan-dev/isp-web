<?php

namespace Zapi\Helper;

class ApiResponse
{
    public static function success($data = null, int $statusCode = 200): array
    {
        return [
            'statusCode' => $statusCode,
            'success' => true,
            'data' => $data,
            'error' => null,
        ];
    }

    public static function error(
        string $message,
        int $statusCode = 400,
        string $code = 'GENERAL_ERROR',
        array $details = []
    ): array {
        return [
            'statusCode' => $statusCode,
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ];
    }
}

