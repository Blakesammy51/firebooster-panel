# Firebooster

A responsive frontend prototype for a social media campaign workspace.

## Current state
This is a **frontend demo**, not a production SMM panel. It includes:
- Responsive dark dashboard and navigation
- Overview, campaign brief, services, campaigns/orders, and wallet screens
- Campaign briefs stored locally in the visitor's browser
- Demo labels and zero-value wallet; no real balances, payments, orders, provider API calls, or user accounts

## Preview
Open `index.html` in a browser. The project can be hosted as a static site for preview.

## Roadmap
1. Confirm the business model and audit any third-party provider or starter code.
2. Build a secure backend and database.
3. Add server-side authentication and admin roles.
4. Integrate a supported payment provider with server-side verification.
5. Add compliant provider API adapters, order tracking, logs, and support tools.
6. Test security, privacy, reliability, and deployment before accepting customers.

## Security
Never commit API keys, payment secrets, database passwords, or access tokens. Browser local storage is not secure storage and must never be used as the source of truth for balances, payments, or orders.
