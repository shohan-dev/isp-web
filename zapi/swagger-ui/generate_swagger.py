#!/usr/bin/env python3
import json
import re
from pathlib import Path

from swagger_defaults import get_endpoint_request_defaults

ROOT = Path(__file__).resolve().parents[1]
ROUTES_FILE = ROOT / "config" / "api_routes.php"
ROUTES_DIR = ROOT / "config" / "routes"
SWAGGER_FILE = ROOT / "swagger-ui" / "swagger.json"
_INT_FIELDS = ("id", "user_id", "admin_id", "amount", "page", "per_page", "limit", "offset")


def _schema_for_name(name: str) -> dict:
    lowered = name.lower()
    if lowered in _INT_FIELDS or lowered.endswith("_id"):
        return {"type": "integer", "minimum": 1}
    return {"type": "string"}


def _example_for_name(name: str, schema: dict) -> object:
    lowered = name.lower()
    if schema.get("type") == "integer":
        return 1
    if schema.get("type") == "number":
        return 100.0
    if schema.get("type") == "array":
        item_type = (schema.get("items") or {}).get("type")
        if item_type == "integer":
            return [1, 2]
        return ["value1", "value2"]
    if schema.get("format") == "binary":
        return "<upload-file>"
    if lowered in ("email", "contact_email"):
        return "user@example.com"
    if lowered in ("mobile", "phone", "contact_number"):
        return "01700000000"
    if lowered in ("status", "subscription_status"):
        return "active"
    if lowered in ("password", "confirm_password"):
        return "secret123"
    if lowered in ("from", "to", "date", "fromdate", "todate"):
        return "2026-01-01"
    if lowered.endswith("_id"):
        return 1
    return f"sample_{name}"


def _extract_use_map(source: str) -> dict[str, str]:
    uses = {}
    for match in re.finditer(r"use\s+([^;]+);", source):
        fqcn = match.group(1).strip().lstrip("\\")
        # Keep only namespace imports; skip trait "use Foo;" inside class bodies.
        if "\\" not in fqcn:
            continue
        alias = fqcn.split("\\")[-1]
        uses[alias] = fqcn
    return uses


def _extract_namespace(source: str) -> str:
    match = re.search(r"namespace\s+([^;]+);", source)
    return match.group(1).strip().lstrip("\\") if match else ""


def _extract_parent_fqcn(source: str) -> str:
    namespace = _extract_namespace(source)
    uses = _extract_use_map(source)
    match = re.search(r"class\s+[A-Za-z_][A-Za-z0-9_]*\s+extends\s+([\\A-Za-z_][\\A-Za-z0-9_]*)", source)
    if not match:
        return ""
    parent = match.group(1).strip().lstrip("\\")
    if "\\" in parent:
        return parent
    if parent in uses:
        return uses[parent]
    if namespace:
        return f"{namespace}\\{parent}"
    return parent


def _namespace_to_file(fqcn: str) -> Path:
    if fqcn.startswith("Zapi\\"):
        rel = fqcn[len("Zapi\\") :].replace("\\", "/") + ".php"
        return ROOT / rel
    return Path("")


def _extract_method_body(source: str, method_name: str) -> str:
    sig = re.search(rf"public function {re.escape(method_name)}\s*\([^)]*\)\s*\{{", source)
    if not sig:
        return ""
    start = sig.end() - 1
    depth = 0
    for idx in range(start, len(source)):
        char = source[idx]
        if char == "{":
            depth += 1
        elif char == "}":
            depth -= 1
            if depth == 0:
                return source[start + 1 : idx]
    return ""


def _extract_param_names_from_sig(raw_sig_params: str) -> list[str]:
    raw_params = [p.strip() for p in raw_sig_params.split(",") if p.strip()]
    names: list[str] = []
    for raw in raw_params:
        # Match PHP argument variable names in signatures like:
        # int $id, ?string $user_id = null, $resellerId
        m = re.search(r"\$([A-Za-z_][A-Za-z0-9_]*)", raw)
        if not m:
            continue
        names.append(m.group(1))
    return names


