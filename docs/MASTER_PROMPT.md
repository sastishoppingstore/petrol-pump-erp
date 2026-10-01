# MASTER PROMPT — Pakistan ka State-of-the-Art Petrol Pump ERP

> Ye document is project ka master brief hai. Har agent/contributor kaam
> shuru karne se pehle ye parhe. Research (1 Oct 2026) par mabni:
> `~/workspace/deliverables/pakistan-petrol-pump-market-research.md` aur
> `~/workspace/deliverables/frontend-motion-research.md`.

## 1. Vision

Ek mukammal **Petrol Pump Management System** — sirf ERP nahi — jis se
pora pump paperless control ho: forecourt se accounts tak, OGRA/FBR
compliance samet. Standard: Pakistan ka top system. Mobile par khule to
mobile app lage, desktop par khule to full desktop command center lage.

## 2. Sakht Usool (kabhi nahi tootenge)

1. **Stack: sirf Laravel / PHP.** Blade views, koi React/Vue SPA nahi,
   koi doosra backend stack nahi. Frontend assets pehle se build karke
   `public/build` me commit hote hain — StackCP deploy = files update +
   `php artisan view:clear`. Server par Node nahi chalega.
2. **Koi dummy button, fake number ya demo data nahi.** Har visible
   action asli backend functionality se jura ho. Data hamesha
   database/models se aaye.
3. **Routes, form fields, CSRF, permissions, JS/Livewire/Alpine hooks
   preserve** karo. Financial/stock/audit history append-only hai.
4. Business logic Service classes me, Blade me nahi.
5. **Zero-error policy:** Blade/div balance, PHP syntax, route names —
   sab validate karke hi commit hoga.

## 3. Design & Motion System ("film chal rahi ho")

- Design system: `resources/css/app.css` — 3D glass components
  (`.glass-card`, `.table-3d`, `.stat-tile-3d`, `.btn-3d`, `.fab-3d`,
  `.chart-card-3d`, `.page-head` centered).
- Motion engine: **GSAP** (free) + **Chart.js** (repo me hai) +
  desktop-only halki **Three.js** particles. Entry point:
  `resources/js/dashboard-motion.js`.
- Data contract: PHP sirf data deta hai —
  `<canvas data-chart='@json($data)' data-chart-type="film-line">`,
  `<span data-countup="1234" data-prefix="₨ ">`. JS me hardcoded
  numbers kabhi nahi.
- Chart presets: `film-line` (progressive draw-on), `gradient-bars`
  (staggered rise), `donut-rotate` (rotate-only + center total).
- Performance budget: mobile par chart animation ≤ ~1.2s, Three.js
  bilkul nahi; desktop par full cinematic; `prefers-reduced-motion`
  hamesha respect.

## 4. Market Research — 15 Must-Have Modules (status)

| # | Module | Status |
|---|--------|--------|
| M1 | Forecourt / nozzle master + totalizer | ✅ Mojood |
| M2 | Shift open/close/handover + lock | ✅ Mojood |
| M3 | Wet stock / tank dipping + reconciliation | ✅ Mojood |
| M4 | Tank lorry receipt / decantation shortfall | ✅ Mojood (Purchases) |
| M5 | Rate management — OGRA **daily** prices (17 Jul 2026 se); `FuelPrice.effective_from/to` | ✅ Mojood |
| M6 | Cash management + bank deposits | ✅ Mojood |
| M7 | Credit / fleet customers + vehicles | ✅ Mojood — **Amanat (prepaid) missing → Phase A** |
| M8 | Lubricants / mart / services (split tax: fuel 0%, lubes 18%) | ✅ Mojood |
| M9 | Expenses | ✅ Mojood |
| M10 | Staff / attendance / payroll | ✅ Mojood |
| M11 | OMC procurement / supplier ledger | ✅ Mojood |
| M12 | Reports — DSR + financial | ✅ Mojood |
| M13 | Alerts / notifications | ✅ Mojood (Notification models) |
| M14 | Audit trail, roles, period locks | ✅ Mojood |
| M15 | Multi-branch | ✅ Mojood |

**Compliance:** OGRA daily inventory reconciliation (Reg 17.2.1),
daily price notifications, ATG mandate (Jan 2027 tak — integration
readiness chahiye), Petroleum (Amendment) Act 2025 track-and-trace,
FBR real-time digital invoicing (IRN + QR; `FbrInvoice` mojood).

## 5. Is Phase ke Tasks

- **T1 Dashboard Cinematic:** Dashboard par film-line (last 30 days
  sales trend), gradient-bars (fuel-wise sales), donut-rotate
  (payment mix ya product mix) — sab **asli data** DashboardController
  se. Count-up KPIs.
- **T2 Pages Motion Pass:** Tamaam pages par GSAP entrance, 3D tilt
  stat tiles, chart cards jahan data charts mojood hon. Sirf
  view-level polish — logic change nahi.
- **T3 Amanat Module:** Customer prepaid deposits — deposit entry,
  fuel sale par auto-deduction ledger, balance report, low-balance
  alert. Customer/Ledger conventions follow karo.
- **T4 Document Vault:** OGRA licence, dealership agreement, NOCs,
  calibration certificates — upload, expiry dates, expiry alerts
  (Notification system se).
- **T5 Loyalty (agar waqt mile):** Points per litre/rupee, redemption
  sale par. Customer module ke saath.
- **T6 Installer:** `public/install.php` ka review — requirements
  check, .env, migrations+seed, admin creation, self-delete + lock,
  khoobsurat UI.
- **T7 ATG Readiness (sirf data layer):** Tank readings ke liye
  `source` field (manual/atg) ki tayyari — hardware integration baad me.

## 6. Quality Gates (har commit se pehle)

1. Blade files: div/directive balance clean.
2. Routes/fields/permissions/hooks preservation audit vs git HEAD.
3. `npm run build` kamyab; `public/build` commit.
4. PHP syntax: jitna ho sake static check (php -l jahan PHP mile).
5. PROGRESS.md update + DECISIONS.md me nayi entries.
