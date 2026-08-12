<?php

use App\Modules\Sales\Actions\RecordPosSale;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a POS sale can be paid with a split across multiple payment methods', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id);

    $invoice = app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '60.00'],
        ['method' => 'mobile_money', 'amount' => '40.00', 'reference' => 'MM-REF-1'],
    ]);

    expect($invoice->grand_total->toMajor())->toBe('100.00')
        ->and($invoice->status)->toBe('paid')
        ->and($invoice->source)->toBe('pos')
        ->and($invoice->payments)->toHaveCount(2);

    expect($invoice->payments->every(fn ($payment) => $payment->till_session_id === $till->id))->toBeTrue();
});

test('a POS sale with an unpaid remainder within the customer credit limit is allowed and stays unpaid/partial', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id);

    $invoice = app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '40.00'],
    ]);

    expect($invoice->status)->toBe('partially_paid');
});
