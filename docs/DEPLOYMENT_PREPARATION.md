# Firebooster deployment preparation

Status: Laravel 12 application assembled on the isolated laravel-integration branch. GitHub Actions passed on commit 47f7702ee9ab6201471045c890c1b7ee4506f598 (Composer validation/install, Vite build, SQLite migrations, 5 tests / 10 assertions). Not deployed. Manual staging review is still required.

## Current application layout
- Static preview: repository root; keep main unchanged.
- Laravel 12 application root: backend/.
- Web document root: backend/public/.
- Intended staging branch: laravel-integration.
- Intended free staging host candidate: EthioDeploy, subject to rechecking terms and limits.

## Before connecting hosting
1. Recheck the free host's current terms, privacy policy, free-tier limits, and database backup/restore process.
2. EthioDeploy Free has no branch-preview deployments. Do not create the Firebooster project from the default main branch, because main is the static preview and not the Laravel app.
3. At project creation, select laravel-integration as the project's production/deploy branch if the dashboard offers that choice. If it does not, stop before creating/deploying and confirm the supported way to choose a production branch. Do not merge this branch into main just to work around a missing selector.
4. Set the project root directory to backend. Confirm the host serves backend/public.
5. Scope the host's GitHub App access to this repository only. Do not share passwords or API tokens in chat.
6. Configure environment values in the host's private settings, never in GitHub files.

## EthioDeploy staging candidate

The provider's published Laravel guide describes PHP 8.2–8.5, Composer install, Vite asset builds, nginx/PHP-FPM, HTTPS, and MySQL/Postgres addons. The free plan includes one project and a 512 MB database addon, but has limited compute, sleeps after inactivity, and provides no branch previews. Treat it as a non-critical demo only.

For staging use:
- Repository: Blakesammy51/firebooster-panel
- Branch: laravel-integration (must be selected as the project's deploy/production branch at creation)
- Root directory: backend
- Web root: public
- Database: fresh staging MySQL or Postgres addon, not SQLite.

Set private environment variables before relying on the app:
- APP_NAME=Firebooster
- APP_ENV=production
- APP_DEBUG=false
- APP_URL=https://your-staging-hostname
- APP_KEY (EthioDeploy can generate and persist one on first deploy; never paste it into chat or commit it)
- DB_CONNECTION=mysql or pgsql
- DB_URL or the host's supported DATABASE_URL
- SESSION_DRIVER=database
- CACHE_STORE=database
- QUEUE_CONNECTION=sync for free staging
- MAIL_MAILER=log until a real private mail transport is configured.

The app database config supports DB_URL or DATABASE_URL. Set the start command to run migrations before the platform startup command, following the provider's Laravel guide: php artisan migrate --force && ethiodeploy-start. Do not set any paid-provider credentials, payment secrets, or real customer data in this staging project.

Confirm the host does not expose .env, source files, or the repository root over HTTP. If the dashboard does not allow selecting laravel-integration as the production branch on the Free plan, do not proceed; ask for a screenshot so the safe branch strategy can be confirmed.

Official provider references:
- Laravel deployment guide: https://ethiodeploy.com/deploy/laravel
- Getting started: https://ethiodeploy.com/docs/getting-started
- Free plan / branch preview limits: https://ethiodeploy.com/docs/billing
- CLI branch selection: https://ethiodeploy.com/docs/cli
- Terms: https://ethiodeploy.com/terms
- Privacy: https://ethiodeploy.com/privacy

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
