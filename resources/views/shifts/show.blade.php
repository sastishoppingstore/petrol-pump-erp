@extends('layouts.app')

@section('title', 'Shift ' . $shift->shift_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('shifts.index') }}">Shifts</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $shift->shift_number }}</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 mb-0">{{ $shift->shift_number }}</h1>
            <span class="badge bg-{{ $shift->statusBadgeClass() }} fs-6">
                {{ $shift->status }}
            </span>
            @if ($shift->isOpen())
                <span class="badge bg-success-subtle text-success border border-success small">🟢 LIVE</span>
            @endif
        </div>
        <p class="text-muted small mb-0 mt-1">
            Branch: <strong>{{ $shift->branch?->name }}</strong> | Attendant: <strong>{{ $shift->user?->name }}</strong> ({{ $shift->user?->employee_code }})
            | Opened: <strong>{{ $shift->opened_at->format('d M Y, h:i A') }}</strong>
            @if ($shift->closed_at)
                | Closed: <strong>{{ $shift->closed_at->format('d M Y, h:i A') }}</strong>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('shifts.print', $shift) }}" target="_blank" class="btn btn-outline-dark">
            🖨️ Print Shift Report
        </a>

        @if ($shift->isOpen())
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cashMovementModal">
                💵 Add Cash Drop / Float
            </button>
            @if (auth()->id() === $shift->user_id || auth()->user()->hasPermission('shift.close'))
                <a href="{{ route('shifts.close.form', $shift) }}" class="btn btn-danger">
                    🔒 Close Shift
                </a>
            @endif
        @endif

        @if ($shift->isPendingApproval() && (auth()->user()->isSuperAdmin() || auth()->user()->hasRole('MANAGER') || auth()->user()->hasPermission('shift.close')))
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#approveModal">
                ✅ Approve Shift Variance
            </button>
        @endif
    </div>
</div>

