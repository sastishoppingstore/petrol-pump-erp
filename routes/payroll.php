<?php

use App\Http\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

Route::prefix('payroll')->name('payroll.')->middleware(['auth', 'verified'])->group(function () {
    // Attendance management
    Route::get('/attendance', [PayrollController::class, 'attendance'])->name('attendance');
    Route::post('/attendance', [PayrollController::class, 'recordAttendance'])->name('attendance.store');
    Route::get('/attendance/{employee}/summary', [PayrollController::class, 'attendanceSummary'])->name('attendance.summary');

    // Staff advances
    Route::get('/advances', [PayrollController::class, 'advances'])->name('advances');
    Route::post('/advances', [PayrollController::class, 'recordAdvance'])->name('advances.store');

    // Salary & payroll
    Route::get('/payroll', [PayrollController::class, 'payroll'])->name('payroll');
    Route::post('/payroll/generate', [PayrollController::class, 'generateSalarySheets'])->name('generate');
    Route::get('/payroll/summary', [PayrollController::class, 'summary'])->name('summary');
    Route::get('/salary/{salary}', [PayrollController::class, 'showSalary'])->name('salary.show');
    Route::post('/salary/{salary}/mark-paid', [PayrollController::class, 'markSalaryPaid'])->name('salary.mark-paid');
});
