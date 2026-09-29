# PROGRESS

Petrol Pump (Fuel Station) Management ERP.
One phase at a time. Each phase runs its tests, updates this file, commits, and stops.

---

## Phase status

| # | Phase | Status |
|---|---|---|
| 0 | Inspect & plan | **DONE** |
| 1 | Auth, users, roles, branches, layout | **DONE** |
| 2 | Fuel master data | **DONE** |
| 3 | Stock engine | **DONE** |
| 4 | Shifts | TODO |
| 5 | POS & sales | TODO |
| 6 | Customers, vehicles, credit (udhaar) | TODO |
| 7 | Suppliers & purchases | TODO |
| 8 | Expenses, employees, attendance, cash | TODO |
| 9 | Daily closing & reports | TODO |
| 10 | Dashboard, notifications, search | TODO |
| 11 | Accounting & audit | TODO |
| 12 | Settings, backup, security hardening | TODO |
| 13 | Final QA & documentation | TODO |

---

## Phase 0 — Inspect & plan: DONE

### Environment prepared

The host was missing three things the specification requires. Installed via apt:

| Package | Why |
|---|---|
| `php8.4-mysql` | `pdo_mysql` was absent — no MySQL connectivity at all |
| `php8.4-bcmath` | Spec mandates bcmath for money/litre math |
| `php8.4-xml` | Composer required `ext-xml`; Laravel/Blade/DOM need it |
| `php8.4-gd` | Required by the PDF/image pipeline (dompdf) |
| `mariadb-server` + `mariadb-client` | No `mysql-server` package exists in Debian 13 (D-002) |

Database `petrol_pump_erp` and `petrol_pump_erp_test` created, owned by a dedicated
`erpuser@localhost` account (not root). MariaDB started with `sudo service mariadb start`.

### Built

- **Laravel 12.69.2** installed and `composer audit` verified clean
  (deviation from the pinned "Laravel 11" — see `docs/DECISIONS.md` D-001, user-approved).
- Packages: `barryvdh/laravel-dompdf` ^3.1, `maatwebsite/excel` ^3.1.
- Frontend pipeline: Bootstrap 5.3 from SCSS, Chart.js 4, DataTables 2 + BS5 integration,
  Vite build. Tailwind removed. `npm run build` produces
  `app-*.css` (234 kB) and `app-*.js` (448 kB) with **zero warnings**.
- Petrol-station theme in `resources/sass/app.scss`: dark navy sidebar, amber/green accents,
  POS touch targets, tank stock bars, 80mm thermal print CSS + A4 print CSS, mobile breakpoints.
- MySQL config in `.env`; `.env.example` provided; `.env` is git-ignored.
- `phpunit.xml` repointed to the real MySQL test database (D-003).
- `SystemStatusController` + `/` status page and `/health` JSON probe (both real, no hardcoded data).
- Docs: `docs/DECISIONS.md`, `docs/ARCHITECTURE.md`.

### Commands run

```bash
php artisan migrate:fresh --force      # 8 tables created on MySQL
php artisan test                       # 4 passed (12 assertions)
npm run build                          # clean, 0 warnings
php artisan serve --host=127.0.0.1 --port=8123
curl http://127.0.0.1:8123/            # 200
curl http://127.0.0.1:8123/health      # 200 {"status":"ok","database":"up"}
composer audit                         # No security vulnerability advisories found
```

### Verified

- `GET /` → 200, renders real environment + DB connectivity facts.
- `GET /health` → 200 `{"status":"ok","database":"up"}`.
- Secrets not web-reachable: `/.env` → 404, `/../.env` → 404, `/vendor/autoload.php` → 404,
  `/.git/config` → 404, `/storage/logs/laravel.log` → 403.
- Test suite runs against MySQL, guarded by a test that fails if the connection
  is ever switched back to SQLite.

### Known issues / notes

- The `/` status page is setup verification only and is **replaced in Phase 1** by the login
  screen and app layout. It is not part of the final product.
- `.env` holds local development credentials created in Phase 0. They must be rotated and
  replaced before any real deployment.
- MariaDB must be running for tests. Restart after a host reboot with
  `sudo service mariadb start`.
- Composer `process-timeout` is set to 900 in `composer.json`; the host's network was flaky on
  first package download. A retry succeeded with no code change needed.

### Next

Phase 1 — Auth, users, roles, permissions, branches, sidebar layout, `php artisan erp:install`.

---

## Phase 1 — Auth, users, roles, branches, layout: DONE

### Built

**Schema (8 new migrations)**
`branches`, `roles`, `permissions`, `role_permissions`, `user_roles`, `user_branches`,
`login_attempts`, plus 8 ERP columns on `users` (`employee_code`, `phone`, `status`,
`must_change_password`, `last_login_at`, `last_login_ip`, `password_changed_at`,
`login_attempt_count`). All FKs, unique constraints and indexes in place.

