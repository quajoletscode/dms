<?php

use App\Modules\Van\Actions\ApproveLoadout;
use App\Modules\Van\Actions\RequestLoadout;
use App\Support\Exceptions\SegregationOfDutiesException;
use App\Support\Quantity;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('the requester of a loadout cannot also approve it', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');
    $van = createVanFixture($warehouse);

    $user = createManagerUser();
    $user->givePermissionTo('loadout.create');
    actingAsUser($user);

    $loadout = app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '5'],
    ]);

    expect(fn () => app(ApproveLoadout::class)->execute($loadout))
        ->toThrow(SegregationOfDutiesException::class);
});

test('a different user can approve a requested loadout', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');
    $van = createVanFixture($warehouse);

    $requester = createManagerUser();
    $requester->givePermissionTo('loadout.create');
    $approver = createManagerUser();

    actingAsUser($requester);
    $loadout = app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '5'],
    ]);

    actingAsUser($approver);
    $approved = app(ApproveLoadout::class)->execute($loadout);

    expect($approved->status)->toBe('approved')
        ->and($approved->items->first()->qty_approved->equals(Quantity::fromString('5')))->toBeTrue();
});
