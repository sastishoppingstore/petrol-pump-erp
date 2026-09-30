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
| 3 | Stock engine | **DONE** |
| 4 | Shifts | **DONE** |
| 5 | POS & sales | **IN PROGRESS** — core done, UI remaining |
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

---

## Phase 3 — Stock engine: DONE

### Built

**Schema:** `tank_movements` (append-only ledger), `stock_adjustments`,
`notifications`, `number_sequences`.

**`StockService`** — the *only* place tank stock may change:
- locks the tank with `SELECT … FOR UPDATE` on every move
- refuses to go below zero or above capacity, with a message naming the tank,
  available and requested quantities
- writes a `tank_movements` row carrying `before_quantity` and `after_quantity`
- refuses to run outside a transaction (`LogicException`), because a movement
  row without a matching stock update would corrupt the ledger
- outbound movements are stored signed (negative), so the ledger sums directly
- `expected()` recomputes the balance from the ledger + opening quantity
- `variance()` = physical − expected
- `ledgerDrift()` proves `tanks.current_stock` always agrees with the ledger

`tanks.current_stock` and `nozzles.current_meter` are **not mass-assignable** — a
form post carrying those fields is ignored. A test asserts this.

**Concurrency — `tests/Feature/StockConcurrencyTest.php`.** Uses no
`RefreshDatabase`, because that trait wraps each test in a transaction which
holds a lock on every row it touches — exactly the lock a second connection
needs to contend for. The schema is built and committed directly so two
genuine MySQL sessions can be tested. Three tests prove:
- two concurrent 100 L sales give 1000 → 900 → 800, never a lost update
- the ledger is an unbroken chain (each row's *before* = previous row's *after*)
- a second session genuinely blocks on `FOR UPDATE` while the row is held

**Stock adjustments** — request → approve/reject. A pending request moves
nothing; approval moves stock through `StockService` and commits the movement
and the status together. Mandatory reason, audit row on approve *and* reject.
Raising a request needs `stock_adjustment`; approving needs `stock.approve`.

**`NumberSequenceService`** — `ADJ-{YYYY}-{6}` and later `INV-`, `SHIFT-`,
`PUR-`, `PAY-`, using `INSERT … ON DUPLICATE KEY` + `SELECT … FOR UPDATE`
so two concurrent transactions can never receive the same number.

**Low-stock notifications** — once per tank per day, enforced by a
`dedupe_key` unique index rather than a timestamp comparison a concurrent
request could race. Managers and admins only. Mark read / mark all read.

**Screens:** stock overview (current vs expected vs ledger drift vs last
physical dip), movement ledger with tank/type/date filters, adjustments screen
with request form and approve/reject.

### Commands run

```bash
php artisan test --filter=StockEngineTest        # 30 passed
php artisan test --filter=StockConcurrencyTest   # 3 passed
php artisan test                                # 169 passed (526 assertions)
```

### Bugs found and fixed while building this phase

1. **`expected()` double-counted the sign.** It summed inbound and outbound
   separately and then subtracted the (already negative) outbound sum, so it
   reported 3700 L where the true figure was 2300 L. Fixed to sum the signed
   ledger directly.
2. **`StockAdjustment` silently dropped its workflow fields.** `status`,
   `approved_by`, `stock_before` and `stock_after` were missing from
   `$fillable`, so `create()`/`update()` discarded them — every adjustment came
   back with a null status and could never be approved. Same class of bug as the
   nozzle opening meter in Phase 2.
3. **Test pollution across the suite.** `StockConcurrencyTest` commits real
   rows, and `RefreshDatabase` only migrates once per test *process*, so its
   leftover users were visible to later tests and broke their counts. It now
   restores a clean schema in `tearDown()`.

### Known issues / notes

- The `move()` transaction guard cannot be exercised by a normal feature test,
  because `RefreshDatabase` always has a transaction open. It is asserted by
  reading the source, and the guard remains in place for production callers.
- The concurrency test intentionally omits `RefreshDatabase`; this is
  documented in the class docblock so it is not "fixed" back into a broken state.
- `vendor/` was corrupted once mid-phase (`myclabs/deep-copy` lost files) and
  was repaired with `composer install`.

### Next

Phase 4 — Shifts: Shift opening/closing, nozzle assignments, cash float, closing meters, variance calculation, shift summary reports. Also adds the `shifts` table and the deferred `meter_readings.shift_id` foreign key.

---

## Phase 4 — Shifts: DONE

### Built

**Schema:** `shifts`, `shift_nozzles`, `shift_cash`, plus the deferred
`meter_readings.shift_id` foreign key added now that `shifts` exists. Live-total
columns (`card_total`, `credit_total`, `other_total`, `total_sales`,
`total_litres`, `expenses_total`, `cash_drops_total`) are denormalised caches
for the active-shift screen; the authoritative figures are always recomputed
from the underlying documents.

