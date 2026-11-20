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
- Feature tests for admin access control.

## Important limitations
- Order requests are NOT paid orders. They are stored as pending records for review only.
- No wallet, deposit, payment gateway, refunds, SMM provider API, or automatic fulfillment exists.
- Admin status changes do not charge customers or send requests to a provider.
- Do not accept customer funds until payment verification, wallet ledger, provider adapter, idempotency, reconciliation, and security testing are implemented.
- Source has not yet been executed in a PHP runtime in this workflow. GitHub Actions will run Composer, Vite, migrations, and tests; deployment must wait for a green run and manual staging review.

## Deployment
The intended staging project root is backend. The web document root must be backend/public. Read the deployment preparation checklist before connecting a host. Configure APP_ENV=production, APP_DEBUG=false, APP_URL, APP_KEY, DB_CONNECTION=mysql (or pgsql), and private database URL/credentials in host settings. The database config accepts DB_URL or DATABASE_URL.

Do not deploy from main; keep the staging branch isolated until the app has passed CI and manual review.

## Licensing checkpoint
The proposed third-party starter zuramai/laravel-smm has conflicting MIT metadata and GPL-3.0 license text. No code from that project has been imported. Resolve the license with the copyright holder or qualified counsel before reuse.
