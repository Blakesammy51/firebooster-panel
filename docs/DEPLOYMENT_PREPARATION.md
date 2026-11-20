# Firebooster deployment preparation

Status: not deployed. This checklist prepares the Laravel integration branch for a later free staging deployment. It does not create a live website or provision hosting.

## What the host must provide

Before choosing a host, confirm all of the following in its current plan and documentation:

- PHP 8.2 or newer, with the extensions required by the selected Laravel 12 release.
- Composer 2 support and a way to run Artisan commands during deployment.
- A persistent MySQL-compatible database (SQLite may be used only for isolated development/testing if the app is configured and tested for it).
- A persistent writable Laravel `storage/` directory and writable `bootstrap/cache/`.
- HTTPS, private environment-variable/secret configuration, and a supported deployment method (Git integration or secure upload).
- A way to run scheduled tasks and queue workers before enabling features that depend on them.
- Backups and a documented restore process before storing real customer information.

Free plans may sleep, impose CPU/storage limits, restrict background workers, or remove inactive projects. Treat free hosting as staging/demo only unless its terms and reliability are independently verified.

## Deployment blockers

Do not point a public domain at this branch or accept real customer funds until all items below are complete:

1. Create a complete Laravel 12 application and merge the reviewed files from `backend/` into it. The current `backend/` directory contains integration source files, not a complete Laravel project.
2. Resolve the MIT-versus-GPL-3.0 licensing discrepancy in the proposed third-party starter. No code from that starter has been imported.
3. Install dependencies in a supported PHP environment; review Composer audit output and update vulnerable/outdated packages.
4. Configure a private production-like environment. Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, secure database credentials, HTTPS/session settings, and a real mail transport. Never commit `.env` or secrets.
5. Confirm the web server document root points to Laravel's `public/` directory, not the repository root.
6. Run migrations, feature tests, authorization checks, and a manual security review in staging.
7. Configure database backups, log retention/redaction, monitoring, and a tested restore procedure.
8. Complete wallet ledger, verified payment webhooks, provider API integration, idempotent fulfillment, refunds/reconciliation, and operational support before accepting deposits or paid orders.

## Safe staging sequence

1. Select a host only after checking the requirements above and its current free-tier limits.
2. Create a staging database and configure secrets using the host's private environment settings.
3. Deploy the complete Laravel app over HTTPS.
4. Run migrations and tests using the host's supported command runner.
5. Register a test customer and promote a private test account to admin through a trusted database operation.
6. Test customer isolation, service visibility, order validation, admin authorization, email verification, and error handling.
7. Keep payments and provider fulfillment disabled until their separate implementation and tests are complete.

## Moving to premium hosting and a custom domain later

Keep application configuration environment-based; do not hard-code the temporary host name. When upgrading:

- Provision the premium PHP runtime and database first.
- Deploy the same reviewed application version to the new host.
- Transfer data using a verified backup and restore, then test the restored data.
- Configure the custom domain's DNS and HTTPS certificate.
- Update trusted host, session, cookie, mail, and callback/webhook URLs as appropriate.
- Test the site on the custom domain before switching public traffic.
- Keep the old staging deployment available briefly for rollback, then remove test data and secrets securely.

## Current feature boundary

The current backend foundation supports authentication integration, admin/customer screens, service catalogue management, and manual order requests. It does not yet implement a wallet, deposits, payment gateway, refunds, provider API, or automatic fulfillment. Order requests are not paid orders.
