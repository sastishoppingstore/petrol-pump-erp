<?php

use App\Http\Controllers\ChequeController;
use Illuminate\Support\Facades\Route;

Route::prefix('cheques')->name('cheques.')->group(function () {
    Route::get('/', [ChequeController::class, 'index'])->name('index');
    Route::get('/create', [ChequeController::class, 'create'])->name('create');
    Route::post('/', [ChequeController::class, 'store'])->name('store');
    Route::get('/{cheque}', [ChequeController::class, 'show'])->name('show');
    Route::post('/{cheque}/deposit', [ChequeController::class, 'deposit'])->name('deposit');
    Route::post('/{cheque}/clear', [ChequeController::class, 'clear'])->name('clear');
    Route::post('/{cheque}/bounce', [ChequeController::class, 'bounce'])->name('bounce');
    Route::post('/{cheque}/cancel', [ChequeController::class, 'cancel'])->name('cancel');
});
