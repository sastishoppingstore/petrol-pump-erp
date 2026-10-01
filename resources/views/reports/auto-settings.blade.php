@extends('layouts.app')

@section('title', __('finance.reports.auto_title'))

@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('reports.index') }}">{{ __('finance.reports.reports_word') }}</a></li>
    <li>/</li>
    <li class="font-semibold">{{ __('finance.reports.auto_title') }}</li>
@endsection

@section('content')
<div class="space-y-6">

    <div class="page-head">
        <h1>{{ __('finance.reports.auto_heading') }}</h1>
        <p>{{ __('finance.reports.auto_sub') }}</p>
    </div>

    {{-- Period Toggles --}}
    <div class="glass-card card-3d p-6">
        <h3 class="mb-1 text-center text-lg font-black text-slate-800 dark:text-white">{{ __('finance.reports.periods_heading') }}</h3>
        <p class="mb-5 text-center text-sm text-slate-500">{{ __('finance.reports.periods_sub') }}</p>

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
                                    {{ __('finance.reports.last_report') }} <strong>{{ $config['last']->branch?->name ?? '—' }}</strong> •
                                    {{ $config['last']->updated_at?->format('M d, Y H:i') }} •
                                    <span class="pill-status {{ $config['enabled'] ? 'pill-active' : '' }}"><span class="dot"></span>{{ $config['last']->status }}</span>
                                @else
                                    <span class="text-amber-600 font-semibold">{{ __('finance.reports.no_report_yet') }}</span>
                                @endif
                            </div>
                        </div>

                        <label class="relative inline-block h-8 w-14 shrink-0 cursor-pointer" title="{{ __('finance.reports.on_off') }}">
                            <input type="checkbox" name="{{ $config['key'] }}" value="1" class="peer sr-only" @checked($config['enabled'])>
                            <span class="absolute inset-0 rounded-full bg-slate-300 shadow-inner transition-colors duration-300 peer-checked:bg-emerald-500 after:absolute after:left-1 after:top-1 after:h-6 after:w-6 after:rounded-full after:bg-white after:shadow after:transition-transform after:duration-300 peer-checked:after:translate-x-6"></span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/70 bg-white/50 px-5 py-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                <div>
                    <div class="font-extrabold text-slate-800 dark:text-white">{{ __('finance.reports.send_time_heading') }}</div>
                    <div class="text-xs text-slate-500">{{ __('finance.reports.send_time_sub') }}</div>
                </div>
                <input type="time" name="report_send_time" value="{{ $sendTime }}" class="input-3d w-36">
            </div>

            {{-- Owner Email Delivery --}}
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/70 bg-white/50 px-5 py-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                <div class="min-w-56 flex-1">
                    <div class="font-extrabold text-slate-800 dark:text-white">📧 Report Email Recipients / رپورٹ ای میل وصول کنندگان</div>
                    <div class="text-xs text-slate-500">
                        Har generated report (PDF + Excel attached) in addresses par khud-ba-khud jayegi.
                        Comma se alag karein — khali chhorein to company email par jayegi.
                        @if (! $sendViaEmail)
                            <span class="font-semibold text-amber-600">Note: Email delivery filhal OFF hai (Settings → report_send_via_email).</span>
                        @endif
                    </div>
                </div>
                <input type="text" name="report_email_recipients" value="{{ $reportEmailRecipients }}"
                       placeholder="owner@example.com, manager@example.com" class="input-3d w-full max-w-md">
            </div>

            {{-- Custom Interval --}}
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/70 bg-white/50 px-5 py-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                <div class="min-w-56 flex-1">
                    <div class="font-extrabold text-slate-800 dark:text-white">
                        ⏱️ Custom Interval / کسٹم وقفہ
                        <span class="ml-1 text-xs font-semibold text-slate-400">(custom)</span>
                    </div>
                    <div class="text-xs text-slate-500">
                        Apni pasand ki muddat (ghanton me) — report utne ghante peeche ka data legi aur email bhi utne hi waqfe se jayegi. 0 = band.
                        @if ($customLast)
                            <br>{{ __('finance.reports.last_report') }} <strong>{{ $customLast->branch?->name ?? '—' }}</strong> •
                            {{ $customLast->updated_at?->format('M d, Y H:i') }}
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="number" name="auto_report_custom_hours" value="{{ $customHours }}" min="0" max="2160" step="1" class="input-3d w-28 text-center">
                    <span class="text-sm font-semibold text-slate-500">hours</span>
                </div>
            </div>

            <div class="mt-6 text-center">
                <button type="submit" class="btn-3d btn-3d-primary">{{ __('finance.reports.save_settings') }}</button>
            </div>
        </form>
    </div>

    {{-- Generate Now --}}
    <div class="glass-card card-3d p-6 text-center">
        <h3 class="mb-1 text-lg font-black text-slate-800 dark:text-white">{{ __('finance.reports.generate_heading') }}</h3>
        <p class="mx-auto mb-5 max-w-xl text-sm text-slate-500">
            {{ __('finance.reports.gen_text_a') }} <strong>{{ __('finance.reports.gen_text_strong') }}</strong> {{ __('finance.reports.gen_text_b') }}
        </p>
        <form method="POST" action="{{ route('reports.auto-reports.generate') }}" onsubmit="return confirm('Enabled periods ki reports abhi generate kar dein?')">
            @csrf
            <button type="submit" class="btn-3d btn-3d-primary btn-3d-lg">{{ __('finance.reports.generate_now') }}</button>
        </form>
        <p class="mt-4 text-xs text-slate-400">
            {{ __('finance.reports.cron_a') }} <code class="rounded bg-slate-100 px-1.5 py-0.5 dark:bg-slate-800">* * * * * php artisan schedule:run</code>
            {{ __('finance.reports.cron_b') }}
        </p>
    </div>

    {{-- Recent Generated Reports --}}
    <div class="glass-card overflow-hidden">
        <h3 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">{{ __('finance.reports.recent_heading') }}</h3>

        @if ($recentReports->isEmpty())
            <div class="alert alert-info m-6">
                {{ __('finance.reports.empty_a') }} <strong>{{ __('finance.reports.generate_now') }}</strong> {{ __('finance.reports.empty_b') }}
            </div>
        @else
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('finance.reports.branch') }}</th>
                            <th>{{ __('finance.reports.period') }}</th>
                            <th>{{ __('finance.reports.report_date') }}</th>
                            <th>{{ __('finance.reports.total_sales') }}</th>
                            <th>{{ __('finance.common.status') }}</th>
                            <th>{{ __('finance.reports.generated_refreshed') }}</th>
                            <th>{{ __('finance.reports.sent_at') }}</th>
                            <th>Download</th>
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
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('reports.download.pdf', $report) }}" class="btn-3d btn-3d-ghost btn-3d-sm">📄 PDF</a>
                                    <a href="{{ route('reports.download.excel', $report) }}" class="btn-3d btn-3d-ghost btn-3d-sm">📊 Excel</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
