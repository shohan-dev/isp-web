#!/usr/bin/env python3
import json
from pathlib import Path

from generate_swagger import build_swagger, parse_routes

ROOT = Path(__file__).resolve().parents[1]
SWAGGER_FILE = ROOT / "swagger-ui" / "swagger.json"


def main() -> None:
    routes = parse_routes()
    route_pairs = {(r["path"], r["method"]) for r in routes}
    expected = build_swagger(routes)
    swagger = json.loads(SWAGGER_FILE.read_text(encoding="utf-8"))
    doc_pairs = {
        (path, method)
        for path, ops in swagger.get("paths", {}).items()
        for method in ops.keys()
    }

    missing = sorted(route_pairs - doc_pairs)
    extra = sorted(doc_pairs - route_pairs)

    if missing:
        print("Missing in swagger.json:")
        for p, m in missing:
            print(f"  - {m.upper()} {p}")

    if extra:
        print("Extra in swagger.json (not in routes):")
        for p, m in extra:
            print(f"  - {m.upper()} {p}")

    param_mismatches = []
    for path, method in sorted(route_pairs & doc_pairs):
        expected_op = ((expected.get("paths") or {}).get(path) or {}).get(method) or {}
        actual_op = ((swagger.get("paths") or {}).get(path) or {}).get(method) or {}

        expected_path_params = [
            p["name"]
            for p in expected_op.get("parameters", [])
            if p.get("in") == "path"
        ]
        actual_path_params = [
            p["name"]
            for p in actual_op.get("parameters", [])
            if p.get("in") == "path"
        ]
        if expected_path_params != actual_path_params:
            param_mismatches.append((path, method, expected_path_params, actual_path_params))

    if param_mismatches:
        print("Path parameter name mismatches:")
        for path, method, exp, got in param_mismatches:
            print(f"  - {method.upper()} {path}")
            print(f"    expected: {exp}")
            print(f"    actual:   {got}")

    if not missing and not extra and not param_mismatches:
        print("Swagger is in sync with ZAPI route map.")

    raise SystemExit(1 if (missing or extra or param_mismatches) else 0)


if __name__ == "__main__":
    main()
