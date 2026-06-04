# VASMON — Business Logic Reference

VAS (Value Added Services) monitoring dashboard for telecom operators. Tracks hourly/daily/weekly transaction revenue, MO registrations, success ratios, subscriber counts, and alerts for performance drops.

---

## Core Business Rules

### Currency Conversion (Oman Rule)
Every revenue-to-USD conversion applies a two-tier rate. Oman is the only country that divides by 1000 before multiplying by the exchange rate:

```sql
CASE WHEN UPPER(countries.country) = 'OMAN'
     THEN revenue * (countries.convert_usd / 1000)
     ELSE revenue * countries.convert_usd
END
```

This rule appears in 15+ places: AlertReport, AlertRenewalReport, TransactionHourly, TransactionDaily, ChartRevenue, WeeklySummary, DashboardController, and SummaryDaily model accessor.

### Hour = 24 Convention
`hour = 24` is the end-of-day snapshot and represents the daily total. Queries for daily reports always filter `WHERE hour = 24`.

### Drop Percentage Calculation
Used in alerts to measure performance decline:

```sql
((yesterday - today) / NULLIF(yesterday, 0)) * 100
```

Default threshold: **≥ 10%** drop triggers an alert. `NULLIF` guards against division by zero.

---

## Tables & Schema

| Table | Key Columns | Purpose |
|---|---|---|
| `alerts` | `date, hour, id_operator, id_country, type, today, yesterday, drop` | Revenue / MO drop alerts |
| `transaction_hours` | `date, hour, operator, id_operator, service, id_service, revenue` | Hourly transaction revenue |
| `mo_hours` | `date, hour, id_operator, id_service, total_reg, total_unreg` | MO registration counts |
| `sr_hours` | `date, hour, operator, id_operator, service, id_service, sr` | Success ratio (%) |
| `summary_daily` | `date, hour, id_operator, id_service, id_country, mt_success, mt_failed, mo_reg, mo_unreg, revenue, sub_active, sr, click` | Daily aggregated rollup |
| `summary_weekly` | same shape as summary_daily | Weekly aggregated rollup |
| `sub_active_user_hours` | `date, hour, id_operator, id_service, total_sub` | Active subscriber counts |
| `countries` | `country, country_code, currency, convert_usd, chat_id` | Exchange rates |
| `operators` | `operator, id_country, alias, id_telco` | Operator master |
| `services` | `service, id_operator, id_country, sdc, price, type` | Service master |
| `user_has_operators` | `user_id, operator_id` | User ↔ operator access control |

---

## Alert Pages

### AlertReport (`app/Filament/Pages/AlertReport.php`)
The main alert dashboard. Groups raw alert rows and aggregates revenue with currency conversion.

**Core query:**
```sql
SELECT
  alerts.date,
  alerts.hour,
  operators.operator,
  countries.country,
  alerts.type,
  SUM(CASE WHEN alerts.type = 'REVENUE'
        THEN alerts.today * (CASE WHEN UPPER(countries.country) = 'OMAN'
                                  THEN countries.convert_usd / 1000
                                  ELSE countries.convert_usd END)
        ELSE alerts.today END) AS today,
  SUM(CASE WHEN alerts.type = 'REVENUE'
        THEN alerts.yesterday * (...)
        ELSE alerts.yesterday END) AS yesterday,
  ROUND(AVG(alerts.drop), 2) AS avg_drop
FROM alerts
JOIN operators ON alerts.id_operator = operators.id
JOIN countries ON alerts.id_country = countries.id
WHERE ... filters ...
GROUP BY alerts.date, alerts.hour, alerts.id_operator, alerts.id_country, alerts.type
HAVING avg_drop >= :min_drop
ORDER BY <user-selected column> <direction>
```

**Filters:** date range, hour range (0–23), operator, country, type, min_today, min_yesterday, min_drop (default 10%)

**Sorting:** date, hour, today, yesterday, drop — all configurable direction

**Access control:** Super-admin sees all. Other users are restricted to operators in `user_has_operators`. If a user has no operators assigned, returns no rows (`WHERE 0 = 1`).

---

### AlertRenewalReport (`app/Filament/Pages/AlertRenewalWeeklyReport.php` via `AlertRenewalReport`)
Same structure as AlertReport but with two hard restrictions:

- **Fixed hours only:** `WHERE hour IN (12, 16, 21, 24)`
- **Revenue type only:** `WHERE alerts.type = 'REVENUE'`
- Default min_yesterday threshold: **100**
- Default drop threshold: **10%**

---

### AlertRenewalWeeklyReport (`app/Filament/Pages/AlertRenewalWeeklyReport.php`)
Weekly trend view. Groups by ISO-like week number, aggregating revenue for this week vs. last week.

**Week number calculation:**
```sql
-- Monday of the week
DATE_SUB(alerts.date, INTERVAL (DAYOFWEEK(alerts.date) + 5) % 7 DAY) AS week_start

-- ISO-like week number
FLOOR(DATEDIFF(week_start, first_monday_of_year) / 7) + 1 AS week_number
```

**Fixed hours:** `IN (12, 16, 19)`

**Metrics per group:**
- `total_today` — revenue this week
- `total_yesterday` — revenue prior week
- `total_drop_revenue` — absolute difference
- `drop_pct` — percentage drop

---

## Transaction Pages

### TransactionHourly (`app/Filament/Resources/TransactionHourResource/Pages/TransactionHourly.php`)
Pivots 25 hours (0–24) into columns dynamically. One row per (date, operator, service).

**Generated SELECT fragment (repeated 25×):**
```sql
SUM(CASE WHEN hour = 0  THEN revenue * <conversion_rate> ELSE 0 END) AS hour_0_revenue,
SUM(CASE WHEN hour = 1  THEN revenue * <conversion_rate> ELSE 0 END) AS hour_1_revenue,
...
SUM(CASE WHEN hour = 24 THEN revenue * <conversion_rate> ELSE 0 END) AS hour_24_revenue
```

Oman rule applied inside each CASE expression.

---

### TransactionDaily (`app/Filament/Resources/TransactionHourResource/Pages/TransactionDaily.php`)
```sql
SELECT date, operator, service,
       SUM(revenue * <conversion_rate>) AS revenue
FROM transaction_hours
JOIN ...
WHERE hour = 24
GROUP BY date, operator, service
```

---

### TransactionMonthly (`app/Filament/Resources/TransactionHourResource/Pages/TransactionMonthly.php`)
Two-layer query. Inner query aggregates to daily; outer groups to monthly.

```sql
-- Inner
SELECT DATE_FORMAT(date, '%Y-%m') AS month_key, operator, service,
       SUM(revenue * <conversion_rate>) AS daily_revenue
FROM transaction_hours
WHERE hour = 24
GROUP BY month_key, operator, service

-- Outer
SELECT month_key, operator, service,
       SUM(daily_revenue) AS total_revenue
FROM (<inner>) m
GROUP BY month_key, operator, service
```

---

## MO (Mobile Originated) Pages

### MODaily (`app/Filament/Resources/MOHourResource/Pages/MODaily.php`)
```sql
SELECT date, operator, service,
       SUM(total_reg)   AS total_reg,
       SUM(total_unreg) AS total_unreg
FROM mo_hours
WHERE hour = 24
GROUP BY date, operator, service
```

Filament needs a primary key — uses `MD5(CONCAT(date, operator, service))` as a synthetic key.

---

### MOHourly (`app/Filament/Resources/MOHourResource/Pages/MOHourly.php`)
Same pivot pattern as TransactionHourly but for reg/unreg — generates 50 columns (25 hours × 2 metrics):

```sql
SUM(CASE WHEN hour = {i} THEN total_reg   ELSE 0 END) AS reg_{i},
SUM(CASE WHEN hour = {i} THEN total_unreg ELSE 0 END) AS unreg_{i}
```

---

## Success Ratio Pages

### SRHourly (`app/Filament/Resources/SrHourResource/Pages/SRHourly.php`)
Pivots SR averages across 25 hours. Uses `AVG` (not SUM) and NULL to exclude missing hours:

```sql
AVG(CASE WHEN hour = {i} THEN sr ELSE NULL END) AS sr_{i}
```

**Guard:** If no filter is selected, the page renders an empty table (avoids a full-table scan).

---

## Subscriber Pages

