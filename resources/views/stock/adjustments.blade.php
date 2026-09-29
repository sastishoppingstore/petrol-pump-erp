@extends('layouts.app')

@section('title', 'Stock Adjustments')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.index') }}">Stock</a></li>
    <li class="breadcrumb-item active">Adjustments</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Stock Adjustments</h1>

    <div class="row g-3">
        <div class="col-lg-4">
            @can('stock.stock_adjustment')
                <div class="erp-card p-4">
                    <h2 class="h6 mb-3">Raise an adjustment</h2>
                    <p class="text-muted small">
                        An adjustment is a request. Stock does not move until it is approved.
                    </p>

                    <form method="POST" action="{{ route('stock.adjustments.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="tank_id" class="form-label">Tank <span class="text-danger">*</span></label>
                            <select id="tank_id" name="tank_id" class="form-select @error('tank_id') is-invalid @enderror" required>
                                <option value="">Select tank…</option>
                                @foreach ($tanks as $tank)
                                    <option value="{{ $tank->id }}" @selected((string) old('tank_id') === (string) $tank->id)>
                                        {{ $tank->displayName() }} — {{ $tank->fuelName() }}
                                        (stock: {{ number_format((float) $tank->current_stock, 3) }} L)
                                    </option>
                                @endforeach
                            </select>
                            @error('tank_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                                @foreach (['IN' => 'Stock in', 'OUT' => 'Stock out', 'LOSS' => 'Loss / evaporation', 'CORRECTION' => 'Correction'] as $k => $v)
                                    <option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity (L) <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0.001" id="quantity" name="quantity"
                                   value="{{ old('quantity') }}"
                                   class="form-control @error('quantity') is-invalid @enderror" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <input type="text" id="reason" name="reason" value="{{ old('reason') }}"
                                   class="form-control @error('reason') is-invalid @enderror"
                                   maxlength="200" required placeholder="Shortage confirmed at dip">
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit for approval</button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="col-lg-8">
            <div class="erp-card">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Tank</th>
                                <th>Type</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Before → After</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustments as $a)
                                <tr>
                                    <td class="small"><code>{{ $a->reference_number }}</code></td>
                                    <td class="small">{{ $a->tank?->tank_number ?? '—' }}</td>
                                    <td class="small">{{ $a->type }}</td>
                                    <td class="text-end small">{{ number_format((float) $a->quantity, 3) }}</td>
                                    <td class="text-end small">
                                        @if ($a->stock_after !== null)
                                            {{ number_format((float) $a->stock_before, 3) }} →
                                            <strong>{{ number_format((float) $a->stock_after, 3) }}</strong>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="small text-muted">
                                        {{ $a->reason }}
                                        @if ($a->rejection_reason)
                                            <br><span class="text-danger">Rejected: {{ $a->rejection_reason }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $a->statusBadgeClass() }}">{{ $a->status }}</span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if ($a->isPending())
                                            @can('stock.approve')
                                                <form method="POST" action="{{ route('stock.adjustments.approve', $a) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Approve this adjustment? Stock will be updated.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>

                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal" data-bs-target="#rejectAdj{{ $a->id }}">
                                                    Reject
                                                </button>
                                            @endcan
                                        @else
                                            <span class="text-muted small">
                                                by {{ $a->approver?->name ?? '—' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                                @if ($a->isPending())
                                    <div class="modal fade" id="rejectAdj{{ $a->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form class="modal-content" method="POST"
                                                  action="{{ route('stock.adjustments.reject', $a) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject {{ $a->reference_number }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <label for="rej{{ $a->id }}" class="form-label">Reason <span class="text-danger">*</span></label>
                                                    <input type="text" id="rej{{ $a->id }}" name="rejection_reason"
                                                           class="form-control" maxlength="500" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No adjustments yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $adjustments->links() }}</div>
        </div>
    </div>
@endsection
