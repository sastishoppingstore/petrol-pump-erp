<?php

use App\Http\Controllers\Reports\ReportController;
use App\Support\PermissionList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:' . PermissionList::REPORTS_VIEW])->prefix('reports')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/sales', [ReportController::class, 'show'])->defaults('report', 'sales')->name('reports.sales');
    Route::get('/stock', [ReportController::class, 'show'])->defaults('report', 'tank-stock-variance')->name('reports.stock');
    Route::get('/financial', [ReportController::class, 'show'])->defaults('report', 'profit-and-loss')->name('reports.financial');
    Route::get('/{report}', [ReportController::class, 'show'])->name('reports.show');
});
