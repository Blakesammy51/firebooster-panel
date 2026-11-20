# Firebooster backend foundation

This folder contains Laravel backend source to merge into a fresh Laravel 12 application. It is separate from the static preview at the repository root.

## Create the Laravel runtime

Run these commands in a PHP + Composer environment:

```bash
composer create-project laravel/laravel backend "^12.0"
cd backend
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run build
```

Copy the backend source files in this folder into the matching paths of the generated Laravel application. The supplied `routes/web.php` replaces the generated web routes; keep Breeze's generated `routes/auth.php`.

In `bootstrap/app.php`, merge this alias into the existing `withMiddleware` callback (do not overwrite the rest of the file):

```php
$middleware->alias([
    'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
]);
```

Then configure a private database in `.env` and run:

```bash
php artisan key:generate
php artisan migrate
php artisan test
php artisan serve
```

Never commit `.env`, APP_KEY, database credentials, payment secrets, or provider tokens. Use private environment variables on the host.

## Authentication and roles

Laravel Breeze supplies session login, registration, password reset, and CSRF-protected forms. The migration adds a `role` field; new users default to `customer`. Admin routes require authentication, email verification, and the admin middleware.

After registering your own account in a private test environment, promote it to admin through a trusted database operation:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-private-admin-email';
```

Never expose public admin registration or ship a default admin password. Before accepting public users, configure email delivery and confirm the User model enforces email verification.

## Included features

- Customer authentication foundation using Laravel Breeze.
- Admin-only dashboard with user, service, and order-request counts.
- Searchable, read-only customer account directory with role and email-verification status.
- Admin service catalogue: create, edit, publish, and unpublish services.
- Customer service catalogue showing only active services.
- Customer order-request form and private order history.
- Server-side price calculation from the service record; client-supplied prices are ignored.
- Admin order queue with manually controlled request statuses.
- Basic feature tests for admin access control.

## Important limitations

- Order requests are **not paid orders**. They are stored as pending records for review only.
- No wallet, deposit, payment gateway, refunds, SMM provider API, or automatic fulfillment exists.
- Admin status changes do not charge customers or send requests to a provider.
- Do not accept customer funds or advertise fulfillment until payment verification, wallet ledger, provider adapter, reconciliation, and security testing are implemented.
- The Laravel app has not been installed or executed in a PHP runtime in this repository workflow. Run migrations, tests, and a full security review in a staging environment before launch.

## Licensing checkpoint

The proposed third-party starter at `zuramai/laravel-smm` has conflicting MIT metadata and GPL-3.0 license text. No code from that project has been imported. Resolve the license with the copyright holder or qualified counsel before reuse.
