<!-- Auto-Generated Reports Section -->
<div class="row mt-5 mb-4">
    <div class="col-12">
        <h4 class="d-flex align-items-center gap-2">
            📊 Auto-Generated Reports
            <small class="text-muted">(Updated hourly)</small>
        </h4>
    </div>
</div>

<!-- Period Tabs -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs" role="tablist" id="reportPeriodTabs">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="report-24h-tab" data-bs-toggle="tab" 
                        data-bs-target="#report-24h" type="button" role="tab">
                    📅 Daily (24h)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="report-12h-tab" data-bs-toggle="tab" 
                        data-bs-target="#report-12h" type="button" role="tab">
                    🕐 12 Hours
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="report-7d-tab" data-bs-toggle="tab" 
                        data-bs-target="#report-7d" type="button" role="tab">
                    📆 Weekly (7d)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="report-15d-tab" data-bs-toggle="tab" 
                        data-bs-target="#report-15d" type="button" role="tab">
                    📊 15 Days
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="report-30d-tab" data-bs-toggle="tab" 
                        data-bs-target="#report-30d" type="button" role="tab">
                    📈 Monthly (30d)
                </button>
            </li>
        </ul>
    </div>
</div>

<!-- Report Content -->
<div class="tab-content" id="reportPeriodContent">
    <!-- 24h Report -->
    <div class="tab-pane fade show active" id="report-24h" role="tabpanel">
        @include('dashboard.report-widget-content', ['period' => '24h', 'report' => $reports['24h'] ?? null])
    </div>
    
    <!-- 12h Report -->
    <div class="tab-pane fade" id="report-12h" role="tabpanel">
        @include('dashboard.report-widget-content', ['period' => '12h', 'report' => $reports['12h'] ?? null])
    </div>
    
    <!-- 7d Report -->
    <div class="tab-pane fade" id="report-7d" role="tabpanel">
        @include('dashboard.report-widget-content', ['period' => '7d', 'report' => $reports['7d'] ?? null])
    </div>
    
    <!-- 15d Report -->
    <div class="tab-pane fade" id="report-15d" role="tabpanel">
        @include('dashboard.report-widget-content', ['period' => '15d', 'report' => $reports['15d'] ?? null])
    </div>
    
    <!-- 30d Report -->
    <div class="tab-pane fade" id="report-30d" role="tabpanel">
        @include('dashboard.report-widget-content', ['period' => '30d', 'report' => $reports['30d'] ?? null])
    </div>
</div>

<style>
    .report-metric {
        padding: 20px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .report-metric.revenue {
        background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
    }
    
    .report-metric.profit {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    
    .report-metric.expense {
        background: linear-gradient(135deg, #ffb347 0%, #ffcc33 100%);
    }
    
    .report-metric.fuel {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .metric-label {
        font-size: 12px;
        text-transform: uppercase;
        font-weight: bold;
        opacity: 0.8;
        margin-bottom: 5px;
    }
    
    .metric-value {
        font-size: 24px;
        font-weight: bold;
    }
    
    .metric-sub {
        font-size: 12px;
        margin-top: 5px;
        opacity: 0.7;
    }
</style>
