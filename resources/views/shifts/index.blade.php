@extends('layouts.app')

@section('title', __('ui.nav.shifts'))

@section('breadcrumb')
    <li class="text-slate-500">{{ __('ui.nav.shifts') }}</li>
@endsection

@section('content')
<div class="page-head">
    <h1>{{ __('forecourt.shifts.index.heading') }}</h1>
    <p>{{ __('forecourt.shifts.index.sub') }}</p>
    <div class="page-actions">
        @can('shift.create')
            <a href="{{ route('shifts.create') }}" class="btn-3d btn-3d-primary">{{ __('forecourt.shifts.index.open_new') }}</a>
        @endcan
    </div>
</div>

<div class="glass-card filter-bar-3d mb-6">
    <form method="GET" action="{{ route('shifts.index') }}" class="flex flex-wrap items-end justify-center gap-3">
        <div class="field-3d">
            <label for="filter-status">{{ __('forecourt.common.status') }}</label>
            <select id="filter-status" name="status" class="input-3d sm:w-48">
                <option value="">{{ __('forecourt.shifts.index.all_statuses') }}</option>
                <option value="OPEN" @selected(($filters['status'] ?? '') === 'OPEN')>{{ __('forecourt.shifts.index.st_open') }}</option>
                <option value="PENDING_APPROVAL" @selected(($filters['status'] ?? '') === 'PENDING_APPROVAL')>{{ __('forecourt.shifts.index.st_pending') }}</option>
                <option value="CLOSED" @selected(($filters['status'] ?? '') === 'CLOSED')>{{ __('forecourt.shifts.index.st_closed') }}</option>
            </select>
        </div>
        <div class="field-3d">
            <label for="filter-branch">{{ __('forecourt.common.branch') }}</label>
            <select id="filter-branch" name="branch_id" class="input-3d sm:w-48">
                <option value="">{{ __('forecourt.shifts.index.all_branches') }}</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field-3d">
            <label for="filter-employee">{{ __('forecourt.shifts.index.attendant') }}</label>
            <select id="filter-employee" name="user_id" class="input-3d sm:w-48">
                <option value="">{{ __('forecourt.shifts.index.all_employees') }}</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(($filters['user_id'] ?? '') == $emp->id)>
                        {{ $emp->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field-3d">
            <label for="filter-date">{{ __('forecourt.common.date') }}</label>
            <input type="date" id="filter-date" name="date" class="input-3d sm:w-44" value="{{ $filters['date'] ?? '' }}">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-3d btn-3d-navy">{{ __('forecourt.shifts.index.filter_btn') }}</button>
            <a href="{{ route('shifts.index') }}" class="btn-3d btn-3d-ghost" title="{{ __('forecourt.shifts.index.clear') }}">✖</a>
        </div>
    </form>
</div>

<div class="glass-card overflow-hidden">
    <div class="table-3d">
        <table>
            <thead>
                <tr>
                    <th>{{ __('forecourt.shifts.index.shift_no') }}</th>
                    <th>{{ __('forecourt.common.branch') }}</th>
                    <th>{{ __('forecourt.shifts.index.attendant') }}</th>
                    <th>{{ __('forecourt.shifts.index.opened') }}</th>
                    <th>{{ __('forecourt.shifts.index.closed') }}</th>
                    <th>{{ __('forecourt.shifts.index.opening_float') }}</th>
                    <th>{{ __('forecourt.shifts.index.expected_cash') }}</th>
                    <th>{{ __('forecourt.shifts.index.actual_cash') }}</th>
                    <th>{{ __('forecourt.shifts.index.difference') }}</th>
                    <th>{{ __('forecourt.common.status') }}</th>
                    <th>{{ __('forecourt.common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shifts as $shift)
                    <tr>
                        <td>
                            <a href="{{ route('shifts.show', $shift) }}" class="font-black text-vital-primary hover:underline">
                                {{ $shift->shift_number }}
                            </a>
                        </td>
                        <td>{{ $shift->branch?->name }}</td>
                        <td>
                            <span class="font-bold">{{ $shift->user?->name }}</span>
                            <span class="block text-xs text-slate-400">{{ $shift->user?->employee_code }}</span>
                        </td>
                        <td class="whitespace-nowrap text-xs">{{ $shift->opened_at->format('d M Y, h:i A') }}</td>
                        <td class="whitespace-nowrap text-xs">
                            {{ $shift->closed_at ? $shift->closed_at->format('d M Y, h:i A') : '—' }}
                        </td>
                        <td class="tabular font-mono font-semibold">Rs. {{ number_format((float)$shift->opening_cash, 2) }}</td>
                        <td class="tabular font-mono font-semibold">Rs. {{ number_format((float)$shift->expected_cash, 2) }}</td>
                        <td class="tabular font-mono font-semibold">
                            @if ($shift->actual_cash !== null)
                                Rs. {{ number_format((float)$shift->actual_cash, 2) }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="tabular font-mono">
                            @if ($shift->cash_difference !== null)
                                @php
                                    $diff = (float)$shift->cash_difference;
                                    $diffPill = $diff == 0 ? 'pill-active' : ($diff > 0 ? 'pill-pending' : 'pill-danger');
                                @endphp
                                <span class="pill-status {{ $diffPill }}"><span class="dot"></span>{{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>
                            <span @class([
                                'pill-status',
                                'pill-active' => $shift->status === 'OPEN',
                                'pill-pending' => $shift->status === 'PENDING_APPROVAL',
                                'pill-inactive' => $shift->status === 'CLOSED',
                            ])><span class="dot"></span>{{ $shift->status }}</span>
                        </td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('shifts.show', $shift) }}" class="btn-3d btn-3d-ghost btn-3d-sm" title="{{ __('forecourt.shifts.index.view_details') }}">👁️</a>
                            @if ($shift->isOpen() && (auth()->id() === $shift->user_id || auth()->user()->hasPermission('shift.close')))
                                <a href="{{ route('shifts.close.form', $shift) }}" class="btn-3d btn-3d-amber btn-3d-sm" title="{{ __('forecourt.shifts.close_shift') }}">{{ __('forecourt.shifts.index.close_btn') }}</a>
                            @endif
                            <a href="{{ route('shifts.print', $shift) }}" target="_blank" class="btn-3d btn-3d-navy btn-3d-sm" title="{{ __('forecourt.shifts.index.print_report') }}">🖨️</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="py-10 text-center text-slate-500">
                            <div class="text-4xl">🕐</div>
                            <div class="mt-2 font-semibold">{{ __('forecourt.shifts.index.empty') }}</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($shifts->hasPages())
        <div class="border-t border-slate-200/70 px-5 py-3 dark:border-slate-700/50">
            {{ $shifts->links() }}
        </div>
    @endif
</div>

@can('shift.create')
    <a href="{{ route('shifts.create') }}" class="fab-3d" title="{{ __('forecourt.shifts.index.open_new_title') }}">
        <span class="text-2xl leading-none">＋</span>
    </a>
@endcan
@endsection