def _extract_method_param_names_from_source(source: str, method_name: str) -> list[str]:
    sig = re.search(rf"public function {re.escape(method_name)}\s*\(([^)]*)\)", source)
    if not sig:
        return []
    return _extract_param_names_from_sig(sig.group(1))


def _extract_trait_fqcns(source: str) -> list[str]:
    namespace = _extract_namespace(source)
    uses = _extract_use_map(source)
    trait_fqcns: list[str] = []
    for trait_name in re.findall(r"^\s*use\s+([A-Za-z_][A-Za-z0-9_]*)\s*;", source, flags=re.MULTILINE):
        fqcn = uses.get(trait_name)
        if fqcn:
            trait_fqcns.append(fqcn)
        elif namespace:
            trait_fqcns.append(f"{namespace}\\{trait_name}")
    return trait_fqcns


def _extract_method_param_names_with_traits(source: str, method_name: str) -> list[str]:
    own = _extract_method_param_names_from_source(source, method_name)
    if own:
        return own
    for trait_fqcn in _extract_trait_fqcns(source):
        trait_path = _namespace_to_file(trait_fqcn)
        if not trait_path.is_file():
            continue
        trait_src = trait_path.read_text(encoding="utf-8")
        names = _extract_method_param_names_from_source(trait_src, method_name)
        if names:
            return names
    return []


def _resolve_method_owner_source(source: str, method_name: str) -> str:
    if _extract_method_body(source, method_name):
        return source
    parent_fqcn = _extract_parent_fqcn(source)
    if not parent_fqcn:
        return ""
    parent_path = _namespace_to_file(parent_fqcn)
    if not parent_path.is_file():
        return ""
    parent_src = parent_path.read_text(encoding="utf-8")
    return _resolve_method_owner_source(parent_src, method_name)


def _extract_method_param_names(handler: str) -> list[str]:
    try:
        class_part, method_name = handler.strip("\\").split("::", 1)
    except ValueError:
        return []

    controller_path = _namespace_to_file(class_part)
    if not controller_path.is_file():
        return []

    controller_src = controller_path.read_text(encoding="utf-8")
    owner_src = _resolve_method_owner_source(controller_src, method_name) or controller_src
    controller_params = _extract_method_param_names_with_traits(owner_src, method_name)

    # Modular wrapper controllers often expose (...$args) and delegate to services.
    if controller_params and controller_params != ["args"]:
        return controller_params

    method_src = _extract_method_body(owner_src, method_name)
    if not method_src:
        return controller_params

    delegate = re.search(r"\$this->service->([A-Za-z_][A-Za-z0-9_]*)\s*\(", method_src)
    if not delegate:
        return controller_params
    service_method = delegate.group(1)

    uses = _extract_use_map(owner_src)
    init_match = re.search(r"\$this->service\s*=\s*new\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(", owner_src)
    if not init_match:
        return controller_params
    service_alias = init_match.group(1)
    service_fqcn = uses.get(service_alias)
    if not service_fqcn:
        return controller_params

    service_path = _namespace_to_file(service_fqcn)
    if not service_path.is_file():
        return controller_params
    service_src = service_path.read_text(encoding="utf-8")
    service_params = _extract_method_param_names_with_traits(service_src, service_method)
    return service_params or controller_params


