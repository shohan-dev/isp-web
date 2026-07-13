<?php

namespace Zapi\utils;

class JwtToken
{
    /**
     * Resolve the configured JWT signing secret.
     *
     * Returns an empty string when unset so verification fails closed
     * (see verifyDetailed) instead of falling back to a publicly-known
     * literal. Never returns the old 'change-this-secret' default.
     */
    public static function secret(): string
    {
        return (string) env('zapi.jwtSecret', '');
    }

    public static function issue(array $claims, string $secret, int $ttlSeconds = 3600): string
    {
        if ($secret === '' || $secret === 'change-this-secret') {
            throw new \RuntimeException('zapi.jwtSecret is not configured — refusing to issue an unsigned/forgeable token.');
        }

        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $headerEncoded = self::base64UrlEncode((string) json_encode($header));
        $payloadEncoded = self::base64UrlEncode((string) json_encode($payload));
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);

        return $headerEncoded . '.' . $payloadEncoded . '.' . self::base64UrlEncode($signature);
    }

    public static function verify(string $jwt, string $secret): ?array
    {
        $result = self::verifyDetailed($jwt, $secret);
        if (($result['valid'] ?? false) !== true) {
            return null;
        }

        return is_array($result['payload'] ?? null) ? $result['payload'] : null;
    }

    public static function verifyDetailed(string $jwt, string $secret, int $leewaySeconds = 0): array
    {
        // Fail closed: an unset or default secret can never validate a token.
        if ($secret === '' || $secret === 'change-this-secret') {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'NO_SECRET',
            ];
        }

        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'MALFORMED_TOKEN',
            ];
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        $header = json_decode(self::base64UrlDecode($headerEncoded), true);
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!is_array($header) || !is_array($payload)) {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'INVALID_JSON',
            ];
        }
        if (($header['alg'] ?? '') !== 'HS256') {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'INVALID_ALGORITHM',
            ];
        }

        $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $providedSignature = self::base64UrlDecode($signatureEncoded);
        if (!hash_equals($expectedSignature, $providedSignature)) {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'INVALID_SIGNATURE',
            ];
        }

        $now = time();
        if (isset($payload['nbf']) && $now + $leewaySeconds < (int) $payload['nbf']) {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'NOT_YET_VALID',
            ];
        }
        if (isset($payload['exp']) && $now - $leewaySeconds >= (int) $payload['exp']) {
            return [
                'valid' => false,
                'payload' => $payload,
                'error' => 'TOKEN_EXPIRED',
            ];
        }

        return [
            'valid' => true,
            'payload' => $payload,
            'error' => null,
        ];
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'));
    }
}
