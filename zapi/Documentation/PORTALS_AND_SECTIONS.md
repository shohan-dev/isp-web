# Portals and Sections

## Portal: Common

Namespace roots:

- `Zapi\Modules\Legacy\Controllers\Common`
- Shared service logic: `Zapi\Modules\Legacy\Controllers\Common\CommonService`

Sections:

- Connectivity helpers (`pppoe-expiry-check`, captive portal probes)
- Movie servers
- News
- Payment webhooks
- Docs hosting (`/api/docs`)

## Portal: Customer

Namespace root: `Zapi\Modules\Legacy\Controllers\Portal\Customer`

Sections:

- `User` -> profile/user fetch, packages, ping
- `Subscription` -> index/renew/activate/update
- `Support` -> fetch/details/send/create ticket
- `Profile` -> profile update
- `Permission` -> permission check
- `Payment` -> payment and reseller-payment flows

## Portal: Reseller

Namespace root: `Zapi\Modules\Legacy\Controllers\Portal\Reseller`

Sections:

- `Dashboard`
- `Area`
- `Package`
- `Customer`
- `CustomerPayment`
- `Employee`
- `EmployeePayment`
- `SupportTicket`
- `Transaction`
- `Funding`
- `Subscription`
- `Sms`
- `VoiceSms`
- `Payment`
- `Profile`
- `Router`
- `Core` (shared reseller core service)
