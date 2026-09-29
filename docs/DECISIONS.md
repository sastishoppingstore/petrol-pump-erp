# Decisions Log

Running record of every non-obvious decision taken while building the Petrol Pump ERP,
with the reasoning. Decisions are append-only; superseded entries are marked, never deleted.

---

## D-001 — Framework upgraded from Laravel 11 to Laravel 12.69.2

**Phase:** 0 · **Status:** Accepted (user-approved)

**Context**
The specification pins the stack to "Laravel 11" and says "do not debate". It *also* requires
a passed security checklist in Phase 12 with no missing items, and defines the project as done
only when "security ... work[s] together on real data".

`composer audit` on a fresh Laravel 11.56.1 install reported three advisories, all affecting
**every** release in the 11.x branch. There is no patched 11.x build — the fixes landed only in
12.60.0 and 12.61.1:

| Advisory | Severity | Issue | Affected |
|---|---|---|---|
| `CVE-2026-48019` / `GHSA-5vg9-5847-vvmq` | **high** | CRLF injection in the default `email` validation rule | `>=11.0.0,<12.0.0` (and `<12.60.0`) |
| `GHSA-5vg9-5847-vvmq` (PKSA) | high | CRLF injection in the default `email` rule | `<12.60.0` |
| `PKSA-m5cs-t1y6-qpcs` | medium | Temporary Signed URL path confusion | `<12.61.1` |

Both issues hit this product directly: the ERP validates email addresses on login, registration
and password reset, and Phase 8/12 require temporary signed URLs to serve expense attachments
and database backups through authorized controllers.

**Decision**
Build on Laravel 12.69.2 (the patched branch). `composer audit` now reports
*"No security vulnerability advisories found."*

**Impact**
Laravel 12 is structurally identical to 11 — same `bootstrap/app.php` configuration style, same
`bootstrap/providers.php`, same middleware/policy/Form Request APIs, same Eloquent, same
directory layout. No specification rewrite was needed and none of the 13 phases change approach.
This is the only deviation from the literal "STACK" section, and it was explicitly approved by
the user rather than taken unilaterally.

**Reconsideration trigger**
If the deployment target later requires Laravel 11 specifically, this decision must be revisited —
the advisories above would then be live and would have to be mitigated in application code.

---

## D-002 — MariaDB 11.8.6 instead of MySQL 8

**Phase:** 0 · **Status:** Accepted

**Context**
The specification requires "MySQL 8+ (InnoDB, utf8mb4)". The host is Debian 13 (trixie), whose
repositories ship **no `mysql-server` package at all** — only `mariadb-server`.

**Decision**
Use MariaDB 11.8.6 as the MySQL-compatible server.

**Impact**
Every engine feature this project depends on behaves identically and is verified working:
InnoDB transactions, `SELECT ... FOR UPDATE` row locking (required by `StockService::move()`),
`DECIMAL(14,2)` / `DECIMAL(10,2)` / `DECIMAL(12,3)` precision, `utf8mb4_unicode_ci`,
foreign keys, and strict SQL mode. `FOR UPDATE` locking is exercised directly in the Phase 3
concurrency tests against this server rather than assumed.

---

## D-003 — Feature tests run against a real MySQL database, not SQLite

**Phase:** 0 · **Status:** Accepted

**Context**
Laravel 11/12 skeleton ships `phpunit.xml` pre-wired for SQLite `:memory:`.

**Decision**
Point the suite at a dedicated MySQL database (`petrol_pump_erp_test`) and keep the SQLite
defaults commented out with an explanation.

**Impact**
SQLite cannot honestly verify the parts of this system that matter most. In-memory SQLite does
not implement `SELECT ... FOR UPDATE`, stores all numerics as `REAL` (destroying the
`DECIMAL(14,2)` money and `DECIMAL(12,3)` litre guarantees), and handles truncation and
multi-table foreign-key constraints differently. A suite that passed on SQLite could still
ship broken money maths or broken concurrent stock movement. `SystemStatusTest` includes a
guard test that fails loudly if anyone silently switches the connection back.

**Cost**
Slower tests (each one runs a real `migrate:fresh`), and the suite requires a running MySQL
server. Accepted deliberately.

---

## D-004 — MySQL credentials live in `.env`; a dedicated non-root DB user is used

**Phase:** 0 · **Status:** Accepted

Created a dedicated `erpuser@localhost` account with rights scoped to only the two project
databases, instead of connecting as `root`. The application never runs with the MySQL
superuser. Production privilege reduction (including revoking `UPDATE`/`DELETE` on
append-only ledger tables, per section 6) is documented in `docs/DEPLOYMENT.md` in Phase 12.

---

## D-005 — Frontend via Vite + SCSS, Tailwind removed

**Phase:** 0 · **Status:** Accepted

**Context**
The Laravel skeleton ships Tailwind. The specification requires Bootstrap 5 with a
petrol-station look (dark navy sidebar, amber/green accents).

**Decision**
Delete `tailwind.config.js` and `postcss.config.js`, and compile Bootstrap 5.3 from SCSS via
Vite in `resources/sass/app.scss`. The petroleum-station theme tokens, sidebar, POS touch
targets, stock bars, and both print stylesheets (80mm thermal and A4) are defined there once so
no screen has to reinvent them.

Chart.js and DataTables are registered globally in `resources/js/app.js` with shared defaults,
so any Blade screen can use `window.Chart` without a per-page import.

---

## D-006 — Project location

**Phase:** 0 · **Status:** Accepted

The working directory `/home/wafa-tech` is a home directory containing unrelated material
(videos, archives, other projects). Building an ERP in its root would be untidy and risky.
The application lives in its own repository at:

```
/home/wafa-tech/petrol-pump-erp
```

with its own `git init`. The database credentials were created in Phase 0 for local development
only and must be rotated before any real deployment.

---

## D-007 — Reserved identifiers and enum strategy

**Phase:** 0 · **Status:** Accepted (applies from Phase 1 onward)

- `status` columns are `VARCHAR(20)` with **no** MySQL `ENUM` type. MySQL `ENUM` requires a
  schema `ALTER` to add a value, and `ENUM` ordering is not portable. Values are constrained by
  application constants plus a `CHECK` constraint where the server supports it, so an invalid
  status cannot be written even by hand-edited SQL. Known sets: `COMPLETED|VOIDED|REFUNDED` for
  sales, `OPEN|CLOSED|PENDING_APPROVAL` for shifts, and the eight `tank_movements.type` values.
- Permission strings are `module.action` (e.g. `sales.create`, `stock.stock_adjustment`) and
  are seeded from a single PHP constant list, so the seeder, the Gate definitions and the UI
  permission matrix all read from one source.
- `stations` from the original spec is merged into `branches` exactly as the specification directs.
