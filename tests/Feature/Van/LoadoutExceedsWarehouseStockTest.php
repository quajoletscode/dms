<?php

use App\Modules\Van\Actions\ApproveLoadout;
use App\Modules\Van\Actions\RequestLoadout;
use App\Modules\Warehouse\Domain\Exceptions\InsufficientStockException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('requesting a loadout for more than the warehouse has in stock is blocked', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);

    expect(fn () => app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '15'],
    ]))->toThrow(InsufficientStockException::class);
});

test('editing an approved qty upward beyond available warehouse stock is blocked at approval', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);
    $loadout = app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '5'],
    ]);
    $item = $loadout->items->first();

    actingAsUser(createManagerUser());

    expect(fn () => app(ApproveLoadout::class)->execute($loadout, [$item->id => '15']))
        ->toThrow(InsufficientStockException::class);
});
