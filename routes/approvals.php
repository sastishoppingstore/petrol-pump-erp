<?php

use App\Http\Controllers\ApprovalController;
use Illuminate\Support\Facades\Route;

Route::prefix('approvals')->name('approvals.')->group(function () {
    Route::get('/', [ApprovalController::class, 'index'])->name('index');
    Route::post('/', [ApprovalController::class, 'store'])->name('store');
    Route::post('/{approvalRequest}/approve', [ApprovalController::class, 'approve'])->name('approve');
    Route::post('/{approvalRequest}/reject', [ApprovalController::class, 'reject'])->name('reject');
});
