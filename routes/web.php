<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Modules\MasterData\Http\Controllers\CustomerController;
use App\Modules\MasterData\Http\Controllers\ProductController;
use App\Modules\MasterData\Http\Controllers\SupplierController;
use App\Modules\Van\Http\Controllers\VanController;
use App\Modules\Warehouse\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::singleton('/profile', ProfileController::class);

    Route::resource('warehouses', WarehouseController::class)->except(['show', 'destroy']);
    Route::resource('vans', VanController::class)->except(['show', 'destroy']);
    Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);
    Route::resource('customers', CustomerController::class)->except(['show', 'destroy']);
});
