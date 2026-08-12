<?php

use App\Modules\Van\Actions\ApproveLoadout;
use App\Modules\Van\Actions\MarkLoadoutLoaded;
use App\Modules\Van\Actions\RejectLoadout;
use App\Modules\Van\Actions\RequestLoadout;
use App\Support\Exceptions\IllegalTransitionException;
use App\Support\Exceptions\SegregationOfDutiesException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('the requester of a loadout cannot also reject it', function () {
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

    expect(fn () => app(RejectLoadout::class)->execute($loadout))
        ->toThrow(SegregationOfDutiesException::class);
});

test('a different user can reject a requested loadout', function () {
    $warehouse = createWarehouseFixture();
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');
    $van = createVanFixture($warehouse);

    $requester = createManagerUser();
    $requester->givePermissionTo('loadout.create');
    $rejecter = createManagerUser();

    actingAsUser($requester);
    $loadout = app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '5'],
    ]);

    actingAsUser($rejecter);
    $rejected = app(RejectLoadout::class)->execute($loadout, 'Warehouse stock needed elsewhere');

    expect($rejected->status)->toBe('rejected')
        ->and($rejected->rejected_by)->toBe($rejecter->id)
        ->and($rejected->rejection_reason)->toBe('Warehouse stock needed elsewhere');
});

test('an already-loaded loadout can no longer be rejected', function () {
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
    app(ApproveLoadout::class)->execute($loadout);
    app(MarkLoadoutLoaded::class)->execute($loadout->fresh());

    expect(fn () => app(RejectLoadout::class)->execute($loadout->fresh()))
        ->toThrow(IllegalTransitionException::class);
});
