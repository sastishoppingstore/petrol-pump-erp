# Petrol Pump ERP — Phased Master Prompt for Codex CLI

## Kaise use karein (Roman Urdu)

1. Khali folder banayein, `git init` karein, us folder mein CLI kholein.
2. Is file ko `AGENTS.md` ke naam se project root mein rakh dein (ya poora content pehle message mein paste karein).
3. CLI ko pehla message dein: `Read AGENTS.md fully. Start with Phase 0. Do not start Phase 1 until Phase 0 is done and PROGRESS.md is updated.`
4. Har phase khatam hone par agent rukega. Aap check karein, phir likhein: `Continue with the next phase.`
5. Agar agent beech mein ruk jaye ya context bhar jaye to likhein: `Read AGENTS.md and PROGRESS.md, then continue from the first unfinished phase.`
6. Stack badalna ho (pure PHP chahiye) to neeche `STACK` section ki pehli line badal dein.

Sab se important: **ek waqt mein ek phase.** Poora system ek saath banwane se code adhura aur buggy hota hai.

---

# THE PROMPT (AGENT INSTRUCTIONS START HERE)

You are a senior ERP architect and PHP/MySQL engineer. Build a complete, production-quality **Petrol Pump (Fuel Station) Management ERP** from scratch, following the phases below in order.

## 0. WORKING RULES (read first, obey always)

1. Work **one phase at a time**. After finishing a phase: run its tests, fix failures, update `PROGRESS.md`, make a git commit (`Phase N: <name>`), then STOP and report a short summary. Do not start the next phase until told to continue.
2. `PROGRESS.md` must list every phase with status (TODO / IN PROGRESS / DONE), what was built, what commands were run, and known issues.
3. No fake work: no demo-only screens, no dummy buttons, no hardcoded dashboard or report numbers, no `TODO`/`FIXME` left in code. Every visible button must call real backend code and real MySQL data.
4. If something is ambiguous, choose the simplest reasonable option, write the decision in `docs/DECISIONS.md`, and continue. Only stop to ask if the decision would be destructive or irreversible.
5. Never edit or delete existing working code blindly. Inspect first (`ls`, read files, check existing schema) and reuse what exists.
6. Business logic lives in **Service classes**, never in Blade/HTML views or controllers. Controllers stay thin: validate, authorize, call service, return response.
7. All SQL via Eloquent/Query Builder/PDO prepared statements. Never concatenate user input into SQL.
8. Never show raw SQL or stack traces to users. Log technical errors to `storage/logs`. Show friendly messages, e.g. "Unable to complete transaction. No changes were saved."
9. Write tests alongside features, not at the end. A phase is not DONE if its tests fail.

## 1. STACK (fixed — do not debate)

- **Laravel 11**, PHP 8.2+, MySQL 8+ (InnoDB, utf8mb4)
- Blade templates + Bootstrap 5 + vanilla JS (ES6), Chart.js for charts, DataTables (server-side) for big tables
- Laravel built-ins: migrations, seeders, policies/gates, Form Requests, queues not required
- PDF: `barryvdh/laravel-dompdf`. Excel/CSV: `maatwebsite/excel` (or plain CSV streaming)
- Tests: PHPUnit / Pest (feature tests against a test MySQL database)
- `.env` for secrets; provide `.env.example`; never commit `.env`

> If Laravel cannot be installed in this environment, fall back to plain PHP 8.2 with a clean MVC layout (`app/Controllers, Models, Services, Repositories, Middleware, Validators`, `public/index.php`, `routes/`, `database/migrations`, `resources/views`, `storage/`) using PDO. Record this in `docs/DECISIONS.md`. Everything else in this document still applies.

## 2. CORE DATA RULES (apply everywhere)

- Money: `DECIMAL(14,2)`. Rates/prices: `DECIMAL(10,2)`. Litres: `DECIMAL(12,3)`. Meter readings: `DECIMAL(14,3)`. **Never FLOAT/DOUBLE.**
- In PHP use `bcmath` (or integer paisa/millilitre math) for money and litres. Never use float arithmetic for them.
- Every table: `id`, `created_at`, `updated_at`. Every station-owned record: `branch_id` (FK, indexed).
- Financial/stock/audit history is **append-only**. Never `UPDATE`/`DELETE` ledger rows, stock movements, audit logs, meter readings. Corrections are new reversing rows.
- Use soft-delete or `status` for masters (fuels, tanks, customers…). Never hard-delete records that have transactions.
- Foreign keys everywhere. Indexes on: `invoice_number` (unique), `branch_id`, `customer_id`, `supplier_id`, `shift_id`, `fuel_product_id`, `tank_id`, `dispenser_id`, `nozzle_id`, `sale_date`, `purchase_date`.