def _resolve_handler_source(handler: str) -> str:
    class_part, method_name = handler.strip("\\").split("::", 1)
    controller_path = _namespace_to_file(class_part)
    if not controller_path.is_file():
        return ""

    controller_src = controller_path.read_text(encoding="utf-8")
    owner_src = _resolve_method_owner_source(controller_src, method_name) or controller_src
    method_src = _extract_method_body(owner_src, method_name)
    if not method_src:
        return ""

    # Detect common delegation: return $this->service->method(...)
    delegate = re.search(r"\$this->service->([A-Za-z_][A-Za-z0-9_]*)\s*\(", method_src)
    if not delegate:
        return method_src

    service_method = delegate.group(1)
    uses = _extract_use_map(owner_src)
    init_match = re.search(r"\$this->service\s*=\s*new\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(", owner_src)
    if not init_match:
        return method_src

    service_alias = init_match.group(1)
    service_fqcn = uses.get(service_alias)
    if not service_fqcn:
        return method_src

    service_path = _namespace_to_file(service_fqcn)
    if not service_path.is_file():
        return method_src

    service_src = service_path.read_text(encoding="utf-8")
    resolved = _extract_method_body(service_src, service_method)
    if resolved:
        return resolved
    for trait_fqcn in _extract_trait_fqcns(service_src):
        trait_path = _namespace_to_file(trait_fqcn)
        if not trait_path.is_file():
            continue
        trait_src = trait_path.read_text(encoding="utf-8")
        trait_method = _extract_method_body(trait_src, service_method)
        if trait_method:
            return trait_method
    return method_src


def _infer_required_fields(method_src: str, kind: str) -> set[str]:
    required: set[str] = set()
    assignments = re.findall(
        rf"\$([A-Za-z_][A-Za-z0-9_]*)\s*=\s*\$this->request->get{kind}\('([^']+)'\)",
        method_src,
    )
    for var_name, field_name in assignments:
        if re.search(rf"empty\(\${re.escape(var_name)}\)|!\${re.escape(var_name)}", method_src):
            required.add(field_name)
        if re.search(rf"{re.escape(field_name)}[^'\n]*required", method_src, flags=re.IGNORECASE):
            required.add(field_name)
    return required


def _infer_validation_required_fields(method_src: str) -> set[str]:
    out: set[str] = set()
    for field, rules in re.findall(r"'([A-Za-z0-9_]+)'\s*=>\s*'([^']+)'", method_src):
        if field in ("rules", "errors"):
            continue
        if "required" in rules.split("|"):
            out.add(field)
    return out


def _fallback_path_param_name(prev_static: str, idx: int, schema_type: str) -> str:
    token = re.sub(r"[^A-Za-z0-9_]", "_", prev_static.lower()).strip("_")
    if token in ("", "api", "common", "customer", "reseller"):
        return "id" if schema_type == "integer" else f"param{idx}"
    if token.endswith("ies") and len(token) > 3:
        token = token[:-3] + "y"
    elif token.endswith("s") and len(token) > 1:
        token = token[:-1]
    if token.endswith("_id"):
        return token
    return f"{token}_id"


def normalize_path(ci_path: str, path_param_names: list[str] | None = None) -> tuple[str, list[dict]]:
    path_param_names = path_param_names or []
    params = []
    idx = 1
    arg_idx = 0
    out = []
    used_names: set[str] = set()
    last_static = ""
    for token in ci_path.strip("/").split("/"):
        if token.startswith("(:"):
            schema_type = "string"
            if token in ("(:num)", "(:num?)"):
                schema_type = "integer"
            fallback = _fallback_path_param_name(last_static, idx, schema_type)
            candidate = path_param_names[arg_idx] if arg_idx < len(path_param_names) else fallback
            name = re.sub(r"[^A-Za-z0-9_]", "_", candidate).strip("_") or fallback
            if name in ("args", "arg"):
                name = fallback
            while name in used_names:
                name = f"{name}_{idx}"
            used_names.add(name)
            required = not token.endswith("? )") and not token.endswith("?)")
            params.append(
                {
                    "name": name,
                    "in": "path",
                    "required": required,
                    "schema": {"type": schema_type},
                }
            )
            out.append("{" + name + "}")
            idx += 1
            arg_idx += 1
        else:
            out.append(token)
            last_static = token
    return "/" + "/".join([p for p in out if p]), params


