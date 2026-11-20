<?php
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>view('welcome'));
require __DIR__.'/auth.php';
Route::middleware(['auth','verified'])->group(function(){
Route::view('/dashboard','dashboard')->name('dashboard');
Route::get('/services',[ServiceController::class,'index'])->name('services.index');
Route::get('/orders',[OrderController::class,'index'])->name('orders.index');
Route::post('/orders',[OrderController::class,'store'])->name('orders.store');
});
Route::prefix('admin')->name('admin.')->middleware(['auth','verified','admin'])->group(function(){
Route::get('/dashboard',DashboardController::class)->name('dashboard');
Route::get('/users',[AdminUserController::class,'index'])->name('users.index');
Route::get('/services',[AdminServiceController::class,'index'])->name('services.index');
Route::post('/services',[AdminServiceController::class,'store'])->name('services.store');
Route::put('/services/{service}',[AdminServiceController::class,'update'])->name('services.update');
Route::get('/orders',[AdminOrderController::class,'index'])->name('orders.index');
Route::patch('/orders/{order}',[AdminOrderController::class,'updateStatus'])->name('orders.update');
});
