{{-- Auto-Generated Reports Section --}}
<div class="mt-10">
    <div class="page-head !mb-4">
        <h1 class="!text-2xl">📊 {{ __('admin.report_widgets.title') }}</h1>
        <p>{{ __('admin.report_widgets.updated') }}</p>
        <p class="mt-2">
            <a href="{{ route('reports.auto-reports') }}" class="btn-3d btn-3d-ghost btn-3d-sm">⚙️ {{ __('admin.report_widgets.settings_btn') }}</a>
        </p>
    </div>

    {{-- Period Tabs — pill style, tab behaviour app.js shim se chalta hai --}}
    <div class="mb-5 flex flex-wrap justify-center gap-2" role="tablist" id="reportPeriodTabs">
        <button class="btn-3d btn-3d-primary btn-3d-sm nav-link active" id="report-24h-tab" data-bs-toggle="tab"
                data-bs-target="#report-24h" type="button" role="tab">
            📅 {{ __('admin.report_widgets.tab_24h') }}
        </button>
        <button class="btn-3d btn-3d-ghost btn-3d-sm nav-link" id="report-12h-tab" data-bs-toggle="tab"
                data-bs-target="#report-12h" type="button" role="tab">
            🕐 {{ __('admin.report_widgets.tab_12h') }}
        </button>
        <button class="btn-3d btn-3d-ghost btn-3d-sm nav-link" id="report-7d-tab" data-bs-toggle="tab"
                data-bs-target="#report-7d" type="button" role="tab">
            📆 {{ __('admin.report_widgets.tab_7d') }}
        </button>
        <button class="btn-3d btn-3d-ghost btn-3d-sm nav-link" id="report-15d-tab" data-bs-toggle="tab"
                data-bs-target="#report-15d" type="button" role="tab">
            📊 {{ __('admin.report_widgets.tab_15d') }}
        </button>
        <button class="btn-3d btn-3d-ghost btn-3d-sm nav-link" id="report-30d-tab" data-bs-toggle="tab"
                data-bs-target="#report-30d" type="button" role="tab">
            📈 {{ __('admin.report_widgets.tab_30d') }}
        </button>
    </div>

    {{-- Report Content --}}
    <div class="tab-content" id="reportPeriodContent">
        <div class="tab-pane fade show active" id="report-24h" role="tabpanel">
            @include('dashboard.report-widget-content', ['period' => '24h', 'report' => $reports['24h'] ?? null])
        </div>

        <div class="tab-pane fade" id="report-12h" role="tabpanel">
            @include('dashboard.report-widget-content', ['period' => '12h', 'report' => $reports['12h'] ?? null])
        </div>

        <div class="tab-pane fade" id="report-7d" role="tabpanel">
            @include('dashboard.report-widget-content', ['period' => '7d', 'report' => $reports['7d'] ?? null])
        </div>

        <div class="tab-pane fade" id="report-15d" role="tabpanel">
            @include('dashboard.report-widget-content', ['period' => '15d', 'report' => $reports['15d'] ?? null])
        </div>

        <div class="tab-pane fade" id="report-30d" role="tabpanel">
            @include('dashboard.report-widget-content', ['period' => '30d', 'report' => $reports['30d'] ?? null])
        </div>
    </div>
</div>
