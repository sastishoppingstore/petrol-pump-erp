@extends('layouts.app')

@section('title', 'Shift ' . $shift->shift_number)

@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('shifts.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Shifts</a></li>
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
            <span class="pill-status pill-active align-middle"><span class="dot"></span>🟢 LIVE</span>
        @endif
    </h1>
    <p>
        Branch: <strong>{{ $shift->branch?->name }}</strong> · Attendant: <strong>{{ $shift->user?->name }}</strong> ({{ $shift->user?->employee_code }})
        · Opened: <strong>{{ $shift->opened_at->format('d M Y, h:i A') }}</strong>
        @if ($shift->closed_at)
            · Closed: <strong>{{ $shift->closed_at->format('d M Y, h:i A') }}</strong>
        @endif
    </p>
    <div class="page-actions">
        <a href="{{ route('shifts.print', $shift) }}" target="_blank" class="btn-3d btn-3d-navy">
            🖨️ Print Shift Report
        </a>

        @if ($shift->isOpen())
            <button type="button" class="btn-3d btn-3d-ghost" data-bs-toggle="modal" data-bs-target="#cashMovementModal">
                💵 Add Cash Drop / Float
            </button>
            @if (auth()->id() === $shift->user_id || auth()->user()->hasPermission('shift.close'))
                <a href="{{ route('shifts.close.form', $shift) }}" class="btn-3d btn-3d-primary">
                    🔒 Close Shift
                </a>
            @endif
        @endif

        @if ($shift->isPendingApproval() && (auth()->user()->isSuperAdmin() || auth()->user()->hasRole('MANAGER') || auth()->user()->hasPermission('shift.close')))
            <button type="button" class="btn-3d btn-3d-amber" data-bs-toggle="modal" data-bs-target="#approveModal">
                ✅ Approve Shift Variance
            </button>
        @endif
    </div>
</div>

@if ($shift->isPendingApproval())
    <div class="alert alert-warning">
        <div class="text-base font-black">⚠️ Shift Requires Manager Approval</div>
        <div class="mt-1 font-medium">
            This shift closed with a cash difference of <strong>Rs. {{ number_format((float)$shift->cash_difference, 2) }}</strong>,
            which exceeds the configured variance threshold. Closing notes: <em>{{ $shift->closing_notes ?: 'No note provided' }}</em>
        </div>
    </div>
@endif

@php
    $diff = $shift->cash_difference !== null ? (float)$shift->cash_difference : null;
    $diffTile = $diff === null ? 'stat-slate' : ($diff == 0 ? 'stat-green' : ($diff > 0 ? 'stat-amber' : 'stat-red'));
@endphp

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="stat-tile-3d stat-navy">
        <div class="stat-label">Opening Float</div>
        <div class="stat-value">Rs. {{ number_format((float)$shift->opening_cash, 2) }}</div>
        <div class="stat-sub">Declared at shift start</div>
    </div>
    <div class="stat-tile-3d stat-slate">
        <div class="stat-label">Expected Cash</div>
        <div class="stat-value">Rs. {{ number_format((float)($shift->isOpen() ? $liveExpectedCash : $shift->expected_cash), 2) }}</div>
        <div class="stat-sub">Float + Sales − Drops/Expenses</div>
    </div>
    <div class="stat-tile-3d stat-green">
        <div class="stat-label">Actual Cash Handed</div>
        <div class="stat-value">
            @if ($shift->actual_cash !== null)
                Rs. {{ number_format((float)$shift->actual_cash, 2) }}
            @else
                Pending Close
            @endif
        </div>
        <div class="stat-sub">Counted physical cash</div>
    </div>
    <div class="stat-tile-3d {{ $diffTile }}">
        <div class="stat-label">Cash Variance</div>
        <div class="stat-value">
            @if ($diff !== null)
                {{ $diff >= 0 ? '+' : '' }}Rs. {{ number_format($diff, 2) }}
            @else
                —
            @endif
        </div>
        <div class="stat-sub">Actual − Expected cash</div>
    </div>
</div>

<div class="grid items-start gap-5 xl:grid-cols-3">
    <div class="space-y-5 xl:col-span-2">
        <div class="glass-card overflow-hidden">
            <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">⛽ Assigned Nozzles & Meter Readings</h2>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>Nozzle</th>
                            <th>Fuel</th>
                            <th>Opening Meter</th>
                            <th>Closing / Current Meter</th>
                            <th>Litres Dispensed</th>
                            <th>Meter Variance</th>
                            <th>Notes</th>
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
                                        <span class="text-slate-400">{{ number_format((float)$sn->nozzle?->current_meter, 3) }} L (live)</span>
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
                <h2 class="text-base font-black text-slate-800 dark:text-white">💵 Cash Drops & Intermediate Handovers</h2>
                <span class="pill-status pill-inactive font-mono">Total: Rs. {{ number_format((float)$totalDrops, 2) }}</span>
            </div>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Recorded By</th>
                            <th>Amount</th>
                            <th>Notes</th>
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
                                    No intermediate cash drops or handovers recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="glass-card card-3d p-6">
        <h2 class="mb-4 text-center text-base font-black text-slate-800 dark:text-white">📋 Shift Summary</h2>
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                <span class="text-slate-500">Total Litres Dispensed</span>
                <span class="tabular font-black">{{ number_format((float)$shift->total_litres, 3) }} L</span>
            </div>
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                <span class="text-slate-500">Card Settlement</span>
                <span class="tabular font-mono font-bold">Rs. {{ number_format((float)$shift->card_total, 2) }}</span>
            </div>
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                <span class="text-slate-500">Approved By</span>
                <span class="font-semibold">{{ $shift->approver?->name ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500">Approval Time</span>
                <span class="text-xs font-semibold">{{ $shift->approved_at ? $shift->approved_at->format('d M, h:i A') : '—' }}</span>
            </div>
        </div>

        @if ($shift->opening_notes)
            <div class="mt-5">
                <div class="text-center text-xs font-black uppercase tracking-wider text-slate-400">Opening Notes</div>
                <p class="mt-1.5 rounded-xl bg-slate-100/80 p-3 text-center text-sm dark:bg-slate-800/70">{{ $shift->opening_notes }}</p>
            </div>
        @endif

        @if ($shift->closing_notes)
            <div class="mt-4">
                <div class="text-center text-xs font-black uppercase tracking-wider text-slate-400">Closing Notes</div>
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
                <h5 class="modal-title font-black" id="cashMovementModalLabel">💵 Record Cash Drop / Float</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="field-3d mb-4">
                    <label for="cash_type">Movement Type</label>
                    <select id="cash_type" name="type" class="input-3d" required>
                        <option value="DROP">Cash Drop / Vault Deposit (Reduces Cash in Hand)</option>
                        <option value="HANDOVER">Handover to Next Attendant (Reduces Cash in Hand)</option>
                        <option value="EXPENSE_PAYOUT">Cash Expense Payout (Reduces Cash in Hand)</option>
                        <option value="FLOAT_ADDITION">Float Addition / Top-up (Increases Cash in Hand)</option>
                    </select>
                </div>
                <div class="field-3d mb-4">
                    <label for="cash_amount">Amount (Rs.)</label>
                    <input type="number" step="0.01" min="0.01" id="cash_amount" name="amount" class="input-3d text-center font-mono font-bold" required placeholder="0.00">
                </div>
                <div class="field-3d">
                    <label for="cash_notes">Notes / Receipt Ref</label>
                    <input type="text" id="cash_notes" name="notes" class="input-3d" placeholder="e.g. Mid-shift vault drop to safe...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">Save Cash Record</button>
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
                <h5 class="modal-title font-black" id="approveModalLabel">✅ Approve Shift Variance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    You are approving a cash variance of <strong>Rs. {{ number_format((float)$shift->cash_difference, 2) }}</strong> for shift {{ $shift->shift_number }}.
                    This will mark the shift as officially CLOSED and audit your manager credentials.
                </div>
                <div class="field-3d">
                    <label for="approval_notes">Manager Review Notes</label>
                    <textarea id="approval_notes" name="notes" class="input-3d" rows="3" placeholder="Enter review note, explanation of variance accepted..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-3d btn-3d-amber btn-3d-sm">Authorize & Approve Shift</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