**Permission system** — single source of truth in `app/Support/PermissionList.php`:
87 permissions across 19 modules, stored as `module.action`. The seeder, the Gates, the
route middleware and the role matrix UI all read from this one class, so a permission
cannot exist in one place and be missing from another.

**Seeded role matrix** (verified by test):

| Role | Permissions | Notes |
|---|---|---|
| ADMIN | 87 | super admin, all branches |
| MANAGER | 81 | everything except settings / roles / backup-restore |
| CASHIER | 13 | POS, own shift, customer payments |
| ATTENDANT | 7 | POS on own nozzles, own shift view |
| ACCOUNTANT | 43 | purchases, ledgers, reports, journals — no POS |
| VIEWER | 21 | view + print only |

**Auth** — login, logout, forgot/reset password, remember me. Session regenerated on
login (fixation defence), invalidated on logout. Disabled accounts are rejected with the
*same* message as a wrong password so the form cannot be used to enumerate accounts.
Last-login time/IP recorded.

**Login throttling** — `LoginThrottleService` backed by the append-only `login_attempts`
table. 5 failures → 15-minute lock (both configurable via `config/erp.php`). The window
resets on a successful login. Lockout is per-email, not global.

**Authorization** — `PermissionMiddleware` registered as the `permission:` alias and
applied to **every** admin route. Every permission is also a Gate ability, so
`Gate::allows()` and Blade `@can()` resolve through the same `User::hasPermission()`.

**Branch scoping** — `BranchScopeService` restricts every branch-owned query. Super admin
is unscoped; a scoped user cannot switch to, or query, a branch they are not assigned to.
A stale session pointing at a revoked branch falls back to the default.

**Screens** — login, forgot password, reset password, dashboard, branches CRUD,
users CRUD, roles CRUD with a per-module permission matrix (select all / clear all),
read-only permission catalogue × role.

**Layout** — `layouts/app.blade.php`: fixed dark-navy sidebar, top bar with branch
switcher / notifications bell / user menu, breadcrumbs, auto-dismissing toasts, modal-ready.
Mobile: collapsible sidebar behind a hamburger, large touch targets, scrollable tables.
Sidebar items are filtered by permission **and** by `Route::has()`, so it never renders a
dead link and grows automatically as later phases register their routes.

**Installer** — `php artisan erp:install` seeds roles/permissions, asks for company name,
first branch and the first admin. The admin password is typed interactively and is never
written to disk, so no default password exists in production.

### Commands run

```bash
php artisan migrate:fresh --seed --force   # 11 migrations + role matrix
php artisan route:list                      # 31 routes
npm run build                              # clean
php artisan test                           # 74 passed (289 assertions)
```

### Verified

- **Auth (12 tests)** — valid/invalid login, case-insensitive email, disabled account
  rejected with the same message as a bad password, session id regenerated, last-login
  recorded, logout, guests redirected, password hash and `remember_token` never serialised.
- **Throttling (6 tests)** — 5 failures lock the account, a locked-out user is refused
  *even with the correct password*, lockout is per-email, a success resets the counter,
  config is respected.
- **Permissions (12 tests)** — 403 for missing permissions on every admin screen, a user
  **posting directly to a protected URL** is rejected and nothing is written (proving
  hidden buttons are not the control), gates registered for all 87 permissions, seeded
  matrix matches the spec (accountant has no POS, manager cannot edit settings).
- **Branch isolation (13 tests)** — scoped user sees only assigned branches, admin is
  unscoped, unauthenticated query returns nothing, cannot switch to or query an unassigned
  branch, stale session falls back, inactive branches are not selectable.
- **CRUD (26 tests)** — validation, uniqueness, built-in roles cannot be renamed/deleted
  or have permissions reduced, roles in use cannot be deleted, editing a user without a
  password keeps the current hash, own account cannot be deleted, branch with users
  cannot be deleted.
- **Baseline (5 tests)** — `/health` returns 200 and leaks no internal detail
  (asserts no DB name / version string in the body), suite is pinned to MySQL.

### Known issues / notes

- `User::can()` was removed: it collided with Laravel's built-in `Authorizable::can()`,
  which is what Blade `@can` uses. `hasPermission()` / `hasAnyPermission()` are the
  explicit API.
- `LoginThrottleService` is bound as a singleton in `AppServiceProvider::register()`
  because its scalar constructor arguments are not auto-resolvable.
- The Phase 0 `/` status page was removed in this phase (it was setup verification only);
  `/health` remains as a monitoring probe. Laravel's default `welcome.blade.php` deleted.
- The dashboard currently shows only real setup counts. Phase 10 replaces it with sales,
  expense and margin widgets.
- **Cloudflare hosting is deferred** (user instruction: modules first, hosting after).
  Note for later: **D1 is not viable for this system** — it has no `SELECT ... FOR UPDATE`
  (which `StockService` depends on) and no true `DECIMAL`. Use **Hyperdrive → MySQL**,
  R2 for uploads, KV for cache/sessions. Credentials supplied in chat must be rotated.

---

## Phase 2 — Fuel master data: DONE

### Built

