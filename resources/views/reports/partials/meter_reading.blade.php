<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>Dispenser / Nozzle</th>
                <th>Shift</th>
                <th>Reading Type</th>
                <th class="text-right">Previous Reading</th>
                <th class="text-right">Current Reading</th>
                <th class="text-right">Delta / Testing</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['readings'] as $r)
                <tr>
                    <td>{{ $r->reading_time->format('d M Y, h:i A') }}</td>
                    <td>{{ $r->nozzle?->dispenser?->name }} - Nozzle #{{ $r->nozzle?->nozzle_number }}</td>
                    <td>{{ $r->shift ? '#' . $r->shift->shift_number : '-' }}</td>
                    <td><span class="badge bg-secondary">{{ $r->reading_type ?? 'SHIFT_CLOSE' }}</span></td>
                    <td class="text-right font-mono tabular">{{ number_format((float) $r->previous_reading, 3) }}</td>
                    <td class="text-right font-mono fw-bold tabular">{{ number_format((float) $r->reading_value, 3) }}</td>
                    <td class="text-right tabular">{{ number_format((float) ($r->test_litres ?? 0), 3) }} L</td>
                    <td>{{ $r->user?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No meter readings found in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
