@extends('layouts.app')

@section('title', 'Stock Movements')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.index') }}">Stock</a></li>
    <li class="breadcrumb-item active">Movements</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Stock Movements</h1>

    <form method="GET" action="{{ route('stock.movements') }}" class="erp-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="f_tank" class="form-label small mb-1">Tank</label>
                <select id="f_tank" name="tank_id" class="form-select form-select-sm">
                    <option value="">All tanks</option>
                    @foreach ($tanks as $tank)
                        <option value="{{ $tank->id }}" @selected((string) request('tank_id') === (string) $tank->id)>
                            {{ $tank->displayName() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="f_type" class="form-label small mb-1">Type</label>
                <select id="f_type" name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    @foreach ($types as $t)
                        <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="f_from" class="form-label small mb-1">From</label>
                <input type="date" id="f_from" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label for="f_to" class="form-label small mb-1">To</label>
                <input type="date" id="f_to" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Tank</th>
                        <th>Fuel</th>
                        <th>Type</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Before</th>
                        <th class="text-end">After</th>
                        <th>Reference</th>
                        <th>Reason</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $m)
                        <tr>
                            <td class="small text-nowrap">{{ $m->created_at?->format('d M Y H:i') }}</td>
                            <td class="small">{{ $m->tank?->tank_number ?? '—' }}</td>
                            <td class="small">{{ $m->fuelProduct?->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $m->typeBadgeClass() }}">{{ $m->type }}</span>
                            </td>
                            <td class="text-end fw-semibold
                                {{ \App\Support\Quantity::isNegative($m->quantity) ? 'text-danger' : 'text-success' }}">
                                {{ number_format((float) $m->quantity, 3) }}
                            </td>
                            <td class="text-end small">{{ number_format((float) $m->before_quantity, 3) }}</td>
                            <td class="text-end small">{{ number_format((float) $m->after_quantity, 3) }}</td>
                            <td class="small text-muted">
                                @if ($m->reference_type)
                                    {{ class_basename($m->reference_type) }}#{{ $m->reference_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small text-muted">{{ $m->reason ?: '—' }}</td>
                            <td class="small text-muted">{{ $m->user?->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No movements recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $movements->links() }}</div>
@endsection
