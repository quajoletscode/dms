<?php

use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('invoice tax is computed from the tax rate in effect at conversion time, not order time', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Widget', 'unit_id' => $unit->id, 'tax_rate' => 10,
    ]);
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);

    expect($so->items->first()->tax->toMajor())->toBe('10.00'); // 100 * 10%

    $product->update(['tax_rate' => 20]);
    $so = fulfillSalesOrderFixture($so);

    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($so);

    expect($invoice->items->first()->tax_rate)->toBe('20.00')
        ->and($invoice->items->first()->tax->toMajor())->toBe('20.00'); // 100 * 20%, not 10%
});
