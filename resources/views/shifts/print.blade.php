<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shift Summary — {{ $shift->shift_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .erp-no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; font-size: 12px; }
            .card { border: none !important; box-shadow: none !important; }
            .table-light { background-color: #f8f9fa !important; }
        }
        .thermal-slip {
            max-width: 80mm;
            margin: 0 auto;
            padding: 10px;
            font-family: 'Courier New', Courier, monospace;
        }
        @media (min-width: 768px) and not print {
            .thermal-slip {
                border: 1px dashed #ccc;
                background: #fff;
                margin-top: 20px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body class="bg-light">

<div class="erp-no-print text-center py-3 bg-dark text-white mb-3">
    <button onclick="window.print()" class="btn btn-success fw-bold px-4">
        🖨️ Print Report
    </button>
    <a href="{{ route('shifts.show', $shift) }}" class="btn btn-outline-light ms-2">
        ← Back to Shift
    </a>
</div>

<div class="thermal-slip">
    <div class="text-center mb-3">
        <h4 class="fw-bold mb-0 text-uppercase">{{ config('app.name', 'Petrol Pump ERP') }}</h4>
        <div class="small fw-semibold">{{ $shift->branch?->name }}</div>
        <div class="small text-muted">{{ $shift->branch?->address }}</div>
        <div class="small text-muted">Phone: {{ $shift->branch?->phone ?: 'N/A' }}</div>
        <hr class="my-2 border-dark">
        <h5 class="fw-bold mb-0">SHIFT SUMMARY REPORT</h5>
        <div class="font-monospace fw-bold">{{ $shift->shift_number }}</div>
    </div>

    <div class="small mb-2">
        <div class="d-flex justify-content-between">
            <span>Attendant:</span>
            <strong>{{ $shift->user?->name }} ({{ $shift->user?->employee_code }})</strong>
        </div>
        <div class="d-flex justify-content-between">
            <span>Status:</span>
            <strong>{{ $shift->status }}</strong>
        </div>
        <div class="d-flex justify-content-between">
            <span>Opened:</span>
            <span>{{ $shift->opened_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Closed:</span>
            <span>{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') : 'LIVE' }}</span>
        </div>
    </div>

    <hr class="my-2 border-dark">

    <div class="small fw-bold text-uppercase mb-1">Nozzle Readings (Litres)</div>
    <table class="w-100 small mb-2">
        <thead>
            <tr class="border-bottom border-dark">
                <th class="text-start">Nozzle</th>
                <th class="text-end">Open</th>
                <th class="text-end">Close</th>
                <th class="text-end">Sale (L)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($shift->shiftNozzles as $sn)
                <tr>
                    <td class="text-start">{{ $sn->nozzle?->nozzle_number }} ({{ substr($sn->nozzle?->fuelProduct?->code ?? '', 0, 3) }})</td>
                    <td class="text-end font-monospace">{{ number_format((float)$sn->opening_meter, 1) }}</td>
                    <td class="text-end font-monospace">
                        {{ $sn->closing_meter !== null ? number_format((float)$sn->closing_meter, 1) : number_format((float)$sn->nozzle?->current_meter, 1) }}
                    </td>
                    <td class="text-end font-monospace fw-bold">
                        {{ $sn->closing_meter !== null ? number_format((float)$sn->meter_sales_litres, 1) : number_format(max(0, (float)$sn->nozzle?->current_meter - (float)$sn->opening_meter), 1) }}
                    </td>
                </tr>
            @endforeach
            <tr class="border-top border-dark fw-bold">
                <td colspan="3">Total Dispensed:</td>
                <td class="text-end font-monospace">{{ number_format((float)$shift->total_litres, 3) }} L</td>
            </tr>
        </tbody>
    </table>

    <hr class="my-2 border-dark">

    <div class="small fw-bold text-uppercase mb-1">Cash Reconciliation</div>
    <div class="small">
        <div class="d-flex justify-content-between">
            <span>Opening Float:</span>
            <span class="font-monospace">Rs. {{ number_format((float)$shift->opening_cash, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Cash Drops / Handovers:</span>
            <span class="font-monospace">-Rs. {{ number_format((float)$shift->cash_drops_total, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between border-top border-secondary pt-1 fw-bold">
            <span>Expected Cash:</span>
            <span class="font-monospace">Rs. {{ number_format((float)$shift->expected_cash, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between fw-bold">
            <span>Actual Cash Counted:</span>
            <span class="font-monospace">Rs. {{ number_format((float)($shift->actual_cash ?? 0), 2) }}</span>
        </div>
        @if ($shift->cash_difference !== null)
            <div class="d-flex justify-content-between border-top border-dark pt-1 fw-bold fs-6">
                <span>Variance (Short/Over):</span>
                <span class="font-monospace">
                    {{ (float)$shift->cash_difference >= 0 ? '+' : '' }}Rs. {{ number_format((float)$shift->cash_difference, 2) }}
                </span>
            </div>
        @endif
        <div class="d-flex justify-content-between mt-1">
            <span>Card POS Settlement:</span>
            <span class="font-monospace">Rs. {{ number_format((float)$shift->card_total, 2) }}</span>
        </div>
    </div>

    @if ($shift->closing_notes)
        <hr class="my-2 border-dark">
        <div class="small">
            <span class="fw-bold">Notes:</span>
            <div>{{ $shift->closing_notes }}</div>
        </div>
    @endif

    @if ($shift->approved_by)
        <div class="small mt-1 text-muted">
            Approved by: {{ $shift->approver?->name }} ({{ $shift->approved_at?->format('d/m/Y H:i') }})
        </div>
    @endif

    <div class="mt-4 pt-3 border-top border-dark text-center small">
        <div class="d-flex justify-content-between mt-4">
            <div class="border-top border-dark pt-1" style="width: 45%;">Attendant Sig</div>
            <div class="border-top border-dark pt-1" style="width: 45%;">Manager Sig</div>
        </div>
        <div class="text-muted mt-3" style="font-size: 10px;">
            Printed: {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>
</div>

</body>
</html>
