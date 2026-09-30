<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] }} — Mehar Filling Station</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #1B1B1B; margin: 0; padding: 0; background: #FFF; }
        .header-box { border-bottom: 2px solid #D71920; padding-bottom: 10px; margin-bottom: 15px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: top; width: 65%; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; width: 35%; }
        .station-name { font-size: 18px; font-weight: bold; color: #D71920; text-transform: uppercase; margin: 0; }
        .sub-header { font-size: 11px; color: #555; margin-top: 2px; }
        .report-title { font-size: 16px; font-weight: bold; margin-top: 10px; color: #1B1B1B; }
        .urdu-title { font-family: 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma; font-size: 15px; color: #A30F15; margin-top: 2px; }
        .meta-info { font-size: 11px; color: #666; margin-top: 4px; }
        table.report-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }
        table.report-table th { background-color: #F6F6F6; border: 1px solid #DDD; padding: 6px 8px; text-align: left; font-weight: bold; }
        table.report-table td { border: 1px solid #DDD; padding: 6px 8px; }
        table.report-table tr:nth-child(even) td { background-color: #FAFAFA; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .kpi-row { display: table; width: 100%; margin-bottom: 15px; }
        .kpi-cell { display: table-cell; padding: 8px; background: #F8F9FA; border-left: 3px solid #D71920; }
        .footer-note { margin-top: 25px; border-top: 1px solid #EEE; padding-top: 8px; font-size: 10px; color: #777; display: table; width: 100%; }
        .print-btn { padding: 8px 16px; background-color: #D71920; color: #FFF; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; margin: 10px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    @if(!($isPdf ?? false))
        <div class="no-print" style="text-align: right; background: #F0F0F0; padding: 5px;">
            <button class="print-btn" onclick="window.print()">🖨️ Print This Document</button>
        </div>
    @endif

    <div class="header-box">
        <div class="header-left">
            <h1 class="station-name">Vital Petroleum &bull; Mehar Filling Station</h1>
            <div class="sub-header">Franchise Station, Gujranwala Road, Sheikhupura | Phone: 0300-1234567</div>
            <div class="report-title">{{ $meta['title'] }}</div>
            <div class="urdu-title">{{ $meta['urdu'] }}</div>
        </div>
        <div class="header-right">
            <div class="meta-info"><strong>Branch:</strong> {{ $branch->name }}</div>
            <div class="meta-info"><strong>Period:</strong> {{ $range['label'] }}</div>
            <div class="meta-info"><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</div>
            <div class="meta-info"><strong>User:</strong> {{ auth()->user()?->name ?? 'System' }}</div>
        </div>
    </div>

    <!-- Embed the partial table -->
    @include('reports.partials.' . str_replace('-', '_', $report), ['data' => $data, 'range' => $range, 'isPrint' => true])

    <div class="footer-note">
        <div style="display: table-cell; width: 50%;">
            Official Computerized Accounting Record &bull; Vital Petroleum ERP
        </div>
        <div style="display: table-cell; width: 50%; text-align: right;">
            Page 1 of 1 &bull; Signature: _______________________
        </div>
    </div>
</body>
</html>
