<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('role:super_admin,admin');
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('role:super_admin,admin');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store'])->middleware('role:super_admin,admin,user');
    Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])
        ->name('invoices.update')
        ->middleware('role:super_admin,admin,user')
        ->where('invoice', '.*');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->middleware('role:super_admin,admin,user')->where('invoice', '.*');

    Route::resource('quotations', App\Http\Controllers\QuotationController::class)->only(['index', 'create', 'store'])->middleware('role:super_admin,admin,user');
    Route::get('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'show'])->name('quotations.show')->middleware('role:super_admin,admin,user')->where('quotation', '.*');
    Route::post('/quotations/{quotation}/convert-to-invoice', [App\Http\Controllers\QuotationController::class, 'convertToInvoice'])->name('quotations.convert')->middleware('role:super_admin,admin,user');



    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::get('/billing', [ProfileController::class, 'billing'])->name('billing');
        Route::get('/security', [ProfileController::class, 'security'])->name('security');
        
        Route::prefix('bank-accounts')->name('bank-accounts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\BankAccountController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\BankAccountController::class, 'store'])->name('store');
            Route::put('/{bankAccount}', [\App\Http\Controllers\BankAccountController::class, 'update'])->name('update');
            Route::delete('/{bankAccount}', [\App\Http\Controllers\BankAccountController::class, 'destroy'])->name('destroy');
        });
    });
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\SettingController::class, 'index'])->name('index');
        Route::put('/', [App\Http\Controllers\SettingController::class, 'update'])->name('update');
        
        Route::post('/taxes', [App\Http\Controllers\TaxController::class, 'store'])->name('taxes.store');
        Route::put('/taxes/{tax}', [App\Http\Controllers\TaxController::class, 'update'])->name('taxes.update');
        Route::delete('/taxes/{tax}', [App\Http\Controllers\TaxController::class, 'destroy'])->name('taxes.destroy');
    });
});
