<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CustomerController;
use App\Support\PermissionList;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Khata Statement (signed link, login-free)
|--------------------------------------------------------------------------
| Shared with the customer (e.g. via WhatsApp). The `signed` middleware
| validates the 30-day signature; `auth` is explicitly excluded because
| this file is required from inside the authenticated group in web.php.
*/
Route::get('/customers/{customer}/statement/public', [CustomerController::class, 'publicStatement'])
    ->name('customers.statement.public')
    ->middleware('signed')
    ->withoutMiddleware('auth');

/*
|--------------------------------------------------------------------------
| Customer & Udhaar Management
|--------------------------------------------------------------------------
*/

Route::middleware('permission:' . PermissionList::CUSTOMER_VIEW)->group(function () {
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/collection', [CollectionController::class, 'index'])->name('customers.collection');
    Route::post('/customers/collection/sms', [CollectionController::class, 'bulkSms'])->name('customers.collection.sms');
    Route::get('/customers/ageing', [CustomerController::class, 'ageingReport'])->name('customers.ageing');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
    Route::get('/customers/{customer}/statement/pdf', [CustomerController::class, 'statementPdf'])->name('customers.statement.pdf');
});

Route::middleware('permission:' . PermissionList::CUSTOMER_CREATE)->group(function () {
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{customer}/vehicles', [CustomerController::class, 'addVehicle'])->name('customers.vehicles.store');
    Route::delete('/customers/{customer}/vehicles/{vehicle}', [CustomerController::class, 'removeVehicle'])->name('customers.vehicles.destroy');
});

Route::middleware('permission:' . PermissionList::CUSTOMER_EDIT)->group(function () {
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::post('/customers/{customer}/documents', [CustomerController::class, 'storeDocument'])->name('customers.documents.store');
    Route::delete('/customers/{customer}/documents/{document}', [CustomerController::class, 'destroyDocument'])->name('customers.documents.destroy');
});

Route::middleware('permission:' . PermissionList::CUSTOMER_PAYMENT)->group(function () {
    Route::post('/customers/{customer}/payments', [CustomerController::class, 'recordPayment'])->name('customers.payments.store');
});

/*
|--------------------------------------------------------------------------
| Invoice Designer & Digital Signatures
|--------------------------------------------------------------------------
*/

Route::middleware('permission:' . PermissionList::SALES_VIEW)->group(function () {
    Route::get('/invoices/design-options', [\App\Http\Controllers\InvoiceDesignerController::class, 'getDesignOptions'])
        ->name('invoices.design-options');
    Route::get('/invoices/{sale}/snapshot', [\App\Http\Controllers\InvoiceDesignerController::class, 'viewSnapshot'])
        ->name('invoices.snapshot');
    Route::get('/invoices/{sale}/signature', [\App\Http\Controllers\InvoiceDesignerController::class, 'getSignature'])
        ->name('invoices.signature');
});

Route::middleware('permission:' . PermissionList::SALES_CREATE)->group(function () {
    Route::post('/invoices/initialize-templates', [\App\Http\Controllers\InvoiceDesignerController::class, 'initializeTemplates'])
        ->name('invoices.initialize-templates');
    Route::post('/invoices/{sale}/snapshot', [\App\Http\Controllers\InvoiceDesignerController::class, 'generateSnapshot'])
        ->name('invoices.snapshot.generate');
    Route::post('/invoices/{sale}/signature', [\App\Http\Controllers\InvoiceDesignerController::class, 'addSignature'])
        ->name('invoices.signature.add');
    Route::post('/invoices/{sale}/snapshot/regenerate', [\App\Http\Controllers\InvoiceDesignerController::class, 'regenerateSnapshot'])
        ->name('invoices.snapshot.regenerate');
});

Route::middleware('permission:' . PermissionList::SALES_VIEW)->group(function () {
    Route::post('/invoices/{sale}/export-pdf', [\App\Http\Controllers\InvoiceDesignerController::class, 'exportPdf'])
        ->name('invoices.export-pdf');
});
