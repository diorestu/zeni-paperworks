<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\BillingPaymentController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [LoginController::class, 'createRegister'])->name('register');
    Route::post('/register', [LoginController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/email/verification-notification', [LoginController::class, 'sendVerification'])
        ->name('verification.send')
        ->middleware('throttle:6,1');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard')->with('status', 'Email berhasil diverifikasi.');
    })->middleware(['signed'])->name('verification.verify');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/super-admin/users/{user}/verify', [DashboardController::class, 'verifyUser'])
        ->name('super-admin.users.verify')
        ->middleware('role:super_admin');

    // Unverified users can still access dashboard.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('verified_account')->group(function () {
        Route::resource('clients', ClientController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('role:admin');
        Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('role:admin');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store'])->middleware('role:admin,user');
        Route::get('/invoices/{invoice}/download-pdf', [InvoiceController::class, 'downloadPdf'])
            ->name('invoices.download-pdf')
            ->middleware('role:admin,user')
            ->where('invoice', '.*');
        Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])
            ->name('invoices.update')
            ->middleware('role:admin,user')
            ->where('invoice', '.*');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->middleware('role:admin,user')->where('invoice', '.*');

        Route::resource('quotations', App\Http\Controllers\QuotationController::class)->only(['index', 'create', 'store'])->middleware('role:admin,user');
        Route::get('/quotations/{quotation}/download-pdf', [App\Http\Controllers\QuotationController::class, 'downloadPdf'])
            ->name('quotations.download-pdf')
            ->middleware('role:admin,user')
            ->where('quotation', '.*');
        Route::get('/quotations/{quotation}/edit', [App\Http\Controllers\QuotationController::class, 'edit'])->name('quotations.edit')->middleware('role:admin,user')->where('quotation', '.*');
        Route::patch('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'update'])->name('quotations.update')->middleware('role:admin,user')->where('quotation', '.*');
        Route::get('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'show'])->name('quotations.show')->middleware('role:admin,user')->where('quotation', '.*');
        Route::post('/quotations/{quotation}/convert-to-invoice', [App\Http\Controllers\QuotationController::class, 'convertToInvoice'])->name('quotations.convert')->middleware('role:admin,user')->where('quotation', '.*');

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('edit');
            Route::put('/', [ProfileController::class, 'update'])->name('update');

            Route::prefix('bank-accounts')->name('bank-accounts.')->group(function () {
                Route::get('/', [\App\Http\Controllers\BankAccountController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\BankAccountController::class, 'store'])->name('store');
                Route::put('/{bankAccount}', [\App\Http\Controllers\BankAccountController::class, 'update'])->name('update');
                Route::delete('/{bankAccount}', [\App\Http\Controllers\BankAccountController::class, 'destroy'])->name('destroy');
            });
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/billing', [ProfileController::class, 'billing'])->name('billing')->middleware('role:admin,user');
            Route::get('/billing/checkout', [ProfileController::class, 'billingCheckout'])->name('billing.checkout.page')->middleware('role:admin,user');
            Route::get('/billing/success', [ProfileController::class, 'billingSuccess'])->name('billing.success')->middleware('role:admin,user');
            Route::post('/billing/checkout', [BillingPaymentController::class, 'createTransaction'])->name('billing.checkout')->middleware('role:admin,user');
            Route::post('/billing/confirm', [BillingPaymentController::class, 'confirmTransaction'])->name('billing.confirm')->middleware('role:admin,user');
            Route::get('/billing/receipts/{invoice}/download', [BillingPaymentController::class, 'downloadReceipt'])->name('billing.receipts.download')->middleware('role:admin,user');
            Route::get('/reset-password', [ProfileController::class, 'security'])->name('reset-password')->middleware('role:admin,user');
            Route::put('/reset-password', [ProfileController::class, 'updatePassword'])->name('reset-password.update')->middleware('role:admin,user');
        });

        Route::prefix('settings')->name('settings.')->middleware('role:admin')->group(function () {
            Route::get('/', [App\Http\Controllers\SettingController::class, 'index'])->name('index');
            Route::put('/', [App\Http\Controllers\SettingController::class, 'update'])->name('update');
            Route::post('/sub-users', [App\Http\Controllers\SettingController::class, 'storeSubUser'])->name('sub-users.store');
            Route::delete('/sub-users/{user}', [App\Http\Controllers\SettingController::class, 'destroySubUser'])->name('sub-users.destroy');

            Route::post('/taxes', [App\Http\Controllers\TaxController::class, 'store'])->name('taxes.store');
            Route::put('/taxes/{tax}', [App\Http\Controllers\TaxController::class, 'update'])->name('taxes.update');
            Route::delete('/taxes/{tax}', [App\Http\Controllers\TaxController::class, 'destroy'])->name('taxes.destroy');
        });
    });
});

Route::post('/webhooks/midtrans', [BillingPaymentController::class, 'notification'])->name('webhooks.midtrans');
