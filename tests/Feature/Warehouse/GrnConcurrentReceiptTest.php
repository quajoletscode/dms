<?php

use App\Modules\Warehouse\Actions\CreateGrnFromPo;
use App\Modules\Warehouse\Actions\PostGrn;
use App\Modules\Warehouse\Models\PurchaseOrderItem;
use App\Modules\Warehouse\Models\StockBalance;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

/**
 * True multi-connection concurrency can't be exercised meaningfully against
 * the test suite's SQLite in-memory database (separate connections mean
 * separate, empty databases). What actually prevents lost updates in
 * production is that PostGrn::execute() never does read-in-PHP-then-write:
 * both the PO item's qty_received and the stock balance's qty_on_hand are
 * updated via a single parameterised `col = col + ?` SQL statement
 * (StockMover, PostGrn), which the database serialises atomically per row
 * regardless of how many writers race on it. This test proves that
 * accumulation is exact over several GRNs — the property an atomic
 * increment guarantees under real concurrency too.
 */
test('several GRNs posted against the same PO item accumulate exactly, with no lost update', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '100');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    foreach (['12.500', '30.250', '7.125', '50.125'] as $qty) {
        $grn = app(CreateGrnFromPo::class)->execute($po, [
            ['po_item_id' => $poItem->id, 'qty_received' => $qty, 'unit_cost' => '5.00'],
        ]);
        app(PostGrn::class)->execute($grn);
    }

    $freshPoItem = PurchaseOrderItem::query()->whereKey($poItem->id)->firstOrFail();
    expect((string) $freshPoItem->qty_received)->toBe('100.000');

    $balance = StockBalance::query()
        ->where('location_type', 'warehouse')
        ->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect((string) $balance->qty_on_hand)->toBe('100.000');
});
