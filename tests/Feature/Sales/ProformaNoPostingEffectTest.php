<?php

use App\Modules\Sales\Actions\CreateProformaInvoice;
use App\Modules\Warehouse\Models\StockBalance;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('creating a proforma invoice has no stock or accounting effect', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createManagerUser());

    $proforma = app(CreateProformaInvoice::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ], now()->addDays(7)->toDateString());

    expect($proforma->status)->toBe('open')
        ->and($proforma->grand_total->toMajor())->toBe('100.00');

    $balance = StockBalance::query()
        ->where('location_type', 'warehouse')
        ->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect((string) $balance->qty_on_hand)->toBe('10.000');
    $this->assertDatabaseCount('journal_entries', 0);
    $this->assertDatabaseCount('stock_ledger', 1); // just the fixture's receipt, nothing from the proforma
});
