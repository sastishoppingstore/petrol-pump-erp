{{--
    Keyboard shortcuts help modal — "?" (Shift+/) dabane par khulta hai.
    Kholna/band karna command-palette partial ki global key handler karti hai
    (window.MFSShortcutsHelp.open/close). Print par hidden.
--}}
<div id="shortcuts-help-modal" class="no-print fixed inset-0 z-50 hidden items-start justify-center px-4 pt-24" role="dialog" aria-modal="true" aria-label="{{ __('ui.palette.shortcuts_title') }}">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" data-shortcuts-close></div>
    <div class="glass-card card-3d relative w-full max-w-md p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-black text-slate-800 dark:text-white">⌨️ {{ __('ui.palette.shortcuts_title') }}</h2>
            <button type="button" data-shortcuts-close class="btn-3d btn-3d-ghost btn-3d-sm" aria-label="✕">✕</button>
        </div>
        <ul class="space-y-2.5 text-sm text-slate-600 dark:text-slate-300">
            <li class="flex items-center justify-between gap-3">
                <span>{{ __('ui.palette.sc_palette') }}</span>
                <span><kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">Ctrl</kbd> + <kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">K</kbd></span>
            </li>
            <li class="flex items-center justify-between gap-3">
                <span>{{ __('ui.palette.sc_dashboard') }}</span>
                <span><kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">Alt</kbd> + <kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">1</kbd></span>
            </li>
            <li class="flex items-center justify-between gap-3">
                <span>{{ __('ui.palette.sc_pos') }}</span>
                <span><kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">Alt</kbd> + <kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">2</kbd></span>
            </li>
            <li class="flex items-center justify-between gap-3">
                <span>{{ __('ui.palette.sc_customers') }}</span>
                <span><kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">Alt</kbd> + <kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">3</kbd></span>
            </li>
            <li class="flex items-center justify-between gap-3">
                <span>{{ __('ui.palette.sc_gauges') }}</span>
                <span><kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">Alt</kbd> + <kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">4</kbd></span>
            </li>
            <li class="flex items-center justify-between gap-3">
                <span>{{ __('ui.palette.sc_reports') }}</span>
                <span><kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">Alt</kbd> + <kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">5</kbd></span>
            </li>
            <li class="flex items-center justify-between gap-3">
                <span>{{ __('ui.palette.sc_help') }}</span>
                <span><kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">?</kbd></span>
            </li>
            <li class="flex items-center justify-between gap-3">
                <span>{{ __('ui.palette.sc_close') }}</span>
                <span><kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">Esc</kbd></span>
            </li>
        </ul>
    </div>
</div>
<script>
    window.MFSShortcutsHelp = (function () {
        var modal = document.getElementById('shortcuts-help-modal');
        function open() {
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function close() {
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        function isOpen() { return modal && !modal.classList.contains('hidden'); }
        if (modal) {
            modal.querySelectorAll('[data-shortcuts-close]').forEach(function (el) {
                el.addEventListener('click', close);
            });
        }
        return { open: open, close: close, isOpen: isOpen };
    })();
</script>
