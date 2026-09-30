@extends('layouts.app')

@section('title', '22 Core Reports — رپورٹنگ مرکز')

@section('content')
<div class="container-fluid py-4">
    <!-- Header banner with Vital Petroleum branding -->
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #D71920 0%, #A30F15 100%); color: #FFFFFF; border-radius: 10px;">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <span class="badge bg-white text-danger mb-2 px-3 py-1 font-monospace fw-bold">TILE 15 &bull; CORE ERP REPORTS</span>
                <h2 class="h3 fw-bold mb-1">Station Analytics & Core Reports</h2>
                <div class="text-white-50" style="font-family: 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma; font-size: 1.1rem;">
                    مہر فلنگ اسٹیشن (وائٹل پیٹرولیم) — مکمل 22 کاروباری اور مالیاتی رپورٹس
                </div>
            </div>
            <div class="text-end mt-3 mt-md-0">
                <span class="badge bg-dark bg-opacity-25 text-white px-3 py-2 fs-6">
                    {{ count($reports) }} Reports Ready
                </span>
            </div>
        </div>
    </div>

    <!-- 6 Report Categories Grid -->
    <div class="row g-4">
        @foreach($grouped as $groupName => $items)
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #D71920 !important; border-radius: 8px;">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark">
                            @switch($groupName)
                                @case('Sales')
                                    <span class="me-2">📊</span> Sales & Forecourt
                                    @break
                                @case('Cash & Bank')
                                    <span class="me-2">💵</span> Cash & Banking
                                    @break
                                @case('Udhaar / Customers')
                                    <span class="me-2">🧑‍🤝‍🧑</span> Udhaar (Credit Customers)
                                    @break
                                @case('Suppliers')
                                    <span class="me-2">🏭</span> Suppliers & Decantation
                                    @break
                                @case('Stock')
                                    <span class="me-2">🛢️</span> Fuel Tanks & Stock
                                    @break
                                @case('Financial & HR')
                                    <span class="me-2">📈</span> General Ledger & HR
                                    @break
                                @default
                                    <span class="me-2">📁</span> {{ $groupName }}
                            @endswitch
                        </h5>
                        <span class="badge bg-light text-secondary rounded-pill px-2.5 py-1">{{ count($items) }} reports</span>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($items as $key => $r)
                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 px-4 border-light">
                                    <div>
                                        <a href="{{ route('reports.show', $key) }}" class="text-decoration-none fw-semibold text-dark d-block">
                                            {{ $r['title'] }}
                                        </a>
                                        <small class="text-muted" style="font-family: 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma; font-size: 0.95rem;">
                                            {{ $r['urdu'] }}
                                        </small>
                                    </div>
                                    <a href="{{ route('reports.show', $key) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        View &rarr;
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
