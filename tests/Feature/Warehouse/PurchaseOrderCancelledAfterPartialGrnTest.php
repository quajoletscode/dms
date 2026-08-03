<?php

use App\Modules\Warehouse\Actions\CancelPurchaseOrder;
use App\Modules\Warehouse\Actions\ClosePurchaseOrder;
use App\Modules\Warehouse\Actions\CreateGrnFromPo;
use App\Modules\Warehouse\Actions\PostGrn;
use App\Support\Exceptions\IllegalTransitionException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a purchase order cannot be cancelled once any GRN has posted against it — it must be closed instead', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '10');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    $grn = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '4', 'unit_cost' => '5.00'],
    ]);
    app(PostGrn::class)->execute($grn);

    $po->refresh();
    expect($po->status)->toBe('partially_received');

    expect(fn () => app(CancelPurchaseOrder::class)->execute($po))
        ->toThrow(IllegalTransitionException::class);

    $po = app(ClosePurchaseOrder::class)->execute($po);
    expect($po->status)->toBe('closed');
});