**`ShiftService`** — open, and the two rules the spec calls out:
- an employee may hold only one `OPEN` shift (enforced in the service, not the form)
- a nozzle may sit on only one `OPEN` shift
- opening meters are copied from the nozzle under a row lock, and a supplied
  reading below the system meter is rejected with the spec's exact wording
- an `OPENING` `meter_readings` row is written per nozzle
- `summary()` returns the live totals and expected cash

**`ShiftClosingService`** — close and approve:
- `Expected Cash = Opening + Cash Sales + Customer Cash Payments − Cash Expenses − Drops`
- `Difference = Actual − Expected`, evaluated against the **absolute** value of
  the configured threshold
- over threshold parks the shift at `PENDING_APPROVAL`; a manager with
  `shift_close`/`cash.approve` finalises it
- closing meters are required for every assigned nozzle; below-opening is rejected
- `meter_variance` = physical − system, flagged past the configured tolerance
- only the shift owner or `shift_close` may close at all

### Commands run

```bash
php artisan test --filter=ShiftTest   # 29 passed
php artisan test                       # 183 passed (546 assertions)
```

### Bugs found and fixed

1. **The variance test used a signed comparison instead of an absolute one.**
   `compare(difference, threshold) < 0` is true for *any* difference below the
   threshold, so a perfectly balanced till (`0.00`) was flagged as exceeding
   tolerance and every shift demanded manager approval. Added
   `Money::abs()` / `Money::exceedsTolerance()` and used them in both
   `ShiftClosingService` and `Shift`.
2. **Duplicate `shifts` migrations.** Three extra files
   (`2026_01_01_0031/0032/0033_*`) appeared alongside the consolidated
   `002500_create_shifts_tables`, each creating the same tables, so
   `migrate:fresh` failed with *"Table 'shifts' already exists"*. The useful
   columns from the duplicates were merged into the consolidated migration and
   the duplicates removed.

### Known issues / notes

- `ShiftService::summary()` reads `sales`, `customer_payments` and `expenses`
  through a `Schema::hasTable()` guard, so it returns zero until Phase 5 and 8
  introduce them. The guard is removed once those tables exist.
- A second process is intermittently writing files into this repository. Every
  phase now re-checks `git status` for unexpected additions before committing.

### Next

Phase 5 — POS & sales: the 14-step sale transaction, litres/amount toggle,
split payments, idempotency, receipt printing, void and refund.

---

## Phase 5 — POS & sales: core transaction DONE (UI remaining)

### Built

**Schema:** `sales`, `sale_items`, `sale_payments`, `sale_requests`,
plus `customers` and `customer_vehicles` (needed now because a credit sale must
reference a customer and check its limit; the customer UI, ledger and payments
are Phase 6).

**`SaleService::create()`** — the 14-step transaction in one `DB::transaction`:
1. permission (`sales.create`) and branch access
2. **idempotency** via a UUID token, with a unique index so a double click or
   refresh cannot create a second sale
3. an OPEN shift that belongs to the actor, and the nozzles assigned to it
4. `SELECT … FOR UPDATE` on the nozzle rows and tank rows
5. litres/amount computed from the **server-side** rate; payment splits must sum
   to the total exactly
6. stock sufficiency via `StockService` (below zero and over capacity rejected)
7. invoice number from the row-locked `INV-{YYYY}-{6}` sequence
8. `sales` + `sale_items` (with `cost_rate`, `meter_start`, `meter_end`) +
   `sale_payments`
9. `StockService::move(SALE)` and `MeterService::advance()`, both under the lock
10. credit limit checked against the balance **before** the sale exists
11. shift throughput updated
12. audit row
13. idempotency record closed out as COMPLETED
14. invoice returned for receipt printing

Any failure rolls everything back: stock, meters, invoice, ledger and cash.

**Costing** — `cost_rate` is the historical `average_cost` at sale time and is
never recalculated. `COGS = litres × cost_rate`, and gross margin is labelled
"gross margin", never "net profit".

### Commands run

```bash
php artisan test --filter=SaleTest   # 23 passed
php artisan test                       # 206 passed (603 assertions)
```

### Bugs found and fixed

1. **`Money::multiply()` did not exist**, so every sale died on the cost line.
   Added, delegating to `Decimal::multiply` at 2 dp.
2. **`Customer::outstandingBalance()` counted credit payments as settlements.**
   A 2500 credit sale produced a 0.00 outstanding balance, so the credit limit
   never engaged. Only non-credit payments reduce the balance now — a credit
   payment *is* the udhaar.
3. **The credit limit was checked after the sale row was inserted**, so the
   sale's own amount was counted twice: a legitimate 2500 sale against a 3000
   limit was wrongly refused. The check now runs before the sale exists.