def detect_content_hint(route: str) -> str | None:
    html_markers = ("invoice-print", "make-payment", "make-reseller-payment")
    text_markers = (
        "pppoe-expiry-check",
        "captive-portal",
        "generate_204",
        "hotspot-detect.html",
        "connecttest.txt",
        "ncsi.txt",
    )
    if any(m in route for m in html_markers):
        return "Returns HTML content."
    if any(m in route for m in text_markers):
        return "Returns text/plain content."
    return None


def _parse_route_file(route_file: Path) -> list[dict]:
    lines = route_file.read_text(encoding="utf-8").splitlines()
    stack: list[str] = []
    routes: list[dict] = []
    for line in lines:
        group_match = re.search(r"\$routes->group\('([^']+)'", line)
        if group_match:
            stack.append(group_match.group(1))
            continue

        if line.strip() == "});" and stack:
            stack.pop()
            continue

        route_match = re.search(
            r"\$routes->(get|post|put|delete)\('([^']+)',\s*'([^']+)'\)", line
        )
        if not route_match:
            continue

        method, raw_path, handler = route_match.groups()
        method = method.lower()
        handler = handler.split("/", 1)[0]

        prefix = [s.strip("/") for s in stack if s.strip("/") and s != "api"]
        full_path = "/api"
        if prefix:
            full_path += "/" + "/".join(prefix)
        if raw_path.strip("/") and raw_path != "/":
            full_path += "/" + raw_path.strip("/")

        if not (
            full_path.startswith("/api/common/")
            or full_path.startswith("/api/customer/")
            or full_path.startswith("/api/reseller/")
            or full_path.startswith("/api/monitor/")
            or full_path.startswith("/api/docs")
        ):
            continue

        if full_path.startswith("/api/common/") or full_path.startswith("/api/docs"):
            tag = "Common"
        elif full_path.startswith("/api/customer/"):
            tag = "Customer"
        elif full_path.startswith("/api/monitor/"):
            tag = "Monitor"
        else:
            tag = "Reseller"

        path_param_names = _extract_method_param_names(handler)
        openapi_path, params = normalize_path(full_path, path_param_names)
        description = detect_content_hint(openapi_path)
        routes.append(
            {
                "method": method,
                "path": openapi_path,
                "handler": handler,
                "tag": tag,
                "params": params,
                "description": description,
            }
        )
    return routes


def parse_routes() -> list[dict]:
    route_files = [ROUTES_FILE]
    if ROUTES_DIR.is_dir():
        route_files.extend(sorted(ROUTES_DIR.glob("*_routes.php")))

    routes: list[dict] = []
    for route_file in route_files:
        if route_file.is_file():
            routes.extend(_parse_route_file(route_file))
    return routes


def operation_id(handler: str, method: str) -> str:
    cleaned = handler.strip("\\").replace("\\", "_").replace("::", "_")
    return f"{cleaned}_{method}".replace("/", "_").replace("-", "_")


def infer_query_params(path: str, method: str, handler: str) -> list[dict]:
    if method != "get":
        return []

    method_src = _resolve_handler_source(handler)
    params: list[dict] = []
    explicit = sorted(set(re.findall(r"\$this->request->getGet\('([^']+)'\)", method_src)))
    required_fields = _infer_required_fields(method_src, "Get")
    for field in explicit:
        params.append(
            {
                "name": field,
                "in": "query",
                "required": field in required_fields,
                "schema": _schema_for_name(field),
            }
        )

    lower_path = path.lower()

    # Common list/filter controls used across reseller/customer endpoints.
    if any(marker in lower_path for marker in ("fetch", "customers", "payments", "tickets", "sms", "routers", "areas", "transactions")):
        params.extend(
            [
                {"name": "page", "in": "query", "required": False, "schema": {"type": "integer", "minimum": 1}},
                {"name": "per_page", "in": "query", "required": False, "schema": {"type": "integer", "minimum": 1}},
                {"name": "search", "in": "query", "required": False, "schema": {"type": "string"}},
                {"name": "status", "in": "query", "required": False, "schema": {"type": "string"}},
            ]
        )

    if "details" in lower_path:
        params.append({"name": "include", "in": "query", "required": False, "schema": {"type": "string"}})

    if any(marker in lower_path for marker in ("usage", "dashboard")):
        params.extend(
            [
                {"name": "from", "in": "query", "required": False, "schema": {"type": "string", "format": "date"}},
                {"name": "to", "in": "query", "required": False, "schema": {"type": "string", "format": "date"}},
            ]
        )

    unique = []
    seen = set()
    for param in params:
        key = (param["name"], param["in"])
        if key in seen:
            continue
        seen.add(key)
        unique.append(param)
    return unique


