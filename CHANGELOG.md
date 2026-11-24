# Changelog

This project follows [Semantic Versioning](https://semver.org/).

## [2.0.0] - Unreleased

### Changed

- **Breaking:** separated the code into `backend/` and `frontend/`.
- **Breaking:** upgraded to Laravel 13, PHP 8.4+, Inertia 3, Vue 3.5, Vite 8, and Node.js 22.12+.
- **Breaking:** new passwords require 12 characters with mixed case and numbers.
- **Breaking:** changing an email address requires the current password.
- Password changes sign out other sessions, rotate the remember token, and revoke API tokens. Password resets do the same.
- Session payloads are encrypted, and API tokens expire after 24 hours by default.
- Bcrypt cost raised to 12.

### Added

- Security headers middleware with a nonce-based Content Security Policy.
- Rate limits on credential endpoints.
- Security regression tests, Vitest component tests, committed test reports, and a CI workflow.
- `db/schema.sql`, architecture, security, and release documentation.

### Fixed

- Password reset request and submission no longer reveal whether an account exists through their response text.
- Session cookies stay HTTPS-only in production even if the environment file disables them.
- A blank API token lifetime no longer disables token expiry.
- Login has a per-client limit across accounts, and profile credential actions are rate limited.
- Non-string password confirmation input no longer causes a server error.
- Public page and response headers no longer disclose runtime versions.
- All Composer and npm advisories reported at the time of the audit.

### Removed

- Tracked `node_modules`, the unused Blade auth stack, the sample view, the hosting placeholder page, and the placeholder unit test.

## Pre-2.0 - 2023

Initial Laravel and Breeze scaffold with login. No version was tagged.
