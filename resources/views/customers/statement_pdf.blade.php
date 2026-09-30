<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Customer Statement - {{ $customer->name }} ({{ $customer->code }})</title>
    <style>
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1B1B1B;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #D71920;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #D71920;
            text-transform: uppercase;
            margin: 0;
        }
        .brand-sub {
            font-size: 11px;
            font-weight: bold;
            color: #1B1B1B;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-info {
            font-size: 9px;
            color: #555555;
            margin-top: 4px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }
        .meta-table td {
            vertical-align: top;
            padding: 6px;
        }
        .meta-box {
            background-color: #F8F9FA;
            border: 1px solid #E5E7EB;
            border-radius: 4px;
            padding: 8px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .table th {
            background-color: #D71920;
            color: #FFFFFF;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 7px 8px;
            text-align: left;
            border: 1px solid #D71920;
        }
        .table th.text-right, .table td.text-right {
            text-align: right;
        }
        .table td {
            padding: 6px 8px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 10px;
        }
        .table tr:nth-child(even) {
            background-color: #FAFAFA;
        }
        .table tfoot tr {
            background-color: #F3F4F6;
            font-weight: bold;
            border-top: 2px solid #D1D5DB;
        }
        .summary-card {
            background-color: #FEF2F2;
            border: 2px solid #D71920;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .summary-title {
            font-size: 10px;
            font-weight: bold;
            color: #991B1B;
            text-transform: uppercase;
        }
        .summary-amount {
            font-size: 18px;
            font-weight: bold;
            color: #D71920;
            margin-top: 2px;
        }
        .urdu-words {
            font-size: 11px;
            font-weight: bold;
            color: #1F2937;
            text-align: right;
            direction: rtl;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 9px;
            color: #4B5563;
        }
        .sign-line {
            border-top: 1px solid #6B7280;
            width: 140px;
            margin: 0 auto 4px auto;
            padding-top: 4px;
            font-weight: bold;
            color: #111827;
        }
        .footer-note {
            font-size: 8px;
            color: #6B7280;
            margin-top: 24px;
            text-align: center;
            border-top: 1px dashed #D1D5DB;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: top;">
                    <h1 class="brand-title">VITAL PETROLEUM</h1>
                    <div class="brand-sub">MEHAR FILLING STATION (FRANCHISE)</div>
                    <div class="company-info">
                        Lahore-Sargodha Road, Sheikhupura, Punjab, Pakistan<br>
                        NTN: 4210987-6 | STRN: 03-05-2710-001-82 | Ph: 0300-4567890
                    </div>
                </td>
                <td style="vertical-align: top; text-align: right;">
                    <div style="font-size: 14px; font-weight: bold; color: #D71920;">CUSTOMER STATEMENT OF ACCOUNT</div>
                    <div style="font-size: 10px; font-weight: bold; color: #374151; margin-top: 4px;">
                        Period: {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}
                    </div>
                    <div style="font-size: 8px; color: #9CA3AF; margin-top: 2px;">
                        Generated: {{ now()->format('d/m/Y h:i A') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 55%; padding-left: 0;">
                <div class="meta-box">
                    <strong style="color: #D71920; font-size: 9px; text-transform: uppercase;">Customer Profile:</strong><br>
                    <span style="font-size: 13px; font-weight: bold; color: #111827;">{{ $customer->name }}</span><br>
                    <strong>Code:</strong> {{ $customer->code }} &nbsp;|&nbsp;
                    <strong>Phone:</strong> {{ $customer->phone ?? 'N/A' }}<br>
                    <strong>CNIC:</strong> {{ $customer->cnic ?? 'N/A' }} &nbsp;|&nbsp;
                    <strong>NTN:</strong> {{ $customer->ntn_number ?? 'N/A' }}<br>
                    <strong>Address:</strong> {{ $customer->address ?? 'Sheikhupura' }}
                </div>
            </td>
            <td style="width: 45%; padding-right: 0;">
                <div class="meta-box">
                    <strong style="color: #4B5563; font-size: 9px; text-transform: uppercase;">Credit Status:</strong><br>
                    <strong>Credit Limit:</strong> {{ $customer->creditLimitIsUnlimited() ? 'Unlimited' : \App\Support\PakistaniCurrency::format($customer->credit_limit, true, 0) }}<br>
                    <strong>Opening Balance:</strong> {{ \App\Support\PakistaniCurrency::format($opening_balance) }}<br>
                    <strong>Total Period Debits:</strong> {{ \App\Support\PakistaniCurrency::format($total_debits) }}<br>
                    <strong>Total Period Credits:</strong> {{ \App\Support\PakistaniCurrency::format($total_credits) }}
                </div>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 14%;">Date</th>
                <th style="width: 41%;">Description / Narration</th>
                <th class="text-right" style="width: 15%;">Debit (Rs.)</th>
                <th class="text-right" style="width: 15%;">Credit (Rs.)</th>
                <th class="text-right" style="width: 15%;">Balance (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #F3F4F6; font-weight: bold;">
                <td>{{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }}</td>
                <td colspan="3">Opening Balance Brought Forward</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($opening_balance, false) }}</td>
            </tr>

            @forelse($items as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>
                        {{ $row['description'] }}
                        @if($row['reference_type'])
                            <span style="font-size: 8px; color: #6B7280;">({{ class_basename($row['reference_type']) }} #{{ $row['reference_id'] }})</span>
                        @endif
                    </td>
                    <td class="text-right" style="color: #DC2626;">
                        {{ \App\Support\Money::compare($row['debit'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($row['debit'], false) : '-' }}
                    </td>
                    <td class="text-right" style="color: #059669;">
                        {{ \App\Support\Money::compare($row['credit'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($row['credit'], false) : '-' }}
                    </td>
                    <td class="text-right" style="font-weight: bold;">
                        {{ \App\Support\PakistaniCurrency::format($row['balance'], false) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #9CA3AF; padding: 16px;">
                        No transactions recorded in this statement period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-transform: uppercase;">Period Totals</td>
                <td class="text-right" style="color: #DC2626;">{{ \App\Support\PakistaniCurrency::format($total_debits, false) }}</td>
                <td class="text-right" style="color: #059669;">{{ \App\Support\PakistaniCurrency::format($total_credits, false) }}</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($closing_balance, false) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-card">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="summary-title">Total Outstanding Balance Payable</div>
                    <div class="summary-amount">{{ $formatted_closing }}</div>
                </td>
                <td style="vertical-align: middle; text-align: right;">
                    <div style="font-size: 9px; color: #4B5563;">Amount in Words (اردو میں):</div>
                    <div class="urdu-words">{{ $in_words_urdu }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 4px; padding: 8px; font-size: 9px; color: #4B5563;">
        <strong>Payment Instructions:</strong> Please make crossed cheques or bank transfers in favor of <strong>"Mehar Filling Station"</strong>. Please share deposit slips via WhatsApp on 0300-4567890.
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line">Prepared By</div>
                <div>Accounts Officer</div>
            </td>
            <td>
                <div class="sign-line">Station Manager</div>
                <div>Mehar Filling Station</div>
            </td>
            <td>
                <div class="sign-line">Customer Signature</div>
                <div>Acknowledgement</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        This is a computer-generated statement issued by Mehar Filling Station (Vital Petroleum Franchise, Sheikhupura).
    </div>

</body>
</html>
