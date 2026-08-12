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

/**
 * Chosen so summing each line's independently-rounded tax (376 + 188 = 564
 * pesewas) disagrees with rounding the tax on the combined subtotal
 * (round(4516 * 0.125) = 565) by exactly one pesewa — the classic rounding
 * bug this test guards against. The invoice must always sum per-line
 * rounded amounts, never recompute tax from the document-level subtotal.
 */
test('line-level tax rounding sums exactly to the invoice total, not a re-rounded document-level figure', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);

    $productA = app(CreateProduct::class)->execute([
        'sku' => 'SKU-A-'.uniqid(), 'name' => 'A', 'unit_id' => $unit->id, 'tax_rate' => 12.5,
    ]);
    $productB = app(CreateProduct::class)->execute([
        'sku' => 'SKU-B-'.uniqid(), 'name' => 'B', 'unit_id' => $unit->id, 'tax_rate' => 12.5,
    ]);
    receiveStockFixture('warehouse', $warehouse->id, $productA, '10');
    receiveStockFixture('warehouse', $warehouse->id, $productB, '10');

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $productA->id, 'qty' => '3', 'unit_price' => '10.03'],
        ['product_id' => $productB->id, 'qty' => '1', 'unit_price' => '15.07'],
    ]);

    $so = fulfillSalesOrderFixture($so);
    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($so);

    $lineTaxSum = $invoice->items->reduce(fn (int $carry, $item) => $carry + $item->tax->minorUnits, 0);

    expect($invoice->subtotal->minorUnits)->toBe(4516)
        ->and($lineTaxSum)->toBe(564)
        ->and($invoice->tax_total->minorUnits)->toBe(564) // NOT 565, which round(4516 * 0.125) would give
        ->and($invoice->grand_total->minorUnits)->toBe($invoice->subtotal->minorUnits + $invoice->tax_total->minorUnits);
});
