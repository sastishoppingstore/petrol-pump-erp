@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Dashboard</h1>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="erp-stat">
                <div class="erp-stat-label">Branches</div>
                <div class="erp-stat-value">{{ $branchCount }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="erp-stat">
                <div class="erp-stat-label">Active Users</div>
                <div class="erp-stat-value">{{ $userCount }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="erp-stat">
                <div class="erp-stat-label">Roles</div>
                <div class="erp-stat-value">{{ $roleCount }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="erp-stat">
                <div class="erp-stat-label">Active Branch</div>
                <div class="erp-stat-value" style="font-size: 1.1rem;">
                    {{ $activeBranch ? \App\Models\Branch::find($activeBranch)?->name : 'All' }}
                </div>
            </div>
        </div>
    </div>

    <div class="erp-card p-4">
        <p class="text-muted mb-0">
            System setup is in place. Sales, stock, shifts and reporting modules arrive in the
            following phases.
        </p>
    </div>
@endsection
