@extends('layouts.app')

@section('title', __('ui.nav.dispensers'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.nav.dispensers') }}</li>
@endsection

{{--
    Dispensers — 2026 redesign.
    Glass card table (tamam cells centered), status pills aur FAB.
    Routes aur permissions pehle jaisay hi hain.
--}}
@section('content')
    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('forecourt.dispensers.heading') }}</h1>
        <p>{{ __('forecourt.dispensers.count_sub', ['count' => $dispensers->count()]) }}</p>
        @can('fuel.create')
            <div class="page-actions">
                <a href="{{ route('dispensers.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> {{ __('forecourt.dispensers.add') }}
                </a>
            </div>
        @endcan
    </div>

    {{-- ================= Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('forecourt.common.number') }}</th>
                        <th>{{ __('forecourt.common.name') }}</th>
                        <th>{{ __('forecourt.common.branch') }}</th>
                        <th>{{ __('forecourt.common.model') }}</th>
                        <th>{{ __('forecourt.dispensers.serial') }}</th>
                        <th>{{ __('forecourt.dispensers.nozzles') }}</th>
                        <th>{{ __('forecourt.common.status') }}</th>
                        <th>{{ __('forecourt.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dispensers as $dispenser)
                        <tr>
                            <td><code class="rounded-md bg-slate-900/5 px-1.5 py-0.5 font-mono text-xs font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $dispenser->dispenser_number }}</code></td>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $dispenser->name ?: '—' }}</td>
                            <td class="text-slate-500">{{ $dispenser->branch?->name }}</td>
                            <td class="text-slate-500">{{ $dispenser->model ?: '—' }}</td>
                            <td class="text-xs text-slate-500">{{ $dispenser->serial_number ?: '—' }}</td>
                            <td>
                                <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-slate-900/5 px-2 text-xs font-black text-slate-700 dark:bg-white/10 dark:text-slate-200">{{ $dispenser->nozzles_count }}</span>
                            </td>
                            <td>
                                <span class="pill-status {{ $dispenser->isActive() ? 'pill-active' : 'pill-inactive' }}">
                                    <span class="dot"></span>{{ $dispenser->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                @can('fuel.edit')
                                    <a href="{{ route('dispensers.edit', $dispenser) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-slate-400">
                                {{ __('forecourt.dispensers.empty') }} @can('fuel.create')<a href="{{ route('dispensers.create') }}" class="font-bold text-vital-primary hover:underline">{{ __('forecourt.common.add_first') }}</a>.@endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= Floating Action Button ================= --}}
    @can('fuel.create')
        <a href="{{ route('dispensers.create') }}" class="fab-3d" title="{{ __('forecourt.dispensers.add_title') }}">
            <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('forecourt.dispensers.add') }}
        </a>
    @endcan
@endsection
