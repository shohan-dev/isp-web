# Authentication and Authorization

## Auth modes

Auth behavior is controlled from env:

- `zapi.enabled` -> enables/disables ZAPI routes
- `zapi.requireAuth` -> enables/disables auth filters

## Filters

- `zapijwt` (`zapi/Filters/JwtAuthFilter.php`) validates JWT
- `zapirole:customer` (`zapi/Filters/RoleAuthFilter.php`) enforces customer role
- `zapirole:reseller` (`zapi/Filters/RoleAuthFilter.php`) enforces reseller role

## Group-level protection

- `api/common` -> `zapijwt` when auth is enabled
- `api/customer` -> `zapijwt + zapirole:customer` when auth is enabled
- `api/reseller` -> `zapijwt + zapirole:reseller` when auth is enabled

## Swagger auth usage

Swagger includes bearer auth scheme (`bearerAuth`).

Use:

1. Open `/api/docs`
2. Click `Authorize`
3. Paste token as bearer token
4. Execute protected endpoints
