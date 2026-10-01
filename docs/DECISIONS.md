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

---

## D-008 — 2026 Frontend Redesign: one responsive dashboard, Tailwind design system, admin-driven theme

**Phase:** Post-v4 (owner-requested) · **Status:** Accepted

**Context**
The owner reported four visible defects: (1) the dashboard rendered inside a fixed
430px "phone frame" even on desktop; (2) several index pages (Fuel Products, Nozzles)
were written with Bootstrap class names while the app only ships Tailwind, so they
rendered as plain unstyled lists; (3) the Nozzles meter-correction modal markup lived
inside `<tbody>`, which is invalid HTML — browsers hoist it out, so the "Corrected
meter / Reason" fields bled to the bottom of the page, and with no Bootstrap JS the
modal never opened; (4) the login page was plain and its layout broke under browser
autocomplete overlays.

**Decisions**
1. **One responsive dashboard.** `dashboard.blade.php` no longer switches between a
   mobile launcher and a separate `?mode=desktop` view. The Livewire app-launcher now
   renders inside the standard app layout at all sizes: below `lg` it keeps the
   app-style shell (430px frame, bottom nav, FAB); at `lg`+ the frame dissolves into
   a fluid full-width dashboard (expanded grids, ERP sidebar, desktop FAB).
2. **Design system in `resources/css/app.css`.** Shared 3D/glass primitives
   (`.glass-card`, `.card-3d`, `.btn-3d-*`, `.fab-3d`, `.input-3d`, `.field-3d`,
   `.pill-status`, fuel badges) plus a **legacy compatibility layer** that styles the
   Bootstrap class names still used by older screens (`.btn`, `.table`, `.erp-card`,
   `.form-control`, `.modal`, grid helpers) in the new look — every legacy page is
   upgraded without editing its Blade file, and `.modal` is hidden by default so
   modal markup can never bleed into a page again. A tiny vanilla-JS shim in
   `resources/js/app.js` wires `data-bs-toggle="modal|tab"` and
   `data-bs-dismiss="modal"` to that layer, so no legacy button is dead.
3. **Admin-editable branding actually wired.** Settings already stored
   `theme_primary_color` / station names, but nothing consumed them. A new
   `partials/theme.blade.php` emits `--brand-primary-rgb` / `--brand-dark-rgb` CSS
   variables from `SettingService`, and the Tailwind `vital` palette + hero/FAB CSS
   consume those variables — changing colours or the station name in Admin →
   Settings now re-skins the whole ERP (sidebar, login, dashboard) with no rebuild
   of PHP code; only the standard Vite build is needed after CSS/Blade changes.
4. **Autocomplete safety rule.** Form screens must wrap inputs in `.field-3d`
   (position:relative, focus raises z-index) and must not put transforms or
   `overflow:hidden` on card ancestors of inputs — that is what clipped/shifted
   the login layout under the browser's autofill dropdown.
5. **Nozzles correction modals** render after the table (never inside it) and are
   driven by Alpine; the form, route (`nozzles.meter-correction`) and fields are
   unchanged, so the audited CORRECTION workflow is untouched.


## D-009 — Full-repo visual redesign: ek design system, centered content (2026-10-01)

**Decision:** ERP ke tamam screen pages ek hi Tailwind design system
(`resources/css/app.css`: `.glass-card`, `.card-3d`, `.btn-3d`, `.fab-3d`,
`.input-3d`/`.field-3d`, `.table-3d`, `.pill-status`, `.badge-fuel`,
`.stat-tile-3d`, `.page-head`) par standardize kiye gaye. Owner ki farmaish ke
mutabiq page titles, card content aur table cells **centered** hain. Naye pages
me Bootstrap classes mamnoo hain; purani files ke liye app.css ki compatibility
layer (`.btn`, `.table`, `.modal`, utility classes…) us waqt tak rahegi jab tak
har file convert na ho jaye.

**Print boundary:** Invoice/receipt/payslip/statement aur email templates is
system se bahar hain — printed output hamesha saada aur saaf rahega.

**Theme:** Brand rang admin Settings (`theme_primary_color`, `theme_dark_red`)
se CSS variables ke zariye aate hain (D-008); koi naya hardcoded brand hex nahi.

**Delivery constraint:** Muse GitHub app read-only hai, is liye redesign
patch/ZIP ki surat me deliver hua; owner apne git se push karte hain.

