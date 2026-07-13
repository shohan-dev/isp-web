<?php

namespace Zapi\utils;

class RequestParser
{
    public static function pagination(array $input): array
    {
        $page = max(1, (int) ($input['page'] ?? 1));
        $perPage = max(1, min(200, (int) ($input['perPage'] ?? 25)));

        return [
            'page' => $page,
            'perPage' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }
}

