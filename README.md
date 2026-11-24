# App Shop

A Laravel 13 and Vue 3 account-management foundation intended as the starting point for an online shop.

> **Current scope.** This repository implements accounts only: registration, login, password recovery, and profile management. It has **no product catalog, cart, checkout, or payment code**. The name describes the original goal, not a shipped feature set.

## Why this project exists

The project was started to build an online shop on Laravel, beginning with the part every shop needs first: a secure user account system. The 2026 modernization keeps that scope and brings the foundation up to a maintained, tested, and hardened baseline so that shop features can be added on top of it.

## What it does

| Area | Behavior |
| --- | --- |
| Registration | Creates an account with a validated name, unique email, and strong password |
| Authentication | Session login with remember-me, rate limiting, and session regeneration |
| Password recovery | Emails a single-use, expiring reset link with a response that does not reveal whether an account exists |
| Profile | Update name and email, change password, delete account after password confirmation |
| API | `GET /api/user` returns the current user for a valid Sanctum token |
| Email verification | Routes and screens exist; enforcement is off until `User` implements `MustVerifyEmail` |

## Architecture

```text
app-shop/
├── backend/    Laravel 13 application: HTTP, domain, persistence, feature tests
├── frontend/   Vue 3, Inertia, Tailwind source and component tests
├── db/         Reference SQL schema for MySQL 8.4
├── docs/       Architecture and security audit
└── reports/    Test reports from real runs
```

The browser loads a Vue single-page shell. Inertia exchanges page data with Laravel controllers, so routing, authorization, and validation stay on the server and no separate public JSON API is needed for the UI. Vite builds `frontend/` into `backend/public/build`. See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Quick start

Requirements: PHP 8.3 or newer with `dom`, `mbstring`, `pdo_sqlite`, and `xml`; Composer 2; Node.js 22.12 or newer.

```bash
cd backend
composer install --no-scripts
cp .env.example .env
php artisan package:discover
php artisan key:generate
touch database/database.sqlite
php artisan migrate

cd ../frontend
npm ci --ignore-scripts
npm run build

cd ../backend
php artisan serve
```

Open <http://localhost:8000>. For hot reloading, run `npm run dev` in `frontend/` alongside `php artisan serve`.

## Usage guide

1. Select **Register** and create an account. Passwords need at least 12 characters with mixed case, letters, and numbers.
2. After registration you land on the **Dashboard**.
3. Open **Profile** from the user menu to edit details, change your password, or delete the account.
4. Use **Forgot your password?** on the login page to request a reset link. With the default `MAIL_MAILER=log`, the email is written to `backend/storage/logs/laravel.log`.

## Configuration

Settings live in `backend/.env`; [`backend/.env.example`](backend/.env.example) is the template.

| Variable | Purpose | Default |
| --- | --- | --- |
| `APP_KEY` | Encryption key, generated per environment | empty |
| `APP_DEBUG` | Detailed errors; keep `false` outside development | `false` |
| `DB_CONNECTION` | Database driver | `sqlite` |
| `SESSION_ENCRYPT` | Encrypt session payloads | `true` |
| `SESSION_SECURE_COOKIE` | Send cookies over HTTPS only; set `true` in production | `false` |
| `CORS_ALLOWED_ORIGINS` | Comma-separated cross-origin allowlist | empty |
| `SANCTUM_EXPIRATION` | API token lifetime in minutes | `1440` |
| `MAIL_MAILER` | Mail transport | `log` |

## Database

Laravel migrations in `backend/database/migrations` are the source of truth. [`db/schema.sql`](db/schema.sql) is the equivalent MySQL 8.4 schema for review or provisioning. Use one or the other on an empty database.

## Testing

```bash
cd backend && php vendor/bin/phpunit
cd frontend && npm test
```

The backend suite covers authentication, profile flows, and security controls. The frontend suite covers shared form components. Results from the latest recorded run are in [reports/tests/](reports/tests/). CI runs both suites and dependency audits through [.github/workflows/ci.yml](.github/workflows/ci.yml).

## Security notes

Controls include bcrypt password hashing, CSRF protection, a nonce-based Content Security Policy, rate limiting on credential endpoints, encrypted sessions, and a closed CORS default. Two-factor authentication is not implemented. See [SECURITY.md](SECURITY.md) and [docs/SECURITY_AUDIT.md](docs/SECURITY_AUDIT.md).

## Project history

Originally created in 2023, modernized in 2026.

## Credits and license

The application was scaffolded from the [Laravel](https://github.com/laravel/laravel) application skeleton and the [Laravel Breeze](https://github.com/laravel/breeze) Vue and Inertia starter kit, both MIT-licensed by Taylor Otwell. Most authentication controllers, pages, and components derive from those templates. The package metadata declares MIT, inherited from the skeleton.
