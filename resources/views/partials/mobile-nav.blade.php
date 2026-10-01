{{--
    Mobile bottom navigation (app shell) — sirf mobile/tablet par (lg:hidden).
    5 jagah: Dashboard, Bill (POS), Khata (Customers), Tanks (Gauges), More
    (More sidebar kholta hai — wahi `sidebar-open` class jo app.js ka
    hamburger toggle istemal karta hai). Desktop par sidebar hi kaafi hai.
    Print par .no-print + neeche wale style se hamesha hidden.
--}}
<style>
    .mobile-bottom-nav { padding-bottom: env(safe-area-inset-bottom, 0px); box-shadow: 0 -6px 24px rgba(2, 6, 23, 0.12); }
    @media print { .mobile-bottom-nav { display: none !important; } }
</style>
<nav class="mobile-bottom-nav no-print fixed inset-x-0 bottom-0 z-40 border-t border-slate-200/80 bg-white/90 backdrop-blur-md lg:hidden dark:border-slate-800 dark:bg-slate-900/90"
     aria-label="Mobile">
    <div class="grid grid-cols-5">
        <a href="{{ route('dashboard') }}"
           @class([
               'flex flex-col items-center gap-0.5 px-1 py-2 text-[11px] font-semibold no-underline transition',
               'text-vital-primary' => request()->routeIs('dashboard'),
               'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100' => ! request()->routeIs('dashboard'),
           ])>
            <span class="text-xl leading-none" aria-hidden="true">🏠</span>
            <span>{{ __('ui.nav.dashboard') }}</span>
        </a>

        @if (auth()->user()->hasPermission(\App\Support\PermissionList::SALES_CREATE))
            <a href="{{ route('pos.index') }}"
               @class([
                   'flex flex-col items-center gap-0.5 px-1 py-2 text-[11px] font-semibold no-underline transition',
                   'text-vital-primary' => request()->routeIs('pos.*') || request()->routeIs('cashier.*'),
                   'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100' => ! (request()->routeIs('pos.*') || request()->routeIs('cashier.*')),
               ])>
                <span class="text-xl leading-none" aria-hidden="true">🧾</span>
                <span>{{ __('ui.palette.bill') }}</span>
            </a>
        @else
            <span class="flex flex-col items-center gap-0.5 px-1 py-2 text-[11px] font-semibold text-slate-300 dark:text-slate-600" aria-hidden="true">
                <span class="text-xl leading-none">🧾</span>
                <span>{{ __('ui.palette.bill') }}</span>
            </span>
        @endif

        @if (auth()->user()->hasPermission(\App\Support\PermissionList::CUSTOMER_VIEW))
            <a href="{{ route('customers.index') }}"
               @class([
                   'flex flex-col items-center gap-0.5 px-1 py-2 text-[11px] font-semibold no-underline transition',
                   'text-vital-primary' => request()->routeIs('customers.*'),
                   'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100' => ! request()->routeIs('customers.*'),
               ])>
                <span class="text-xl leading-none" aria-hidden="true">👥</span>
                <span>{{ __('ui.palette.khata') }}</span>
            </a>
        @else
            <span class="flex flex-col items-center gap-0.5 px-1 py-2 text-[11px] font-semibold text-slate-300 dark:text-slate-600" aria-hidden="true">
                <span class="text-xl leading-none">👥</span>
                <span>{{ __('ui.palette.khata') }}</span>
            </span>
        @endif

        @if (auth()->user()->hasPermission(\App\Support\PermissionList::FUEL_VIEW))
            <a href="{{ route('gauges.index') }}"
               @class([
                   'flex flex-col items-center gap-0.5 px-1 py-2 text-[11px] font-semibold no-underline transition',
                   'text-vital-primary' => request()->routeIs('gauges.*'),
                   'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100' => ! request()->routeIs('gauges.*'),
               ])>
                <span class="text-xl leading-none" aria-hidden="true">⛽</span>
                <span>{{ __('ui.palette.tanks') }}</span>
            </a>
        @else
            <span class="flex flex-col items-center gap-0.5 px-1 py-2 text-[11px] font-semibold text-slate-300 dark:text-slate-600" aria-hidden="true">
                <span class="text-xl leading-none">⛽</span>
                <span>{{ __('ui.palette.tanks') }}</span>
            </span>
        @endif

        <button type="button" id="mobile-nav-more"
                class="flex flex-col items-center gap-0.5 px-1 py-2 text-[11px] font-semibold text-slate-500 transition hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100"
                aria-label="{{ __('ui.palette.more') }}">
            <span class="text-xl leading-none" aria-hidden="true">☰</span>
            <span>{{ __('ui.palette.more') }}</span>
        </button>
    </div>
</nav>
<script>
    // More = sidebar kholo/band karo (app.js wali hi `sidebar-open` class).
    (function () {
        var btn = document.getElementById('mobile-nav-more');
        if (!btn) return;
        btn.addEventListener('click', function () {
            document.documentElement.classList.toggle('sidebar-open');
        });
    })();
</script>
