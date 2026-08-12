<?php

use App\Modules\Sales\Actions\RecordPosSale;
use App\Modules\Warehouse\Domain\Exceptions\InsufficientStockException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

/**
 * "Two tills racing for the last unit" — SQLite `:memory:` makes literal
 * multi-connection concurrency testing meaningless (separate connections are
 * separate databases), the same reasoning documented on
 * GrnConcurrentReceiptTest (decisions.md ADR-20). This proves the actually
 * testable guarantee: once the only unit in stock is sold, an immediately
 * following sale attempt for the same product is rejected, never oversold.
 */
test('selling the last unit in stock succeeds once and is rejected on immediate resale attempt', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '1');

    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id);

    $invoice = app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '1', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '20.00'],
    ]);

    expect($invoice->status)->toBe('paid');

    expect(fn () => app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '1', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '20.00'],
    ]))->toThrow(InsufficientStockException::class);
});
