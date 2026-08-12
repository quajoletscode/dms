<?php

use App\Modules\Van\Actions\ApproveLoadout;
use App\Modules\Van\Actions\ConfirmLoadoutReceipt;
use App\Modules\Van\Actions\MarkLoadoutLoaded;
use App\Modules\Van\Actions\RequestLoadout;
use App\Modules\Van\Models\LoadoutRequest;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a loadout that is loaded but never confirmed received shows in the stuck-in-transit report', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);
    $loadout = app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '5'],
    ]);

    actingAsUser(createManagerUser());
    $loadout = app(ApproveLoadout::class)->execute($loadout);
    $loadout = app(MarkLoadoutLoaded::class)->execute($loadout);

    expect(LoadoutRequest::stuckInTransit()->pluck('id')->all())->toBe([$loadout->id]);

    actingAsUser($dsr);
    app(ConfirmLoadoutReceipt::class)->execute($loadout);

    expect(LoadoutRequest::stuckInTransit()->count())->toBe(0);
});

test('a requested-but-not-yet-loaded loadout does not show in the stuck-in-transit report', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);
    app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '5'],
    ]);

    expect(LoadoutRequest::stuckInTransit()->count())->toBe(0);
});
