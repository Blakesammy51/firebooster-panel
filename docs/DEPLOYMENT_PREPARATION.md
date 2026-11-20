# Firebooster deployment preparation

Status: source foundation assembled on the isolated laravel-integration branch. Not deployed. A green automated build and manual staging review are still required.

## Current application layout
- Static preview: repository root; keep main unchanged.
- Laravel 12 application root: backend/.
- Web document root: backend/public/.
- Intended staging branch: laravel-integration.
- Intended free staging host candidate: EthioDeploy, subject to rechecking terms and limits.

## Before connecting hosting
1. Wait for the GitHub Actions workflow on laravel-integration to pass.
2. Review failed PHP, Composer, npm, migration, or feature-test steps; do not deploy with failing checks.
3. Recheck the free host's current terms, privacy policy, free-tier limits, and database backup/restore process.
4. Confirm the project root can be set to backend and the host serves only backend/public.
5. Scope the host's GitHub App access to this repository only. Do not share passwords or API tokens in chat.
6. Configure environment values in the host's private settings, never in GitHub files.

## EthioDeploy staging candidate
The provider's published Laravel guide describes PHP 8.2–8.5, Composer install, Vite asset builds, nginx/PHP-FPM, HTTPS, and MySQL/Postgres addons. The free plan has limited resources and sleeps after inactivity; the first request after sleep may take up to 30 seconds. Treat it as a non-critical demo only.

For staging use:
- Repository: Blakesammy51/firebooster-panel
- Branch: laravel-integration (if branch selection is available)
- Root directory: backend
- Web root: public
- Database: fresh staging MySQL or Postgres addon, not SQLite.

Set private environment variables before deployment:
- APP_NAME=Firebooster
- APP_ENV=production
- APP_DEBUG=false
- APP_URL=https://your-staging-hostname
- APP_KEY (generate in the host or a secure local environment; never paste it into chat or commit it)
- DB_CONNECTION=mysql or pgsql
- DB_URL or the host's supported DATABASE_URL
- SESSION_DRIVER=database
- CACHE_STORE=database
- QUEUE_CONNECTION=sync for free staging
- A real private mail transport before relying on email verification.

The app database config supports DB_URL or DATABASE_URL. Run php artisan migrate --force using the host's supported start/deploy command after configuring the database. Confirm the host does not expose .env, source files, or the repository root over HTTP.

Official provider references:
- https://ethiodeploy.com/deploy/laravel
- https://ethiodeploy.com/terms
- https://ethiodeploy.com/privacy
- https://ethiodeploy.com/docs/billing

No repository has been connected to EthioDeploy and no deployment has been created.

## Security and launch blockers
- No wallet, deposits, payment gateway, refunds, SMM provider API, or automated fulfillment is implemented.
- Customer submissions are order requests only; do not accept funds or represent them as paid orders.
- Before launch, add a ledger-based wallet, verified payment webhooks, provider API adapter, idempotent submission and status reconciliation, refunds, abuse controls, monitoring, backups, and a tested restore process.
- Configure production email and verify signed verification links.
- Review authorization, validation, sessions, secrets, logging, and dependencies.
- The proposed third-party starter zuramai/laravel-smm has conflicting MIT metadata and GPL-3.0 license text; no code from it has been imported. Resolve licensing before reuse.

## Moving to premium hosting and a custom domain
Provision the new PHP runtime and database, deploy the same reviewed app version, restore a verified database backup, configure DNS and HTTPS, update APP_URL and session/mail callback settings, test on the new domain, then switch public traffic. Keep staging isolated and remove test data when no longer needed.
