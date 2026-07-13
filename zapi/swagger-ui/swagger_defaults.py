from __future__ import annotations

from typing import Any


# Endpoint-level request body defaults used by swagger generation.
# Key format: ("<openapi_path>", "<http_method>")
ENDPOINT_REQUEST_DEFAULTS: dict[tuple[str, str], dict[str, Any]] = {
    ("/api/common/login", "post"): {
        "required": ["email", "password"],
        "properties": {
            "email": {"type": "string", "format": "email", "example": "alom@gmail.com"},
            "password": {"type": "string", "example": "11223344"},
        },
    },
    ("/api/common/check-user", "post"): {
        "required": ["email"],
        "properties": {
            "email": {"type": "string", "example": "00shagor@"},
        },
    },
    ("/api/common/refresh", "post"): {
        "required": ["refresh_token"],
        "properties": {
            "refresh_token": {"type": "string", "example": "paste_your_refresh_token_here"},
        },
    },
}


def get_endpoint_request_defaults(path: str, method: str) -> dict[str, Any]:
    return ENDPOINT_REQUEST_DEFAULTS.get((path, method.lower()), {})
