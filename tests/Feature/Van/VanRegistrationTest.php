<?php

use App\Models\User;
use App\Modules\Van\Actions\RegisterVan;
use App\Modules\Warehouse\Actions\RegisterWarehouse;
use Illuminate\Database\QueryException;

test('a van is registered under a parent warehouse', function () {
    $warehouse = app(RegisterWarehouse::class)->execute(['code' => 'WH-01', 'name' => 'Main']);

    $van = app(RegisterVan::class)->execute([
        'code' => 'VAN-01',
        'warehouse_id' => $warehouse->id,
    ]);

    expect($van->warehouse_id)->toBe($warehouse->id);
});

test('registering a van with a dsr writes an initial history row', function () {
    $warehouse = app(RegisterWarehouse::class)->execute(['code' => 'WH-01', 'name' => 'Main']);
    $dsr = User::factory()->create();

    $van = app(RegisterVan::class)->execute([
        'code' => 'VAN-01',
        'warehouse_id' => $warehouse->id,
        'dsr_user_id' => $dsr->id,
    ]);

    expect($van->history()->count())->toBe(1)
        ->and($van->history()->first()->dsr_user_id)->toBe($dsr->id);
});

test('a dsr cannot be the active driver of two vans at once', function () {
    $warehouse = app(RegisterWarehouse::class)->execute(['code' => 'WH-01', 'name' => 'Main']);
    $dsr = User::factory()->create();

    app(RegisterVan::class)->execute(['code' => 'VAN-01', 'warehouse_id' => $warehouse->id, 'dsr_user_id' => $dsr->id]);

    expect(fn () => app(RegisterVan::class)->execute(['code' => 'VAN-02', 'warehouse_id' => $warehouse->id, 'dsr_user_id' => $dsr->id]))
        ->toThrow(QueryException::class);
});
