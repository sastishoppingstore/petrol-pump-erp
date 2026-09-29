# Architecture

Petrol Pump (Fuel Station) Management ERP — structural blueprint.
Phase 0 establishes the skeleton; each later phase adds the services and tables listed here.

---

## 1. Technology stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12.69.2 (see `DECISIONS.md` D-001 for the Laravel 11 deviation) |
| Language | PHP 8.4 (project requires `^8.2`) |
| Database | MySQL-compatible, InnoDB, `utf8mb4_unicode_ci` (MariaDB 11.8.6 locally, D-002) |
| Views | Blade |
| CSS/JS | Bootstrap 5.3 + vanilla ES6, compiled by Vite |
| Charts | Chart.js 4 |
| Tables | DataTables 2 (server-side) |
| PDF | `barryvdh/laravel-dompdf` |
| Excel | `maatwebsite/excel` (CSV streaming also used for large exports) |
| Tests | PHPUnit feature tests against a real MySQL test database (D-003) |

---

## 2. Folder layout

```
app/
├── Console/Commands/          erp:install and other artisan commands
├── Enums/                     Shared status + movement-type constants
├── Exceptions/                Domain exceptions -> friendly messages
├── Http/
│   ├── Controllers/           Thin: validate, authorize, call service, respond
│   │   ├── Auth/              Login, password reset
│   │   ├── Fuel/              Fuel products, prices, tanks, dispensers, nozzles
│   │   ├── Pos/               POS, sales, void, refund, receipt
│   │   ├── Shift/             Open / active / close
│   │   ├── Purchase/          Suppliers, purchases, payments
│   │   ├── Accounts/          Customers, vehicles, ledgers, expenses, cash
│   │   ├── Reports/           Every report screen
│   │   └── Settings/          Users, roles, permissions, branches, settings, backup
│   ├── Middleware/            Permission, BranchScope, Auth, Security headers
│   ├── Requests/              Form Requests — all validation lives here
│   └── Resources/             JSON response shape helpers
├── Models/                    One model per table
├── Policies/                  Per-model authorization
├── Providers/                 Gate + policy registration
├── Services/                  ALL business logic lives here
│   ├── Auth/                  Login throttling, session handling
│   ├── Fuel/                  FuelPriceService, TankService, NozzleService, MeterService
│   ├── Stock/                 StockService, StockAdjustmentService
│   ├── Shift/                 ShiftService, ShiftClosingService
│   ├── Sale/                  SaleService, SaleVoidService, ReceiptService
│   ├── Purchase/              PurchaseService, PurchaseApprovalService
│   ├── Accounts/              CustomerService, CustomerLedgerService, PaymentService,
│   │                          SupplierService, SupplierLedgerService, ExpenseService
│   ├── Cash/                  CashSessionService, CashTransactionService
│   ├── Accounting/            JournalService, AccountService, TrialBalanceService
│   ├── Report/                One service per report family
│   ├── Security/              AuditLogService, PermissionService
│   └── System/                NumberSequenceService, SettingService, NotificationService
└── Support/                   Value objects, Money/Quantity helpers (bcmath)
```

**Hard rule:** controllers and Blade views never compute money, litres, stock, balances or
variance. They call a service and render what it returns.

---

## 3. Service list and responsibilities

| Service | Phase | Responsibility |
|---|---|---|
| `NumberSequenceService` | 4 | Row-locked `INV-`, `SHIFT-`, `PUR-`, `PAY-` document numbers |
| `AuditLogService` | 0/11 | Append-only `audit_logs` writes; never updated or deleted |
| `PermissionService` | 1 | Role/permission resolution, matrix CRUD |
| `BranchScopeService` | 1 | Restrict every query to the user's assigned branches |
| `AuthService` | 1 | Login throttling via `login_attempts`, lockout, last-login |
| `FuelProductService` | 2 | Fuel master CRUD, tax, min-stock |
| `FuelPriceService` | 2 | Server-side rate resolution; `fuel_prices` history on change |
| `TankService` | 2 | Tanks, capacity limits, current stock reads |
| `TankReadingService` | 2 | Physical dip readings vs expected stock, variance |
| `DispenserService` | 2 | Dispensers per branch |
| `NozzleService` | 2 | Nozzles; enforces nozzle fuel == tank fuel |
| `MeterService` | 2 | Meter increments, monotonicity, correction workflow |
| `StockService` | 3 | `move()`, `expected()`, `variance()`; `FOR UPDATE` locking |
| `StockAdjustmentService` | 3 | Adjustments + approval flow + low-stock alerts |
| `ShiftService` | 4 | Open shift, one-open-shift rules, nozzle assignment |
| `ShiftClosingService` | 4 | Closing meters, expected cash, variance, approval |
| `SaleService` | 5 | The 14-step sale transaction |
| `SaleVoidService` | 5 | Void/refund with full reversal |
| `ReceiptService` | 5 | 80mm thermal + A4 invoice rendering |
| `CustomerService` | 6 | Customers, vehicles, credit-limit checks |
| `CustomerLedgerService` | 6 | Running balance, statement |
| `PaymentService` | 6 | Customer/supplier payments + cash effect |
| `SupplierService` | 7 | Supplier master |
| `PurchaseService` | 7 | Purchase entry, approval, weighted-average cost |
| `CostingService` | 7 | Weighted average cost per fuel per branch |
| `ExpenseService` | 8 | Expenses, categories, attachment handling |
| `EmployeeService` | 8 | Employees + attendance |
| `CashSessionService` | 8 | Cash sessions, drops, handovers |
| `DailyClosingService` | 9 | Per-day stock + financial summary, day lock |
| `ReportService` + sub-services | 9 | All report families, shared filters |
| `DashboardService` | 10 | Aggregates for dashboard, cached briefly |
| `NotificationService` | 10 | De-duplicated alerts, mark read |
| `SearchService` | 10 | Global search across 8 entity types |
| `JournalService` | 11 | Balanced double-sided entries, trial balance |
| `SettingService` | 12 | Key/value settings with typed access |
| `BackupService` | 12 | mysqldump create/list/restore |
| `InstallCommand` | 1 | `php artisan erp:install` |

