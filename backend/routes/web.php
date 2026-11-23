<?php
use App\\Http\\Controllers\\Admin\\DashboardController;
use App\\Http\\Controllers\\Admin\\OrderController as AdminOrderController;
use App\\Http\\Controllers\\Admin\\ServiceController as AdminServiceController;
use App\\Http\\Controllers\\Admin\\SupplierReportController;
use App\\Http\\Controllers\\Admin\\UserController as AdminUserController;
use App\\Http\\Controllers\\OrderController;
use App\\Http\\Controllers\\ServiceController;
use App\\Http\\Controllers\\WalletController;
use App\\Http\\Controllers\\WalletDepositController;
use Illuminate\\Support\\Facades\\Route;

Route::view('/', 'welcome');
require __DIR__.'/auth.php';

Route::post('/webhooks/paystack', [WalletDepositController::class, 'webhook'])->name('webhooks.paystack');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/deposits', [WalletDepositController::class, 'store'])->middleware('throttle:5,1')->name('wallet.deposit.store');
    Route::get('/wallet/deposits/callback', [WalletDepositController::class, 'callback'])->name('wallet.deposit.callback');
    Route::get('/wallet/deposits/cancel', [WalletDepositController::class, 'cancel'])->name('wallet.deposit.cancel');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/dashboard/supplier-balance/refresh', [DashboardController::class, 'refreshSupplierBalance'])->name('dashboard.supplier-balance.refresh');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/reports/supplier', [SupplierReportController::class, 'index'])->name('reports.supplier');
    Route::get('/services', [AdminServiceController::class, 'index'])->name('services.index');
    Route::post('/services/smm-center/sync', [AdminServiceController::class, 'syncSmmCenter'])->name('services.smm-center.sync');
    Route::post('/services', [AdminServiceController::class, 'store'])->name('admin.services.store');
    Route::put('/services/{service}', [AdminServiceController::class, 'update'])->name('services.update');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/submit-to-provider', [AdminOrderController::class, 'submitToProvider'])->name('orders.submit-to-provider');
    Route::post('/orders/{order}/refresh-provider-status', [AdminOrderController::class, 'refreshProviderStatus'])->name('orders.refresh-provider-status');
    Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refundToWallet'])->name('orders.refund');
    Route::patch('/orders/{order}', [AdminOrderController::class, 'updateStatus'])->name('orders.update');
});