## D-010 — Cinematic motion layer + Amanat/Vault modules (2026-10-01)
- Motion sirf browser layer me: GSAP (free) + Chart.js presets (film-line/gradient-bars/donut-rotate) + Three.js particles sirf desktop par dynamic import se. Backend 100% PHP; data contract = Blade `data-chart=@json` + `data-countup`.
- Market research (Pakistan) ke mutabiq 15 must-have modules me se 13 mojood thay; Amanat (prepaid deposits) aur Document Vault naye add hue. ATG/loyalty mustaqbil ke phases.
- install.php hardening: requirements gate, asal admin+branch creation, lock file, self-delete, CSRF, injection-safe. Purana installer admin create hi nahi karta tha.

## D-011 — Bug-fix mission: phantom schema safai (2026-10-01)
- Codebase ki bari bimari "phantom schema" thi: code aisay columns/methods/functions par likha tha jo migrations/models me thay hi nahi (expense_date, sales.payment_method, setting(), Bank::TYPE_*, PayrollService ke 7 methods). Usool: har fix pehle migration/model se verify, phir code ko asal schema par align — kabhi ulta nahi.
- setting() global helper (app/helpers.php, provider se load) — undefined-function fatals ki poori class khatam.
- i18n foundation: ui_language setting + SetLocale middleware; chrome pehle, module pages batadreej.

## D-012 — Full-site i18n pass: per-cluster lang files + unified locale source (2026-10-01)
- Decision: poori site ki static text translation keys par. Har cluster ki apni dictionary: `lang/{en,ur}/forecourt.php` (511 keys), `sales.php` (1033), `finance.php` (566), `admin.php` (715), `ui.php` (114, incl. naye `auth` keys login ke liye). Kul **2,939 keys/locale**, har file ka EN/UR key set script se proven identical; 122 views migrate; print/PDF/email templates aur JS strings jaan boojh kar untouched.
- Launcher unification: AppLauncher ka purana session-only $lang toggle ab ek hi source of truth par hai — SetLocale middleware session override ko setting par tarjeeh deta hai; launcher ka toggle admins ke liye `ui_language` setting bhi update karta hai.
- Gotcha (recorded): PHP block comments me `lang/*/ui.php` jaisa text `*/` sequence bana kar comment ko waqt se pehle band kar deta hai aur file ko parse-error bana deta hai — sales.php ke headers me ye bug pakra gaya aur commit se pehle fix kiya. Lang file headers me kabhi `*/` wali string na likhi jaye.
- Validation: key-parity + used-key existence (2,855 static keys, 3 dynamic-prefix usages manually verified) + blade div balance 167 views par 0 mismatch. PHP runtime smoke test deploy ke baad hi mumkin hai (environment me PHP nahi).

## D-013 — Print/FBR chain asal wiring + market match audit (2026-10-01)
- Context: Global/Pakistani market research (deliverables/global-petrol-pump-systems-research.md) ke baad print audit ne sabit kiya: Thermal/A4 ke simple prints thay lekin **FBR combos (IRN+QR) poori tarah missing thay** — `fiscalise()` aur `createFromSale()` ke zero callers thay, Invoice records bante hi nahi thay, designer snapshot phantom columns par tha, designer QR nakli placeholder tha, print settings dead thay, settings UI ka FBR toggle ghalat key (`tax.fbr_enabled`) par tha jabke canonical `tax.fbr_invoicing_enabled` hai.
- Decision/Fix: Har completed sale par `SaleService::ensureSalesDocuments()` (transaction ke baad, idempotent, failure par sale rollback nahi) → `InvoiceService::invoiceForSale()` → FBR enabled ho to `FbrInvoiceService::fiscalise()` (status **PENDING** — transmission licensed integrator/PRAL ka kaam hai; system kabhi jhoota SUBMITTED/ACCEPTED nahi likhta). Chaaron combos ab asal hain: Thermal (58/80mm, setting-driven) aur A4 (browser + PDF template), fiscalised par fiscal number + asal FBR QR (simple-qrcode), warna saada INVOICE label — baghair fiscal number ke "OFFICIAL GENUINE TAX INVOICE" ka dawa print views se khatam. Designer snapshot canonical schema par; nakli QR khatam. Payment methods me RAAST aur FLEET_CARD (OMC) add.
- Gotcha: number-type settings DB se `"1.0000"` string ati hain — toggles hamesha numeric compare se parho, `=== '1'` kabhi nahi.
- Baqi rehne wale (flagged): InvoiceService invoice total me Rs.1 POS fee add karta hai jo sale total me nahi (pre-existing semantics — product decision chahiye); `InvoiceDesignerService::exportPdf()` sirf filename deta hai.

