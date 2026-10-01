{{--
    Command Palette (Ctrl+K) — pages (SidebarNav, server-side permission
    filtered) + entities (customers / invoices / vehicles, GlobalSearchController
    se live). Recents localStorage me. Global shortcuts (Alt+1..5, "?") bhi
    isi partial ki script handle karti hai.
--}}
@php
    $palettePages = [];
    $paletteSection = null;
    foreach (\App\Support\SidebarNav::itemsFor(auth()->user()) as $paletteItem) {
        if ($paletteItem['type'] === 'section') {
            $paletteSection = $paletteItem['label'];
            continue;
        }
        if (($paletteItem['permission'] ?? null) !== null && ! auth()->user()->hasPermission($paletteItem['permission'])) {
            continue;
        }
        if (empty($paletteItem['route']) || ! \Illuminate\Support\Facades\Route::has($paletteItem['route'])) {
            continue;
        }
        $palettePages[] = [
            'label' => $paletteItem['label'],
            'icon' => $paletteItem['icon'] ?? '📄',
            'section' => $paletteSection,
            'url' => route($paletteItem['route']),
        ];
    }
    if (auth()->user()->hasPermission(\App\Support\PermissionList::CUSTOMER_VIEW) && \Illuminate\Support\Facades\Route::has('amanat.index')) {
        $palettePages[] = ['label' => __('ui.nav.amanat_deposits'), 'icon' => '💳', 'section' => null, 'url' => route('amanat.index')];
    }
    if (auth()->user()->hasPermission(\App\Support\PermissionList::SETTINGS_VIEW) && \Illuminate\Support\Facades\Route::has('documents.index')) {
        $palettePages[] = ['label' => __('ui.nav.document_vault'), 'icon' => '🗂️', 'section' => null, 'url' => route('documents.index')];
    }

    $paletteNavUrls = [
        '1' => \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : null,
        '2' => \Illuminate\Support\Facades\Route::has('pos.index') ? route('pos.index') : null,
        '3' => \Illuminate\Support\Facades\Route::has('customers.index') ? route('customers.index') : null,
        '4' => \Illuminate\Support\Facades\Route::has('gauges.index') ? route('gauges.index') : null,
        '5' => \Illuminate\Support\Facades\Route::has('reports.index') ? route('reports.index') : null,
    ];
@endphp

{{-- Topbar trigger: desktop par label + Ctrl+K hint, mobile par sirf icon --}}
<button type="button" id="palette-open-btn"
        class="flex items-center gap-2 rounded-md border border-slate-300 px-2.5 py-1.5 text-sm text-slate-500 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700"
        title="{{ __('ui.palette.search') }} (Ctrl+K)" aria-label="{{ __('ui.palette.search') }}">
    <span aria-hidden="true">🔍</span>
    <span class="hidden md:inline">{{ __('ui.palette.search') }}</span>
    <kbd class="hidden items-center gap-0.5 rounded border border-slate-300 bg-slate-50 px-1.5 font-mono text-[10px] font-bold text-slate-400 lg:inline-flex dark:border-slate-600 dark:bg-slate-900">Ctrl K</kbd>
</button>

{{-- Palette overlay --}}
<div id="command-palette" class="no-print fixed inset-0 z-50 hidden items-start justify-center px-4 pt-20 sm:pt-28" role="dialog" aria-modal="true" aria-label="{{ __('ui.palette.search') }}">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" data-palette-close></div>
    <div class="glass-card card-3d relative flex max-h-[70vh] w-full max-w-xl flex-col overflow-hidden">
        <div class="flex items-center gap-2 border-b border-slate-200/70 px-4 py-3 dark:border-slate-700/70">
            <span class="text-lg" aria-hidden="true">🔍</span>
            <input id="palette-input" type="text" autocomplete="off" spellcheck="false"
                   class="w-full bg-transparent text-base text-slate-800 outline-none placeholder:text-slate-400 dark:text-slate-100"
                   placeholder="{{ __('ui.palette.placeholder') }}">
            <kbd class="rounded border border-slate-300 px-1.5 font-mono text-[10px] font-bold text-slate-400 dark:border-slate-600">ESC</kbd>
        </div>
        <div id="palette-results" class="overflow-y-auto p-2"></div>
        <div class="flex items-center gap-4 border-t border-slate-200/70 px-4 py-2 text-[11px] text-slate-400 dark:border-slate-700/70">
            <span><kbd class="font-mono font-bold">↑↓</kbd> navigate</span>
            <span><kbd class="font-mono font-bold">↵</kbd> open</span>
            <span><kbd class="font-mono font-bold">esc</kbd> close</span>
        </div>
    </div>
