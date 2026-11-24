# Security Audit

**Date:** 2026-11-23. **Scope:** application code, configuration, dependencies, and repository contents on branch `modernize/2026`. This was an engineering review with automated dependency audits, not an independent penetration test.

## Dependency results

| Ecosystem | Before | After |
| --- | --- | --- |
| Composer (`composer audit`) | 59 advisories across 16 packages | 0 |
| npm (`npm audit`) | 20 advisories (1 critical, 13 high, 6 moderate) | 0 |

The npm result relies on `overrides` for `minimatch` and `glob`, which are development-only transitive dependencies of the test tooling.

## Fixed

| Area | Finding | Resolution |
| --- | --- | --- |
| End-of-life stack | Laravel 10, Inertia 1, Vite 4 | Laravel 13, Inertia 3, Vite 8; PHP 8.4 minimum |
| Repository hygiene | 3,360 dependency files tracked | Untracked and ignored |
| Account enumeration | Password reset request and submission revealed whether an email was registered | Uniform response text on both |
| Session revocation | Password change left other sessions, remember tokens, and API tokens valid | Other sessions signed out, remember token rotated, API tokens revoked |
| Account takeover | A hijacked session could change the account email without the password | Email change requires the current password |
| Orphaned credentials | Account deletion left API token rows behind | Tokens deleted with the account |
| Brute force | No throttle on registration, reset, password change, confirmation, or account deletion, and none across accounts at login | Per-client and per-user rate limits |
| Weak passwords | 8-character minimum | 12 characters, mixed case, numbers; breach check in production |
| XSS and clickjacking | No browser security headers | Nonce-based CSP, frame denial, `nosniff`, referrer and permissions policies, HSTS over HTTPS |
| Information disclosure | Public page and `X-Powered-By` exposed framework and PHP versions | Removed |
| Host header | Trusted-host middleware disabled | Enabled |
| CORS | Wildcard origin | Explicit allowlist, empty by default |
| Sessions | Unencrypted payloads, secure flag not enforced | Encrypted; HTTPS-only cookies are enforced when `APP_ENV=production` |
| API tokens | No expiry | 24-hour default; a blank setting keeps the default |
| Hashing | bcrypt cost 10 | bcrypt cost 12; existing hashes remain valid |
| Unused surface | Duplicate Blade auth stack, sample view, hosting placeholder page | Removed |

## Reviewed with no finding

- gitleaks 8.30.1 reported no findings across the full git history. No real `.env` file or application key is present in any commit.
- Database access uses Eloquent and parameter binding, with no raw SQL in application code.
- No `eval` or `v-html` usage in application code.

## Open items

| Item | Severity | Note |
| --- | --- | --- |
| Two-factor authentication | Medium | Not implemented; recommended before adding administrative roles or payments |
| Email verification | Low | Available but not enforced; enable through `MustVerifyEmail` |
| Password reset timing | Low | Synchronous mail and reset-token hashing can differ in timing; use a queued mailer in production |
| Long passwords | Low | bcrypt uses only the first 72 bytes of a password |
| Trusted proxies | Low | Must be configured per deployment for correct client IPs and HTTPS detection |
| Registration enumeration | Low | The unique-email validation error is retained for usability |
| `style-src 'unsafe-inline'` | Low | Retained for Vue transitions and inline style bindings |
| Git history | Informational | Old commits still contain vulnerable dependency snapshots; removal would need a history rewrite |
