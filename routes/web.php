<?php

use App\Http\Controllers\SystemStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System routes
|--------------------------------------------------------------------------
| Phase 0 only. From Phase 1 onward "/" is served by the auth / dashboard
| stack and this section is replaced by the POS / master-data route groups.
*/

Route::get('/', [SystemStatusController::class, 'index'])->name('system.status');
Route::get('/health', [SystemStatusController::class, 'health'])->name('system.health');
