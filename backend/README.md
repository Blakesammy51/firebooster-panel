# Firebooster backend foundation

This folder contains the first Laravel backend implementation layer. It is intentionally separate from the static preview at the repository root.

## Runtime setup

Create a fresh Laravel 12 application in this folder using the official Laravel installer or Composer, then merge these files into the generated app. Install Laravel Breeze with Blade authentication:

```bash
composer create-project laravel/laravel backend "^12.0"
cd backend
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run build
```

Copy the supplied `routes/web.php`, middleware, controller, migration, and Blade view into the generated Laravel app. Merge the middleware alias into `bootstrap/app.php` as shown below; do not overwrite the rest of Laravel's generated bootstrap file.

In `bootstrap/app.php`, add the alias inside the existing `withMiddleware` callback:

```php
$middleware->alias([
    'admin' => \\App\\Http\\Middleware\\EnsureUserIsAdmin::class,
]);
```

Then run:

```bash
cp .env.example .env
php artisan key:generate
# Configure DB_* values in the private .env file before migrating.
php artisan migrate
php artisan serve
```

Never commit `.env`, APP_KEY, database credentials, payment secrets, or provider tokens. Use a private environment file on the host.

## Authentication and admin access

Breeze supplies the login, registration, password reset, CSRF-protected forms, and session authentication. The supplied migration adds a role field. New registrations default to the customer role. Admin routes require both authentication and the admin middleware.

Promote the first administrator only through a trusted database operation after registering that account:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-private-admin-email';
```

Do not expose public admin registration or ship a default admin password. Use a unique strong password and enable email verification before accepting public users.

## Current scope

- Session-based customer authentication is supplied by Laravel Breeze.
- Admin-only dashboard route and access middleware are included.
- Dashboard currently shows account counts, not financial balances or real orders.
- No deposits, wallet credits, SMM provider calls, or order fulfillment are implemented.
- This code has not yet been installed, executed, or security-tested in a PHP runtime. Run migrations and tests locally or on a staging host before deployment.
