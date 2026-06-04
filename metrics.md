# Vasmon — Project Metrics

> Generated: 2026-06-04

---

## File Counts

| Type | Count |
|------|-------|
| PHP Files | 417 |
| TypeScript / TSX Files | 26 |
| Blade Templates | 34 |
| Migration Files | 22 |
| Test Files | 3 |
| **Total Lines of Code** | **~42,367** |

PHP: ~39,023 lines · JS/TS: ~3,344 lines

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12.0, PHP 8.2 |
| Admin UI | Filament 3.x + Filament Shield |
| Frontend | React 19 + TypeScript + Inertia.js |
| Styling | Tailwind CSS 4.0 |
| Charts | Recharts (React), ApexCharts (Filament) |
| Auth | Sanctum (API), Spatie Permission (RBAC) |
| Build | Vite 7.0 |
| Export | Maatwebsite Excel + Filament Excel |
| Database | MySQL (production), SQLite (tests) |

---

## Database: 22 Migrations

| Migration | Description |
|-----------|-------------|
| `0001_01_01_000000` | users table |
| `0001_01_01_000001` | cache table |
| `0001_01_01_000002` | jobs table |
| `2025_07_24_045225` | mo_hours |
| `2025_07_24_045231` | sr_hours |
| `2025_07_24_045238` | transaction_hours |
| `2025_07_24_045247` | countries |
| `2025_07_24_045320` | operators |
| `2025_07_24_045457` | services |
| `2025_07_24_045729` | setting_alerts |
| `2025_07_24_072305` | merchants |
| `2025_07_26_084807` | permission tables (Spatie) |
| `2025_09_12_033006` | add MT field to transaction_hours |
| `2025_09_12_033132` | sub_active_user_hours |
| `2025_09_23_044724` | personal_access_tokens |
| `2025_10_13_035632` | companies |
| `2025_10_13_040037` | add company_id to users |
| `2026_05_18_062528` | telcos |
| `2026_05_18_062531` | status_telcos |
| `2026_05_18_062534` | add id_telco to operators |
| `2026_05_18_062537` | add name_wakiad to services |
| `2026_05_18_075314` | add type to status_telcos |

---

## Models: 18

| Model | Table | Key Relationships |
|-------|-------|-------------------|
| User | users | belongsToMany(Operator) via user_has_operators |
| Operator | operators | belongsTo(Telco, Country); hasMany(Service, Alert, SummaryWeekly, SummaryDaily) |
| Service | services | belongsTo(Operator, Merchant); hasMany(SummaryWeekly, SummaryDaily) |
| Country | countries | hasMany(Operator, Alert, Service, SummaryDaily) |
| Alert | alerts | belongsTo(Operator, Service, Country) |
| Merchant | merchants | belongsTo(Country); hasMany(Service) |
| Telco | telcos | hasMany(Operator, StatusTelco) |
| StatusTelco | status_telcos | belongsTo(Telco) |
| MOHour | mo_hours | — |
| SrHour | sr_hours | belongsTo(Operator, Service) |
| TransactionHour | transaction_hours | belongsTo(Operator, Service) |
| SubActiveUserHour | sub_active_user_hours | belongsTo(Operator, Service) |
| SummaryDaily | summary_daily | belongsTo(Country, Service, Operator) |
| SummaryWeekly | summary_weekly | belongsTo(Operator, Country) |
| SummaryStatus | summary_status | belongsTo(Country, Service, Operator) |
| Company | companies | — |
| SettingAlert | setting_alerts | — |
| UserHasOperator | user_has_operators | pivot — no relationships |

---

## Filament Admin Panel

### Resources: 17

| Resource | Custom Pages (beyond List/Create/Edit) |
|----------|----------------------------------------|
| AlertResource | — |
| CompanyResource | — |
| CountryResource | — |
| MerchantResource | — |
| MoHourResource | MODaily, MOHourly |
| OperatorResource | — |
| ServiceResource | — |
| SrHourResource | SRHourDaily, SRHourly |
| StatusTelcoResource | — |
| SubActiveUserHourResource | SubActiveList |
| SummaryAirpayResource | — (with SummaryAirpayStatsWidget) |
| SummaryAirpayWeeklyResource | — (with SummaryAirpayWeeklyStatsWidget) |
| SummaryDailyResource | — |
| SummaryWeeklyResource | — |
| TelcoResource | — |
| TransactionHourResource | TransactionDaily, TransactionHourly, TransactionMonthly |
| UserResource | ViewUser |

### Custom Pages: 5

1. AlertReport *(most complex — custom GROUP BY + HAVING + multi-sort)*
2. AlertRenewalReport
3. AlertRenewalWeeklyReport
4. SubActiveRenewalDaily
5. WeeklySummary