@if ($shift->isPendingApproval())
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <span class="fs-4 me-3">⚠️</span>
        <div>
            <h6 class="fw-bold mb-1">Shift Requires Manager Approval</h6>
            <div class="small">
                This shift closed with a cash difference of <strong>Rs. {{ number_format((float)$shift->cash_difference, 2) }}</strong>,
                which exceeds the configured variance threshold. Closing notes: <em>{{ $shift->closing_notes ?: 'No note provided' }}</em>
            </div>
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100 border-start border-primary border-4">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-semibold">Opening Float</span>
                <h3 class="fw-bold font-monospace mt-1 mb-0 text-primary">
                    Rs. {{ number_format((float)$shift->opening_cash, 2) }}
                </h3>
                <small class="text-muted">Declared at shift start</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-start border-info border-4">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-semibold">Expected Cash</span>
                <h3 class="fw-bold font-monospace mt-1 mb-0 text-info">
                    Rs. {{ number_format((float)($shift->isOpen() ? $liveExpectedCash : $shift->expected_cash), 2) }}
                </h3>
                <small class="text-muted">Float + Sales - Drops/Expenses</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-start border-success border-4">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-semibold">Actual Cash Handed</span>
                <h3 class="fw-bold font-monospace mt-1 mb-0 text-success">
                    @if ($shift->actual_cash !== null)
                        Rs. {{ number_format((float)$shift->actual_cash, 2) }}
                    @else
                        <span class="text-muted fs-5">Pending Close</span>
                    @endif
                </h3>
                <small class="text-muted">Counted physical cash</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        @php
            $diff = $shift->cash_difference !== null ? (float)$shift->cash_difference : null;
            $diffClass = $diff === null ? 'border-secondary' : ($diff == 0 ? 'border-success' : ($diff > 0 ? 'border-info' : 'border-danger'));
        @endphp
        <div class="card h-100 border-start {{ $diffClass }} border-4">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-semibold">Cash Variance</span>
                <h3 class="fw-bold font-monospace mt-1 mb-0 {{ $diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-info' : 'text-success') }}">
                    @if ($diff !== null)
                        {{ $diff >= 0 ? '+' : '' }}Rs. {{ number_format($diff, 2) }}
                    @else
                        <span class="text-muted fs-5">—</span>
                    @endif
                </h3>
                <small class="text-muted">Actual - Expected cash</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Assigned Nozzles & Meter Readings</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nozzle</th>
                            <th>Fuel</th>
                            <th class="text-end">Opening Meter</th>
                            <th class="text-end">Closing / Current Meter</th>
                            <th class="text-end">Litres Dispensed</th>
                            <th class="text-end">Meter Variance</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shift->shiftNozzles as $sn)
                            <tr>
                                <td>
                                    <strong>{{ $sn->nozzle?->nozzle_number }}</strong>
                                    <span class="text-muted small d-block">{{ $sn->nozzle?->dispenser?->name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $sn->nozzle?->fuelProduct?->name }}</span>
                                </td>
                                <td class="text-end font-monospace">{{ number_format((float)$sn->opening_meter, 3) }} L</td>
                                <td class="text-end font-monospace">
                                    @if ($sn->closing_meter !== null)
                                        {{ number_format((float)$sn->closing_meter, 3) }} L
                                    @else
                                        <span class="text-muted">{{ number_format((float)$sn->nozzle?->current_meter, 3) }} L (live)</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace fw-bold text-primary">
                                    @if ($sn->closing_meter !== null)
                                        {{ number_format((float)$sn->meter_sales_litres, 3) }} L
                                    @else
                                        @php
                                            $liveSales = max(0, (float)$sn->nozzle?->current_meter - (float)$sn->opening_meter);
                                        @endphp
                                        <span class="text-muted">{{ number_format($liveSales, 3) }} L</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace">
                                    @if ($sn->closing_meter !== null)
                                        @php $mVar = (float)$sn->meter_variance; @endphp
                                        <span class="{{ $mVar == 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $mVar >= 0 ? '+' : '' }}{{ number_format($mVar, 3) }} L
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $sn->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Cash Drops & Intermediate Handovers</h5>
                <span class="badge bg-secondary font-monospace">Total: Rs. {{ number_format((float)$totalDrops, 2) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Recorded By</th>
                            <th class="text-end">Amount</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shift->shiftCash as $cash)
                            <tr>
                                <td class="small">{{ $cash->created_at->format('d M, h:i A') }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ $cash->typeLabel() }}</span>
                                </td>
                                <td>{{ $cash->user?->name }}</td>
                                <td class="text-end font-monospace fw-bold">Rs. {{ number_format((float)$cash->amount, 2) }}</td>
                                <td class="small text-muted">{{ $cash->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted small">
                                    No intermediate cash drops or handovers recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">Shift Summary</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Total Litres Dispensed</span>
                        <span class="fw-bold font-monospace">{{ number_format((float)$shift->total_litres, 3) }} L</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Card Settlement</span>
                        <span class="font-monospace">Rs. {{ number_format((float)$shift->card_total, 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Approved By</span>
                        <span>{{ $shift->approver?->name ?: '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Approval Time</span>
                        <span class="small">{{ $shift->approved_at ? $shift->approved_at->format('d M, h:i A') : '—' }}</span>
                    </li>
                </ul>

                @if ($shift->opening_notes)
                    <div class="mt-3">
                        <span class="text-muted small fw-semibold">Opening Notes:</span>
                        <p class="small bg-light p-2 rounded mb-0">{{ $shift->opening_notes }}</p>
                    </div>
                @endif

                @if ($shift->closing_notes)
                    <div class="mt-3">
                        <span class="text-muted small fw-semibold">Closing Notes:</span>
                        <p class="small bg-light p-2 rounded mb-0">{{ $shift->closing_notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Cash Movement Modal --}}
<div class="modal fade" id="cashMovementModal" tabindex="-1" aria-labelledby="cashMovementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('shifts.cash', $shift) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="cashMovementModalLabel">Record Cash Drop / Float</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="cash_type" class="form-label fw-semibold">Movement Type</label>
                    <select id="cash_type" name="type" class="form-select" required>
                        <option value="DROP">Cash Drop / Vault Deposit (Reduces Cash in Hand)</option>
                        <option value="HANDOVER">Handover to Next Attendant (Reduces Cash in Hand)</option>
                        <option value="EXPENSE_PAYOUT">Cash Expense Payout (Reduces Cash in Hand)</option>
                        <option value="FLOAT_ADDITION">Float Addition / Top-up (Increases Cash in Hand)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="cash_amount" class="form-label fw-semibold">Amount (Rs.)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rs.</span>
                        <input type="number" step="0.01" min="0.01" id="cash_amount" name="amount" class="form-control font-monospace" required placeholder="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="cash_notes" class="form-label fw-semibold">Notes / Receipt Ref</label>
                    <input type="text" id="cash_notes" name="notes" class="form-control" placeholder="e.g. Mid-shift vault drop to safe...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Cash Record</button>
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
                <h5 class="modal-title" id="approveModalLabel">Approve Shift Variance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3 small">
                    You are approving a cash variance of <strong>Rs. {{ number_format((float)$shift->cash_difference, 2) }}</strong> for shift {{ $shift->shift_number }}.
                    This will mark the shift as officially CLOSED and audit your manager credentials.
                </div>
                <div class="mb-3">
                    <label for="approval_notes" class="form-label fw-semibold">Manager Review Notes</label>
                    <textarea id="approval_notes" name="notes" class="form-control" rows="3" placeholder="Enter review note, explanation of variance accepted..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-semibold">Authorize & Approve Shift</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
