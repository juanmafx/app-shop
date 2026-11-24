# App Shop 2.0.0

A modernization release that moves the 2023 account foundation to a supported stack and hardens it. It adds no shop features.

## Highlights

- Laravel 13, Vue 3.5, Inertia 3, and Vite 8
- Separate `backend/` and `frontend/` projects
- Zero known Composer or npm advisories at audit time
- Content Security Policy, stricter password rules, rate limiting, and account-enumeration protection
- Automated backend and frontend suites with committed reports and CI

## Upgrading from the 2023 code

1. Move your `.env` to `backend/.env` and compare it with `backend/.env.example`.
2. Install PHP 8.3+ and Node.js 22.12+.
3. Run `composer install --no-scripts` and `php artisan package:discover` in `backend/`.
4. Run `npm ci --ignore-scripts && npm run build` in `frontend/`.
5. Point the web server document root to `backend/public`.
6. Set `SESSION_SECURE_COOKIE=true` in production.

Enabling session encryption invalidates existing sessions, so users will need to sign in again. Existing password hashes keep working.

## Known limitations

No catalog, cart, or payments; no two-factor authentication; email verification is not enforced. See [docs/SECURITY_AUDIT.md](docs/SECURITY_AUDIT.md).
