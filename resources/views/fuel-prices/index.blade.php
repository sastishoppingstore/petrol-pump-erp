@extends('layouts.app')

@section('title', 'Fuel Prices')
@section('breadcrumb')
    <li class="breadcrumb-item active">Fuel Prices</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Fuel Prices</h1>

    <div class="row g-3">
        <div class="col-lg-5">
            @can('fuel.price_change')
                <div class="erp-card p-4">
                    <h2 class="h6 mb-3">Change price</h2>
                    <form method="POST" action="{{ route('fuel-prices.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="fuel_product_id" class="form-label">Fuel <span class="text-danger">*</span></label>
                            <select id="fuel_product_id" name="fuel_product_id"
                                    class="form-select @error('fuel_product_id') is-invalid @enderror" required>
                                <option value="">Select fuel…</option>
                                @foreach ($fuels as $fuel)
                                    <option value="{{ $fuel->id }}"
                                        @selected((string) old('fuel_product_id') === (string) $fuel->id)>
                                        {{ $fuel->name }} — {{ number_format((float) $fuel->selling_price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fuel_product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="selling_price" class="form-label">New price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" id="selling_price" name="selling_price"
                                   value="{{ old('selling_price') }}"
                                   class="form-control @error('selling_price') is-invalid @enderror" required>
                            @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="effective_from" class="form-label">Effective from</label>
                            <input type="datetime-local" id="effective_from" name="effective_from"
                                   value="{{ old('effective_from') }}" class="form-control">
                            <div class="form-text">Leave blank to take effect immediately.</div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <input type="text" id="reason" name="reason" value="{{ old('reason') }}"
                                   class="form-control" maxlength="500" placeholder="Government price increase">
                        </div>

                        <button type="submit" class="btn btn-primary">Update price</button>
                    </form>
                </div>
            @endcan

            <div class="erp-card p-4 mt-3">
                <h2 class="h6 mb-2">Current prices</h2>
                <ul class="list-group list-group-flush">
                    @foreach ($fuels as $fuel)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>{{ $fuel->name }}</span>
                            <span class="fw-semibold">{{ number_format((float) $fuel->selling_price, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="erp-card">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fuel</th>
                                <th>Scope</th>
                                <th class="text-end">Price</th>
                                <th>Effective from</th>
                                <th>Effective to</th>
                                <th>Reason</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $row)
                                <tr>
                                    <td>{{ $row->fuelProduct?->name ?? '—' }}</td>
                                    <td class="text-muted small">{{ $row->branch?->name ?? 'All branches' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $row->price, 2) }}</td>
                                    <td class="small">{{ $row->effective_from?->format('d M Y H:i') }}</td>
                                    <td class="small">
                                        {{ $row->effective_to?->format('d M Y H:i') ?? '— current —' }}
                                    </td>
                                    <td class="small text-muted">{{ $row->reason ?? '—' }}</td>
                                    <td class="small text-muted">{{ $row->creator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No price history yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $history->links() }}</div>
        </div>
    </div>
@endsection