## D-014 — Email automation (reports + bills), per-bill FBR choice, customer quick-add, final sweep (2026-10-01)
- **Reports auto-email:** Scheduled report generation (console path) ab `ReportDeliveryService` se owner ko email jati hai — recipients setting `report_email_recipients` (fallback: company email), toggle `report_send_via_email`, attachments PDF (DomPDF) + Excel (maatwebsite) ek hi `ReportExportService` se jo manual downloads (`reports.download.pdf/.excel`) ko bhi power karta hai. Cadence guard: har report sirf apni period muddat ke baad dobara email hoti hai (hourly generation par spam nahi). Admin page par **custom interval (hours)** period bhi add — is ke liye `auto_reports.period` ENUM me `'custom'` migration lazmi hai. Email me FBR section asal `fbr_invoices` data se (count + total) aata hai.
- **SMTP:** `MailSettingsService::apply()` DB email settings ko runtime mail config par lagata hai; configure na ho to sends log + skip — sale/report kabhi nahi rukti.
- **Per-bill FBR/Simple:** `sales.bill_type` (migration, default simple) + POS wizard/cashier par segmented select (default global setting se). Fiscalise ab sirf `bill_type=fbr` par hota hai — per-bill choice global par jeet ti hai; Invoice har surat banta hai.
- **Customer on bill:** POS par autocomplete search endpoint (`pos.customers.search`, branch-scoped) + quick-add (`pos.customers.quick-store`, CUST-### code, email samet) — email mojooda customers.email column (003200) hi istemal hota hai.
- **Bill auto-email:** `BillEmailService::sendForSale()` (kabhi throw nahi) — email-bill checkbox on ho aur customer ka email ho to A4 PDF (DomPDF output) attached email; `invoices.email` resend route + dono show pages par button.
- **Final sweep fixes:** Reports hub 500 thi (24 dead route names — ab controller ki `$grouped` registry se dynamic render); `Cheque` model ke phantom constants/fillable migration 007600 se align (cheque receive/issue runtime fail hota); phantom `App\Models\NumberSequence` ke LIVE calls canonical `NumberSequenceService` par delegate (internal-consumption → journal fatal ruk gaya); `InvoiceDesignerService::exportPdf()` asal PDF banata hai; `DailyClosingCommand` ka namespace import theek. Sweep totals: 288 route targets, 131 view refs, 578 constant refs — sab 0 missing.
- Deploy note: is commit ke saath `php artisan migrate` LAZMI hai (2 migrations).

## D-015 — Final match: DigiKhata khata + PWA app-shell + paperless operations + intelligence (2026-10-01)
- Context: User ki final demand — DigiKhata se customer/udhaar/notification flows borrow karo (bill apna hi rahe), paperless mukammal karo, mobile = mobile-app feel / desktop = desktop-app feel, taake Fable 5 ke system se aage nikla jaye. Research: DigiKhata flow analysis + paperless/app-feel checklist (deliverables/paperless-and-app-feel-research.md) + 18-item gap audit (sirf 1 item pehle se mukammal tha).
- **K1 Khata (DigiKhata-style):** Collection/Wasooli screen (`customers.collection`) — due-sorted list, FIFO ageing chips, bulk SMS (SmsService, asal sent/failed counts), bulk WhatsApp modal (browser limit ki imandari), tiles (Kul Wasooli + Overdue count). Customers index par "Kul Dene Hain (Advance)" tile + har row Call/WhatsApp. Khata show page rework: sticky header (count-up balance + credit progress), date-grouped timeline ledger (type chips, running balance), sticky action bar (Payment Mili / New Bill / Call / WhatsApp). **Public signed statement link** (`signed` middleware, 30-day expiry, guest page, station identity SettingService se) — DigiKhata ke paas ye hai hi nahi, ye hamari lead hai. Payment par customer SMS (setting `sms.notify_customer_on_payment`, default OFF). Customer documents (CNIC/NTN attachments, migration + model). POS preselect hook parent ne lagaya: `/pos?customer_id=` deep-link ab asal me customer select karta hai (CREDIT tab + pickCustomer).
- **K2 App shell:** PWA mukammal — asal PNG icons (192/512/maskable/apple), manifest (shortcuts: POS/Gauges/Khata), service worker (shell cache-first, **financial data hamesha network-only**), offline page, install prompt (Android prompt + iOS hint). Global mobile bottom nav har page par (lg:hidden, safe-area, permission-gated). **Ctrl+K command palette** — pages SidebarNav se + entities (customers/invoices/vehicles) naye `GlobalSearchController` se (branch-scoped, permission-gated, har section top 6). Alt+1..5 shortcuts + `?` help modal. Asset constraint ki pabandi: `@vite`/`resources/js` untouched — saara naya JS Blade partials me inline, assets `public/` me.
- **K3 Paperless operations:** Shift **handover sign-off** — nayi shift tab tak nahi khulti jab tak pichli band shift ka handover count kiye hue cash + PIN (`User::verifyPin()`) se accept na ho; deadlock se bachne ke liye guard sirf latest band shift par hai, apni shift khud accept nahi ho sakti, cash farq par acceptance rukti nahi — audit + managers ko variance notification. **Void PIN gate** — Rs. 5,000+ void par manager PIN (setting `void_manager_pin`: 1=threshold, always, 0=off; PIN holder hi na ho to gate skip + audit note — station band nahi hota). Meter reading **photo** (`capture="environment"`, purchases ke bill_photo pattern par). Tank readings me **temperature/density** (variance logic untouched).
- **K4 Intelligence:** Schedule me `erp:sync-notifications` (08:00) + naya `documents:scan-expiry` (08:05) register — pehle command mojood tha lekin scheduled hi nahi tha. Cheque due/overdue alerts (canonical Cheque constants — model me PENDING status hai hi nahi, RECEIVED/DEPOSITED + ISSUED/PRESENTED hi pending hain). Document expiry 30/7/0-day escalating tiers (notifyOnce, tier-wise dedupe). **Fleet Settlement report** (registry slug, OMC/reference grouping, `sale_payments.settled_at` migration, mark-settled `reports.export` permission ke peeche, append-only). **Cash-flow forecast widget** dashboard par — agle 30 din ke 4 weekly buckets, sirf asal data (receivables, cheques dono taraf, aakhri payroll month ka salary estimate), gradient-bars chart motion-kit contract par. Sales show par WhatsApp share (POS success wala hi pattern).
- **Lang:** 4 teams ne apne groups append kiye — final parity script-verified: ui 137 / forecourt 531 / sales 1104 / finance 607 / admin 715 = **3,094 keys/locale, EN=UR identical**. Blade balance: 177 views, 0 mismatch. Bootstrap diff: sirf 2 schedule lines.
- Deploy note: `php artisan migrate` LAZMI (5 migrations: handover, settled_at, customer_documents, meter photo, tank temp/density) + view/config/route clear. Schedule ke liye server cron `schedule:run` pehle se chahiye.
- Imandari wale baqi items: offline POS ab bhi nahi (server-based system — Fable bhi nahi kar sakta baghair local app ke); ATG/dispenser hardware integration hardware-bound hai; FBR transmission PRAL integrator par rehti hai (D-013).

## D-016 — Standing policy: security OTPs sirf email par, kabhi SMS par nahi (2026-10-01)
- Context: User ki wazeh hidayat — "SMS nahi, sab zaroori OTP email par aane chahiye."
- Audit ka natija: system me **koi OTP flow hai hi nahi** — na login OTP, na password-reset OTP, na approval OTP. Password reset pehle se Laravel ke email reset-link par hai. SMS sirf 4 jagah istemal hota hai, sab opt-in: admin alert subscriptions (default SMS off), daily closing SMS (setting-gated), Collection screen ke customer reminders (button dabane par), payment SMS (default off). In me se koi security code nahi hai.
- Enforcement (code me, sirf baat nahi): `SmsService::send($phone, $message, $category)` me naya `$category` param (`CATEGORY_GENERAL` default — mojooda callers unchanged); `CATEGORY_SECURITY` wali call **refuse** (log + false). `NotificationService` me `SECURITY_TYPES` list (SECURITY_OTP, LOGIN_CODE, PASSWORD_RESET_CODE, APPROVAL_CODE — abhi koi mojood nahi, future OTP/2FA ke liye reserved): `sendToAdmins()` me security type par email force hoti hai aur SMS skip; `sendSms()` bhi security type refuse karta hai (defense-in-depth).
- Settings → Notifications ke Delivery Methods section me bilingual note: "Security one-time codes (OTP) are always sent by email — never by SMS." (lang key `admin.settings_page.otp_email_only_note`, EN/UR).
- Email channel pehle se tayyar hai (D-014: MailSettingsService SMTP ko runtime par lagata hai). Jab kabhi OTP/2FA feature banega to wo isi email channel par banega — SMS par nahi.
