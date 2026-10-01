@extends('layouts.app')

@section('title', 'Auto-Report Settings / خودکار رپورٹس')

@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('reports.index') }}">Reports</a></li>
    <li>/</li>
    <li class="font-semibold">Auto-Report Settings</li>
@endsection

@section('content')
<div class="space-y-6">

    <div class="page-head">
        <h1>⚙️ Auto-Report Settings</h1>
        <p>Kaun si reports khud-ba-khud banein — 12 ghante, 24 ghante, 7 din, 15 din ya 30 din. Scheduler har ghante enabled reports refresh karta hai (خودکار رپورٹس کی ترتیبات)</p>
    </div>

    {{-- Period Toggles --}}
    <div class="glass-card card-3d p-6">
        <h3 class="mb-1 text-center text-lg font-black text-slate-800 dark:text-white">🕐 Report Periods — On / Off</h3>
        <p class="mb-5 text-center text-sm text-slate-500">Jo period ON hoga, uski report har branch ke liye auto-generate hogi. OFF period ki report nahi banegi.</p>

        <form method="POST" action="{{ route('reports.auto-reports.update') }}">
            @csrf

            <div class="space-y-3">
                @foreach ($periods as $period => $config)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/70 bg-white/50 px-5 py-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                        <div>
                            <div class="font-extrabold text-slate-800 dark:text-white">
                                📊 {{ $config['label'] }}
                                <span class="ml-1 text-xs font-semibold text-slate-400">({{ $period }})</span>
                            </div>
                            <div class="mt-0.5 text-xs text-slate-500">
                                @if ($config['last'])
                                    Aakhri report: <strong>{{ $config['last']->branch?->name ?? '—' }}</strong> •
                                    {{ $config['last']->updated_at?->format('M d, Y H:i') }} •
                                    <span class="pill-status {{ $config['enabled'] ? 'pill-active' : '' }}"><span class="dot"></span>{{ $config['last']->status }}</span>
                                @else
                                    <span class="text-amber-600 font-semibold">Abhi tak koi report generate nahi hui</span>
                                @endif
                            </div>
                        </div>

                        <label class="relative inline-block h-8 w-14 shrink-0 cursor-pointer" title="On/Off">
                            <input type="checkbox" name="{{ $config['key'] }}" value="1" class="peer sr-only" @checked($config['enabled'])>
                            <span class="absolute inset-0 rounded-full bg-slate-300 shadow-inner transition-colors duration-300 peer-checked:bg-emerald-500 after:absolute after:left-1 after:top-1 after:h-6 after:w-6 after:rounded-full after:bg-white after:shadow after:transition-transform after:duration-300 peer-checked:after:translate-x-6"></span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/70 bg-white/50 px-5 py-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                <div>
                    <div class="font-extrabold text-slate-800 dark:text-white">📧 Daily Report Send Time</div>
                    <div class="text-xs text-slate-500">Rozana closing report (email/SMS) is waqt bheji jati hai — scheduler daily 23:00 default par chalta hai.</div>
                </div>
                <input type="time" name="report_send_time" value="{{ $sendTime }}" class="input-3d w-36">
            </div>

            <div class="mt-6 text-center">
                <button type="submit" class="btn-3d btn-3d-primary">💾 Settings Save Karein</button>
            </div>
        </form>
    </div>

    {{-- Generate Now --}}
    <div class="glass-card card-3d p-6 text-center">
        <h3 class="mb-1 text-lg font-black text-slate-800 dark:text-white">⚡ Abhi Report Banayein</h3>
        <p class="mx-auto mb-5 max-w-xl text-sm text-slate-500">
            Cron ka intezaar kiye baghair — tamam <strong>enabled</strong> periods ki reports tamam active branches ke liye foran generate/refresh ho jayengi. Dashboard par foran nazar aayengi.
        </p>
        <form method="POST" action="{{ route('reports.auto-reports.generate') }}" onsubmit="return confirm('Enabled periods ki reports abhi generate kar dein?')">
            @csrf
            <button type="submit" class="btn-3d btn-3d-primary btn-3d-lg">🚀 Generate Now</button>
        </form>
        <p class="mt-4 text-xs text-slate-400">
            Server cron lazmi hai: <code class="rounded bg-slate-100 px-1.5 py-0.5 dark:bg-slate-800">* * * * * php artisan schedule:run</code>
            — is ke baghair sirf "Generate Now" se reports banein gi, auto-refresh nahi hoga.
        </p>
    </div>

    {{-- Recent Generated Reports --}}
    <div class="glass-card overflow-hidden">
        <h3 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">🗂️ Aakhri Generated Reports</h3>

        @if ($recentReports->isEmpty())
            <div class="alert alert-info m-6">
                📊 Abhi tak koi auto-report generate nahi hui. Upar wale <strong>Generate Now</strong> button se pehli report bana kar dekhein.
            </div>
        @else
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Branch</th>
                            <th>Period</th>
                            <th>Report Date</th>
                            <th>Total Sales</th>
                            <th>Status</th>
                            <th>Generated / Refreshed</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentReports as $report)
                            @php
                                $rowSummary = $report->summary_json;
                                if (is_string($rowSummary)) {
                                    $rowSummary = json_decode($rowSummary, true) ?: [];
                                }
                            @endphp
                            <tr>
                                <td>{{ $report->id }}</td>
                                <td><strong>{{ $report->branch?->name ?? '—' }}</strong></td>
                                <td><span class="pill-status pill-active"><span class="dot"></span>{{ $report->period }}</span></td>
                                <td class="tabular">{{ $report->report_date?->format('M d, Y') }}</td>
                                <td class="tabular font-bold">Rs {{ number_format($rowSummary['total_sales'] ?? 0, 0) }}</td>
                                <td>{{ $report->status }}</td>
                                <td class="tabular">{{ $report->updated_at?->format('M d, H:i') }}</td>
                                <td class="tabular">{{ $report->sent_at?->format('M d, H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