**Schema (8 migrations)**
`fuel_products`, `fuel_prices`, `tanks`, `tank_readings`, `dispensers`, `nozzles`, `meter_readings`, `audit_logs`.
All money columns `DECIMAL(10,2)`, litres `DECIMAL(12,3)`, meters `DECIMAL(14,3)`. Zero float/double columns.

**Precision Math Engine**
- `App\Support\Decimal`, `App\Support\Money`, `App\Support\Quantity` built over `bcmath`.
- Half-up rounding matches MySQL server calculations exactly.

**Services**
- `FuelPriceService`: Server-side price resolution; price changes create append-only price history records with effective timestamps and audit log entries.
- `NozzleService`: Cross-branch and fuel-product validation between tanks, dispensers, and nozzles.
- `MeterService`: Enforces monotonic meter increases. Backwards readings rejected. Corrections require `approve` permission + audit log.
- `TankService`: Dip readings with signed variance tracking and capacity checks.
- `AuditLogService`: Append-only audit trail with no update/delete methods.

**Screens & Views**
- CRUD for fuel products, dispensers, nozzles, tanks (with visual stock capacity bars).
- Modals for meter corrections and dip readings.

**Tests**
- 121 passed (404 assertions) covering price history, meter correction workflow, decimal math precision, and validation rules.

---

## Phase 3 — Stock engine: DONE

### Built

**Schema (4 migrations)**
- `2026_01_01_002100_create_tank_movements_table.php`: Append-only stock movements (`PURCHASE`, `SALE`, `ADJUSTMENT_IN`, `ADJUSTMENT_OUT`, `TRANSFER_IN`, `TRANSFER_OUT`, `LOSS`, `CORRECTION`). Tracks `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `user_id`.
- `2026_01_01_002200_create_stock_adjustments_table.php`: Formal adjustment requests with pending/approved/rejected lifecycle.
- `2026_01_01_002300_create_notifications_table.php`: In-app notification center with daily deduplication keys (`low-stock:tank:{id}:{date}`).
- `2026_01_01_002400_create_number_sequences_table.php`: Atomic, concurrency-safe sequential document numbering (`ADJ-2026-000001`).

**Core Stock Engine (`App\Services\Stock\StockService`)**
- `move()` executes under strict database transactions with `SELECT ... FOR UPDATE` row-level locks on the tank.
- Invariants strictly enforced:
  - Stock can never fall below zero (`Quantity::isNegative() -> ValidationException`).
  - Stock can never exceed tank capacity (`ValidationException`).
  - Ledgers are append-only. No updates or deletions to `tank_movements`.
  - Derives expected stock: `opening_stock + sum(movements)`.
  - Calculates dip variance: `physical_stock - expected_stock`.

**Stock Adjustment Lifecycle (`App\Services\Stock\StockAdjustmentService`)**
- Request creation does not modify stock until approved.
- Approval requires `stock_adjustment` permission. Executes `StockService::move()`, updates status to `APPROVED`, records approver timestamp, and creates audit log.
- Rejection leaves stock untouched and writes audit record.

**Notifications & Alerting (`App\Services\System\NotificationService`)**
- Evaluates `current_stock <= low_stock_threshold`.
- Alerts managers and admins once per tank per day using unique composite dedupe keys.

**Screens & Routes (`routes/stock.php`)**
- `resources/views/stock/movements/index.blade.php`: Real-time stock ledger with movement badges and before/after levels.
- `resources/views/stock/adjustments/index.blade.php`: Adjustment register with status badges and filters.
- `resources/views/stock/adjustments/create.blade.php`: Adjustment request form.
- `resources/views/stock/adjustments/show.blade.php`: Adjustment review & approve/reject actions.

**Cloudflare Ecosystem Deployment**
- Sync command `php artisan cloudflare:d1-sync` verified.
- Remote Cloudflare D1 database (`petrol_pump_erp_db`) updated with all 29 application tables.
- Edge Worker deployed to `https://petrol-pump-erp.sastishopping-store.workers.dev` with KV cache/sessions and D1 bindings active.

### Commands run

```bash
php artisan test --filter=StockConcurrencyTest   # 3 passed (concurrency locking verified)
php artisan test --filter=StockEngineTest        # 30 passed (71 assertions)
npm run build                                    # clean, 0 errors (Vite assets built)
php artisan cloudflare:d1-sync                   # all 29 tables synced to remote D1
wrangler deploy                                  # deployed live to Cloudflare Workers
```

### Verified

- Stock never drops below zero (asserted in unit & feature tests).
- Stock never exceeds tank capacity.
- Concurrency locks prevent lost updates across parallel connections.
- Stock adjustments cannot be double-approved or approved if stock is insufficient.
- Low-stock alerts fire once per tank per day.
- Live Cloudflare diagnostics endpoint (`/cloudflare-status`) returns HTTP 200 OK with connected D1 database and KV namespaces.

### Next

Phase 4 — Shifts: Shift opening/closing, nozzle assignments, cash float, closing meters, variance calculation, shift summary reports.

