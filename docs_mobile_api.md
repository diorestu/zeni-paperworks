# Mobile API (v1)

Base URL: `/api/v1`
Auth: `Bearer <token>` using Sanctum personal access token.

## Auth
- `POST /auth/login`
  - body: `{ "email": "...", "password": "...", "device_name": "ios-app" }`
- `GET /auth/me`
- `POST /auth/logout`

## Dashboard
- `GET /dashboard/summary`

## Clients
- `GET /clients?search=`
- `POST /clients`
- `PUT /clients/{id}`
- `DELETE /clients/{id}`

## Products
- `GET /products?search=`
- `POST /products`
- `PUT /products/{id}`
- `DELETE /products/{id}`

## Bank Accounts
- `GET /bank-accounts`
- `POST /bank-accounts`
- `PUT /bank-accounts/{id}`
- `DELETE /bank-accounts/{id}`

## Invoices
- `GET /invoices?search=&status=`
- `GET /invoices/{invoice_number}`
- `POST /invoices`
- `PATCH /invoices/{invoice_number}/status`

## Quotations
- `GET /quotations?search=&status=`
- `GET /quotations/{quotation_number}`
- `POST /quotations`
- `PUT /quotations/{quotation_number}`
- `POST /quotations/{quotation_number}/convert`

## Company Settings
- `GET /settings/company`
- `PUT /settings/company`

## Notes
- Routes follow existing tenant scope (`company_id`) and user role restrictions from current app logic.
- Invoice and quotation route params use existing route keys (`invoice_number` and `quotation_number`).