def infer_request_body(path: str, method: str) -> dict | None:
    if method not in ("post", "put", "patch", "delete"):
        return None
    method_src = _resolve_handler_source(route_handler_cache.get((path, method), ""))

    required = method in ("post", "put")
    lower_path = path.lower()
    properties: dict[str, dict] = {}
    required_fields: set[str] = set()

    post_fields = sorted(set(re.findall(r"\$this->request->getPost\('([^']+)'\)", method_src)))
    file_fields = sorted(set(re.findall(r"\$this->request->getFile\('([^']+)'\)", method_src)))
    required_fields |= _infer_required_fields(method_src, "Post")
    required_fields |= _infer_validation_required_fields(method_src)

    for field in post_fields:
        schema = _schema_for_name(field)
        schema["example"] = _example_for_name(field, schema)
        properties[field] = schema
    for field in file_fields:
        properties[field] = {"type": "string", "format": "binary"}

    # Baseline payload fields supported by legacy + new handlers.
    if ("bulk-delete" in lower_path or method == "delete") and "ids" not in properties:
        properties["ids"] = {"type": "array", "items": {"type": "integer"}}
        properties["ids"]["example"] = _example_for_name("ids", properties["ids"])
        required_fields.add("ids")

    if not properties and any(marker in lower_path for marker in ("create", "update", "renew", "send", "message", "import-excel", "bulk-recharge")):
        properties.update(
            {
                "name": {"type": "string", "example": "sample_name"},
                "amount": {"type": "number", "example": 100.0},
                "status": {"type": "string", "example": "active"},
                "note": {"type": "string", "example": "sample_note"},
            }
        )

    endpoint_defaults = get_endpoint_request_defaults(path, method)
    for field, meta in (endpoint_defaults.get("properties") or {}).items():
        if field not in properties:
            properties[field] = meta
            continue
        properties[field].update(meta)
    required_fields.update(endpoint_defaults.get("required") or [])

    schema: dict = {"type": "object"}
    if properties:
        schema["properties"] = properties
    if required_fields:
        schema["required"] = sorted(required_fields)

    if properties:
        required_examples = {
            field: (properties.get(field, {}).get("example"))
            for field in sorted(required_fields)
            if "example" in (properties.get(field) or {})
        }
        all_examples = {
            field: meta.get("example")
            for field, meta in properties.items()
            if "example" in meta
        }
        schema["example"] = required_examples or all_examples

    # Keep Swagger input easy in UI (form-data) while also supporting JSON payloads.
    content = {
        "application/json": {"schema": schema},
        "multipart/form-data": {"schema": schema},
        "application/x-www-form-urlencoded": {"schema": schema},
    }

    return {
        "required": required,
        "content": content,
    }


route_handler_cache: dict[tuple[str, str], str] = {}


