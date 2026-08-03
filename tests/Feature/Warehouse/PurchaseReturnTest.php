<?php

use App\Modules\Finance\Models\JournalEntry;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\Warehouse\Actions\CreateGrnFromPo;
use App\Modules\Warehouse\Actions\PostGrn;
use App\Modules\Warehouse\Actions\PostPurchaseReturn;
use App\Modules\Warehouse\Domain\Exceptions\ExcessiveReturnException;
use App\Modules\Warehouse\Models\Grn;
use App\Modules\Warehouse\Models\StockBalance;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

function postedTestGrn(
    Warehouse $warehouse,
    Supplier $supplier,
    Product $product,
    string $qty = '10',
    string $unitCost = '5.00',
): Grn {
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: $qty, unitCost: $unitCost);
    actingAsUser(createManagerUser());

    $grn = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $po->items->first()->id, 'qty_received' => $qty, 'unit_cost' => $unitCost],
    ]);

    return app(PostGrn::class)->execute($grn);
}

test('a purchase return reduces warehouse stock and reverses the GRN journal', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $grn = postedTestGrn($warehouse, $supplier, $product, qty: '10', unitCost: '5.00');
    $grnItem = $grn->items->first();

    $return = app(PostPurchaseReturn::class)->execute($grn, [
        ['grn_item_id' => $grnItem->id, 'qty_returned' => '3'],
    ], reasonCode: 'damaged');

    expect($return->status)->toBe('posted');

    $balance = StockBalance::query()
        ->where('location_type', 'warehouse')
        ->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect((string) $balance->qty_on_hand)->toBe('7.000');

    $journalEntry = JournalEntry::query()
        ->where('postable_type', $return->getMorphClass())
        ->where('postable_id', $return->id)
        ->firstOrFail();

    expect($journalEntry->lines()->count())->toBe(2);
});

test('cannot return more than was received on a GRN line, even across multiple returns', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $grn = postedTestGrn($warehouse, $supplier, $product, qty: '10', unitCost: '5.00');
    $grnItem = $grn->items->first();

    app(PostPurchaseReturn::class)->execute($grn, [
        ['grn_item_id' => $grnItem->id, 'qty_returned' => '6'],
    ], reasonCode: 'damaged');

    expect(fn () => app(PostPurchaseReturn::class)->execute($grn, [
        ['grn_item_id' => $grnItem->id, 'qty_returned' => '5'],
    ], reasonCode: 'damaged'))->toThrow(ExcessiveReturnException::class);
});

test('returning exactly the remaining receivable quantity is allowed', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $grn = postedTestGrn($warehouse, $supplier, $product, qty: '10', unitCost: '5.00');
    $grnItem = $grn->items->first();

    app(PostPurchaseReturn::class)->execute($grn, [
        ['grn_item_id' => $grnItem->id, 'qty_returned' => '6'],
    ], reasonCode: 'damaged');

    $return = app(PostPurchaseReturn::class)->execute($grn, [
        ['grn_item_id' => $grnItem->id, 'qty_returned' => '4'],
    ], reasonCode: 'damaged');

    expect($return->status)->toBe('posted');
});
