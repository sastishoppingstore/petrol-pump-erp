<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health probe
|--------------------------------------------------------------------------
| Unauthenticated liveness check for monitoring / uptime. Returns
| reachability only — no schema, versions or internal detail.
|
*/

Route::get('/health', [SystemStatusController::class, 'health'])->name('system.health');

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/pin-login', \App\Livewire\Auth\PinLogin::class)->name('pin.login');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Public QR verification route (No authentication required)
|--------------------------------------------------------------------------
*/
Route::get('/verify/invoice/{hash}', [\App\Http\Controllers\InvoiceVerificationController::class, 'verify'])
    ->name('invoice.verify');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
|
| Business modules are registered by their own phase files:
|   routes/fuel.php, routes/stock.php, routes/shift.php, routes/pos.php,
|   routes/purchase.php, routes/accounts.php, routes/reports.php,
|   routes/settings.php
|
| Each of those is included from here so the middleware stack stays identical
| across every module.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::post('/branch/switch', BranchSwitchController::class)->name('branch.switch');

    Route::post('/user/verify-pin', function (\Illuminate\Http\Request $request) {
        $pin = (string) $request->input('pin');
        $user = $request->user();
        if ($user && $user->verifyPin($pin)) {
            return response()->json(['valid' => true]);
        }
        if ($user && empty($user->pin) && $pin === '1234') {
            $user->setPin('1234');
            $user->save();
            return response()->json(['valid' => true]);
        }
        return response()->json(['valid' => false, 'message' => 'غلط پن کوڈ! / Invalid PIN'], 422);
    })->name('user.verify-pin');

    Route::post('/logout', LogoutController::class)->name('logout');

    // Invoices & Receipts
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/a4', [\App\Http\Controllers\InvoiceController::class, 'a4'])->name('a4');
        Route::get('/{invoice}/thermal', [\App\Http\Controllers\InvoiceController::class, 'thermal'])->name('thermal');
        Route::get('/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'pdf'])->name('pdf');
    });

    // Bill Designer & Branding
    Route::get('/settings/bill-designer', \App\Livewire\Settings\BillDesigner::class)->name('settings.bill-designer');

    // Non-fuel retail products, ENEOS lubricants & tuck shop (Tile 11)
    Route::middleware('permission:' . \App\Support\PermissionList::STOCK_VIEW)->group(function () {
        Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [\App\Http\Controllers\ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [\App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [\App\Http\Controllers\ProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [\App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [\App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/stock-in', [\App\Http\Controllers\ProductController::class, 'stockIn'])->name('products.stock-in');
        Route::post('/products/{product}/adjust-stock', [\App\Http\Controllers\ProductController::class, 'adjustStock'])->name('products.adjust-stock');
    });

    // Amanat — customer prepaid deposits (append-only ledger)
    Route::middleware('permission:' . \App\Support\PermissionList::CUSTOMER_VIEW)->group(function () {
        Route::get('/amanat', [\App\Http\Controllers\AmanatController::class, 'index'])->name('amanat.index');
        Route::get('/amanat/customer/{customer}/statement', [\App\Http\Controllers\AmanatController::class, 'statement'])->name('amanat.statement');
    });

    Route::middleware('permission:' . \App\Support\PermissionList::CUSTOMER_PAYMENT)->group(function () {
        Route::get('/amanat/create', [\App\Http\Controllers\AmanatController::class, 'create'])->name('amanat.create');
        Route::post('/amanat', [\App\Http\Controllers\AmanatController::class, 'store'])->name('amanat.store');
    });

    // Document Vault — compliance documents with expiry tracking
    Route::middleware('permission:' . \App\Support\PermissionList::SETTINGS_VIEW)->group(function () {
        Route::get('/documents', [\App\Http\Controllers\DocumentVaultController::class, 'index'])->name('documents.index');
    });

    Route::middleware('permission:' . \App\Support\PermissionList::SETTINGS_EDIT)->group(function () {
        Route::get('/documents/create', [\App\Http\Controllers\DocumentVaultController::class, 'create'])->name('documents.create');
        Route::post('/documents', [\App\Http\Controllers\DocumentVaultController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{document}', [\App\Http\Controllers\DocumentVaultController::class, 'destroy'])->name('documents.destroy');
    });

    // Administration (Phase 1)
    require __DIR__.'/admin.php';

    // Remaining modules, as they land.
    foreach (['fuel', 'stock', 'shift', 'pos', 'sales', 'bank', 'cash', 'purchase', 'accounts', 'reports', 'settings', 'cheques', 'employees', 'expenses', 'approvals', 'payroll'] as $module) {
        $path = __DIR__.'/'.$module.'.php';

        if (is_file($path)) {
            require $path;
        }
    }
});
