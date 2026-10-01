@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Dashboard</li>
@endsection

@section('content')
    {{--
        Ek hi responsive dashboard (2026 redesign):
        - Mobile  → app launcher jaisa feel: touch tiles, bottom nav, FAB.
        - Desktop → wohi launcher poori viewport width me khulta hai
                    (fluid grids + ERP sidebar) — 430px wala phone frame
                    khatam kar diya gaya hai. ?mode=desktop wala alag,
                    adhoora view bhi hata diya gaya hai.
    --}}
    <livewire:dashboard.app-launcher />

    {{-- Auto-generated reports — sirf barri screen par, launcher ke neeche --}}
    <div class="mt-8 hidden lg:block">
        @include('dashboard.report-widgets')
    </div>
@endsection
