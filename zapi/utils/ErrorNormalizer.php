<?php

namespace Zapi\utils;

use Throwable;

class ErrorNormalizer
{
    public static function fromThrowable(Throwable $exception, int $statusCode = 500): array
    {
        $details = ENVIRONMENT === 'production' ? [] : [$exception->getMessage()];

        return [
            'statusCode' => $statusCode,
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'INTERNAL_SERVER_ERROR',
                'message' => 'Unexpected server error.',
                'details' => $details,
            ],
        ];
    }
}