def build_swagger(routes: list[dict]) -> dict:
    paths: dict = {}
    seen = set()
    for r in routes:
        key = (r["path"], r["method"])
        if key in seen:
            continue
        seen.add(key)
        paths.setdefault(r["path"], {})
        resp_200_schema = {"$ref": "#/components/schemas/ApiSuccessEnvelope"}
        if r["description"] and "text/plain" in r["description"]:
            resp_200_schema = {"type": "string"}

        op = {
            "tags": [r["tag"]],
            "operationId": operation_id(r["handler"], r["method"]),
            "responses": {
                "200": {
                    "description": "Successful response",
                    "content": {
                        "application/json": {
                            "schema": resp_200_schema
                        }
                    },
                },
                "400": {
                    "description": "Bad request",
                    "content": {
                        "application/json": {
                            "schema": {"$ref": "#/components/schemas/ApiErrorEnvelope"}
                        }
                    },
                },
                "401": {
                    "description": "Unauthorized",
                    "content": {
                        "application/json": {
                            "schema": {"$ref": "#/components/schemas/ApiErrorEnvelope"}
                        }
                    },
                },
                "403": {
                    "description": "Forbidden",
                    "content": {
                        "application/json": {
                            "schema": {"$ref": "#/components/schemas/ApiErrorEnvelope"}
                        }
                    },
                },
                "404": {
                    "description": "Not found",
                    "content": {
                        "application/json": {
                            "schema": {"$ref": "#/components/schemas/ApiErrorEnvelope"}
                        }
                    },
                },
                "500": {
                    "description": "Internal server error",
                    "content": {
                        "application/json": {
                            "schema": {"$ref": "#/components/schemas/ApiErrorEnvelope"}
                        }
                    },
                },
            },
        }
        route_handler_cache[(r["path"], r["method"])] = r["handler"]
        all_params = list(r["params"]) + infer_query_params(r["path"], r["method"], r["handler"])
        if all_params:
            op["parameters"] = all_params
        if r["description"]:
            op["description"] = r["description"]
        request_body = infer_request_body(r["path"], r["method"])
        if request_body is not None:
            op["requestBody"] = request_body
        paths[r["path"]][r["method"]] = op

    return {
        "openapi": "3.0.3",
        "info": {
            "title": "ISP ZAPI",
            "version": "1.1.0",
            "description": "Full endpoint documentation for common, customer, reseller routes.",
        },
        "servers": [{"url": "/", "description": "Current host"}],
        "security": [{"bearerAuth": []}],
        "tags": [{"name": "Common"}, {"name": "Customer"}, {"name": "Reseller"}, {"name": "Monitor"}],
        "paths": dict(sorted(paths.items())),
        "components": {
            "securitySchemes": {
                "bearerAuth": {
                    "type": "http",
                    "scheme": "bearer",
                    "bearerFormat": "JWT",
                }
            },
            "schemas": {
                "ApiErrorObject": {
                    "type": "object",
                    "properties": {
                        "code": {"type": "string"},
                        "message": {"type": "string"},
                        "details": {"type": "array", "items": {"type": "object"}},
                    },
                    "required": ["code", "message", "details"],
                },
                "ApiSuccessEnvelope": {
                    "type": "object",
                    "properties": {
                        "statusCode": {"type": "integer", "example": 200},
                        "success": {"type": "boolean", "example": True},
                        "data": {"nullable": True},
                        "error": {"type": "null", "nullable": True},
                    },
                    "required": ["statusCode", "success", "data", "error"],
                },
                "ApiErrorEnvelope": {
                    "type": "object",
                    "properties": {
                        "statusCode": {"type": "integer", "example": 400},
                        "success": {"type": "boolean", "example": False},
                        "data": {"type": "null", "nullable": True},
                        "error": {"$ref": "#/components/schemas/ApiErrorObject"},
                    },
                    "required": ["statusCode", "success", "data", "error"],
                },
            }
        },
    }


def main() -> None:
    routes = parse_routes()
    doc = build_swagger(routes)
    SWAGGER_FILE.write_text(json.dumps(doc, indent=2), encoding="utf-8")
    print(f"Generated {len(routes)} route entries into {SWAGGER_FILE}")


if __name__ == "__main__":
    main()
