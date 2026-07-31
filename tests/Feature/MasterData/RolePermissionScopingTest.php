<?php

use App\Models\User;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\Van\Actions\RegisterVan;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Actions\RegisterWarehouse;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a dsr only sees their own van', function () {
    $warehouse = app(RegisterWarehouse::class)->execute(['code' => 'WH-01', 'name' => 'Main']);
    $dsr1 = User::factory()->create();
    $dsr1->assignRole('dsr');
    $dsr2 = User::factory()->create();
    $dsr2->assignRole('dsr');

    $van1 = app(RegisterVan::class)->execute(['code' => 'VAN-01', 'warehouse_id' => $warehouse->id, 'dsr_user_id' => $dsr1->id]);
    $van2 = app(RegisterVan::class)->execute(['code' => 'VAN-02', 'warehouse_id' => $warehouse->id, 'dsr_user_id' => $dsr2->id]);

    $this->actingAs($dsr1);

    expect(VanStorage::query()->pluck('id')->all())->toBe([$van1->id])
        ->and(VanStorage::query()->find($van2->id))->toBeNull();
});

test('a warehouse manager without product.manage permission cannot create a product', function () {
    $manager = User::factory()->create();
    $manager->assignRole('warehouse_manager');

    $this->actingAs($manager);

    expect($manager->can('create', Product::class))->toBeFalse();
});

test('an accountant can view suppliers but not manage them', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole('accountant');

    $this->actingAs($accountant);

    expect($accountant->can('viewAny', Supplier::class))->toBeTrue()
        ->and($accountant->can('create', Supplier::class))->toBeFalse();
});

test('an unauthorized role is denied access to a protected route', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('wholesale_cashier');

    $this->actingAs($cashier);

    expect($cashier->can('create', Supplier::class))->toBeFalse()
        ->and($cashier->can('create', Warehouse::class))->toBeFalse();
});
