# ZAPI Documentation

This folder is the human-readable API documentation for `isp-core/zapi`.

## Current completeness status

The API surface is mostly organized and active, but **not fully complete** yet.

Known partial endpoints:

- `GET /api/customer/json/invoice-print` -> returns `501 NOT_IMPLEMENTED`
- `GET /api/customer/usage` -> may return `501 NOT_IMPLEMENTED` when handler is unavailable

## Docs in this folder

- `AUTH.md` -> authentication and authorization model
- `PORTALS_AND_SECTIONS.md` -> portal grouping and section ownership
- `ENDPOINT_CATALOG.md` -> all registered endpoints by portal/section with method
- `USE_CASES_AND_FIELDS.md` -> common use-cases and expected fields
- `ARCHITECTURE.md` -> modular folder structure, naming, and split rules
- `MIGRATION_GUIDE.md` -> legacy-to-modular migration map and status

## Source of truth

- Route source: `zapi/config/api_routes.php` + `zapi/config/routes/*_routes.php`
- Interactive docs: `/api/docs`
- OpenAPI file: `zapi/swagger-ui/swagger.json`
