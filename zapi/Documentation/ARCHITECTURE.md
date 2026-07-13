# ZAPI Modular Architecture

This document defines the permanent folder and code organization standard for `isp-core/zapi`.

## Goals

- Keep API code isolated in `zapi/`.
- Keep files grouped by feature (not one function per file).
- Keep controllers small and HTTP-focused.
- Support growth without deep rewrites.

## Target Structure

```text
zapi/
├─ Modules/
│  ├─ Common/
│  │  ├─ Auth/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Docs/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ CaptivePortal/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Common/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  └─ BkashWebhook/{Controllers,Services,Repositories,DTOs,Routes}/
│  ├─ Customer/
│  │  ├─ User/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Subscription/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Support/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Profile/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Permission/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  └─ Payment/{Controllers,Services,Repositories,DTOs,Routes}/
│  ├─ Reseller/
│  │  ├─ Dashboard/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Area/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Package/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Customer/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ CustomerPayment/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Employee/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ EmployeePayment/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ SupportTicket/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Transaction/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Funding/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Subscription/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Sms/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ VoiceSms/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Payment/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  ├─ Profile/{Controllers,Services,Repositories,DTOs,Routes}/
│  │  └─ Router/{Controllers,Services,Repositories,DTOs,Routes}/
│  └─ Monitor/
│     └─ Traffic/{Controllers,Services,Repositories,DTOs,Routes}/
├─ Core/{Base,Filters,Middleware,Helpers,Support}/
├─ config/{api_routes.php,routes/*.php}
├─ swagger-ui/{index.html,swagger.json,swagger-ui/*}
└─ Documentation/
```

## Controller and File Size Rules

- Controller should stay in HTTP layer only.
- Controller split trigger: more than 200 lines or more than 10 actions.
- Service split trigger: more than 300 lines.
- Repository split trigger: more than 250 lines.
- Split by responsibility groups: `Read`, `Write`, `Bulk`, `Import`.
- Do not split one function per file by default.

## Legacy Controller Policy

- `zapi/Controllers` is removed from runtime architecture.
- Do not create new code under `zapi/Controllers`.
- Runtime handlers must use `Zapi\Modules\...` or `Zapi\Core\...`.
- Legacy compatibility code, if any, must live under `zapi/Modules/Legacy/`.

## Source of Truth

- Routes aggregator: `zapi/config/api_routes.php`
- Modular route files: `zapi/config/routes/*_routes.php`
- Swagger source: route files + swagger generator scripts