### SubActiveRenewalDaily (`app/Filament/Pages/SubActiveRenewalDaily.php`)
Tracks daily subscriber activity and MT (Mobile Termination) performance. Only reads `hour = 24` rows.

**Metrics:**
```sql
SUM(sub_active)              AS total_sub_active,
SUM(mt_success + mt_failed)  AS total_mt,
(total_mt / total_sub_active) * 100  AS avg_percentage
```

---

## Dashboard Widgets

### ChartRevenue (`app/Filament/Widgets/ChartRevenue.php`)
Line chart of hourly revenue for yesterday, broken down by country.

```sql
SELECT hour, country,
       ROUND(SUM(revenue * <conversion_rate>), 0) AS total_revenue
FROM summary_daily
JOIN countries ...
WHERE date = YESTERDAY()
GROUP BY hour, country
ORDER BY hour
```

---

### TableTopRevenue (`app/Filament/Widgets/TableTopRevenue.php`)
Top 10 services by USD revenue for yesterday's end-of-day snapshot.

```sql
SELECT service, operator, SUM(revenue * <conversion_rate>) AS revenue_usd
FROM transaction_hours
JOIN ...
WHERE hour = 23 AND date = YESTERDAY()
GROUP BY service, operator
ORDER BY revenue_usd DESC
LIMIT 10
```

Note: uses `hour = 23` (last complete hour), not `hour = 24`.

---

### TableTopRevenueOperator (`app/Filament/Widgets/TableTopRevenueOperator.php`)
Same as TableTopRevenue but grouped by operator only.

```sql
SELECT operator, SUM(revenue) AS total_revenue, MIN(id) AS id
FROM transaction_hours
WHERE hour = 23 AND date = YESTERDAY()
GROUP BY operator
ORDER BY SUM(revenue) DESC
LIMIT 10
```

`MIN(id)` is a synthetic primary key so Filament can track rows.

---

### TableTopDropSr (`app/Filament/Widgets/TableTopDropSr.php`)
Top 10 services by highest SR drop.

```sql
SELECT operator, service, click, mo_reg, sr
FROM sr_hours
WHERE hour = 23 AND date = YESTERDAY()
ORDER BY sr DESC
LIMIT 10
```

---

### CountWidget (`app/Filament/Widgets/CountWidget.php`)
Static counts of master data records (countries, operators, services, merchants). Polls every **30 seconds**.

---

## API

| Method | Endpoint | Controller | Auth |
|---|---|---|---|
| POST | `/api/success-ratio` | `SRController@store` | none |
| GET | `/api/user` | — | Sanctum |

`/api/success-ratio` is the inbound push endpoint for external systems to submit SR data.

---

## Authorization Model

**Super-admin detection** checks any of: `super_admin`, `super-admin`, `superadmin`, `super admin`.

**Operator-scoped access** (non-super-admin):
```php
// Applied in AlertReport, AlertRenewalReport, WeeklySummary
$query->whereHas('operator', function ($q) use ($user) {
    $q->whereIn('id', $user->operators->pluck('id'));
});
// If user has no operators assigned → whereRaw('0 = 1')
```

**Route-level permissions** (v2 Inertia frontend): every `/v2/*` route requires `middleware('permission:view_any_<resource>')`.

---

## Constants & Thresholds

| Constant | Value | Where |
|---|---|---|
| Oman divisor | `/ 1000` on convert_usd | All revenue-to-USD conversions |
| Daily snapshot hour | `24` | All daily queries |
| Dashboard snapshot hour | `23` | Widget queries (last complete hour) |
| Default drop threshold | `10%` | AlertReport, AlertRenewalReport |
| AlertRenewal fixed hours | `[12, 16, 21, 24]` | AlertRenewalReport |
| WeeklyRenewal fixed hours | `[12, 16, 19]` | AlertRenewalWeeklyReport |
| Min yesterday default | `100` | AlertRenewalReport |
| Widget poll interval | `30s` | CountWidget |

---

## Exports

Handled by `pxlrbt/filament-excel`. Available for:
- SummaryDaily / SummaryWeekly
- TransactionHour / MOHour / SrHour / SubActiveUserHour

Each export mirrors the filtered table query and streams to Excel.
