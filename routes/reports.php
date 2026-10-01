<?php

use App\Http\Controllers\Reports\ReportController;
use App\Support\PermissionList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:' . PermissionList::REPORTS_VIEW])->prefix('reports')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('reports.index');

    // Auto-Report admin control — wildcard '/{report}' se PEHLE lazmi
    Route::get('/auto-reports', [ReportController::class, 'autoSettings'])->name('reports.auto-reports');
    Route::post('/auto-reports', [ReportController::class, 'updateAutoSettings'])
        ->middleware('permission:' . PermissionList::SETTINGS_EDIT)
        ->name('reports.auto-reports.update');
    Route::post('/auto-reports/generate', [ReportController::class, 'generateAutoReportsNow'])
        ->middleware('permission:' . PermissionList::SETTINGS_EDIT)
        ->name('reports.auto-reports.generate');
    Route::get('/auto-reports/{autoReport}/pdf', [ReportController::class, 'downloadAutoReportPdf'])
        ->name('reports.download.pdf');
    Route::get('/auto-reports/{autoReport}/excel', [ReportController::class, 'downloadAutoReportExcel'])
        ->name('reports.download.excel');

    Route::get('/sales', [ReportController::class, 'show'])->defaults('report', 'sales')->name('reports.sales');
    Route::get('/stock', [ReportController::class, 'show'])->defaults('report', 'tank-stock-variance')->name('reports.stock');
    Route::get('/financial', [ReportController::class, 'show'])->defaults('report', 'profit-and-loss')->name('reports.financial');
    Route::get('/{report}', [ReportController::class, 'show'])->name('reports.show');
});