## 3. BUSINESS FORMULAS (exact)

**Sale amount / litres**
- Litres mode: `amount = ROUND(litres × rate, 2)`
- Amount mode: `litres = ROUND(amount ÷ rate, 3)`; the entered `amount` stays the final amount (customer paid exactly that).
- Rate is taken server-side from the current fuel price. Never trust a rate sent from the browser. Changing the price requires the `price_change` permission and creates a `fuel_prices` history row (with effective time) + audit log.

**Meter logic**
- Each nozzle has `current_meter`. On a sale: `meter_start = nozzle.current_meter`, `meter_end = meter_start + litres`, then `nozzle.current_meter = meter_end`.
- A meter value may only increase. Reject any lower value with: "Closing meter reading cannot be lower than opening meter reading."
- Meter reset/rollover/correction only through a dedicated workflow requiring `stock_adjustment`/`approve` permission, a mandatory reason, an audit log, and a `meter_readings` row of type `CORRECTION`. Never overwrite silently.
- At shift close, the attendant enters the physical closing meter per nozzle. System compares it with `nozzle.current_meter`; the difference is stored as **meter variance** and flagged if outside the configured tolerance.

**Stock**
- Tank stock changes ONLY through `StockService::move()`, which writes a `tank_movements` row and updates `tanks.current_stock` in the same transaction, with `SELECT … FOR UPDATE` on the tank row.
- Movement types: `PURCHASE`, `SALE`, `ADJUSTMENT_IN`, `ADJUSTMENT_OUT`, `TRANSFER_IN`, `TRANSFER_OUT`, `LOSS`, `CORRECTION`.
- Each movement stores: tank, fuel, quantity, `before_quantity`, `after_quantity`, type, `reference_type`, `reference_id`, user, timestamp.
- Expected stock = Opening + Purchases + Positive adjustments − Sales − Negative adjustments (± transfers/loss/correction).
- Variance = Physical (dip) stock − Expected stock. Tank stock may never go below 0 or above capacity.

**Costing (weighted average, per fuel per branch)**
- On each purchase: `new_avg = (existing_qty × old_avg + purchased_qty × unit_cost) ÷ (existing_qty + purchased_qty)`.
- Each sale item stores `cost_rate` = average cost at the time of sale (historical, never recalculated).
- `COGS = litres × cost_rate`. `Gross Margin = Revenue − COGS`. `Net Result = Gross Margin − Operating Expenses`.
- Never call gross margin "net profit". Label report figures "Estimated" until the day/period is closed, then "Final".

**Ledgers (double-sided, running balance computed, never overwritten)**
- Customer: credit sale → debit; payment received → credit; balance = Σdebit − Σcredit + opening balance.
- Supplier: purchase → credit (we owe); payment → debit; balance = Σcredit − Σdebit + opening balance.
- Reject a credit sale that would exceed the customer's credit limit unless a user with `approve` permission overrides (audited).

**Cash / shift**
- `Expected Cash = Opening Cash + Cash Sales + Customer Cash Payments Received − Cash Expenses − Cash Handovers/Drops`.
- `Difference = Actual Cash − Expected Cash`.
- If |Difference| > configured threshold (setting `shift_variance_threshold`), closing requires manager approval and a note.
- Only the shift owner, or a user with `shift_close` permission, can close a shift.

## 4. SALE TRANSACTION (implement exactly)

`SaleService::create()` runs inside one DB transaction. Any failure = full rollback, nothing saved.

1. Check `sales.create` permission and branch access.
2. Check idempotency token (hidden form token stored in `sale_requests` / unique column) → a double click or refresh must NOT create a second sale.
3. Validate an OPEN shift for this user and that the nozzle is assigned to that shift.
4. Lock (`FOR UPDATE`) nozzle and tank rows.
5. Compute litres/amount using the rules above; validate rate, payment split totals equal the amount.
6. Validate tank has enough stock.
7. Generate invoice number from a `number_sequences` table (row-locked): `INV-{YYYY}-{6 digits}`. Same mechanism for `SHIFT-{YYYY}-{6}`, `PUR-{YYYY}-{6}`, `PAY-{YYYY}-{6}`.
8. Insert `sales`, `sale_items` (with `cost_rate`, `meter_start`, `meter_end`), `sale_payments`.
9. `StockService::move(SALE)` and update nozzle meter + `meter_readings` row.
10. If credit: validate credit limit, insert customer ledger debit.
11. If cash: insert `cash_transactions` row linked to the shift's cash session.
12. Post journal entry (see Phase 11).
13. Write audit log.
14. Commit; return invoice for receipt printing.

