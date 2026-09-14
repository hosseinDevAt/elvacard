# Deployment Guide

This document collects the production requirements and operational notes for
ElvaCard. It is the companion to the N-Onyx-14 production readiness audit (see
`ARCHITECTURE_REVIEW.md`).

## Non-negotiable environment settings

Set these in `.env` on any production host:

| Variable | Production value | Why |
| --- | --- | --- |
| `APP_ENV` | `production` | Enables production frameworks, disables dev-only tooling. `DatabaseSeeder` refuses to seed development credentials when it sees `production`. |
| `APP_DEBUG` | `false` | Never expose stack traces, file paths, or secrets to visitors. |
| `APP_URL` | canonical HTTPS URL | Signing, redirects, and callback generation depend on it. |
| `SESSION_SECURE_COOKIE` | `true` | Session cookies must only travel over HTTPS. |
| `APP_KEY` | freshly generated, unique per host | Invalid key = broken encryption/signatures. Generate with `php artisan key:generate`. |
| `LOG_CHANNEL` | `stack` (or a dedicated channel) | Payment/order events are emitted through the application logger; keep a durable, rotated channel in production. |

## Application configuration

- **Online payments**: the `config/payments.php` gateway allowlist and
  `PAYMENT_GATEWAY_*` credentials must be set. An empty allowlist disables
  online payment (the UI degrades gracefully, but no money can be collected
  online). Only gateways listed there are resolvable.
- **SMS delivery**: configure a real SMS provider (`config/services.php` /
  provider registration). `LogSmsProvider` refuses to send in production and
  `DisabledSmsProvider` always throws, so leaving either enabled blocks
  registration and password reset.
- **Callbacks**: the gateway callback route is CSRF-exempt by design (it is hit
  by server-to-server provider calls). Only amounts verified server-side are
  trusted; the browser-supplied success flag is ignored.
- **Manual payments**: approve/reject writes `reviewed_by` and `reviewed_at`
  into payment metadata, and the actor id is included in the log lines, so
  every payment decision is attributable.

## Caching

Run after every deploy:

```sh
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Never run `config:cache` with a stray `DEBUG_PAGE`-style dev option enabled.

## Storage

- Symbols: `php artisan storage:link` must run once so public assets resolve.
- **Backup must cover the storage disks**, not just the database. Order
  attachments (design/card previews, payment receipts) live on `storage/app`
  and are referenced by path from the DB. A database-only restore leaves
  broken attachments and receipts.
- Receipts and uploaded images are served through controller routes that
  validate ownership, so back up the whole `storage/app` tree and restore it
  under the same relative layout.

## Database

- Back up the database **and** the storage disks on the same schedule/snapshot
  so they stay consistent.
- MySQL is the supported production driver; the test suite also runs against
  MySQL in CI (see `phpunit.xml`). Row locks (`lockForUpdate`) are active in
  MySQL and protect payment/order lifecycle races.
- Restore with the DB user that owns the schema so foreign keys and migrations
  stay intact.

## Admin accounts

Do **not** run `php artisan db:seed` / `DatabaseSeeder` in production — it is
hard-guarded and will refuse to create the `password`/`password` development
accounts. Create a single admin manually:

```sh
php artisan tinker
```

```php
$admin = App\Models\User::firstOrCreate(
    ['phone' => '09xxxxxxxxx'],
    ['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('a-strong-password')],
);
$admin->forceFill(['role' => 'admin', 'email_verified_at' => now()])->save();
```

Never grant `role = admin` to a customer account.

## Logs / observability

Payment and order lifecycle events are logged with structured context:

- gateway initiation outcome (failure reason, order id, payment id, gateway)
- gateway verification outcome (success, `provider_verification_failed`,
  `amount_mismatch`, `provider_exception`)
- manual payment approval/rejection (actor id, order id, payment id)
- order status transitions (order id, from, to, actor id)

Ship `storage/logs` (or the configured channel) to the central aggregator and
alert on `provider_exception`, `amount_mismatch`, and rejected manual payments.

## Incident runbook (payment)

1. `provider_exception` during initiation/verification → the payment is marked
   `FAILED` with `metadata.reason = provider_exception`; the callback still
   returns a controlled `{"status":"failed"}` (never a 500). Find the order,
   confirm with the provider, and re-initiate or refund.
2. `amount_mismatch` → never auto-success; investigate the discrepancy
   (provider vs local total) before any manual action.
3. Rejected manual payment → review the receipt, correct or refund.