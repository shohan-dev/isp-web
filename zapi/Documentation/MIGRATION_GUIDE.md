# ZAPI Migration Guide (Legacy -> Modules)

This guide tracks the modular migration for `zapi` while keeping existing endpoint behavior unchanged.

## Route Migration

- Legacy single file route map has been split into:
  - `zapi/config/routes/common_routes.php`
  - `zapi/config/routes/monitor_routes.php`
  - `zapi/config/routes/customer_routes.php`
  - `zapi/config/routes/reseller_routes.php`
- `zapi/config/api_routes.php` now acts as an aggregator and loads those route files.

## Namespace Migration Status

### Completed hard cut

- Runtime route handlers now resolve through module namespaces.
- `zapi/Controllers` folder has been removed.
- Legacy namespace references were rewritten away from `Zapi\\Controllers\\...`.
- Legacy code was moved under `zapi/Modules/Legacy/Controllers` for controlled compatibility.
- Feature folders and final policy are defined in `ARCHITECTURE.md`.

## Legacy-to-Module Mapping (Implemented)

- `Zapi\\Modules\\Legacy\\Controllers\\Common\\AuthController` -> `Zapi\\Modules\\Common\\Auth\\Controllers\\AuthController`
- `Zapi\\Modules\\Legacy\\Controllers\\Common\\DocsController` -> `Zapi\\Modules\\Common\\Docs\\Controllers\\DocsController`
- `Zapi\\Modules\\Legacy\\Controllers\\Common\\CommonController` -> `Zapi\\Modules\\Common\\Common\\Controllers\\CommonController`
- `Zapi\\Modules\\Legacy\\Controllers\\Common\\CaptivePortalController` -> `Zapi\\Modules\\Common\\CaptivePortal\\Controllers\\CaptivePortalController`
- `Zapi\\Modules\\Legacy\\Controllers\\Common\\BkashWebhookController` -> `Zapi\\Modules\\Common\\BkashWebhook\\Controllers\\BkashWebhookController`
- `Zapi\\Modules\\Legacy\\Controllers\\Monitor\\TrafficMonitorController` -> `Zapi\\Modules\\Monitor\\Traffic\\Controllers\\TrafficMonitorController`

## Legacy Trait Migration Status

- `PartXXTrait` identifiers were removed from the codebase.
- Legacy segmented files now use `PartXXSegment` naming pending deeper service-class consolidation by feature.

## Swagger and Docs

- Swagger generator now scans modular route files under `zapi/config/routes`.
- Docs UI remains served by `/api/docs` and `/api/docs/swagger.json`.
