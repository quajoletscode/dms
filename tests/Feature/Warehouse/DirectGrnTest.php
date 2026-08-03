<?php

use App\Modules\Warehouse\Actions\CreateDirectGrn;
use App\Modules\Warehouse\Actions\PostGrn;
use App\Modules\Warehouse\Models\StockBalance;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a direct GRN without a PO requires grn.direct.create permission', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();

    actingAsUser(createManagerUser());

    expect(fn () => app(CreateDirectGrn::class)->execute($supplier->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty_received' => '5', 'unit_cost' => '5.00'],
    ]))->toThrow(AuthorizationException::class);
});

test('a direct GRN with grn.direct.create permission posts stock with no PO link', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();

    $user = createManagerUser();
    $user->givePermissionTo('grn.direct.create');
    actingAsUser($user);

    $grn = app(CreateDirectGrn::class)->execute($supplier->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty_received' => '5', 'unit_cost' => '5.00'],
    ]);

    expect($grn->po_id)->toBeNull();

    $grn = app(PostGrn::class)->execute($grn);
    expect($grn->status)->toBe('posted');

    $balance = StockBalance::query()
        ->where('location_type', 'warehouse')
        ->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect((string) $balance->qty_on_hand)->toBe('5.000');
});
