# Firebooster — SMM base-script review

Review date: 2026-11-20

## User requirements carried forward
- Product: Firebooster, an SMM reseller/API panel.
- Preferred starting point: customize an existing SMM panel script.
- No script was supplied by the owner, so a public candidate was reviewed.
- Current constraints: no domain, no hosting, and an initial software/hosting budget of ₦0.
- Development is being done from an Android device.

## Candidate reviewed: zuramai/laravel-smm
Repository: https://github.com/zuramai/laravel-smm
License file: GPL-3.0.
The upstream README describes customer orders for social-media booster services, admin configuration, provider management, mass orders, tickets, and deposit features. It documents PHP 8.0.2+, MySQL-related extensions, Composer, npm, migrations, and scheduled cron tasks.

### Important compatibility and security findings
1. The checked-in Composer manifest targets Laravel Framework ^9.19 and PHP ^8.0.2. This is an older dependency baseline and must be upgraded or security-reviewed before public deployment.
2. The upstream README publishes a default administrator login (admin@gmail.com / admin). Never deploy with these credentials; remove or replace any seeded default administrator and require a unique strong password.
3. The example environment file contains development defaults, including APP_DEBUG=true and placeholder database credentials. Production configuration must be generated privately; never commit a real .env file, APP_KEY, database password, mail password, or provider/payment secret.
4. The source includes payment/deposit integrations and provider-specific assumptions that appear tailored to Indonesian services. These must be audited and replaced with integrations suitable for Firebooster and Nigeria before enabling deposits or fulfillment.
5. The source documents a GPL-3.0 license. If Firebooster distributes or modifies this code, preserve required copyright/license notices and comply with GPL-3.0 obligations. Obtain legal advice if the intended distribution model is unclear.
6. The source has not yet undergone a complete security audit, dependency audit, or runtime test. This review is not approval for production use.

## Recommended implementation sequence
1. Preserve the current Firebooster frontend on the default branch.
2. Import the candidate into a separate working branch only after reviewing its complete source tree and license obligations.
3. Remove demo/default credentials and insecure sample configuration; upgrade framework/dependencies and run automated tests.
4. Rebrand and adapt the customer/admin experience to Firebooster.
5. Implement provider API credentials only on the server, encrypted/secret-managed; add strict input validation, order-state reconciliation, idempotency, audit logs, and rate limits.
6. Design the wallet as a ledger with immutable transactions. Never accept a balance supplied by the browser. Enable deposits only after payment webhooks are verified server-side.
7. Confirm a suitable host and database before deploying. A GitHub repository alone does not run PHP/Laravel or provide a production database.

## Current status
Research only. The Laravel SMM candidate has not been copied into this repository, and no production backend, payment processing, provider fulfillment, or live wallet is active.