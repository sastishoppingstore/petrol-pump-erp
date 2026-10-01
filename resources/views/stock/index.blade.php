@extends('layouts.app')

@section('title', 'Stock')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Stock</li>
@endsection

{{--
    Tank Stock — 2026 redesign.
    Glass table (cells centered), drift/variance rang wohi logic,
    sirf Tailwind rang. Expected/drift ki tashreeh neeche box me.
--}}
@section('content')
    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>🛢️ Tank Stock</h1>
        <p>Live tank stock vs movement-ledger expected stock</p>
        <div class="page-actions">
            <a href="{{ route('stock.movements') }}" class="btn-3d btn-3d-navy">Movements</a>
            <a href="{{ route('stock.adjustments') }}" class="btn-3d btn-3d-amber">Adjustments</a>
        </div>
    </div>

    {{-- ================= Stock table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Tank</th>
                        <th>Fuel</th>
                        <th>Current</th>
                        <th>Expected (from ledger)</th>
                        <th>Ledger drift</th>
                        <th>Last physical</th>
                        <th>Variance</th>
                        <th>Capacity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $tank = $row['tank']; @endphp
                        <tr>
                            <td class="font-bold text-slate-800 dark:text-slate-100">{{ $tank->displayName() }}<br>
                                <span class="text-xs font-medium text-slate-400">{{ $tank->branch?->name }}</span>
                            </td>
                            <td>{{ $tank->fuelName() }}</td>
                            <td class="tabular font-black text-slate-800 dark:text-white">{{ number_format((float) $tank->current_stock, 3) }}</td>
                            <td class="tabular">{{ number_format((float) $row['expected'], 3) }}</td>
                            <td class="tabular">
                                @if (\App\Support\Quantity::isZero($row['drift']))
                                    <span class="text-emerald-600">0.000</span>
                                @else
                                    <span class="font-black text-red-600" title="Current stock does not match the movement ledger">
                                        {{ number_format((float) $row['drift'], 3) }}
                                    </span>
                                @endif
                            </td>
                            <td class="tabular">
                                {{ $row['lastPhysical'] !== null ? number_format((float) $row['lastPhysical'], 3) : '—' }}
                            </td>
                            <td class="tabular">
                                @if ($row['variance'] === null)
                                    —
                                @elseif (\App\Support\Quantity::isZero($row['variance']))
                                    <span class="text-emerald-600">0.000</span>
                                @elseif (\App\Support\Quantity::isNegative($row['variance']))
                                    <span class="font-semibold text-red-600">{{ number_format((float) $row['variance'], 3) }}</span>
                                @else
                                    <span class="font-semibold text-emerald-600">{{ number_format((float) $row['variance'], 3) }}</span>
                                @endif
                            </td>
                            <td class="tabular text-slate-400">{{ number_format((float) $tank->capacity, 3) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-slate-400">No tanks defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="glass-card mt-5 p-4 text-center text-sm text-slate-500 dark:text-slate-400">
        <strong>Expected</strong> is derived from the opening quantity plus every movement ever recorded.
        <strong>Ledger drift</strong> must always be zero — a non-zero value means stock was changed
        outside the stock engine.
    </div>
@endsection
