<?php

use App\Models\User;
use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\MasterData\Models\Product;
use App\Modules\Van\Actions\ReassignVanDsr;
use App\Modules\Van\Actions\RegisterVan;
use App\Modules\Van\Domain\Exceptions\VanHandoverRequiredException;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Actions\RegisterWarehouse;
use App\Modules\Warehouse\Models\StockBalance;

function registerVanWithDsr(User $dsr): VanStorage
{
    $warehouse = app(RegisterWarehouse::class)->execute(['code' => 'WH-'.uniqid(), 'name' => 'Main']);

    return app(RegisterVan::class)->execute([
        'code' => 'VAN-'.uniqid(),
        'warehouse_id' => $warehouse->id,
        'dsr_user_id' => $dsr->id,
    ]);
}

function createTestProduct(): Product
{
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece']);

    return app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Test Product', 'unit_id' => $unit->id,
    ]);
}

test('reassigning a van with stock on board and no handover note is blocked', function () {
    $oldDsr = User::factory()->create();
    $newDsr = User::factory()->create();
    $van = registerVanWithDsr($oldDsr);

    StockBalance::query()->create([
        'location_type' => 'van',
        'location_id' => $van->id,
        'product_id' => createTestProduct()->id,
        'qty_on_hand' => '5.000',
    ]);

    expect(fn () => app(ReassignVanDsr::class)->execute($van, $newDsr))
        ->toThrow(VanHandoverRequiredException::class);

    expect($van->refresh()->dsr_user_id)->toBe($oldDsr->id);
});

test('reassigning a van with stock on board succeeds with a handover note and closes prior history', function () {
    $oldDsr = User::factory()->create();
    $newDsr = User::factory()->create();
    $van = registerVanWithDsr($oldDsr);

    StockBalance::query()->create([
        'location_type' => 'van',
        'location_id' => $van->id,
        'product_id' => createTestProduct()->id,
        'qty_on_hand' => '5.000',
    ]);

    $reassigned = app(ReassignVanDsr::class)->execute($van, $newDsr, 'Counted 5 units, all accounted for.');

    expect($reassigned->dsr_user_id)->toBe($newDsr->id);

    $priorHistory = $van->history()->where('dsr_user_id', $oldDsr->id)->first();
    expect($priorHistory->unassigned_at)->not->toBeNull()
        ->and($priorHistory->handover_note)->toBe('Counted 5 units, all accounted for.');

    $newHistory = $van->history()->where('dsr_user_id', $newDsr->id)->whereNull('unassigned_at')->first();
    expect($newHistory)->not->toBeNull();
});

test('reassigning a van with no stock on board does not require a handover note', function () {
    $oldDsr = User::factory()->create();
    $newDsr = User::factory()->create();
    $van = registerVanWithDsr($oldDsr);

    $reassigned = app(ReassignVanDsr::class)->execute($van, $newDsr);

    expect($reassigned->dsr_user_id)->toBe($newDsr->id);
});
