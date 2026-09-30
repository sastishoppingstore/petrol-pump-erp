# Mehar Filling Station ERP v4

**100% Paperless Petrol Pump Management System**

🚀 **Production-Ready** | ✓ All 14 Features Complete | 📊 98 Database Tables | 🔒 Security Hardened

---

## Overview

**Mehar ERP v4** is a comprehensive, single-pump fuel station management system built on Laravel 12 + MySQL 8. It automates:

- **POS & Sales** — Touch-friendly POS, invoices, void/refund
- **Stock Management** — Tank levels, dip readings, water tests, adjustments
- **Banking** — Bank accounts, cheques (issue/clear/bounce), reconciliation
- **Accounting** — Double-entry journal posting, trial balance, P&L
- **Payroll** — Attendance, salary, advances, deductions
- **FBR & Tax** — Digital invoicing, QR codes, fiscal reporting
- **Customers & Suppliers** — Ledgers, credit limits, outstanding tracking
- **Reports** — 18+ report families (sales, stock, profit, receivables, etc.)
- **Daily Closing** — Variance reconciliation, shift reports, period lock
- **Notifications** — Low stock, overdue credit, pending approvals
- **Dashboard** — Real-time KPIs, charts, alerts

---

## Features (14 Completed ✓)

| # | Feature | Status |
|---|---------|--------|
| 1 | System Verification & Setup | ✓ |
| 2 | FBR Tax Engine | ✓ |
| 3 | Nozzle Tests & Return Litres | ✓ |
| 4 | Tank Dip & Water Contamination | ✓ |
| 5 | Tanker Unloading Wizard | ✓ |
| 6 | Payroll & Staff Shortage | ✓ |
| 7 | Generator Fuel & Own Consumption | ✓ |
| 8 | Advanced Invoicing & Bill Designer | ✓ |
| 9 | Picture-First Cashier UI | ✓ |
| 10 | 3D Tank Gauges & Animations | ✓ |
| 11 | Accounting Engine | ✓ |
| 12 | Banking & Cheques | ✓ |
| 13 | StackCP Deployment & Cron | ✓ |
| 14 | Final QA & Documentation | ✓ |

---

## Quick Start

### Prerequisites

- PHP 8.2+
- MySQL 8+
- Node.js 18+
- Composer

### Local Development

```bash
# Clone repository
git clone https://github.com/your-org/mehar-erp.git
cd mehar-erp

# Install dependencies
composer install
npm install && npm run dev

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database
# Edit .env: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Run migrations & seed
php artisan migrate:fresh --seed

# Start dev server
php artisan serve
```

Access: `http://localhost:8000`
- Email: `admin@company.com`
- Password: `password`

### Production Deployment (StackCP)

See: **[`docs/DEPLOYMENT_STACKCP.md`](docs/DEPLOYMENT_STACKCP.md)** (13-step quick setup)

Or full guide: **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)** (14 sections, 600+ lines)

---

## Architecture

### Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Database:** MySQL 8+, InnoDB, utf8mb4
- **Frontend:** Bootstrap 5, Chart.js, DataTables, Blade templates
- **Assets:** Vite, SCSS, vanilla ES6 JS
- **Testing:** PHPUnit/Pest, feature tests
- **Deployment:** Nginx, systemd, cron scheduler, Redis (optional)

### Directory Structure

```
app/
├── Console/Commands/          # Cron jobs (daily closing, backups, notifications)
├── Http/Controllers/          # REST endpoints
├── Models/                    # Eloquent models (98 tables)
├── Services/                  # Business logic (Stock, Sales, Banking, Accounting, etc.)
├── Http/Middleware/           # Auth, permissions, branch scoping
├── Http/Requests/             # Form validation

database/
├── migrations/                # 98 table schemas
├── factories/                 # Factories for testing
├── seeders/                   # ProductionSeeder, DemoSeeder

routes/
├── web.php                    # Main routes
├── accounting.php             # Journal, trial balance
├── banking.php                # Bank accounts, cheques
├── pos.php, sales.php, etc.   # Feature routes

resources/
├── views/                     # Blade templates (Livewire, forms, reports)
├── sass/app.scss             # Petrol-station theme (dark navy, amber/green)
├── js/                        # ES6 JavaScript

storage/
├── logs/                      # Application logs
├── backups/                   # Daily database backups
```

### Database

- **98 Tables:** Users, Branches, Sales, Purchases, Stock, Accounting, Banking, Payroll, etc.
- **Append-Only:** Audit logs, stock movements, meter readings (never updated)
- **Soft Deletes:** Masters (fuels, tanks, customers)
- **Foreign Keys:** All tables referentially constrained
- **Indexes:** Critical queries indexed (sales date, invoice number, customer ID, etc.)

### Services (Business Logic)

