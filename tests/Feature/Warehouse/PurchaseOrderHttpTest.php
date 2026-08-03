<?php

use App\Models\User;
use App\Modules\Warehouse\Models\PurchaseOrder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('a purchase order can be created via http, with blank line rows filtered out', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $manager = createManagerUser();

    $this->actingAs($manager)->post('/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'items' => [
            ['product_id' => $product->id, 'qty_ordered' => '10', 'unit_cost' => '5.00'],
            ['product_id' => '', 'qty_ordered' => '', 'unit_cost' => ''],
            ['product_id' => '', 'qty_ordered' => '', 'unit_cost' => ''],
        ],
    ])->assertRedirect();

    $po = PurchaseOrder::query()->firstOrFail();

    expect($po->items)->toHaveCount(1)
        ->and($po->status)->toBe('draft')
        ->and($po->grand_total->toMajor())->toBe('50.00');
});

test('a purchase order request with no filled rows fails validation', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $manager = createManagerUser();

    $this->actingAs($manager)->post('/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'items' => [
            ['product_id' => '', 'qty_ordered' => '', 'unit_cost' => ''],
        ],
    ])->assertSessionHasErrors('items');
});

test('a user without po.create cannot create a purchase order', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();

    $dsr = User::factory()->create();
    $dsr->assignRole('dsr');

    $this->actingAs($dsr)->post('/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'items' => [
            ['product_id' => $product->id, 'qty_ordered' => '10', 'unit_cost' => '5.00'],
        ],
    ])->assertForbidden();
});

test('a purchase order detail page shows its items and totals', function () {
    $po = createApprovedPurchaseOrder(createWarehouseFixture(), createSupplierFixture(), createProductFixture(), qty: '4', unitCost: '2.50');

    $viewer = createManagerUser();

    $this->actingAs($viewer)->get("/purchase-orders/{$po->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('purchase-orders/Show')
        ->where('purchaseOrder.status', 'approved')
        ->where('purchaseOrder.grand_total', '10.00')
        ->has('purchaseOrder.items', 1)
    );
});

test('a purchase order can be submitted and approved by different users via http', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $creator = createManagerUser();
    $approver = createManagerUser();

    $this->actingAs($creator)->post('/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'items' => [
            ['product_id' => $product->id, 'qty_ordered' => '1', 'unit_cost' => '1.00'],
        ],
    ])->assertRedirect();

    $po = PurchaseOrder::query()->firstOrFail();

    $this->actingAs($creator)->post("/purchase-orders/{$po->id}/submit")->assertRedirect();
    expect($po->refresh()->status)->toBe('submitted');

    $this->actingAs($approver)->post("/purchase-orders/{$po->id}/approve")->assertRedirect();
    expect($po->refresh()->status)->toBe('approved');
});
