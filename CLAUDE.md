# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

**Start full dev environment** (server + queue worker + Vite hot reload):
```bash
composer dev
```

**Individual processes:**
```bash
php artisan serve
npm run dev
php artisan queue:listen
```

**Build frontend assets:**
```bash
npm run build
```

**Run all tests:**
```bash
composer test
# or
php artisan test
```

**Run a single test file or filter:**
```bash
php artisan test tests/Feature/ExampleTest.php
php artisan test --filter=ExampleTest
```

**Code style (Laravel Pint):**
```bash
./vendor/bin/pint
```

**Database:**
```bash
php artisan migrate
php artisan migrate:fresh --seed
```

**Filament Shield (regenerate permissions after adding resources):**
```bash
php artisan shield:generate --all
php artisan shield:super-admin --user=1
```

## Architecture Overview

This is a **VAS (Value Added Services) monitoring dashboard** for telecom operators, tracking transaction metrics (MO, SR, revenue), subscription data, and alerting on drops vs. prior periods.

### Dual UI Architecture

Two coexisting frontends served from the same Laravel backend:

1. **Filament Admin Panel** (`/admin/*`) — the primary UI. PHP-rendered CRUD, reporting pages, and alert monitoring. Configured in `app/Providers/Filament/AdminPanelProvider.php`. Resources are auto-discovered from `app/Filament/Resources/`.

2. **Inertia/React frontend** (`/v2/*`) — a secondary UI built with React 19 + TypeScript + Tailwind v4. Pages live in `resources/js/Pages/`, routed via `routes/web.php` under the `v2` prefix. Entry point: `resources/js/app.tsx`.

### Authorization Model

Uses `spatie/laravel-permission` + `filament-shield`:
- Every `/v2/*` route is guarded by `middleware('permission:<name>')` with names following the pattern `view_any_<resource>`.
- Non-super-admin users are restricted to their assigned operators via the `user_has_operators` pivot table (`User::operators()` many-to-many relationship).
- Permissions are passed to the Inertia frontend via `HandleInertiaRequests` middleware as `auth.permissions`.

### Core Domain Models

| Model | Table | Purpose |
|-------|-------|---------|
| `TransactionHour` | `transaction_hours` | MT success/fail/revenue by operator/service/hour |
| `MOHour` | `mo_hours` | Mobile origination registrations by hour |
| `SrHour` | `sr_hours` | Success ratio data by hour |
| `SubActiveUserHour` | `sub_active_user_hours` | Active subscriber counts |
| `Alert` | `alerts` | Revenue/MO drops vs prior period (`today`/`yesterday`/`drop` columns) |
| `SummaryDaily` / `SummaryWeekly` | — | Aggregated rollups |
| `Operator` | `operators` | Telecom operators; belongs to `Country` and `Telco` |
| `Service` | `services` | VAS services; belongs to `Operator` |
| `Country` | `countries` | Has `convert_usd` exchange rate; Oman applies an additional `/1000` divisor |

### Filament Resources vs Pages

- **Resources** (`app/Filament/Resources/`) — standard CRUD. Several have custom sub-pages registered alongside the default list/create/edit, e.g. `TransactionHourly`, `TransactionDaily`, `TransactionMonthly` inside `TransactionHourResource/Pages/`.
- **Pages** (`app/Filament/Pages/`) — custom non-CRUD report views that implement `HasTable` + `HasForms`. `AlertReport` is the most complex: `buildFilteredQuery()` contains all filtering, aggregation (GROUP BY with `HAVING`), and multi-column sorting logic for the alert dashboard.

### Artisan Data-Fetch Commands

`app/Console/Commands/GetData*.php` — stubs for pulling data from an external reporting system. All `handle()` methods are currently empty; implementations are pending.

### API Routes

`routes/api.php` exposes a minimal API:
- `POST /api/success-ratio` — receives SR data pushes via `SRController`.
- `GET /api/user` — Sanctum-authenticated user info.

### Frontend (Inertia/React)

- TypeScript strict mode; `@` alias maps to `resources/js/`
- Pages mirror `/v2/*` routes: `resources/js/Pages/{Resource}/Index.tsx`
- Shared layout: `resources/js/Layouts/AppLayout.tsx`
- Route generation via Ziggy — `window.route` is globally available
- Charts use `recharts`; icons use `lucide-react`

### Testing

Tests run against SQLite in-memory (configured in `phpunit.xml`). Only scaffolded example tests exist — no meaningful test coverage yet.

### Database

MySQL in production (`DB_DATABASE=alert`). Queue and cache use the database driver by default.
