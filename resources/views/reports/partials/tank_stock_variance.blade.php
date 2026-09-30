<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Tank Name</th>
                <th>Fuel Product</th>
                <th class="text-right">Opening Stock (L)</th>
                <th class="text-right">Receipts (L)</th>
                <th class="text-right">Sales (L)</th>
                <th class="text-right">Expected Stock (L)</th>
                <th class="text-right">Physical Dip Stock (L)</th>
                <th class="text-right">Dip Variance (L)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['tanks'] as $t)
                <tr>
                    <td class="fw-bold">{{ $t['tank']->name }}</td>
                    <td><span class="badge bg-secondary">{{ $t['product'] }}</span></td>
                    <td class="text-right">{{ number_format((float) $t['opening_stock'], 3) }}</td>
                    <td class="text-right text-success">+{{ number_format((float) $t['receipts'], 3) }}</td>
                    <td class="text-right text-danger">-{{ number_format((float) $t['sales'], 3) }}</td>
                    <td class="text-right">{{ number_format((float) $t['expected_stock'], 3) }}</td>
                    <td class="text-right fw-bold">{{ number_format((float) $t['physical_stock'], 3) }}</td>
                    <td class="text-right fw-bold">
                        @if((float) $t['variance'] >= 0)
                            <span class="badge bg-success">+{{ number_format((float) $t['variance'], 3) }} L</span>
                        @else
                            <span class="badge bg-danger">{{ number_format((float) $t['variance'], 3) }} L</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No tank records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