**Void / Refund:** requires `void` / `refund` permission and mandatory reason. Never delete the sale. Mark status `VOIDED`, create reversing stock movement (`CORRECTION`), reversing ledger/cash/journal rows, audit log. Same transaction rules.

## 5. PERMISSIONS & ROLES

Roles: `ADMIN`, `MANAGER`, `CASHIER`, `ATTENDANT`, `ACCOUNTANT`, `VIEWER`.
Permissions (per module where relevant): `view, create, edit, delete, approve, print, export, void, refund, price_change, stock_adjustment, shift_close, reports, settings`.

Default matrix (seed it; Admin can edit it in UI):

| Role | Access |
|---|---|
| ADMIN | everything, all branches |
| MANAGER | everything except settings/roles/backup restore, assigned branches only; can approve, void, refund, price_change, stock_adjustment, shift_close |
| CASHIER | POS, sales history (own), customer payments, shift open/close (own), print |
| ATTENDANT | POS (own nozzles only), own shift view |
| ACCOUNTANT | view all, purchases, suppliers, expenses, customers, ledgers, reports, export, journals; no POS |
| VIEWER | view + print only |

Enforce with Gates/Policies + route middleware. **Never rely on hidden buttons.** Exports obey the same permissions and branch scope as screens.
Users belong to one or more branches; Admin sees all.

## 6. DATABASE TABLES

Create migrations for all of these with proper FKs, indexes, unique constraints:

`users, roles, permissions, role_permissions, user_roles, user_branches, branches, fuel_products, fuel_prices, tanks, tank_readings, tank_movements, dispensers, nozzles, meter_readings, shifts, shift_nozzles, shift_cash, sales, sale_items, sale_payments, sale_requests (idempotency), customers, customer_vehicles, customer_ledger, customer_payments, suppliers, supplier_ledger, supplier_payments, purchases, purchase_items, expenses, expense_categories, employees, attendance, cash_sessions, cash_transactions, accounts, journal_entries, journal_entry_lines, stock_adjustments, notifications, audit_logs, settings, attachments, backups, number_sequences, login_attempts, password_resets`.

(Note: `stations` from the older spec is merged into `branches`.)

Key column requirements:
- `sale_items`: sale_id, fuel_product_id, tank_id, dispenser_id, nozzle_id, litres, rate, cost_rate, amount, meter_start, meter_end.
- `sales`: branch_id, shift_id, invoice_number (unique), customer_id nullable, vehicle_id nullable, employee_id, sale_date, subtotal, discount, tax, total, status (`COMPLETED|VOIDED|REFUNDED`), notes.
- `nozzles`: dispenser_id, tank_id, fuel_product_id, nozzle_number, opening_meter, current_meter, status. Unique (dispenser_id, nozzle_number). Nozzle's fuel must equal its tank's fuel (validate).
- `shifts`: branch_id, employee_id, shift_number (unique), opened_at, closed_at, opening_cash, expected_cash, actual_cash, cash_difference, status (`OPEN|CLOSED|PENDING_APPROVAL`), approved_by, closing_notes. Only one OPEN shift per employee and per nozzle (enforce in service + DB where possible).
- `audit_logs`: user_id, action, module, reference_type, reference_id, old_data (JSON), new_data (JSON), ip, user_agent, created_at. No update/delete code paths; revoke UPDATE/DELETE for the app DB user in the deployment docs.

## 7. PHASES

### Phase 0 — Inspect & plan
- Inspect the folder, any existing PHP files/schema. Install Laravel (or set up fallback MVC), Composer packages, Bootstrap assets.
- Write `PROGRESS.md`, `docs/DECISIONS.md`, `docs/ARCHITECTURE.md` (folder layout, service list, table list).
- Configure MySQL connection via `.env`; create `.env.example`.
- **Done when:** app boots, `php artisan migrate` runs on empty DB, one smoke test passes.

### Phase 1 — Auth, users, roles, branches, layout
- Login, logout, forgot/reset password, remember me, session regeneration, secure cookies, login throttling (e.g. 5 fails → 15 min lock, stored in `login_attempts`), user status (active/disabled), last login.
- Roles/permissions tables + seeder, permission middleware, branch scoping helper (`BranchScope`).
- Users, Roles, Permissions, Branches screens (CRUD with validation).
- **Layout:** fixed left sidebar (grouped: Sales, Fuel, Purchase, Operations, Accounts, Reports, Settings), top bar with branch switcher + user menu + notifications bell, breadcrumbs, toast alerts, modal forms. Mobile: collapsible sidebar with hamburger, big touch buttons, horizontally scrollable tables. Professional petrol-station look (dark navy sidebar, amber/green accents), not a basic CRUD look.
- **Installer command:** `php artisan erp:install` asks for company name, admin name/email/password and creates the first admin. No default password in production.
- **Done when:** tests for login, throttling, permission denial (403), branch isolation pass.

