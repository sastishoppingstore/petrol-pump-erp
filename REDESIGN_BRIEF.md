# REDESIGN BRIEF — Petrol Pump ERP (har page ke liye ek hi standard)

Repo: `~/workspace/petrol-pump-erp` (Laravel 12 + Blade + Livewire + Alpine + Tailwind).
Ghair-mufeed files (app.css, layouts, dashboard, fuels, nozzles, login) pehle hi ho chuki hain — **unhe mat chhuo**.

## Design system (app.css me pehle se mojood hai — yahi classes use karo)
- `.glass-card` — frosted glass panel (rounded-2xl, soft 3D shadow)
- `.card-3d` — hover lift wali 3D card (glass-card ke saath lagao)
- `.btn-3d` + `.btn-3d-primary` (brand red) / `.btn-3d-success` / `.btn-3d-amber` / `.btn-3d-navy` / `.btn-3d-ghost`, size `.btn-3d-sm`
- `.fab-3d` — fixed bottom-right floating action button (page ka primary action, e.g. Add/Create)
- `.input-3d` — elevated input/select/textarea; har field ko `.field-3d` wrapper me rakho (autocomplete fix)
- `.pill-status` + `.pill-active` / `.pill-inactive` (+ andar `<span class="dot">`) — status ke liye
- `.badge-fuel-petrol|diesel|octane|other` — fuel type gradient badges
- `.stat-tile-3d`, `.page-head`, `.table-3d` — naye helpers (neeche dekho)
- `.modal-bounce` — modal entrance animation; `[x-cloak]` global rule mojood hai
- Brand colors Tailwind classes: `bg-vital-primary`, `text-vital-primary`, `bg-vital-darkred`, `from-vital-primary`, `shadow-glow`, `shadow-3d`, `shadow-fab` — ye admin Settings ke rangon se chalte hain, hardcode hex mat likho.

## Har page ka lazmi pattern
1. **Page head (centered):** `<div class="page-head"><h1>…</h1><p>…subtitle/count…</p></div>` — title hamesha CENTER.
2. **Content boxes:** har section ek `glass-card` (ya `glass-card card-3d`) box me; page par koi hissa baghair box ke khula na pare.
3. **Tables:** `.table-3d` wrapper ke andar; tamam cells CENTER aligned (user ki farmaish); numbers `tabular` class ke saath; header row uppercase chhota; row hover.
4. **Cards grid (index pages):** jahan mumkin ho list ke saath ya uski jagah 3D cards grid (`grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4`), card ka text center.
5. **Forms (create/edit):** centered card `max-w-3xl mx-auto glass-card p-6`; label input ke UPAR aur CENTER; fields `.field-3d` + `.input-3d`; submit/cancel buttons centered row me `.btn-3d`.
6. **Primary action:** header me button ke ilawa `.fab-3d` bhi (permission gate ke saath).
7. **Modals:** kabhi bhi table ke andar nahi; page ke end par, Alpine `x-data` state se; backdrop click + Esc se band.
8. **Empty states:** glass-card ke andar centered icon + text + action.

## Sakht rules (AGENTS.md)
- **Koi dummy button/link nahi.** Har route, form action, method, field name, `@can`/`@canany` permission, `@csrf`, Livewire attribute (`wire:*`), Alpine handler, aur JS hook pehle jaisa hi rakho — sirf markup/classes badlo.
- Business logic Blade me mat daalo; jo expressions pehle se view me hain (number_format, status checks) wahi reuse karo.
- Print views (invoice a4/thermal, payslip, statement_pdf, cash/print, verify) aur emails ko 3D mat karo — sirf halki safai (centered heading) agar bina risk ke mumkin ho, warna chhor do.
- Bootstrap classes (btn, table, form-control, erp-card, modal…) naye likhe gaye pages me **bilkul nahi** — sirf Tailwind + upar wali design classes.
- Har file ke akhir me div balance check karo: `python3 -c` se `<div` vs `</div>` count barabar ho (comments me literal `<div` mat likho).
- Urdu/English labels jo pehle se hain wahi rakho; naye labels dono zubanon ke existing andaaz me.

## Misal (reference ke liye tayyar pages)
- `resources/views/fuels/index.blade.php` — cards + table + FAB pattern
- `resources/views/nozzles/index.blade.php` — table + mobile cards + Alpine modal pattern
- `resources/views/auth/login.blade.php` — glass look
