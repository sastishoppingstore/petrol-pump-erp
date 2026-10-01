@extends('layouts.app')

@section('title', __('forecourt.shifts.show.page_title') . ' ' . $shift->shift_number)

@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('shifts.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.shifts') }}</a></li>
    <li class="text-slate-500">{{ $shift->shift_number }}</li>
@endsection

@section('content')
<div class="page-head">
    <h1>
        {{ $shift->shift_number }}
        <span @class([
            'pill-status align-middle',
            'pill-active' => $shift->status === 'OPEN',
            'pill-pending' => $shift->status === 'PENDING_APPROVAL',
            'pill-inactive' => $shift->status === 'CLOSED',
        ])><span class="dot"></span>{{ $shift->status }}</span>
        @if ($shift->isOpen())
            <span class="pill-status pill-active align-middle"><span class="dot"></span>{{ __('forecourt.shifts.show.live') }}</span>
        @endif
    </h1>
    <p>
        {{ __('forecourt.shifts.show.branch_label') }} <strong>{{ $shift->branch?->name }}</strong> {{ __('forecourt.shifts.show.attendant_frag') }} <strong>{{ $shift->user?->name }}</strong> ({{ $shift->user?->employee_code }})
        {{ __('forecourt.shifts.show.opened_frag') }} <strong>{{ $shift->opened_at->format('d M Y, h:i A') }}</strong>
        @if ($shift->closed_at)
            {{ __('forecourt.shifts.show.closed_frag') }} <strong>{{ $shift->closed_at->format('d M Y, h:i A') }}</strong>
        @endif
    </p>
    <div class="page-actions">
        <a href="{{ route('shifts.print', $shift) }}" target="_blank" class="btn-3d btn-3d-navy">
            {{ __('forecourt.shifts.show.print_btn') }}
        </a>

        @if ($shift->isOpen())
            <button type="button" class="btn-3d btn-3d-ghost" data-bs-toggle="modal" data-bs-target="#cashMovementModal">
                {{ __('forecourt.shifts.show.add_drop') }}
            </button>
            @if (auth()->id() === $shift->user_id || auth()->user()->hasPermission('shift.close'))
                <a href="{{ route('shifts.close.form', $shift) }}" class="btn-3d btn-3d-primary">
                    {{ __('forecourt.shifts.show.close_btn') }}
                </a>
            @endif
        @endif

        @if ($shift->isPendingApproval() && (auth()->user()->isSuperAdmin() || auth()->user()->hasRole('MANAGER') || auth()->user()->hasPermission('shift.close')))
            <button type="button" class="btn-3d btn-3d-amber" data-bs-toggle="modal" data-bs-target="#approveModal">
                {{ __('forecourt.shifts.show.approve_btn') }}
            </button>
        @endif
    </div>
</div>

@if ($shift->isPendingApproval())
    <div class="alert alert-warning">
        <div class="text-base font-black">{{ __('forecourt.shifts.show.approval_warn') }}</div>
        <div class="mt-1 font-medium">
            {{ __('forecourt.shifts.show.approval_text_1') }} <strong>Rs. {{ number_format((float)$shift->cash_difference, 2) }}</strong>,
            {{ __('forecourt.shifts.show.approval_text_2') }} {{ __('forecourt.shifts.show.closing_notes_label') }} <em>{{ $shift->closing_notes ?: 'No note provided' }}</em>
        </div>
    </div>
@endif

@php
    $diff = $shift->cash_difference !== null ? (float)$shift->cash_difference : null;
    $diffTile = $diff === null ? 'stat-slate' : ($diff == 0 ? 'stat-green' : ($diff > 0 ? 'stat-amber' : 'stat-red'));
