<?php

use App\Models\User;
use App\Modules\Warehouse\Actions\AssignWarehouseManager;
use App\Modules\Warehouse\Actions\RegisterWarehouse;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a warehouse can be registered', function () {
    $warehouse = app(RegisterWarehouse::class)->execute([
        'code' => 'WH-01',
        'name' => 'Accra Central Warehouse',
        'location' => 'Accra',
    ]);

    expect($warehouse->code)->toBe('WH-01')
        ->and($warehouse->is_active)->toBeTrue();
});

test('assigning a manager sets manager_id and grants warehouse access', function () {
    $manager = User::factory()->create();
    $manager->assignRole('warehouse_manager');

    $warehouse = app(RegisterWarehouse::class)->execute(['code' => 'WH-01', 'name' => 'Main']);

    app(AssignWarehouseManager::class)->execute($warehouse, $manager);

    expect($warehouse->refresh()->manager_id)->toBe($manager->id)
        ->and($warehouse->assignedUsers->pluck('id'))->toContain($manager->id);
});

test('a warehouse manager only sees warehouses assigned to them, including on a direct id lookup', function () {
    $manager = User::factory()->create();
    $manager->assignRole('warehouse_manager');

    $assigned = app(RegisterWarehouse::class)->execute(['code' => 'WH-A', 'name' => 'Assigned']);
    $unassigned = app(RegisterWarehouse::class)->execute(['code' => 'WH-B', 'name' => 'Unassigned']);

    app(AssignWarehouseManager::class)->execute($assigned, $manager);

    $this->actingAs($manager);

    expect(Warehouse::query()->pluck('id')->all())->toBe([$assigned->id])
        ->and(Warehouse::query()->find($unassigned->id))->toBeNull();
});

test('a super admin sees all warehouses regardless of assignment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    app(RegisterWarehouse::class)->execute(['code' => 'WH-A', 'name' => 'A']);
    app(RegisterWarehouse::class)->execute(['code' => 'WH-B', 'name' => 'B']);

    $this->actingAs($admin);

    expect(Warehouse::query()->count())->toBe(2);
});
