# Endpoint Catalog

Source: `zapi/config/api_routes.php`

## Docs

- GET `/api/docs`
- GET `/api/docs/`
- GET `/api/docs/swagger.json`
- GET `/api/docs/swagger-ui/{asset}`

## Common Portal

- GET `/api/common/pppoe-expiry-check`
- GET `/api/common/captive-portal`
- GET `/api/common/generate_204`
- GET `/api/common/hotspot-detect.html`
- GET `/api/common/connecttest.txt`
- GET `/api/common/ncsi.txt`
- POST `/api/common/login`
- POST `/api/common/check-user`
- POST `/api/common/refresh`
- GET `/api/common/exhome`

**Public (no JWT):** SMS listener and IPN callbacks cannot send a Bearer token.

- POST `/api/common/bkash/webhook` (bKash IPN / logging)
- POST `/api/common/bkash/get_bkash_sendmoney` (public — SMS listener multipart: `sms`, `user_id`, optional `reference_id`)
- POST `/api/bkash/webhook` (legacy alias, same handler)
- POST `/api/bkash/get_bkash_sendmoney` (legacy alias, same handler)

### Monitor
- GET `/api/monitor/traffic`
- GET `/api/monitor/overview`
- GET `/api/monitor/top-endpoints`
- GET `/api/monitor/timeline`
- GET `/api/monitor/recent`
- GET `/api/monitor/snapshot`

### Common: Movieservers
- GET `/api/common/movieservers/`
- GET `/api/common/movieservers/view/{id}`
- POST `/api/common/movieservers/add`
- POST `/api/common/movieservers/update/{id}`
- GET `/api/common/movieservers/delete/{id}`

### Common: News
- GET `/api/common/news/`
- GET `/api/common/news/view/{id}`
- POST `/api/common/news/add`
- POST `/api/common/news/update/{id}`
- POST `/api/common/news/news_view_update/{id}`
- GET `/api/common/news/delete/{id}`

## Customer Portal

### User
- GET `/api/customer/users/{id}`
- GET `/api/customer/users-load-traffic/{id}`
- GET `/api/customer/payment-fetch`
- GET `/api/customer/packages`
- GET `/api/customer/ping-user`

### Subscription
- GET `/api/customer/subscription/index`
- GET `/api/customer/subscription/renew`
- POST `/api/customer/subscription/renew`
- POST `/api/customer/subscription/activate-package`
- POST `/api/customer/subscription/update`

### Support
- GET `/api/customer/support/fetch`
- GET `/api/customer/support/details`
- POST `/api/customer/support/send-message`
- POST `/api/customer/support/create-ticket`

### Profile
- POST `/api/customer/profile/update`

### Permission
- GET `/api/customer/permission`

### Payment
- GET `/api/customer/make-payment/{id}`
- GET `/api/customer/make-reseller-payment/{id}`
- GET `/api/customer/json/make-payment/{id}`
- GET `/api/customer/json/make-reseller-payment/{id}`
- GET `/api/customer/invoice-print`
- GET `/api/customer/json/invoice-print`
- GET `/api/customer/usage`
- GET `/api/customer/routers/load-traffic/{id}`

## Reseller Portal

### Dashboard
- GET `/api/reseller/dashboard/{id}`

### Area
- GET `/api/reseller/areas/{id}/sub/{subId}`
- GET `/api/reseller/areas/{id?}`
- POST `/api/reseller/areas/{id}`
- POST `/api/reseller/areas`
- POST `/api/reseller/subareas`
- GET `/api/reseller/areas/edit/{id}`
- GET `/api/reseller/subareas/edit/{id}`
- PUT `/api/reseller/areas/update/{id}`
- PUT `/api/reseller/subareas/update/{id}`
- DELETE `/api/reseller/areas/{id}/delete`
- DELETE `/api/reseller/areas/delete`
- DELETE `/api/reseller/subareas/delete`

