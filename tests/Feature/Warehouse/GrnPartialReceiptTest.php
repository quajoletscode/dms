<?php

use App\Modules\Warehouse\Actions\CreateGrnFromPo;
use App\Modules\Warehouse\Actions\PostGrn;
use App\Modules\Warehouse\Models\StockBalance;
use App\Support\Exceptions\ImmutableRecordException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('two partial GRNs against one PO sum correctly and the PO status transitions accordingly', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '10');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    $grn1 = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '4', 'unit_cost' => '5.00'],
    ]);
    app(PostGrn::class)->execute($grn1);

    $po->refresh();
    expect($po->status)->toBe('partially_received')
        ->and((string) $po->items->first()->qty_received)->toBe('4.000');

    $grn2 = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '6', 'unit_cost' => '5.00'],
    ]);
    app(PostGrn::class)->execute($grn2);

    $po->refresh();
    expect($po->status)->toBe('received')
        ->and((string) $po->items->first()->qty_received)->toBe('10.000');

    $balance = StockBalance::query()
        ->where('location_type', 'warehouse')
        ->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->first();

    expect((string) $balance->qty_on_hand)->toBe('10.000');
});

test('a GRN cannot be posted twice', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '10');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    $grn = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '4', 'unit_cost' => '5.00'],
    ]);
    $grn = app(PostGrn::class)->execute($grn);

    expect(fn () => app(PostGrn::class)->execute($grn))
        ->toThrow(ImmutableRecordException::class);
});