@endphp

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="stat-tile-3d tilt-3d stat-navy">
        <div class="stat-label">{{ __('forecourt.shifts.show.opening_float') }}</div>
        <div class="stat-value">Rs. {{ number_format((float)$shift->opening_cash, 2) }}</div>
        <div class="stat-sub">{{ __('forecourt.shifts.show.float_sub') }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-slate">
        <div class="stat-label">{{ __('forecourt.shifts.show.expected_cash') }}</div>
        <div class="stat-value">Rs. {{ number_format((float)($shift->isOpen() ? $liveExpectedCash : $shift->expected_cash), 2) }}</div>
        <div class="stat-sub">{{ __('forecourt.shifts.show.expected_sub') }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-green">
        <div class="stat-label">{{ __('forecourt.shifts.show.actual_cash') }}</div>
        <div class="stat-value">
            @if ($shift->actual_cash !== null)
                Rs. {{ number_format((float)$shift->actual_cash, 2) }}
            @else
                {{ __('forecourt.shifts.show.pending_close') }}
            @endif
        </div>
        <div class="stat-sub">{{ __('forecourt.shifts.show.counted') }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d {{ $diffTile }}">
        <div class="stat-label">{{ __('forecourt.shifts.show.variance') }}</div>
        <div class="stat-value">
            @if ($diff !== null)
                {{ $diff >= 0 ? '+' : '' }}Rs. {{ number_format($diff, 2) }}
            @else
                —
            @endif
        </div>
        <div class="stat-sub">{{ __('forecourt.shifts.show.variance_sub') }}</div>
    </div>
</div>

<div class="grid items-start gap-5 xl:grid-cols-3">
    <div class="space-y-5 xl:col-span-2">
        <div class="glass-card overflow-hidden">
            <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">{{ __('forecourt.shifts.show.nozzles_heading') }}</h2>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('forecourt.common.nozzle') }}</th>
                            <th>{{ __('forecourt.common.fuel') }}</th>
                            <th>{{ __('forecourt.common.opening_meter') }}</th>
                            <th>{{ __('forecourt.shifts.show.closing_current') }}</th>
                            <th>{{ __('forecourt.shifts.show.litres_dispensed') }}</th>
                            <th>{{ __('forecourt.shifts.show.meter_variance') }}</th>
                            <th>{{ __('forecourt.common.notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shift->shiftNozzles as $sn)
                            @php
                                $fuelName = strtoupper($sn->nozzle?->fuelProduct?->name ?? '');
                                $badgeClass = str_contains($fuelName, 'OCTANE') || str_contains($fuelName, 'HI-')
                                    ? 'badge-fuel-octane'
                                    : (str_contains($fuelName, 'HSD') || str_contains($fuelName, 'DIESEL')
                                        ? 'badge-fuel-diesel'
                                        : (str_contains($fuelName, 'PETROL') || str_contains($fuelName, 'SUPER') || str_contains($fuelName, 'MOGAS') || str_contains($fuelName, 'PMG')
                                            ? 'badge-fuel-petrol'
                                            : 'badge-fuel-other'));
                            @endphp
                            <tr>
                                <td>
                                    <span class="text-base font-black text-slate-800 dark:text-white">{{ $sn->nozzle?->nozzle_number }}</span>
                                    <span class="block text-xs text-slate-400">{{ $sn->nozzle?->dispenser?->name }}</span>
                                </td>
                                <td><span class="badge-fuel {{ $badgeClass }}">{{ $sn->nozzle?->fuelProduct?->name }}</span></td>
                                <td class="tabular font-mono">{{ number_format((float)$sn->opening_meter, 3) }} L</td>
                                <td class="tabular font-mono">
                                    @if ($sn->closing_meter !== null)
                                        {{ number_format((float)$sn->closing_meter, 3) }} L
                                    @else
                                        <span class="text-slate-400">{{ number_format((float)$sn->nozzle?->current_meter, 3) }} {{ __('forecourt.shifts.show.litres_live') }}</span>
                                    @endif
                                </td>
                                <td class="tabular font-mono font-black text-vital-primary">
                                    @if ($sn->closing_meter !== null)
                                        {{ number_format((float)$sn->meter_sales_litres, 3) }} L
                                    @else
                                        @php
                                            $liveSales = max(0, (float)$sn->nozzle?->current_meter - (float)$sn->opening_meter);
                                        @endphp
                                        <span class="text-slate-400">{{ number_format($liveSales, 3) }} L</span>
                                    @endif
                                </td>
                                <td class="tabular font-mono font-bold">
                                    @if ($sn->closing_meter !== null)
                                        @php $mVar = (float)$sn->meter_variance; @endphp
                                        <span class="{{ $mVar == 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $mVar >= 0 ? '+' : '' }}{{ number_format($mVar, 3) }} L
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-500">{{ $sn->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass-card overflow-hidden">
            <div class="flex flex-col items-center justify-between gap-2 border-b border-slate-200/70 px-6 py-4 dark:border-slate-700/50 sm:flex-row">
                <h2 class="text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.shifts.show.drops_heading') }}</h2>
                <span class="pill-status pill-inactive font-mono">{{ __('forecourt.shifts.show.total_rs') }} {{ number_format((float)$totalDrops, 2) }}</span>
            </div>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('forecourt.shifts.show.time') }}</th>
                            <th>{{ __('forecourt.common.type') }}</th>
                            <th>{{ __('forecourt.shifts.show.recorded_by') }}</th>
                            <th>{{ __('forecourt.shifts.show.amount') }}</th>
                            <th>{{ __('forecourt.common.notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shift->shiftCash as $cash)
                            <tr>
                                <td class="whitespace-nowrap text-xs">{{ $cash->created_at->format('d M, h:i A') }}</td>
                                <td><span class="pill-status pill-pending"><span class="dot"></span>{{ $cash->typeLabel() }}</span></td>
                                <td class="font-semibold">{{ $cash->user?->name }}</td>
                                <td class="tabular font-mono font-black">Rs. {{ number_format((float)$cash->amount, 2) }}</td>
                                <td class="text-xs text-slate-500">{{ $cash->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">
                                    {{ __('forecourt.shifts.show.no_drops') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="glass-card card-3d p-6">
        <h2 class="mb-4 text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.shifts.show.summary_heading') }}</h2>
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                <span class="text-slate-500">{{ __('forecourt.shifts.show.total_litres') }}</span>
                <span class="tabular font-black">{{ number_format((float)$shift->total_litres, 3) }} L</span>
            </div>
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                <span class="text-slate-500">{{ __('forecourt.shifts.show.card_settlement') }}</span>
                <span class="tabular font-mono font-bold">Rs. {{ number_format((float)$shift->card_total, 2) }}</span>
            </div>
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                <span class="text-slate-500">{{ __('forecourt.shifts.show.approved_by') }}</span>
                <span class="font-semibold">{{ $shift->approver?->name ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500">{{ __('forecourt.shifts.show.approval_time') }}</span>
                <span class="text-xs font-semibold">{{ $shift->approved_at ? $shift->approved_at->format('d M, h:i A') : '—' }}</span>
            </div>
        </div>

        @if ($shift->opening_notes)
            <div class="mt-5">
                <div class="text-center text-xs font-black uppercase tracking-wider text-slate-400">{{ __('forecourt.shifts.show.opening_notes') }}</div>
                <p class="mt-1.5 rounded-xl bg-slate-100/80 p-3 text-center text-sm dark:bg-slate-800/70">{{ $shift->opening_notes }}</p>
            </div>
        @endif

        @if ($shift->closing_notes)
            <div class="mt-4">
                <div class="text-center text-xs font-black uppercase tracking-wider text-slate-400">{{ __('forecourt.shifts.show.closing_notes') }}</div>
                <p class="mt-1.5 rounded-xl bg-slate-100/80 p-3 text-center text-sm dark:bg-slate-800/70">{{ $shift->closing_notes }}</p>
            </div>
        @endif
    </div>
</div>

{{-- Cash Movement Modal --}}
<div class="modal fade" id="cashMovementModal" tabindex="-1" aria-labelledby="cashMovementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('shifts.cash', $shift) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title font-black" id="cashMovementModalLabel">{{ __('forecourt.shifts.show.drop_modal') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.actions.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="field-3d mb-4">
                    <label for="cash_type">{{ __('forecourt.shifts.show.movement_type') }}</label>
                    <select id="cash_type" name="type" class="input-3d" required>
                        <option value="DROP">{{ __('forecourt.shifts.show.opt_drop') }}</option>
                        <option value="HANDOVER">{{ __('forecourt.shifts.show.opt_handover') }}</option>
                        <option value="EXPENSE_PAYOUT">{{ __('forecourt.shifts.show.opt_expense') }}</option>
                        <option value="FLOAT_ADDITION">{{ __('forecourt.shifts.show.opt_float') }}</option>
                    </select>
                </div>
                <div class="field-3d mb-4">
                    <label for="cash_amount">{{ __('forecourt.shifts.show.amount_rs') }}</label>
                    <input type="number" step="0.01" min="0.01" id="cash_amount" name="amount" class="input-3d text-center font-mono font-bold" required placeholder="0.00">
                </div>
                <div class="field-3d">
                    <label for="cash_notes">{{ __('forecourt.shifts.show.notes_ref') }}</label>
                    <input type="text" id="cash_notes" name="notes" class="input-3d" placeholder="{{ __('forecourt.shifts.show.drop_placeholder') }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" data-bs-dismiss="modal">{{ __('ui.actions.cancel') }}</button>
                <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">{{ __('forecourt.shifts.show.save_record') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Approve Modal --}}
@if ($shift->isPendingApproval())
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('shifts.approve', $shift) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title font-black" id="approveModalLabel">{{ __('forecourt.shifts.show.approve_btn') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.actions.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    {{ __('forecourt.shifts.show.approve_text_1') }} <strong>Rs. {{ number_format((float)$shift->cash_difference, 2) }}</strong> {{ __('forecourt.shifts.show.approve_text_2') }} {{ $shift->shift_number }}.
                    {{ __('forecourt.shifts.show.approve_note') }}
                </div>
                <div class="field-3d">
                    <label for="approval_notes">{{ __('forecourt.shifts.show.review_notes') }}</label>
                    <textarea id="approval_notes" name="notes" class="input-3d" rows="3" placeholder="{{ __('forecourt.shifts.show.review_placeholder') }}"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" data-bs-dismiss="modal">{{ __('ui.actions.cancel') }}</button>
                <button type="submit" class="btn-3d btn-3d-amber btn-3d-sm">{{ __('forecourt.shifts.show.authorize') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