### Package
- GET `/api/reseller/packages/{id?}`
- DELETE `/api/reseller/packages/{id}/{packageId}`

### Customer
- POST `/api/reseller/customers/create/{id?}`
- POST `/api/reseller/customers/{id}/sync-pppoe`
- POST `/api/reseller/customers/{id}/import-excel`
- POST `/api/reseller/customers/{id}/bulk-recharge`
- POST `/api/reseller/customers/{id}/transfer`
- POST `/api/reseller/customers/{id}/bulk-delete`
- DELETE `/api/reseller/customers/{id}/bulk-delete`
- GET `/api/reseller/customers/{id}/export-excel`
- GET `/api/reseller/customers/{id}/{userId}/audit-logs`
- GET `/api/reseller/customers/{id}/{userId}/mac-status`
- POST `/api/reseller/customers/{id}/{userId}/mac-bind`
- POST `/api/reseller/customers/{id}/{userId}/mac-unbind`
- GET `/api/reseller/customers/{id?}`
- GET `/api/reseller/customers/{id?}/{userId?}`
- POST `/api/reseller/customers/{id?}/{userId?}`
- DELETE `/api/reseller/customers/{id?}/{userId?}`

### Customer Payment
- GET `/api/reseller/customer-payments/{id}`
- GET `/api/reseller/customer-payments/{id}/user/{userId}`
- POST `/api/reseller/customer-payments/{id}`
- PUT `/api/reseller/customer-payments/{id}/{paymentId}`
- DELETE `/api/reseller/customer-payments/{id}`

### Employee
- GET `/api/reseller/employees/{id}`
- GET `/api/reseller/employees/{id}/{employeeId}`
- POST `/api/reseller/employees/{id}`
- PUT `/api/reseller/employees/{id}/{employeeId}`
- DELETE `/api/reseller/employees/{id}`

### Employee Payment
- GET `/api/reseller/employee-payments/{id}`
- POST `/api/reseller/employee-payments/{id}`
- PUT `/api/reseller/employee-payments/{id}/{paymentId}`
- DELETE `/api/reseller/employee-payments/{id}`

### Support Ticket
- GET `/api/reseller/support-tickets/{id}`
- GET `/api/reseller/support-tickets/{id}/{ticketId}`
- POST `/api/reseller/support-tickets/{id}`
- POST `/api/reseller/support-tickets/{id}/{ticketId}/message`
- PUT `/api/reseller/support-tickets/{id}/{ticketId}`
- DELETE `/api/reseller/support-tickets/{id}`

### Transaction
- GET `/api/reseller/transactions/{id}`
- DELETE `/api/reseller/transactions/{id}`

### Funding
- GET `/api/reseller/funding/{id}`
- POST `/api/reseller/funding/{id}`
- DELETE `/api/reseller/funding/{id}`

### Subscription
- GET `/api/reseller/subscription/{id}/{userId}`
- POST `/api/reseller/subscription/{id}/renew`
- POST `/api/reseller/subscription/{id}/bulk-renew`

### SMS
- GET `/api/reseller/sms/{id}/recipients`
- POST `/api/reseller/sms/{id}/send`
- GET `/api/reseller/sms/{id}`
- DELETE `/api/reseller/sms/{id}`

### Voice SMS
- GET `/api/reseller/voice-sms/{id}/recipients`
- POST `/api/reseller/voice-sms/{id}/send`

### Payment
- GET `/api/reseller/payments/{id}`

### Profile
- GET `/api/reseller/profile/{id}`
- PUT `/api/reseller/profile/{id}`
- PUT `/api/reseller/profile/{id}/organization`
- POST `/api/reseller/profile/{id}/change-password`

### Router
- GET `/api/reseller/routers/{id}`
- GET `/api/reseller/router-users/{id}/{routerId}`

## Pagination Standard

List endpoints in `zapi` support pagination with:

- `page` (default `1`)
- `limit` (default `10`)
- `per_page` (backward-compatible alias of `limit`)
