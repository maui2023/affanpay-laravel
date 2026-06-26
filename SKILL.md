---
name: "affanpay-laravel-gateway"
description: "Guides AffanPay payment gateway work in this Laravel repo. Invoke when modifying payment flow, webhook, status verification, order access, admin credentials, or deployment security."
---

# AffanPay Laravel Gateway

Use this skill when working on the AffanPay payment gateway integration in this Laravel project.

This skill is for AI agents that need fast, reliable context before changing:

- bill creation
- payment redirect and return flow
- webhook processing
- payment status requery
- public order access
- admin credential handling
- production hardening

## Goal

Maintain and extend the AffanPay integration without breaking:

- payment creation
- webhook updates
- return-page verification
- live status polling
- security controls added before publishing

## Project Summary

This repository is a Laravel demo/reference implementation for AffanPay.

Core capabilities already implemented:

- product listing and checkout form
- order and payment record creation
- AffanPay bill creation via API
- redirect customer to AffanPay hosted payment page
- webhook processing for asynchronous updates
- status verification through AffanPay requery/check bill API
- live payment tracking UI with auto polling
- admin page for sandbox/live credentials
- pre-publish security hardening

## Key Files

Start here before making payment-related changes:

- `app/Services/AffanPayService.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/WebhookController.php`
- `app/Http/Controllers/AdminController.php`
- `app/Models/Order.php`
- `app/Models/Payment.php`
- `app/Models/Setting.php`
- `routes/web.php`
- `bootstrap/app.php`
- `resources/views/orders/show.blade.php`
- `resources/views/admin/index.blade.php`
- `.env.example`
- `README.md`
- `tests/Feature/SecurityHardeningTest.php`

## Current Payment Flow

The intended flow is:

1. Customer opens product page.
2. Customer submits checkout form.
3. App creates `orders` and `payments` rows.
4. App calls AffanPay create bill API.
5. App stores:
   - bill reference in `payments.affanpay_transaction_id`
   - payment reference when later available
6. App redirects customer to the AffanPay URL returned by the API.
7. AffanPay may:
   - redirect customer back to the order page
   - call webhook endpoint asynchronously
8. App verifies actual payment status via AffanPay status API.
9. Order/payment status becomes:
   - `paid`
   - `processing`
   - `failed`

Important:

- return URL is not trusted as final truth
- webhook is primary asynchronous update channel
- requery/status check is used to confirm final status

## References And Meanings

Do not confuse these:

- `bill reference`
  - stored in `payments.affanpay_transaction_id`
  - usually the bill ID returned when creating a bill
- `payment reference`
  - stored in `payments.payment_reference`
  - comes from actual completed payment info
- `external_ref`
  - internal fallback identifier used to connect webhook data to local orders

Priority for verification logic:

1. payment reference
2. bill reference
3. external ref fallback only

## Public Order Access

This project is hardened so public customer order pages do not use numeric IDs.

Current rule:

- `Order` route model binding uses `public_token`
- public order URLs look like:
  - `/orders/{public_token}`
- old numeric URLs like `/orders/24` should not expose the order

Never revert this to numeric IDs without explicit user approval.

Key implementation:

- `app/Models/Order.php`
- migration adding `public_token`

## Webhook Rules

Webhook routes:

- `POST /api/v1/payments/webhook`
- `POST /webhook/affanpay`

Security design:

- webhook is excluded from CSRF because AffanPay is a third party caller
- webhook must be protected by shared secret
- accepted secret sources:
  - query param `token`
  - header `X-AffanPay-Webhook-Secret`
  - bearer token

Do not remove shared-secret verification just because CSRF is disabled.

If webhook changes are needed:

- preserve secret verification
- preserve rate limiting
- preserve status normalization logic
- avoid logging full sensitive payloads unnecessarily

## Return Page Rules

The order page is customer-facing and security-sensitive.

Rules:

- do not trust redirect query params as proof of payment
- use return query params only as a signal to trigger verification
- verify latest status with AffanPay API
- keep live status polling functional

If changing status behavior, verify:

- order page
- `/orders/{token}/status`
- manual `check status`
- retry payment flow
- webhook update flow

## AffanPay Service Rules

`app/Services/AffanPayService.php` handles:

- auth token retrieval
- create bill
- check bill status

Important constraints:

- `redirect_url` must use `route('orders.show', $order)` so public token URLs remain valid
- `callback_url` must use the webhook route alias and preserve secret token query when configured
- log only safe metadata
- do not log raw passwords, tokens, or full auth responses
- preserve timeout/connectTimeout protections

## Admin Rules

Admin routes are protected with:

- Basic Auth
- route throttling

Credential behavior:

- AffanPay passwords are stored encrypted through `Setting`
- admin UI must not show saved password values back to the browser
- blank password input means "keep current password"

Do not reintroduce plain text password display in the admin page.

## Security Baseline

This repo was hardened before publication. Keep these controls unless the user explicitly asks otherwise:

- `APP_DEBUG=false` in `.env.example`
- `LOG_LEVEL=warning` in `.env.example`
- `SESSION_ENCRYPT=true`
- `AFFANPAY_WEBHOOK_SECRET` required outside local/testing
- `ADMIN_USERNAME` and `ADMIN_PASSWORD`
- security headers middleware
- webhook and admin rate limiting
- encrypted AffanPay passwords in settings
- opaque public order token

Use OWASP-style defensive thinking for all changes:

- least privilege
- secure defaults
- avoid ID enumeration
- minimize PII exposure
- avoid secret leakage in logs or UI
- validate incoming external data

## Safe Change Checklist

Before editing payment code:

1. Read `README.md`
2. Read `AffanPayService.php`
3. Read `OrderController.php`
4. Read `WebhookController.php`
5. Read `routes/web.php`
6. Check if the change may expose:
   - numeric IDs
   - credentials
   - webhook bypass
   - debug data

After editing:

1. Run diagnostics on changed files
2. Run targeted tests if payment/security behavior changed
3. Verify webhook routes still exist
4. Verify redirect URL still uses tokenized order route
5. Verify no secret values were added to docs, logs, or blade views

## Tests To Run

At minimum, when changing payment/security behavior:

```bash
php artisan test --filter=SecurityHardeningTest
```

Useful checks:

```bash
php artisan route:list --path=webhook
php artisan route:list --path=admin
php artisan route:list --path=orders
```

If database/migrations changed:

```bash
php artisan migrate --force
```

## Common Pitfalls

Avoid these mistakes:

- using numeric order IDs again in public URLs
- building `redirect_url` with `/orders/{id}` instead of route model binding
- trusting return params without requery
- removing webhook secret verification
- logging raw AffanPay payloads with secrets or excessive PII
- showing encrypted/plain password values in admin forms
- forgetting `APP_URL` correctness, which breaks callback and redirect URLs

## Environment Variables That Matter

AI agents should pay special attention to:

- `APP_URL`
- `APP_DEBUG`
- `LOG_LEVEL`
- `SESSION_ENCRYPT`
- `AFFANPAY_WEBHOOK_SECRET`
- `ADMIN_USERNAME`
- `ADMIN_PASSWORD`

## When Extending The System

If the user asks for new AffanPay/Laravel payment features:

- preserve tokenized public order access
- preserve webhook auth and rate limiting
- prefer additive changes over breaking route changes
- update `README.md` if the flow or security model changes
- add or adjust focused feature tests when security-sensitive behavior changes

## Expected Documentation Behavior

When major payment or security changes are made, update:

- `README.md`
- `.env.example`
- relevant tests

Do not leave docs inconsistent with the actual payment flow.
