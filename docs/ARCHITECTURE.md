# Architecture

## Overview

App Shop is a server-driven single-page application. Laravel owns routing, authorization, validation, and persistence. Vue renders pages, and Inertia connects the two without a separate public UI API.

```text
Browser (Vue 3 + Inertia)
      | HTML on first load, Inertia JSON on navigation
      v
Laravel HTTP kernel
      | TrustHosts, TrustProxies, CORS, SecurityHeaders, session, CSRF
      v
Routes -> Form Requests -> Controllers -> Eloquent models
                                               |
                                               v
                                     SQLite or MySQL 8.4
```

## Module structure

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/       Account and profile endpoints
│   │   │   └── Auth/          Registration, login, password, verification
│   │   ├── Middleware/        Request pipeline, including SecurityHeaders
│   │   └── Requests/          Input validation
│   ├── Models/                Eloquent models
│   └── Providers/             Framework wiring and password policy
├── config/                    Environment-driven configuration
├── database/                  Migrations, factories, seeders
├── resources/views/           Inertia root template
├── routes/                    Web, auth, API, console, channels
└── tests/                     Feature and unit tests

frontend/
├── src/
│   ├── Components/            Reusable UI controls
│   ├── Layouts/               Guest and authenticated shells
│   ├── Pages/                 One component per Inertia page
│   │   ├── Auth/
│   │   └── Profile/Partials/
│   ├── styles/                Tailwind entry point
│   └── app.js                 Client bootstrap
└── tests/                     Vitest component tests
```

## Key decisions

| Decision | Rationale |
| --- | --- |
| Separate `backend/` and `frontend/` | Independent toolchains, dependency audits, and CI jobs |
| Inertia rather than a REST UI API | One authorization and validation layer, session cookies instead of browser-stored tokens |
| Vite output in `backend/public/build` | Laravel serves one origin, which keeps the CSP and CSRF model simple |
| Migrations as schema source of truth | `db/schema.sql` is a checked reference, not a second migration path |

## Data model

- `users`: accounts with a unique email and bcrypt password hash.
- `password_reset_tokens`: one hashed reset token per email.
- `personal_access_tokens`: hashed Sanctum API tokens, polymorphic on the owner.
- `failed_jobs`: failed queue job records.

## Recommended path to a full shop

These modules are **not implemented**. They are the suggested structure for future work.

```text
backend/app/Domain/
├── Catalog/    Products, categories, inventory
├── Cart/       Cart lifecycle and pricing
├── Orders/     Checkout and order state machine
└── Payments/   Hosted provider integration, webhook verification
```

Use a hosted payment provider so card data never reaches this application, add two-factor authentication before introducing administrative roles, and move sessions, cache, and queues to Redis when running more than one instance.
