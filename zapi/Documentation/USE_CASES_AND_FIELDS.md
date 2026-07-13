# Use Cases and Fields

This file captures the practical request patterns for major use-cases.

For complete machine-readable request/response schema, use:

- `zapi/swagger-ui/swagger.json`
- `/api/docs`

## Customer portal examples

### 1) Fetch customer payment history

- Endpoint: `GET /api/customer/payment-fetch`
- Query fields:
  - `user_id` (required)

### 2) Update customer profile

- Endpoint: `POST /api/customer/profile/update`
- Typical fields:
  - `name`, `mobile`, `email`, `address`
  - `user_id` (depends on legacy flow)

### 3) Create support ticket

- Endpoint: `POST /api/customer/support/create-ticket`
- Typical fields:
  - `user_id`
  - `subject`
  - `category`
  - `priority`
  - `message`

### 4) Renew customer subscription

- Endpoint: `POST /api/customer/subscription/update`
- Typical fields:
  - `user_id`
  - `package_id`
  - renewal/expiry date fields (legacy rules)

## Reseller portal examples

### 1) Create customer

- Endpoint: `POST /api/reseller/customers/create/{resellerId?}`
- Typical fields:
  - customer identity fields (`name`, `mobile`, etc.)
  - package/router/area assignment fields

### 2) Create customer payment

- Endpoint: `POST /api/reseller/customer-payments/{resellerId}`
- Typical fields:
  - `customer` or `customer_id`
  - `amount`
  - `month`
  - `status`
  - `paid_via` (required when status is successful in many flows)

### 3) Create employee payment

- Endpoint: `POST /api/reseller/employee-payments/{resellerId}`
- Typical fields:
  - `employee` or `employee_id`
  - `amount`
  - `month`
  - `status`
  - `paid_via` (required for successful status)

### 4) Send SMS

- Endpoint: `POST /api/reseller/sms/{resellerId}/send`
- Typical fields:
  - message body
  - selected recipient ids/filters

## Common portal examples

### 1) List movie servers

- Endpoint: `GET /api/common/movieservers/`
- Query fields:
  - `user_id` (required)

### 2) Add movie server

- Endpoint: `POST /api/common/movieservers/add`
- Content type:
  - `multipart/form-data`
- Typical fields:
  - `admin_id` (required)
  - `name`
  - `url`
  - `details`
  - `rating`
  - `image` (file)

### 3) List news

- Endpoint: `GET /api/common/news/`
- Query fields:
  - `user_id` (required)

## Notes

- Field-level required/optional can vary by legacy logic and role conditions.
- When in doubt, use `/api/docs` first, then validate with real request examples.