### Phase 2 — Fuel master data
- Fuel products (name, code, unit, selling price, tax rate, minimum stock, status) — admin can add any fuel, nothing hardcoded. Fuel price history screen with effective date.
- Tanks (branch, fuel, number, capacity, min/max level, opening stock, current stock). Card UI with stock % bar and low-stock colour.
- Tank readings (physical dip) → shows expected, physical, variance; saves to `tank_readings`.
- Dispensers, Nozzles (linked dispenser + tank + fuel), meter readings history, meter correction workflow.
- **Done when:** tests for fuel/tank/dispenser/nozzle creation, nozzle–tank fuel mismatch rejection, meter validation pass.

### Phase 3 — Stock engine
- `StockService` with `move()`, `expected()`, `variance()`; stock movement screen with filters.
- Stock adjustment (in/out/loss/correction) with reason, `stock_adjustment` permission, approval flow.
- Low-stock notification (once per tank per day, not repeated spam).
- **Done when:** tests prove stock never goes negative, before/after quantities are correct, concurrent-safe locking is used, adjustments audited.

### Phase 4 — Shifts
- Open shift: employee, opening cash, assigned dispenser/nozzles, opening meter readings (must match current meters within tolerance or require note).
- Active shift screen: live totals (cash/card/credit/other sales, expenses, expected cash).
- Shift closing: closing meters per nozzle, actual cash, card settlement, closing notes, variance, manager approval when over threshold. Printable shift report.
- **Done when:** tests for one-open-shift rule, expected cash, variance threshold, unauthorized close pass.

### Phase 5 — POS & sales
- POS screen (touch friendly): pick dispenser → nozzle (fuel auto-filled), Litres/Amount toggle, live rate & total, customer + vehicle (for credit), payment method (Cash, Card, Bank Transfer, Mobile Wallet, Credit; split payments allowed), Complete Sale, then receipt print (80mm thermal-friendly print CSS + A4 invoice).
- Sales history with filters (date, shift, attendant, fuel, nozzle, payment, customer, invoice), reprint, void, refund per section 4.
- **Done when:** tests for litres↔amount maths, rounding, meter increase, stock decrease, invoice uniqueness, double-submit, insufficient stock, credit limit, void reversal pass.

### Phase 6 — Customers, vehicles, credit (udhaar)
- Customers CRUD, profile tabs: Overview, Sales, Payments, Ledger, Vehicles. Vehicles CRUD (multiple per customer).
- Customer payments (cash/bank/etc.) → ledger credit + cash transaction; printable customer statement (date range).
- Overdue credit notification (configurable days).
- **Done when:** tests for ledger running balance, payment, credit limit, statement pass.

### Phase 7 — Suppliers & purchases
- Suppliers CRUD + ledger + supplier payments.
- Purchase entry: supplier, invoice no., date, fuel, quantity, rate, tax, total, paid, balance, destination tank, tanker no., driver. Draft → Approved. On approval (in one transaction): stock movement `PURCHASE`, update weighted average cost, supplier ledger, journal entry. Tank capacity check.
- Purchase history and printable purchase invoice.
- **Done when:** tests for stock increase, avg cost calc, supplier balance, capacity overflow rejection pass.

### Phase 8 — Expenses, employees, attendance, cash
- Expense categories (seed: Electricity, Salary, Maintenance, Generator, Cleaning, Security, Transport, Office, Miscellaneous; admin can add), expenses with attachment upload, cash expenses create cash transactions tied to the open shift.
- Employees CRUD, attendance (present/absent/leave/half-day, check-in/out, hours), monthly attendance view.
- Cash management: cash sessions, cash drops/handovers.
- File uploads: validate MIME + extension + size, random filenames, stored outside `public/`, served through an authorized controller.
- **Done when:** tests for expense→cash effect, invalid upload rejection, attendance pass.

### Phase 9 — Daily closing & reports
- Daily Closing screen/report per branch/date: per fuel (Opening, Purchase, Sales, Expected Closing, Physical Closing, Variance) + financial summary (Cash, Card, Bank, Credit, Other, Total Sales, Expenses, Cash Difference). Lock the day after finalization (further edits need `approve`).
- Reports (all with date/branch/fuel/employee/payment filters, real queries, pagination): Daily/Monthly/Fuel-wise/Attendant-wise/Nozzle-wise/Shift-wise/Payment-wise sales; Tank stock, Fuel movement, Stock variance; Revenue, COGS, Gross Margin, Expenses, Net Result (labelled Estimated/Final), Receivables, Payables; Shift report, Meter report, Attendant report.
- Every report: View, Print (print CSS), PDF, Excel, CSV — same permission checks as the screen.
- **Done when:** report totals reconcile with the underlying sales/stock in tests.

