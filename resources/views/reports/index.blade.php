@extends('layouts.app')

@section('title', __('finance.reports.title'))

@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold">{{ __('finance.reports.crumb_hub') }}</li>
@endsection

@section('content')
<div class="space-y-6">

    <div class="page-head">
        <h1>{{ __('finance.reports.heading') }}</h1>
        <p>{{ __('finance.reports.subheading') }}</p>
    </div>

    {{-- Report Category Grid — controller se aaya hua asal report registry
         ($grouped) render hota hai: har link reports.show ke asal slug par
         jata hai, koi dead/hardcoded route name nahi. --}}
    @php
        $groupStyles = [
            'Sales' => ['icon' => '🧾', 'gradient' => 'from-vital-primary to-vital-darkred', 'hover' => 'hover:bg-vital-primary/10'],
            'Cash & Bank' => ['icon' => '💵', 'gradient' => 'from-emerald-400 to-emerald-600', 'hover' => 'hover:bg-emerald-500/10'],
            'Udhaar / Customers' => ['icon' => '👥', 'gradient' => 'from-sky-400 to-blue-600', 'hover' => 'hover:bg-blue-500/10'],
            'Suppliers' => ['icon' => '🏭', 'gradient' => 'from-indigo-400 to-indigo-600', 'hover' => 'hover:bg-indigo-500/10'],
            'Stock' => ['icon' => '⛽', 'gradient' => 'from-amber-400 to-amber-600', 'hover' => 'hover:bg-amber-500/10'],
            'Financial & HR' => ['icon' => '📈', 'gradient' => 'from-purple-400 to-purple-600', 'hover' => 'hover:bg-purple-500/10'],
        ];
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($grouped as $group => $items)
            @php $style = $groupStyles[$group] ?? ['icon' => '📊', 'gradient' => 'from-slate-400 to-slate-600', 'hover' => 'hover:bg-slate-500/10']; @endphp
            <div class="glass-card card-3d p-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br {{ $style['gradient'] }} text-3xl shadow-3d">{{ $style['icon'] }}</div>
                <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">{{ $group }}</h3>
                <div class="space-y-2">
                    @foreach ($items as $slug => $meta)
                        <a href="{{ route('reports.show', ['report' => $slug]) }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 {{ $style['hover'] }} text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                            {{ $meta['title'] }}
                            <span class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ $meta['urdu'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Export Reminders --}}
    <div class="glass-card card-3d p-6 text-center">
        <h4 class="font-extrabold text-slate-800 dark:text-white mb-2">{{ __('finance.reports.export_heading') }}</h4>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ __('finance.reports.export_text_a') }} <strong>PDF</strong>, <strong>Excel</strong>, or <strong>CSV</strong> {{ __('finance.reports.export_text_b') }}
        </p>
    </div>

</div>
@endsection
