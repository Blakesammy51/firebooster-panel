# Firebooster Laravel integration — preparation notes

Status: preparation branch only. No upstream application code has been imported and no backend is production-ready.

## Candidate source reviewed
- Upstream: https://github.com/zuramai/laravel-smm
- Default branch reviewed: master
- Composer manifest currently specifies PHP ^8.0.2 and Laravel Framework ^9.19.
- Composer manifest declares the project license as MIT, while the repository LICENSE file contains GNU GPL version 3 text. This is a licensing inconsistency. Do not copy, modify, or distribute the upstream code until the copyright holder or qualified legal counsel clarifies the applicable terms.
- The upstream tree includes a committed `.env` file. Do not copy it or expose its contents. Treat any credentials in a publicly committed environment file as compromised; the upstream maintainer should rotate them.
- `.env.example` contains development settings such as APP_DEBUG=true and placeholder database/mail settings. It is not suitable for production.
- The upstream dependencies are an older Laravel 9-era stack. We must check current PHP/Laravel compatibility, dependency advisories, and package maintenance before deployment.

## Decision
Keep the existing Firebooster frontend on `main`. Work on this separate `laravel-integration` branch. Do not blindly import the candidate. First resolve licensing, inspect the full source and migrations, run dependency/security checks, and verify the installer and admin authentication.

## Firebooster implementation sequence
1. Confirm lawful reuse terms and select a maintained Laravel version compatible with the eventual host.
2. Import reviewed source into this branch, preserving notices and marking all modifications, only after the licensing question is resolved.
3. Configure a fresh database and private environment variables. Never commit `.env`, APP_KEY, payment credentials, provider tokens, or database passwords.
4. Replace default/demo admin credentials; enforce password hashing, validation, CSRF protection, rate limits, and role-based access.
5. Implement a server-side service catalogue and provider adapter. Keep provider secrets on the server; validate URLs and responses; log redacted request metadata.
6. Implement orders with idempotency, status reconciliation, and audit history.
7. Implement a ledger-based wallet. Do not trust browser-supplied balances. Deposits must be credited only after verified server-side payment webhooks.
8. Add Nigerian payment integration only after the business account, provider terms, webhook verification, and hosting are ready.
9. Run automated tests and a security review before any public launch.

## Hosting and cost constraint
The owner previously indicated no domain, no hosting, and a ₦0 initial budget. A GitHub repository does not run Laravel or provide a production database. We can prepare and review code in GitHub, but a live Laravel deployment needs a PHP runtime, Composer, database, HTTPS, and secure secret storage. Do not promise live transactions until those exist.

## Next required decision
Before importing the third-party code, obtain written clarification of the upstream license discrepancy (MIT in composer.json versus GPL-3.0 LICENSE).