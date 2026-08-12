<?php

use App\Modules\Sales\Actions\RecordPosSale;
use App\Modules\Sales\Domain\Exceptions\DiscountOverrideRequiredException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
    // default threshold: 10% (config/sales.php)
});

test('a line discount at or below the threshold is allowed without override', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id);

    // 100.00 gross, 10.00 discount = exactly 10%
    $invoice = app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '10.00'],
    ], [
        ['method' => 'cash', 'amount' => '90.00'],
    ]);

    expect($invoice->discount_total->toMajor())->toBe('10.00');
});

test('a line discount above the threshold is blocked without pos.discount_override', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id);

    // 100.00 gross, 20.00 discount = 20%, above the 10% threshold
    expect(fn () => app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '20.00'],
    ], [
        ['method' => 'cash', 'amount' => '80.00'],
    ]))->toThrow(DiscountOverrideRequiredException::class);
});

test('a line discount above the threshold succeeds with pos.discount_override granted', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    $cashier = createCashierUser();
    $cashier->givePermissionTo('pos.discount_override');
    actingAsUser($cashier);
    $till = openTillSessionFixture($warehouse->id);

    $invoice = app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '20.00'],
    ], [
        ['method' => 'cash', 'amount' => '80.00'],
    ]);

    expect($invoice->discount_total->toMajor())->toBe('20.00');
});

test('pos.discount_override is not granted by default to the wholesale_cashier role', function () {
    $cashier = createCashierUser();

    expect($cashier->can('pos.discount_override'))->toBeFalse();
});
