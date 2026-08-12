<?php

use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Warehouse\Models\StockBalance;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

/**
 * Products are stocked, priced, and sold in a single fixed unit (Product::unit_id)
 * — there is no separate "sell in pieces against carton-costed stock" mode yet.
 * This proves a product whose unit is a derived unit (Carton, factor 12 over
 * Piece) still flows unambiguously through the whole sale pipeline in its
 * native unit, and that the conversion metadata behind the carton<->piece
 * math verified in Phase 1's ProductUnitConversionTest is still reachable.
 */
test('a sale of a carton-unit product moves stock and cost in cartons, with piece-equivalent conversion derivable', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');

    $piece = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);
    $carton = app(CreateUnit::class)->execute(['name' => 'Carton-'.uniqid(), 'base_unit_id' => $piece->id, 'conversion_factor' => 12]);

    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Soap (Carton of 12)', 'unit_id' => $carton->id, 'cost_price' => '120.00',
    ]);

    receiveStockFixture('warehouse', $warehouse->id, $product, '5', unitCost: '120.00');

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '2', 'unit_price' => '150.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);
    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($so);

    $balance = StockBalance::query()
        ->where('location_type', 'warehouse')
        ->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect((string) $balance->qty_on_hand)->toBe('3.000') // 5 cartons received - 2 cartons sold
        ->and($invoice->items->first()->unit_cost->toMajor())->toBe('120.00');

    $cartonsSold = $invoice->items->first()->qty;
    $pieceEquivalent = $cartonsSold->multiply((string) $product->unit->conversion_factor);

    expect((string) $pieceEquivalent)->toBe('24.000');
});
