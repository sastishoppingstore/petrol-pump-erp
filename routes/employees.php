<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('/create', [EmployeeController::class, 'create'])->name('create');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('/attendance', [EmployeeController::class, 'attendance'])->name('attendance');
    Route::post('/attendance', [EmployeeController::class, 'storeAttendance'])->name('attendance.store');
    Route::get('/payroll', [EmployeeController::class, 'payroll'])->name('payroll');
    Route::post('/payroll/generate', [EmployeeController::class, 'generatePayroll'])->name('payroll.generate');
    Route::post('/payroll/{salary}/pay', [EmployeeController::class, 'paySalary'])->name('payroll.pay');
    Route::get('/payroll/{salary}/payslip', [EmployeeController::class, 'payslip'])->name('payslip');
    Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::post('/{employee}/advances', [EmployeeController::class, 'storeAdvance'])->name('advance.store');
    Route::post('/{employee}/adjustments', [EmployeeController::class, 'storeAdjustment'])->name('adjustment.store');
});