| Service | Purpose |
|---------|---------|
| `SaleService` | Complete sale transaction (stock, invoice, payment) |
| `StockService` | Tank movements, expected/actual variance, locking |
| `AccountingEngineService` | Double-entry journal posting, trial balance, P&L |
| `BankingService` | Deposits, cheques, reconciliation |
| `PayrollService` | Salary, advances, deductions, attendance |
| `InvoiceDesignerService` | 3 layouts, FBR QR, digital signatures |
| `CashierUiService` | 4-tile dashboard, keypad, banknotes |
| `Tank3dGaugeService` | 3D wave animations, color coding |
| `FbrTaxEngineService` | FBR invoice format, fiscal year tracking |
| `DailyClosingService` | Variance reconciliation, period lock |

---

## Key Business Rules

### Money & Quantities

- **Money:** `DECIMAL(14,2)` (no float)
- **Litres:** `DECIMAL(12,3)`
- **Math:** `bcmath` for precision

### Sales

- Litres mode: `amount = litres × rate` (rounded)
- Amount mode: `litres = amount ÷ rate` (rounded)
- Rate from server (never trust client)
- Idempotency token prevents double-submit

### Stock

- Tank movements: `before_qty`, `after_qty`, type (SALE/PURCHASE/ADJUSTMENT/etc.)
- Variance: Physical (dip) − Expected
- Meter: Only increases, corrections audited
- Locking: `SELECT … FOR UPDATE` prevents race conditions

### Accounting

- Every sale/purchase posts auto journal entry
- Debit + Credit verified in transaction
- Trial balance = Σdebit = Σcredit (always)
- COGS by weighted-average cost

### Banking

- Cheque: ISSUED → PRESENTED → CLEARED or BOUNCED
- Bounce reverses payment, restores cash
- Reconciliation matches bank statement items

### Payroll

- Monthly attendance + hourly rates
- Advances/deductions tracked
- Staff shortage alerts

---

## Security

✅ **CSRF protection** (form tokens)  
✅ **XSS escaping** (Blade templates)  
✅ **SQL injection prevention** (Eloquent, prepared statements)  
✅ **Authentication** (session, gates, policies)  
✅ **Authorization** (role-based access, branch scoping)  
✅ **Rate limiting** (login: 5 attempts / 15 min)  
✅ **Secure cookies** (httpOnly, secure, sameSite)  
✅ **File uploads** (MIME validation, random filename, outside public/)  
✅ **Audit logs** (all actions logged)  
✅ **Secrets** (`.env` not web-accessible)  

---

## Testing

### Run All Tests

```bash
php artisan test
```

### Test Coverage

- **50+ feature tests** — Sales, stock, accounting, banking
- **Edge cases** — Zero stock, credit limit, duplicate invoice
- **Security** — CSRF, XSS, auth, authorization
- **Performance** — Dashboard load time, report export

---

## Documentation

| Document | Purpose |
|----------|---------|
| [`PROGRESS.md`](PROGRESS.md) | Detailed phase-by-phase completion log |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Full StackCP deployment guide (14 sections) |
| [`docs/DEPLOYMENT_STACKCP.md`](docs/DEPLOYMENT_STACKCP.md) | Quick 13-step setup |
| [`docs/QA.md`](docs/QA.md) | 50-test QA checklist |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | System design & ERD |
| [`docs/DECISIONS.md`](docs/DECISIONS.md) | Design decisions & tradeoffs |

---

## Deployment

### StackCP (Production)

1. **Quick setup (13 steps):**
   ```bash
   bash docs/DEPLOYMENT_STACKCP.md
   ```

2. **Or full guide:**
   Read [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)

### Cron Jobs (Automated)

- **23:30 daily** → Daily closing (`erp:daily-closing`)
- **02:00 daily** → Database backup (`erp:backup --retention=7`)
- **Hourly** → Sync notifications (`erp:sync-notifications`)
- **Weekly** → Cleanup old notifications (`erp:notifications-cleanup`)

### Health Check

```bash
curl https://yourdomain.com/health
# {"status":"ok","database":"up"}
```

---

## First Login

- **Email:** `admin@company.com`
- **Password:** `password` (change immediately)

### Initial Setup

1. Settings → Company details, branches, fuels, banks
2. Users → Create managers, cashiers, accountants
3. POS → Setup nozzles, dispensers, tanks
4. Accounting → Define chart of accounts (if needed)

---

## License

Private — Mehar Filling Station

---

## Support

- **Documentation:** [`docs/`](docs/)
- **Issues:** GitHub Issues
- **Contact:** `support@mehar-erp.com`

---

## Changelog

### v4.0 (October 2026)

✓ All 14 features complete  
✓ 98 database tables  
✓ 50+ test cases  
✓ Production deployment ready  
✓ Complete documentation  

---

**Made with ❤️ for Mehar Filling Station**

**Status:** 🟢 **PRODUCTION READY** | Last updated: October 1, 2026
