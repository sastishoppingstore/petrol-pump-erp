<?php

use App\Http\Controllers\Fuel\FuelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fuel master data (Phase 2)
|--------------------------------------------------------------------------
| fuels, prices, tanks, tank readings, dispensers, nozzles, meters
|
*/

Route::middleware('permission:fuel.view')->group(function () {
    Route::get('/fuels', [FuelController::class, 'fuels'])->name('fuels.index');
    Route::get('/fuels/{fuel}/edit', [FuelController::class, 'editFuel'])->name('fuels.edit');
    Route::get('/tanks', [FuelController::class, 'tanks'])->name('tanks.index');
    Route::get('/tanks/{tank}/edit', [FuelController::class, 'editTank'])->name('tanks.edit');
    Route::get('/tank-readings', [FuelController::class, 'readings'])->name('tank-readings.index');
    Route::get('/dispensers', [FuelController::class, 'dispensers'])->name('dispensers.index');
    Route::get('/dispensers/{dispenser}/edit', [FuelController::class, 'editDispenser'])->name('dispensers.edit');
    Route::get('/nozzles', [FuelController::class, 'nozzles'])->name('nozzles.index');
    Route::get('/nozzles/{nozzle}/edit', [FuelController::class, 'editNozzle'])->name('nozzles.edit');
    Route::get('/meter-readings', [FuelController::class, 'meterReadings'])->name('meter-readings.index');
});

Route::middleware('permission:fuel.view')->group(function () {
    Route::get('/fuel-prices', [FuelController::class, 'prices'])->name('fuel-prices.index');
});

// Creating a tank / dispenser / nozzle needs stock+fuel read, but writing the
// master record itself is gated on the create permission below.
Route::middleware('permission:fuel.create')->group(function () {
    Route::get('/fuels/create', [FuelController::class, 'createFuel'])->name('fuels.create');
    Route::post('/fuels', [FuelController::class, 'storeFuel'])->name('fuels.store');

    Route::get('/dispensers/create', [FuelController::class, 'createDispenser'])->name('dispensers.create');
    Route::post('/dispensers', [FuelController::class, 'storeDispenser'])->name('dispensers.store');

    Route::get('/nozzles/create', [FuelController::class, 'createNozzle'])->name('nozzles.create');
    Route::post('/nozzles', [FuelController::class, 'storeNozzle'])->name('nozzles.store');

    Route::get('/tanks/create', [FuelController::class, 'createTank'])->name('tanks.create');
    Route::post('/tanks', [FuelController::class, 'storeTank'])->name('tanks.store');
});

Route::middleware('permission:fuel.edit')->group(function () {
    Route::put('/fuels/{fuel}', [FuelController::class, 'updateFuel'])->name('fuels.update');
    Route::put('/dispensers/{dispenser}', [FuelController::class, 'updateDispenser'])->name('dispensers.update');
    Route::put('/nozzles/{nozzle}', [FuelController::class, 'updateNozzle'])->name('nozzles.update');
    Route::put('/tanks/{tank}', [FuelController::class, 'updateTank'])->name('tanks.update');
});

Route::middleware('permission:fuel.price_change')->group(function () {
    Route::post('/fuel-prices', [FuelController::class, 'storePrice'])->name('fuel-prices.store');
});

Route::middleware('permission:stock.view')->group(function () {
    Route::post('/tank-readings', [FuelController::class, 'storeReading'])->name('tank-readings.store');
});

// Meter correction is an audited, privileged action (spec section 3).
Route::middleware('permission:stock.stock_adjustment')->group(function () {
    Route::post('/nozzles/{nozzle}/meter-correction', [FuelController::class, 'correctMeter'])
        ->name('nozzles.meter-correction');
});
