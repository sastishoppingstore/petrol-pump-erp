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

// Tank Dip Calibration & Physical Dip Readings (Tile 10)
Route::middleware('permission:stock.view')->group(function () {
    Route::get('/tanks/{tank}/calibration', [\App\Http\Controllers\TankCalibrationController::class, 'index'])->name('tanks.calibration');
    Route::post('/tanks/{tank}/calibration', [\App\Http\Controllers\TankCalibrationController::class, 'store'])->name('tanks.calibration.store');
    Route::post('/tanks/{tank}/calibration/bulk', [\App\Http\Controllers\TankCalibrationController::class, 'bulkStore'])->name('tanks.calibration.bulk');
    Route::post('/tanks/{tank}/calibration/evaluate', [\App\Http\Controllers\TankCalibrationController::class, 'evaluateDip'])->name('tanks.calibration.evaluate');
});

// Forecourt Meter Operations & Calibration (Tile 1)
Route::middleware('permission:fuel.view')->group(function () {
    Route::get('/forecourt/meters', [\App\Http\Controllers\Forecourt\ForecourtMeterController::class, 'index'])
        ->name('forecourt.meters.index');
    Route::post('/forecourt/meters/test', [\App\Http\Controllers\Forecourt\ForecourtMeterController::class, 'storeTest'])
        ->name('forecourt.meters.test');
    Route::post('/forecourt/meters/rollover', [\App\Http\Controllers\Forecourt\ForecourtMeterController::class, 'storeRollover'])
        ->name('forecourt.meters.rollover');
    Route::post('/forecourt/meters/closing', [\App\Http\Controllers\Forecourt\ForecourtMeterController::class, 'storeReading'])
        ->name('forecourt.meters.closing');
});

Route::middleware('permission:stock.stock_adjustment')->group(function () {
    Route::post('/forecourt/meters/correction', [\App\Http\Controllers\Forecourt\ForecourtMeterController::class, 'storeCorrection'])
        ->name('forecourt.meters.correction');
});

// Internal Fuel Consumption (Generator, Station Vehicle, Testing, Cleaning)
Route::middleware('permission:stock.view')->group(function () {
    Route::get('/internal-consumption', [\App\Http\Controllers\InternalFuelConsumptionController::class, 'index'])->name('internal-consumption.index');
    Route::get('/internal-consumption/daily-summary', [\App\Http\Controllers\InternalFuelConsumptionController::class, 'dailySummary'])->name('internal-consumption.daily-summary');
    Route::get('/internal-consumption/history', [\App\Http\Controllers\InternalFuelConsumptionController::class, 'history'])->name('internal-consumption.history');
    Route::get('/internal-consumption/generator-cost', [\App\Http\Controllers\InternalFuelConsumptionController::class, 'generatorCost'])->name('internal-consumption.generator-cost');
    Route::get('/internal-consumption/monthly-report', [\App\Http\Controllers\InternalFuelConsumptionController::class, 'monthlyReport'])->name('internal-consumption.monthly-report');
    Route::get('/internal-consumption/budget-estimate', [\App\Http\Controllers\InternalFuelConsumptionController::class, 'budgetEstimate'])->name('internal-consumption.budget-estimate');
});

Route::middleware('permission:stock.stock_adjustment')->group(function () {
    Route::post('/internal-consumption', [\App\Http\Controllers\InternalFuelConsumptionController::class, 'store'])->name('internal-consumption.store');
    Route::post('/internal-consumption/{consumption}/reverse', [\App\Http\Controllers\InternalFuelConsumptionController::class, 'reverse'])->name('internal-consumption.reverse');
});