### Widgets: 7

| Widget | Location |
|--------|----------|
| ChartRevenue | app/Filament/Widgets |
| CountWidget | app/Filament/Widgets |
| TableTopDropSr | app/Filament/Widgets |
| TableTopRevenue | app/Filament/Widgets |
| TableTopRevenueOperator | app/Filament/Widgets |
| SummaryAirpayStatsWidget | SummaryAirpayResource |
| SummaryAirpayWeeklyStatsWidget | SummaryAirpayWeeklyResource |

---

## Inertia/React Frontend (`/v2/*`)

### Pages: 21

| Page | Path |
|------|------|
| Dashboard | Dashboard.tsx |
| Alerts | Alerts/Index.tsx |
| Companies | Companies/Index.tsx |
| Countries | Countries/Index.tsx, Show.tsx |
| Merchants | Merchants/Index.tsx, Show.tsx |
| MO Hours | MoHour/Index.tsx |
| Operators | Operators/Index.tsx, Show.tsx |
| Profile | Profile/Edit.tsx |
| Roles & Permissions | RolesPermissions/Index.tsx |
| Services | Services/Index.tsx, Show.tsx |
| SR Hours | SrHour/Index.tsx |
| Sub Active User Hours | SubActiveUserHour/Index.tsx |
| Summary Daily | SummaryDaily/Index.tsx |
| Summary Weekly | SummaryWeekly/Index.tsx |
| Transaction Hours | TransactionHour/Index.tsx |
| Users | Users/Index.tsx, Show.tsx |

### Shared Components

- `Layouts/AppLayout.tsx`
- `Components/Pagination.tsx`
- `Components/FilterBar.tsx`

---

## Controllers: 26

**Web (22):** AlertController, CompanyController, CountryController, DashboardController, GetDataFromFrController, MerchantController, MoHourController, OperatorController, ProfileController, RolePermissionController, ServiceController, SettingAlertController, SrHourController, StatusTelcoController, SubActiveUserHourController, SummaryDailyController, SummaryWeeklyController, TelcoController, TransactionDailyController, TransactionHourController, UserController

**API (4):** API/MOController, API/SRController, API/SubActiveController, API/TransactionController

---

## Routes

| Scope | Count | Notes |
|-------|-------|-------|
| Web `/` | 1 | Redirect to Filament login |
| Web `/v2/*` | ~27 | All permission-guarded via `spatie/permission` |
| API `/api/*` | 2 | `POST /success-ratio`, `GET /user` (Sanctum) |

Every `/v2/` route is protected by `middleware('permission:view_any_<resource>')`. Non-super-admin users are further scoped to their assigned operators via the `user_has_operators` pivot.

---

## Artisan Commands: 7

All in `app/Console/Commands/` — all `handle()` methods are **empty stubs** (pending implementation):

1. GetDataCountry
2. GetDataMoHour
3. GetDataOperator
4. GetDataService
5. GetDataSrHour
6. GetDataSubActiveUserHour
7. GetDataTransactionHour

---

## Composer Dependencies (runtime)

| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | ^12.0 | Core framework |
| bezhansalleh/filament-shield | 3.2 | RBAC for Filament |
| inertiajs/inertia-laravel | ^2.0 | Inertia.js server adapter |
| laravel/sanctum | ^4.0 | API token auth |
| laravel/tinker | ^2.10.1 | REPL |
| leandrocfe/filament-apex-charts | ^3.2 | ApexCharts in Filament |
| maatwebsite/excel | ^3.1 | Excel export/import |
| pxlrbt/filament-excel | ^2.4 | Filament Excel export |
| tightenco/ziggy | ^2.6 | JS route helper |

---

## NPM Dependencies

**Runtime (3):** `lucide-react`, `recharts`, `ziggy-js`

**Dev (15):** `@inertiajs/react`, `@tailwindcss/forms`, `@tailwindcss/typography`, `@tailwindcss/vite`, `@types/react`, `@types/react-dom`, `@vitejs/plugin-react`, `axios`, `concurrently`, `laravel-vite-plugin`, `react`, `react-dom`, `tailwindcss`, `typescript`, `vite`

---

## Notable Facts

- **No meaningful test coverage** — only 3 scaffolded example test files exist
- **7 data-fetch commands are empty stubs** — external data ingestion not yet implemented
- **Dual UI** — Filament (`/admin/*`) and Inertia/React (`/v2/*`) coexist; plan is to drop Filament
- **Oman edge case** — `Country` has `convert_usd` rate; Oman applies an additional `/1000` divisor on revenue calculations
- **Alert aggregation** — `AlertReport::buildFilteredQuery()` is the most complex piece: custom GROUP BY + HAVING + multi-column sort for revenue/MO drop detection vs prior periods
