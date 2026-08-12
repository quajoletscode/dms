<?php

use App\Modules\Sales\Models\Invoice;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a pos sale can be recorded via http with full payment', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('0');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    $cashier = createCashierUser();
    $this->actingAs($cashier);
    openTillSessionFixture($warehouse->id);

    $this->actingAs($cashier)->post('/pos', [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
        ],
        'payments' => [
            ['method' => 'cash', 'amount' => '100.00'],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->source)->toBe('pos')
        ->and($invoice->status)->toBe('paid')
        ->and($invoice->grand_total->toMajor())->toBe('100.00');
});

test('a pos sale with split payments across methods can be recorded via http', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    $cashier = createCashierUser();
    $this->actingAs($cashier);
    openTillSessionFixture($warehouse->id);

    $this->actingAs($cashier)->post('/pos', [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
        ],
        'payments' => [
            ['method' => 'cash', 'amount' => '60.00'],
            ['method' => 'mobile_money', 'amount' => '40.00', 'reference' => 'MM-1'],
        ],
    ])->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->payments)->toHaveCount(2)
        ->and($invoice->status)->toBe('paid');
});

test('a discount above the threshold is rejected gracefully, not with a 500', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    $cashier = createCashierUser();
    $this->actingAs($cashier);
    openTillSessionFixture($warehouse->id);

    $this->actingAs($cashier)->post('/pos', [
        'customer_id' => $customer->id,
        'items' => [
            // 100.00 gross, 20.00 discount = 20%, above the 10% default threshold
            ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '20.00'],
        ],
        'payments' => [
            ['method' => 'cash', 'amount' => '80.00'],
        ],
    ])->assertSessionHasErrors('items');

    expect(Invoice::query()->count())->toBe(0);
});

test('a sale that exceeds the customer credit limit is rejected gracefully, not with a 500', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('0');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    $cashier = createCashierUser();
    $this->actingAs($cashier);
    openTillSessionFixture($warehouse->id);

    $this->actingAs($cashier)->post('/pos', [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
        ],
        'payments' => [
            ['method' => 'cash', 'amount' => '50.00'],
        ],
    ])->assertSessionHasErrors('customer_id');

    expect(Invoice::query()->count())->toBe(0);
});

test('selling with no open till session redirects to open one instead of erroring', function () {
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();

    $cashier = createCashierUser();

    $this->actingAs($cashier)->post('/pos', [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '1', 'unit_price' => '20.00', 'discount' => '0'],
        ],
        'payments' => [],
    ])->assertRedirect('/till-sessions/create');
});

test('a user without sales.pos cannot ring up a sale', function () {
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();

    $dsr = createDsrUser();

    $this->actingAs($dsr)->post('/pos', [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'qty' => '1', 'unit_price' => '20.00', 'discount' => '0'],
        ],
        'payments' => [],
    ])->assertForbidden();
});
