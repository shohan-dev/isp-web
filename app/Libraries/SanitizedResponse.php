<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Response;

class SanitizedResponse extends Response
{
    /**
     * Remove sensitive fields from JSON responses.
     *
     * @param array|object|string $body
     *
     * @return $this
     */
    public function setJSON($body, bool $unencoded = false)
    {
        if (! $unencoded) {
            $body = $this->stripSensitiveFields($body);
        }

        return parent::setJSON($body, $unencoded);
    }

    /**
     * Recursively remove password fields from payloads.
     *
     * @param mixed $payload
     *
     * @return mixed
     */
    private function stripSensitiveFields($payload)
    {
        if (is_array($payload)) {
            $sanitized = [];

            foreach ($payload as $key => $value) {
                if (is_string($key) && strtolower($key) === 'password') {
                    continue;
                }

                $sanitized[$key] = $this->stripSensitiveFields($value);
            }

            return $sanitized;
        }

        if (is_object($payload)) {
            if ($payload instanceof \JsonSerializable) {
                return $this->stripSensitiveFields($payload->jsonSerialize());
            }

            if (method_exists($payload, 'toArray')) {
                return $this->stripSensitiveFields($payload->toArray());
            }

            $vars = get_object_vars($payload);
            if ($vars !== []) {
                return $this->stripSensitiveFields($vars);
            }
        }

        return $payload;
    }
}