---

## 4. Table list

**49 project tables** listed below, plus **8 Laravel infrastructure tables** (`migrations`,
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`)
= 57 total.

**Identity & access:** `users`, `roles`, `permissions`, `role_permissions`, `user_roles`,
`user_branches`, `branches`, `login_attempts`

**Fuel master:** `fuel_products`, `fuel_prices`, `tanks`, `tank_readings`, `tank_movements`,
`dispensers`, `nozzles`, `meter_readings`

**Operations:** `shifts`, `shift_nozzles`, `shift_cash`, `sales`, `sale_items`, `sale_payments`,
`sale_requests`, `stock_adjustments`

**Customers:** `customers`, `customer_vehicles`, `customer_ledger`, `customer_payments`

**Suppliers & purchasing:** `suppliers`, `supplier_ledger`, `supplier_payments`, `purchases`,
`purchase_items`

**Expenses & HR:** `expenses`, `expense_categories`, `employees`, `attendance`

**Cash:** `cash_sessions`, `cash_transactions`

**Accounting:** `accounts`, `journal_entries`, `journal_entry_lines`

**System:** `notifications`, `audit_logs`, `settings`, `attachments`, `backups`,
`number_sequences`, `password_resets`

---

## 5. Data rules applied everywhere

- **Money** `DECIMAL(14,2)` · **Rates** `DECIMAL(10,2)` · **Litres** `DECIMAL(12,3)` ·
  **Meters** `DECIMAL(14,3)`. No `FLOAT`/`DOUBLE` anywhere.
- All money and litre arithmetic goes through `app/Support/` helpers backed by **bcmath**
  (or integer paisa / millilitre math), never native float math.
- Every table: `id`, `created_at`, `updated_at`. Every station-owned row: `branch_id` FK, indexed.
- **Append-only** history: `tank_movements`, `customer_ledger`, `supplier_ledger`,
  `meter_readings`, `journal_entries`, `journal_entry_lines`, `cash_transactions`, `audit_logs`,
  `sale_requests`. Corrections are new reversing rows, never `UPDATE`/`DELETE`.
- Masters use `status` or soft delete. Records with transactions are never hard-deleted.
- Every branch-owned query passes through `BranchScopeService`.

---

## 6. Request flow

```
Browser
  -> Middleware (auth, permission, branch scope, security headers)
  -> Controller (validate via Form Request, authorize via Policy/Gate)
  -> Service (all business rules, wrapped in DB::transaction, rows locked FOR UPDATE)
  -> Model / Query Builder (prepared statements only)
  -> Blade or JSON response
```

Rules that never bend:
- SQL is always via Eloquent / Query Builder / prepared PDO. No string-concatenated user input.
- Raw SQL and stack traces are never shown to users. Technical detail goes to
  `storage/logs`; the user sees a friendly message such as
  "Unable to complete transaction. No changes were saved."
- Authorization is enforced in Gates/Policies and route middleware, never by hiding buttons.

---

## 7. Testing strategy

- Feature tests against real MySQL (D-003) — required for `FOR UPDATE`, `DECIMAL` and FK
  behaviour to be genuinely verified.
- Each phase ships its tests with the feature. A phase is not DONE while its tests fail.
- Permission and branch isolation are tested by asserting **403 / 404**, not by inspecting HTML.
