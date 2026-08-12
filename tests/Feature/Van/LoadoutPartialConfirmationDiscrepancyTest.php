<?php

use App\Modules\Van\Actions\ApproveLoadout;
use App\Modules\Van\Actions\ConfirmLoadoutReceipt;
use App\Modules\Van\Actions\MarkLoadoutLoaded;
use App\Modules\Van\Actions\RequestLoadout;
use App\Modules\Warehouse\Models\StockBalance;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('confirming less than loaded records a discrepancy instead of silently losing stock', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);
    $loadout = app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '10'],
    ]);

    actingAsUser(createManagerUser());
    $loadout = app(ApproveLoadout::class)->execute($loadout);
    $loadout = app(MarkLoadoutLoaded::class)->execute($loadout);
    $item = $loadout->items->first();

    actingAsUser($dsr);
    $loadout = app(ConfirmLoadoutReceipt::class)->execute($loadout, [$item->id => '8']);
    $item = $loadout->items->first();

    expect((string) $item->qty_loaded)->toBe('10.000')
        ->and((string) $item->qty_received)->toBe('8.000')
        ->and((string) $item->discrepancy())->toBe('2.000');

    $warehouseBalance = StockBalance::query()
        ->where('location_type', 'warehouse')->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)->firstOrFail();
    $vanBalance = StockBalance::query()
        ->where('location_type', 'van')->where('location_id', $van->id)
        ->where('product_id', $product->id)->firstOrFail();

    expect((string) $warehouseBalance->qty_on_hand)->toBe('10.000') // 20 received - 10 loaded out
        ->and((string) $vanBalance->qty_on_hand)->toBe('8.000'); // only what actually arrived
});

test('confirming exactly what was loaded has no discrepancy', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);
    $loadout = app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '10'],
    ]);

    actingAsUser(createManagerUser());
    $loadout = app(ApproveLoadout::class)->execute($loadout);
    $loadout = app(MarkLoadoutLoaded::class)->execute($loadout);

    actingAsUser($dsr);
    $loadout = app(ConfirmLoadoutReceipt::class)->execute($loadout);
    $item = $loadout->items->first();

    expect((string) $item->discrepancy())->toBe('0.000');
});
