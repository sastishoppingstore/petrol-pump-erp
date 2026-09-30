<h5 class="fw-bold text-success mb-3">Customer Cheques Received (ادھار صارفین سے موصولہ چیکس)</h5>
<div class="table-responsive mb-4">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Payment Date</th>
                <th>Payment #</th>
                <th>Customer</th>
                <th>Cheque #</th>
                <th>Cheque Date</th>
                <th class="text-right">Amount (Rs.)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['customer_cheques'] as $cc)
                <tr>
                    <td>{{ $cc->payment_date->format('d M Y') }}</td>
                    <td class="font-monospace">{{ $cc->payment_number }}</td>
                    <td class="fw-bold">{{ $cc->customer?->name }}</td>
                    <td class="font-monospace fw-bold">{{ $cc->cheque_number }}</td>
                    <td>{{ $cc->cheque_date ? $cc->cheque_date->format('d M Y') : '-' }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($cc->amount) }}</td>
                    <td class="text-center"><span class="badge bg-warning text-dark">{{ $cc->cheque_status ?? 'PENDING' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-3">No customer cheques in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h5 class="fw-bold text-danger mb-3">Supplier Cheques Issued (سپلائرز کو جاری کردہ چیکس)</h5>
<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Payment Date</th>
                <th>Payment #</th>
                <th>Supplier</th>
                <th>Bank Account</th>
                <th>Cheque #</th>
                <th>Cheque Date</th>
                <th class="text-right">Amount (Rs.)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['supplier_cheques'] as $sc)
                <tr>
                    <td>{{ $sc->payment_date->format('d M Y') }}</td>
                    <td class="font-monospace">{{ $sc->payment_number }}</td>
                    <td class="fw-bold">{{ $sc->supplier?->name }}</td>
                    <td>{{ $sc->bankAccount?->account_title }}</td>
                    <td class="font-monospace fw-bold">{{ $sc->cheque_number }}</td>
                    <td>{{ $sc->cheque_date ? $sc->cheque_date->format('d M Y') : '-' }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($sc->amount) }}</td>
                    <td class="text-center"><span class="badge bg-warning text-dark">{{ $sc->cheque_status ?? 'PENDING' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No supplier cheques in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