### Phase 10 — Dashboard, notifications, search
- Dashboard from real queries with date-range selector: Today's Sales, Fuel Sold, Cash/Card/Credit Sales, Expenses, Gross Margin, Outstanding Credit; charts (sales trend, fuel-wise, payment breakdown); tank levels; low-stock alerts; active shifts; recent sales; recent expenses. Cache only short-lived, safe aggregates.
- Notifications: low stock, excessive shift variance, pending approvals, overdue customer credit, overdue supplier balance, unusual stock variance. De-duplicate; mark read.
- Global search: invoice, customer, vehicle, supplier, employee, shift, tank, purchase (respecting permissions/branch).
- **Done when:** dashboard numbers equal manual DB queries in a test.

### Phase 11 — Accounting & audit
- Default chart of accounts seeded (Cash, Bank, Fuel Inventory, Accounts Receivable, Accounts Payable, Fuel Sales Revenue, COGS, each expense category, Cash Over/Short).
- Auto-post **balanced** journal entries (Σdebit = Σcredit enforced) for sales, purchases, payments, expenses, voids/refunds, cash variance. Journal list + trial balance screen.
- Audit log screen (filter by user/module/date; read-only; export). Log: login, logout, sale create/void/refund, purchase, stock adjustment, price change, meter correction, shift open/close, expense, customer/supplier payment, permission/role change, settings change.
- **Done when:** tests prove journals balance and audit rows are written for every listed action.

### Phase 12 — Settings, backup, security hardening
- Settings: company, branch, currency, tax, rounding, receipt, printer, variance thresholds, overdue days, notifications, security.
- Backup: create (mysqldump to `storage/backups`), list, authorized download, restore with typed confirmation; never publicly accessible.
- Security checklist verified by tests: CSRF on all POST, XSS escaping, SQL injection attempts, session regeneration, secure cookies, `.env`/storage not web-accessible, security headers, rate limits, password hashing.
- **Done when:** security tests pass and a manual review confirms none of the checklist is missing.

### Phase 13 — Final QA & documentation
Run the whole flow end to end (automated feature test + manual script in `docs/QA.md`):
login → create fuel, tank, dispenser, nozzle → purchase fuel (stock up) → open shift → cash sale (stock down, meter up, payment recorded) → credit sale (ledger debit) → customer payment → expense → close shift (variance) → daily closing → reports → audit log → unauthorized action blocked → mobile layout → print layouts.

Then:
- Run `php -l` on all PHP, full test suite, migrations from scratch (`migrate:fresh --seed` on a test DB).
- Grep the codebase and remove any `TODO`, `FIXME`, placeholder pages, dummy buttons, hardcoded numbers, dead links, `dd()`, `var_dump`, console errors, PHP warnings.
- Deliver: `README.md` (install, run, test), `.env.example`, `docs/ER.md` (relationships), `docs/MODULES.md`, `docs/DEPLOYMENT.md` (HTTPS, web root = `public/`, DB user privileges, cron for backups/notifications), `docs/BACKUP.md`.
- Seeders: `ProductionSeeder` (roles, permissions, chart of accounts, expense categories, settings only) and `DemoSeeder` (Petrol/Diesel/Hi-Octane, sample tank/dispenser/nozzle/users) clearly marked DEMO and never run automatically in production.

## 8. DEFINITION OF DONE

The project is complete only when UI, backend, database, business rules, permissions, validation, transactions, reports, audit, and security all work together on real data, all tests pass, and the final QA flow succeeds without errors.

## 9. REQUIRED SCREENS (checklist for Phases 1–12)

Login · Dashboard · Fuel Products · Fuel Prices · Tanks · Tank Readings · Dispensers · Nozzles · Meter Readings · POS · Sales History · Sale Details · Shifts · Open Shift · Active Shift · Shift Closing · Purchases · Purchase Details · Suppliers · Supplier Ledger · Customers · Customer Details · Customer Ledger · Customer Payments · Vehicles · Expenses · Expense Categories · Employees · Attendance · Daily Closing · Sales Reports · Stock Reports · Profit Reports · Financial Reports · Users · Roles · Permissions · Audit Logs · Notifications · Settings · Branches · Backup/Restore