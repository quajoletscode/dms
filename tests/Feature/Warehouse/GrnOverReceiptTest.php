<?php

use App\Modules\Warehouse\Actions\CreateGrnFromPo;
use App\Modules\Warehouse\Domain\Exceptions\OverReceiptNotAllowedException;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('receiving more than the remaining PO quantity is blocked without grn.override', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '10');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    expect(fn () => app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '15', 'unit_cost' => '5.00'],
    ]))->toThrow(OverReceiptNotAllowedException::class);
});

test('receiving more than the remaining PO quantity succeeds with grn.override, and is audited', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '10');
    $poItem = $po->items->first();

    $overrider = createManagerUser();
    $overrider->givePermissionTo('grn.override');
    actingAsUser($overrider);

    $grn = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '15', 'unit_cost' => '5.00'],
    ]);

    expect((string) $grn->items->first()->qty_received)->toBe('15.000');

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => $grn->getMorphClass(),
        'action' => 'create',
    ]);
});
