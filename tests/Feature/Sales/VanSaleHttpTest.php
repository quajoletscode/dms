<?php

use App\Modules\Sales\Models\Invoice;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a van sale can be recorded via http by the assigned dsr', function () {
    $warehouse = createWarehouseFixture();
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    $customer = createCustomerFixture('0');
    $product = createProductFixture();
    receiveStockFixture('van', $van->id, $product, '10');

    $this->actingAs($dsr)->post('/van-sales', [
        'van_storage_id' => $van->id,
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '2', 'unit_price' => '15.00', 'discount' => '0'],
        ],
        'payments' => [
            ['method' => 'cash', 'amount' => '30.00'],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->source)->toBe('van')
        ->and($invoice->van_storage_id)->toBe($van->id)
        ->and($invoice->status)->toBe('paid');
});

test('a van sale exceeding the credit limit is rejected gracefully, not with a 500', function () {
    $warehouse = createWarehouseFixture();
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    $customer = createCustomerFixture('0');
    $product = createProductFixture();
    receiveStockFixture('van', $van->id, $product, '10');

    $this->actingAs($dsr)->post('/van-sales', [
        'van_storage_id' => $van->id,
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '2', 'unit_price' => '15.00', 'discount' => '0'],
        ],
        'payments' => [
            ['method' => 'cash', 'amount' => '10.00'],
        ],
    ])->assertSessionHasErrors('customer_id');

    expect(Invoice::query()->count())->toBe(0);
});

test('a manager can record a van sale on behalf of a dsr', function () {
    $warehouse = createWarehouseFixture();
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    $customer = createCustomerFixture('0');
    $product = createProductFixture();
    receiveStockFixture('van', $van->id, $product, '10');

    $manager = createManagerUser();

    $this->actingAs($manager)->post('/van-sales', [
        'van_storage_id' => $van->id,
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '1', 'unit_price' => '15.00', 'discount' => '0'],
        ],
        'payments' => [
            ['method' => 'cash', 'amount' => '15.00'],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->van_storage_id)->toBe($van->id);
});

test('a dsr with no van assigned is redirected rather than erroring on the van sale create page', function () {
    $dsr = createDsrUser();

    $this->actingAs($dsr)->get('/van-sales/create')->assertRedirect('/dashboard');
});

test('a user without van.sale cannot record a van sale', function () {
    $warehouse = createWarehouseFixture();
    $van = createVanFixture($warehouse);
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('van', $van->id, $product, '10');

    $cashier = createCashierUser();

    $this->actingAs($cashier)->post('/van-sales', [
        'van_storage_id' => $van->id,
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '1', 'unit_price' => '15.00', 'discount' => '0'],
        ],
        'payments' => [],
    ])->assertForbidden();
});
