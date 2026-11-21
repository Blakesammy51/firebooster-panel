# Firebooster Laravel backend

This folder is a complete Laravel 12 application root, separate from the static preview at the repository root. It includes customer registration/login, email verification, admin access control, a service catalogue, and manual order requests.

## Requirements
- PHP 8.2+
- Composer 2
- Node.js 20+ and npm
- SQLite for local development, or MySQL/Postgres for staging

## Local setup
From the repository root:
    cd backend
    composer install
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite
    php artisan migrate
    npm install
    npm run build
    php artisan test
    php artisan serve

Open the local URL printed by Artisan. Never commit .env, APP_KEY, database credentials, payment secrets, or provider tokens.

## First admin account
1. Register a normal account through the app.
2. Configure a real mail transport before using email verification outside local testing.
3. Verify the account email.
4. Promote only your own trusted account to admin using a private database console:

    UPDATE users SET role = 'admin' WHERE email = 'your-private-admin-email';

There is no public admin registration and no default admin password. All new users are customer by default.

## Included features
- Laravel 12 app structure and Vite/Tailwind build.
- Session-based registration and login, CSRF protection, login throttling, and signed email verification.
- Admin dashboard with user, service, and order-request counts.
- Searchable, read-only customer account directory.
- Admin service catalogue: create, edit, publish, and unpublish services.
- Customer service catalogue showing only active services.
- Customer order-request form and private order history.
- Server-side price calculation; browser-submitted prices are ignored.
- Admin order queue with manually controlled request statuses.
- SMM Center supplier service import, guarded order submission, supplier status refresh, and wallet refund controls.
- Scheduled SMM Center status synchronization: open supplier-backed orders are checked every five minutes in batches of up to 100; terminal orders are excluded and concurrent admin terminal changes are preserved.
- Feature tests for admin access control and registration defaults.

## Automatic supplier status updates

The Laravel scheduler registers `smm-center:sync-order-statuses` every five minutes. It only checks supplier-backed orders with confirmed provider order IDs that are still locally pending or processing. It uses batch status requests, leaves records unchanged when the supplier omits an order, and does not overwrite a terminal status changed by an administrator while the API request is running.

To activate the schedule on a host that supports cron, configure this cron entry (replace the path with the deployed backend directory):

    * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1

The scheduler does not run merely because the web application is deployed. Confirm that the hosting plan supports scheduled cron tasks before enabling this feature. The command can also be run manually with:

    php artisan smm-center:sync-order-statuses

## CI verification
GitHub Actions passed on commit 47f7702ee9ab6201471045c890c1b7ee4506f598. Composer validation/install, frontend asset build, SQLite migrations, and all 5 tests (10 assertions) completed successfully. This is a clean automated baseline, not a full production security audit or deployment.

## Important limitations
- Order requests are NOT paid orders. They are stored as pending records for review only.
- Supplier status polling is automated only when the host scheduler is configured; provider order submission remains a separate admin action.
- Provider status updates do not themselves issue customer wallet refunds; refunds remain an explicit admin action with payment and supplier-state checks.
- Do not accept customer funds until payment verification, wallet ledger, provider adapter, idempotency, reconciliation, and security testing are implemented.

## Deployment
The intended staging project root is backend. The web document root must be backend/public. Read the deployment preparation checklist before connecting a host. Configure APP_ENV=production, APP_DEBUG=false, APP_URL, APP_KEY, DB_CONNECTION=mysql (or pgsql), and private database URL/credentials in host settings. The database config accepts DB_URL or DATABASE_URL.

Do not deploy from main; keep the staging branch isolated until manual staging review is complete.

## Licensing checkpoint
The proposed third-party starter zuramai/laravel-smm has conflicting MIT metadata and GPL-3.0 license text. No code from that project has been imported. Resolve the license with the copyright holder or qualified counsel before reuse.
