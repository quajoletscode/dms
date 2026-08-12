<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Modules\MasterData\Http\Controllers\CustomerController;
use App\Modules\MasterData\Http\Controllers\ProductController;
use App\Modules\MasterData\Http\Controllers\SupplierController;
use App\Modules\Sales\Http\Controllers\InvoiceController;
use App\Modules\Sales\Http\Controllers\PosSaleController;
use App\Modules\Sales\Http\Controllers\TillSessionController;
use App\Modules\Sales\Http\Controllers\VanSaleController;
use App\Modules\Van\Http\Controllers\VanController;
use App\Modules\Warehouse\Http\Controllers\PurchaseOrderController;
use App\Modules\Warehouse\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route(Auth::check() ? 'dashboard' : 'login'))->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::singleton('/profile', ProfileController::class);

    Route::resource('warehouses', WarehouseController::class)->except(['destroy']);
    Route::resource('vans', VanController::class)->except(['destroy']);
    Route::resource('products', ProductController::class)->except(['destroy']);
    Route::resource('suppliers', SupplierController::class)->except(['destroy']);
    Route::resource('customers', CustomerController::class)->except(['destroy']);

    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    Route::resource('till-sessions', TillSessionController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('till-sessions/{till_session}/cash', [TillSessionController::class, 'cashMovement'])->name('till-sessions.cash');
    Route::post('till-sessions/{till_session}/close', [TillSessionController::class, 'close'])->name('till-sessions.close');

    Route::get('pos/create', [PosSaleController::class, 'create'])->name('pos.create');
    Route::post('pos', [PosSaleController::class, 'store'])->name('pos.store');

    Route::get('van-sales/create', [VanSaleController::class, 'create'])->name('van-sales.create');
    Route::post('van-sales', [VanSaleController::class, 'store'])->name('van-sales.store');

    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
});