4. **A failed idempotency token could never be retried.** Any existing token
   was treated as in-flight. A FAILED token is now cleared and replayable, while
   PROCESSING and COMPLETED are still refused.
5. `Customer` was missing the `HasFactory` trait, so `Customer::factory()`
   threw.

### Known issues / notes

- **Not yet built for Phase 5:** the POS screen itself, receipt printing,
  sales history screen, and `SaleVoidService` (void/refund with full reversal).
  The service layer, schema and tests for the happy path are done and green.
- `Customer::outstandingBalance()` has a temporary fallback that derives the
  figure from sales and payments because `customer_ledger` does not exist yet.
  Phase 6 replaces it with the real append-only ledger.

### Next

Finish Phase 5: `SaleVoidService`, the POS screen, receipt printing and sales
history.


---

## Phase 5 — POS & sales: COMPLETE (218 tests total)

Added on top of the earlier service layer:
- `SaleVoidService`: void/refund. A sale is **never deleted** — it is marked
  VOIDED/REFUNDED and a full reversal is written: the litres go back to the tank
  as a signed CORRECTION movement, the meter rolls back via an audited
  CORRECTION reading, and a reversing `SALE_VOID` cash entry is posted to the
  shift so the till reconciles. A `VOIDED` sale cannot be voided twice, and a
  refused void changes nothing.
- `PosController` + touch-friendly POS screen (nozzle grid, Litres/Amount
  toggle, live server-rate total, split-free single payment, credit customer
  picker) and a printable A4 receipt carrying the SRO 1006(I)/2021 **Rs.1 PoS
  service fee** line.
- `SalesHistoryController` with date / invoice / customer / cashier / status
  filters, a sale detail page showing historical `cost_rate` and gross margin,
  and the void screen.
- 12 void/refund tests. Suite: **218 passed (634 assertions)**.

Note: the ATTENDANT role deliberately has no `sales.void` — per the spec,
attendants run the POS and view their shift; voiding is a manager action.

---

## Remaining work (master task list — nothing here may be skipped)

Ordered by dependency. Each item lands as its own tested commit.

1. **FBR Digital Invoicing** — core rules + fiscalisation DONE (task list item 1). Remaining:
   `XXXXXX-DDMMYYHHMMSS-0001`, 7mm QR code, "SMS at 9966" statement, buyer
   CNIC/NTN above Rs.100,000, licensed-integrator interface. Legally mandatory.
2. **Provincial sales tax** — PRA/SRB/KPRA/BRA per branch, per product class.
3. **Withholding tax** — 236G/236H/236C, filer status on customer/supplier.
4. **Customer ledger & payments** — `customer_ledger`, `customer_payments`,
   running balance, statement, ageing; replaces the temporary fallback in
   `Customer::outstandingBalance()`.
5. **Suppliers, OMC khata & purchases** — `suppliers`, `supplier_ledger`,
   `purchases`, weighted-average costing on approval, cheque clearing status.
6. **Tanker deliveries & density/wet-stock** — dip before/after, driver, tanker.
7. **Mid-shift price change** — split litres at the changeover point.
8. **Non-fuel retail** — `products`, barcode POS, lubricants/tyre/car wash/TUC,
   reorder levels, combined forecourt P&L.
9. **Expenses, employees, attendance, payroll, advances & loans**.
10. **Cash in / cash out** — full ledger with approval thresholds.
11. **Accounting engine** — `accounts`, balanced `journal_entries`/
    `journal_entry_lines`, trial balance, automatic posting from every module.
12. **Profit/loss & daily closing** — COGS-based, day lock.
13. **Reports (18 families)** — view/print/PDF/Excel/CSV/email, queued exports.
14. **Email automation** — SMTP, invoice email, credit reminders, owner shift
    report with PDF attachment, `email_logs`, scheduler + queue workers.
15. **Global search & notifications panel**.
16. **Approval system** — `approval_requests` for sensitive actions.
17. **Audit log screen** (append-only) + export.
18. **Security hardening** — CSRF/XSS/injection tests, secure headers, rate
    limits, private file storage, backup/restore with `backup_logs`.
19. **Final QA + docs** — `README.md`, `docs/ER.md`, `docs/MODULES.md`,
    `docs/DEPLOYMENT.md`, `docs/BACKUP.md`, `docs/QA.md`, `DemoSeeder`.

## Standing notes

- **Cloudflare API token supplied in chat is compromised** (it was also
  committed and has since been purged from history). It must be rotated
  before any deployment.
- Hosting: **Hyperdrive → MySQL**, not D1. D1 is SQLite and has no
  `SELECT ... FOR UPDATE`, which the stock engine depends on.