</div>

<script>
(function () {
    var PAGES = @json($palettePages);
    var NAV_URLS = @json($paletteNavUrls);
    var SEARCH_URL = @json(route('search.global'));
    var LABELS = {
        pages: @json(__('ui.palette.pages')),
        customers: @json(__('ui.palette.customers')),
        invoices: @json(__('ui.palette.invoices')),
        vehicles: @json(__('ui.palette.vehicles')),
        recents: @json(__('ui.palette.recents')),
        noResults: @json(__('ui.palette.no_results'))
    };
    var RECENTS_KEY = 'mfs_palette_recents';

    var overlay = document.getElementById('command-palette');
    var input = document.getElementById('palette-input');
    var results = document.getElementById('palette-results');
    var openBtn = document.getElementById('palette-open-btn');
    if (!overlay || !input || !results) return;

    var flatItems = [];   // {label, subtitle, icon, url}
    var activeIndex = -1;
    var debounceTimer = null;
    var fetchSeq = 0;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function isOpen() { return !overlay.classList.contains('hidden'); }

    function open() {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        input.value = '';
        renderHome();
        setTimeout(function () { input.focus(); }, 30);
    }

    function close() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
    }

    function toggle() { isOpen() ? close() : open(); }

    function loadRecents() {
        try {
            var raw = JSON.parse(localStorage.getItem(RECENTS_KEY) || '[]');
            return Array.isArray(raw) ? raw.filter(function (r) { return r && r.url && r.label; }).slice(0, 6) : [];
        } catch (e) { return []; }
    }

    function saveRecent(item) {
        try {
            var list = loadRecents().filter(function (r) { return r.url !== item.url; });
            list.unshift({ label: item.label, icon: item.icon || '📄', url: item.url });
            localStorage.setItem(RECENTS_KEY, JSON.stringify(list.slice(0, 6)));
        } catch (e) { /* private mode waghera — recent na bane to koi baat nahi */ }
    }

    function groupHtml(title, items) {
        if (!items.length) return '';
        var html = '<div class="px-2 pb-1 pt-3 text-[10px] font-black uppercase tracking-widest text-slate-400">' + esc(title) + '</div>';
        items.forEach(function (item) {
            var idx = flatItems.length;
            flatItems.push(item);
            html += '<button type="button" data-palette-idx="' + idx + '" class="palette-row flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800">'
                + '<span class="text-lg leading-none" aria-hidden="true">' + esc(item.icon || '📄') + '</span>'
                + '<span class="min-w-0 flex-1">'
                + '<span class="block truncate font-semibold text-slate-700 dark:text-slate-100">' + esc(item.label) + '</span>'
                + (item.subtitle ? '<span class="block truncate text-xs text-slate-400">' + esc(item.subtitle) + '</span>' : '')
                + '</span></button>';
        });
        return html;
    }

    function paintActive() {
        results.querySelectorAll('[data-palette-idx]').forEach(function (row) {
            var on = parseInt(row.getAttribute('data-palette-idx'), 10) === activeIndex;
            row.classList.toggle('bg-slate-100', on);
            row.classList.toggle('dark:bg-slate-800', on);
            if (on) row.scrollIntoView({ block: 'nearest' });
        });
    }

    function bindRows() {
        results.querySelectorAll('[data-palette-idx]').forEach(function (row) {
            row.addEventListener('click', function () {
                go(flatItems[parseInt(row.getAttribute('data-palette-idx'), 10)]);
            });
            row.addEventListener('mousemove', function () {
                activeIndex = parseInt(row.getAttribute('data-palette-idx'), 10);
                paintActive();
            });
        });
        activeIndex = flatItems.length ? 0 : -1;
        paintActive();
    }

    function go(item) {
        if (!item || !item.url) return;
        saveRecent(item);
        window.location.href = item.url;
    }

    function renderHome() {
        flatItems = [];
        var html = '';
        var recents = loadRecents();
        if (recents.length) html += groupHtml(LABELS.recents, recents);
        html += groupHtml(LABELS.pages, PAGES);
        results.innerHTML = html || '<div class="px-3 py-6 text-center text-sm text-slate-400">' + esc(LABELS.noResults) + '</div>';
        bindRows();
    }

    function renderSearch(q, entities) {
        flatItems = [];
        var needle = q.toLowerCase();
        var pages = PAGES.filter(function (p) { return p.label.toLowerCase().indexOf(needle) !== -1; }).slice(0, 8);
        var html = '';
        html += groupHtml(LABELS.pages, pages);
        if (entities) {
            html += groupHtml(LABELS.customers, entities.customers || []);
            html += groupHtml(LABELS.invoices, entities.invoices || []);
            html += groupHtml(LABELS.vehicles, entities.vehicles || []);
        }
        results.innerHTML = html || '<div class="px-3 py-6 text-center text-sm text-slate-400">' + esc(LABELS.noResults) + '</div>';
        bindRows();
    }

    function onQuery() {
        var q = input.value.trim();
        if (q.length < 2) { renderHome(); return; }
        renderSearch(q, null); // pages foran, entities fetch ke baad
        var seq = ++fetchSeq;
        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (r) { return r.ok ? r.json() : null; })
          .then(function (data) {
              if (seq !== fetchSeq || !isOpen()) return;
              if (input.value.trim() !== q) return;
              renderSearch(q, data || {});
          })
          .catch(function () { /* network fail — pages wale results hi kaafi hain */ });
    }

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(onQuery, 180);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (flatItems.length) { activeIndex = (activeIndex + 1) % flatItems.length; paintActive(); }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (flatItems.length) { activeIndex = (activeIndex - 1 + flatItems.length) % flatItems.length; paintActive(); }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            go(flatItems[activeIndex]);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            close();
        }
    });

    if (openBtn) openBtn.addEventListener('click', open);
    overlay.querySelectorAll('[data-palette-close]').forEach(function (el) {
        el.addEventListener('click', close);
    });

    function isTypingTarget(el) {
        if (!el) return false;
        var tag = (el.tagName || '').toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable === true;
    }

    // Global shortcuts: Ctrl/Cmd+K (hamesha), Esc, Alt+1..5, "?" help.
    document.addEventListener('keydown', function (e) {
        var key = e.key || '';

        if ((e.ctrlKey || e.metaKey) && key.toLowerCase() === 'k') {
            e.preventDefault();
            toggle();
            return;
        }

        if (key === 'Escape') {
            if (isOpen()) { close(); return; }
            if (window.MFSShortcutsHelp && window.MFSShortcutsHelp.isOpen()) {
                window.MFSShortcutsHelp.close();
            }
            return;
        }

        if (isOpen()) return;
        if (isTypingTarget(e.target)) return;

        if (e.altKey && NAV_URLS[key]) {
            e.preventDefault();
            window.location.href = NAV_URLS[key];
            return;
        }

        if (key === '?' && window.MFSShortcutsHelp) {
            e.preventDefault();
            window.MFSShortcutsHelp.open();
        }
    });

    window.MFSPalette = { open: open, close: close, toggle: toggle };
})();
</script>
