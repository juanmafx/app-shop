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
| End-of-life stack | Laravel 10, Inertia 1, Vite 4 | Laravel 13, Inertia 3, Vite 8; PHP 8.3 minimum |
| Repository hygiene | 3,360 dependency files tracked | Untracked and ignored |
| Account enumeration | Password reset revealed whether an email was registered | Uniform response |
| Brute force | No throttle on registration, reset, password change, or confirmation | Per-client rate limits |
| Weak passwords | 8-character minimum | 12 characters, mixed case, numbers; breach check in production |
| XSS and clickjacking | No browser security headers | Nonce-based CSP, frame denial, `nosniff`, referrer and permissions policies, HSTS over HTTPS |
| Information disclosure | Public page exposed framework and PHP versions | Removed |
| Host header | Trusted-host middleware disabled | Enabled |
| CORS | Wildcard origin | Explicit allowlist, empty by default |
| Sessions | Unencrypted payloads, secure flag not enforced | Encrypted, secure cookies in production |
| API tokens | No expiry | 24-hour default |
| Hashing | bcrypt cost 10 | bcrypt cost 12; existing hashes remain valid |
| Unused surface | Duplicate Blade auth stack, sample view, hosting placeholder page | Removed |

## Reviewed with no finding

- No private keys, cloud credentials, or real `.env` files were found by pattern search of the non-dependency history.
- Database access uses Eloquent and parameter binding, with no raw SQL in application code.
- No `eval` or `v-html` usage in application code.

## Open items

| Item | Severity | Note |
| --- | --- | --- |
| Two-factor authentication | Medium | Not implemented; recommended before adding administrative roles or payments |
| Email verification | Low | Available but not enforced; enable through `MustVerifyEmail` |
| Password reset timing | Low | Synchronous mail can differ in timing; use a queued mailer in production |
| Registration enumeration | Low | The unique-email validation error is retained for usability |
| `style-src 'unsafe-inline'` | Low | Retained for Vue transitions and inline style bindings |
| Git history | Informational | Old commits still contain vulnerable dependency snapshots; removal would need a history rewrite |
