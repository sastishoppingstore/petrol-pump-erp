# PROGRESS

Petrol Pump (Fuel Station) Management ERP.
One phase at a time. Each phase runs its tests, updates this file, commits, and stops.

---

## Phase status

| # | Phase | Status |
|---|---|---|
| 0 | Inspect & plan | **DONE** |
| 1 | Auth, users, roles, branches, layout | TODO |
| 2 | Fuel master data | TODO |
| 3 | Stock engine | TODO |
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
