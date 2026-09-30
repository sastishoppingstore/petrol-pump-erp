@extends('layouts.app')

@section('title', 'Daily Closing Wizard — روزانہ اختتامی وزرڈ')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Banner -->
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #D71920 0%, #A30F15 100%); color: #FFFFFF; border-radius: 10px;">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <span class="badge bg-white text-danger mb-2 px-3 py-1 font-monospace fw-bold">FORECOURT RECONCILIATION &bull; DAY LOCK</span>
                <h2 class="h3 fw-bold mb-1">Daily Closing Wizard</h2>
                <div class="text-white-50" style="font-family: 'Jameel Noori Nastaleeq', Tahoma; font-size: 1.15rem;">
                    روزانہ اختتامی کارروائی، ڈِپ و کیش پڑتال اور تاریخ کا لاک — مہر فلنگ اسٹیشن
                </div>
            </div>
            <div class="text-end mt-3 mt-md-0">
                <form method="GET" action="{{ route('closing.index') }}" class="d-flex align-items-center gap-2">
                    <input type="date" name="date" class="form-control form-control-sm text-dark" value="{{ $date }}" onchange="this.form.submit()">
                    <button type="submit" class="btn btn-sm btn-light text-danger fw-bold">Load Date</button>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Lock Status Card if already closed -->
    @if($existingClosing && $existingClosing->isClosed())
        <div class="card mb-4 border-0 shadow-sm bg-danger bg-opacity-10 border-start border-4 border-danger">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="fw-bold text-danger mb-1">🔒 Day {{ $date }} is Officially Closed & Locked</h5>
                    <p class="text-muted mb-0 small">
                        Closing #<strong>{{ $existingClosing->closing_number }}</strong> approved by {{ $existingClosing->approvedByUser?->name ?? 'Manager' }} on {{ $existingClosing->updated_at->format('d M Y, h:i A') }}.
                        @if($existingClosing->email_sent_at)
                            &bull; Summary emailed to owner ({{ $existingClosing->email_recipient }}).
                        @endif
                    </p>
                </div>
                <div>
                    <form method="POST" action="{{ route('closing.unlock', $existingClosing->id) }}" onsubmit="return confirm('Unlock day for corrections? All edits will be logged.')">
                        @csrf
                        <input type="hidden" name="reason" value="Manager manual unlock for reconciliation adjustments">
                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold">
                            🔓 Unlock Day for Adjustments
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- 4 Verification Cards -->
    <div class="row g-4 mb-4">
        <!-- 1. Shifts Verification -->
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm {{ $checklist['shifts']['is_verified'] ? 'border-top border-4 border-success' : 'border-top border-4 border-warning' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge {{ $checklist['shifts']['is_verified'] ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $checklist['shifts']['is_verified'] ? '✓ Step 1 Verified' : '⚠ Step 1 Pending' }}
                        </span>
                        <span class="fs-4">🕐</span>
                    </div>
                    <h5 class="fw-bold mb-1">Shift Closures</h5>
                    <small class="text-muted d-block mb-3">تمام شفٹوں کی بندش</small>

                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Total Shifts Today:</span>
                        <strong class="text-dark">{{ $checklist['shifts']['total'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Open / Pending Shifts:</span>
                        <strong class="{{ $checklist['shifts']['open_or_pending'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $checklist['shifts']['open_or_pending'] }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Tank Dips Verification -->
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm {{ $checklist['tank_dips']['is_verified'] ? 'border-top border-4 border-success' : 'border-top border-4 border-warning' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge {{ $checklist['tank_dips']['is_verified'] ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $checklist['tank_dips']['is_verified'] ? '✓ Step 2 Verified' : '⚠ Step 2 Pending' }}
                        </span>
                        <span class="fs-4">📐</span>
                    </div>
                    <h5 class="fw-bold mb-1">Tank Physical Dips</h5>
                    <small class="text-muted d-block mb-3">ٹینکیوں کی ڈِپ پیمائش</small>

                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Active Fuel Tanks:</span>
                        <strong class="text-dark">{{ $checklist['tank_dips']['active_tanks'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Dips Recorded Today:</span>
                        <strong class="{{ $checklist['tank_dips']['dips_recorded'] < $checklist['tank_dips']['active_tanks'] ? 'text-danger' : 'text-success' }}">
                            {{ $checklist['tank_dips']['dips_recorded'] }} / {{ $checklist['tank_dips']['active_tanks'] }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Cash Reconciliation -->
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm {{ $checklist['cash']['is_verified'] ? 'border-top border-4 border-success' : 'border-top border-4 border-warning' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge {{ $checklist['cash']['is_verified'] ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $checklist['cash']['is_verified'] ? '✓ Step 3 Verified' : '⚠ Step 3 Pending' }}
                        </span>
                        <span class="fs-4">💵</span>
                    </div>
                    <h5 class="fw-bold mb-1">Cash Counted</h5>
                    <small class="text-muted d-block mb-3">گنتی شدہ نقد رقم</small>

                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Expected Cash:</span>
                        <strong class="text-dark">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expected_cash']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Actual Counted:</span>
                        <strong class="text-dark">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['actual_cash_counted']) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Bank Deposits -->
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm border-top border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-success">✓ Step 4 Verified</span>
                        <span class="fs-4">🏦</span>
                    </div>
                    <h5 class="fw-bold mb-1">Bank Deposits</h5>
                    <small class="text-muted d-block mb-3">بینک میں جمع شدہ رقوم</small>

                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Deposits Count:</span>
                        <strong class="text-dark">{{ $checklist['bank_deposits']['count'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Total Deposited:</span>
                        <strong class="text-success">{{ \App\Support\PakistaniCurrency::format($checklist['bank_deposits']['total_amount']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Details & Closing Execution Form -->
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark">Day {{ $date }} Financial & Stock Reconciliations</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered align-middle">
                        <tbody>
                            <tr>
                                <td><strong>Total Fuel Litres Sold</strong></td>
                                <td class="text-end fw-bold">{{ number_format((float) $checklist['sales_totals']['total_litres'], 3) }} L</td>
                            </tr>
                            <tr>
                                <td><strong>Total Sales Turnover</strong></td>
                                <td class="text-end fw-bold">{{ \App\Support\PakistaniCurrency::format($checklist['sales_totals']['total_amount']) }}</td>
                            </tr>
                            <tr>
                                <td>&bull; Cash Sales (نقد)</td>
                                <td class="text-end">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_sales']) }}</td>
                            </tr>
                            <tr>
                                <td>&bull; Credit / Udhaar Sales (ادھار)</td>
                                <td class="text-end">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['credit_sales']) }}</td>
                            </tr>
                            <tr>
                                <td>&bull; OMC / Fleet Cards</td>
                                <td class="text-end">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['card_sales']) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Customer Udhaar Receipts</strong></td>
                                <td class="text-end text-success">+{{ \App\Support\PakistaniCurrency::format($checklist['cash']['customer_receipts']) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Station Cash Expenses Paid</strong></td>
                                <td class="text-end text-danger">-{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expenses_paid']) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Bank Deposits Made</strong></td>
                                <td class="text-end text-primary">-{{ \App\Support\PakistaniCurrency::format($checklist['cash']['bank_deposits']) }}</td>
                            </tr>
                            <tr class="table-light">
                                <td><strong>Calculated Expected Cash</strong></td>
                                <td class="text-end fw-bold">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expected_cash']) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Actual Cash Handed In</strong></td>
                                <td class="text-end fw-bold">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['actual_cash_counted']) }}</td>
                            </tr>
                            <tr class="{{ (float) $checklist['cash']['cash_variance'] >= 0 ? 'table-success' : 'table-danger' }}">
                                <td><strong>Cash Short / Over (کمی یا بیشی)</strong></td>
                                <td class="text-end fw-bold fs-6">
                                    @if((float) $checklist['cash']['cash_variance'] == 0)
                                        <span class="badge bg-success">Rs. 0.00 (Balanced)</span>
                                    @elseif((float) $checklist['cash']['cash_variance'] > 0)
                                        <span class="text-success">+{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_variance']) }} (Over)</span>
                                    @else
                                        <span class="text-danger">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_variance']) }} (Short)</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-top: 4px solid #D71920 !important;">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark">Execute Daily Closing & Date Lock</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('closing.store') }}">
                        @csrf
                        <input type="hidden" name="closing_date" value="{{ $date }}">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Actual Physical Cash Counted (Rs.)</label>
                            <input type="number" step="0.01" name="actual_cash_counted" class="form-control form-control-lg fw-bold text-dark"
                                   value="{{ old('actual_cash_counted', $checklist['cash']['actual_cash_counted']) }}" required>
                            <small class="text-muted">Total physical cash verified in station safe</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Closing Notes / Manager Remarks</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Enter notes or explanation for any cash/dip variance..."></textarea>
                        </div>

                        @if(! $checklist['ready_to_close'])
                            <div class="alert alert-warning small mb-3">
                                <strong>Checklist Notice:</strong>
                                @if(! $checklist['shifts']['is_verified'])
                                    <div>&bull; There are still {{ $checklist['shifts']['open_or_pending'] }} open/pending shift(s).</div>
                                @endif
                                @if(! $checklist['tank_dips']['is_verified'])
                                    <div>&bull; Dip readings missing for {{ $checklist['tank_dips']['active_tanks'] - $checklist['tank_dips']['dips_recorded'] }} tank(s).</div>
                                @endif
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="force_override" value="1" id="overrideCheck">
                                <label class="form-check-label small fw-bold text-danger" for="overrideCheck">
                                    Manager Emergency Override (Proceed despite incomplete checklist)
                                </label>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-danger w-100 py-2.5 fw-bold fs-6">
                            🔒 Complete Daily Closing & Lock Day
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
