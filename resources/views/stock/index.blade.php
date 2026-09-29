@extends('layouts.app')

@section('title', 'Stock')
@section('breadcrumb')
    <li class="breadcrumb-item active">Stock</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Tank Stock</h1>
        <div class="btn-group">
            <a href="{{ route('stock.movements') }}" class="btn btn-outline-secondary">Movements</a>
            <a href="{{ route('stock.adjustments') }}" class="btn btn-outline-secondary">Adjustments</a>
        </div>
    </div>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tank</th>
                        <th>Fuel</th>
                        <th class="text-end">Current</th>
                        <th class="text-end">Expected (from ledger)</th>
                        <th class="text-end">Ledger drift</th>
                        <th class="text-end">Last physical</th>
                        <th class="text-end">Variance</th>
                        <th class="text-end">Capacity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $tank = $row['tank']; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $tank->displayName() }}<br>
                                <span class="text-muted small">{{ $tank->branch?->name }}</span>
                            </td>
                            <td>{{ $tank->fuelName() }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $tank->current_stock, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $row['expected'], 3) }}</td>
                            <td class="text-end">
                                @if (\App\Support\Quantity::isZero($row['drift']))
                                    <span class="text-success">0.000</span>
                                @else
                                    <span class="text-danger fw-bold" title="Current stock does not match the movement ledger">
                                        {{ number_format((float) $row['drift'], 3) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{ $row['lastPhysical'] !== null ? number_format((float) $row['lastPhysical'], 3) : '—' }}
                            </td>
                            <td class="text-end">
                                @if ($row['variance'] === null)
                                    —
                                @elseif (\App\Support\Quantity::isZero($row['variance']))
                                    <span class="text-success">0.000</span>
                                @elseif (\App\Support\Quantity::isNegative($row['variance']))
                                    <span class="text-danger">{{ number_format((float) $row['variance'], 3) }}</span>
                                @else
                                    <span class="text-success">{{ number_format((float) $row['variance'], 3) }}</span>
                                @endif
                            </td>
                            <td class="text-end text-muted">{{ number_format((float) $tank->capacity, 3) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No tanks defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        <strong>Expected</strong> is derived from the opening quantity plus every movement ever recorded.
        <strong>Ledger drift</strong> must always be zero — a non-zero value means stock was changed
        outside the stock engine.
    </p>
@endsection
