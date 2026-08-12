<?php

use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\Van\Actions\AcceptLoadin;
use App\Modules\Van\Actions\RequestLoadin;
use App\Modules\Warehouse\Models\StockBalance;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('accepting a loadin splits good stock back to the warehouse and writes off damaged stock', function () {
    $warehouse = createWarehouseFixture();
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Widget', 'unit_id' => $unit->id, 'cost_price' => '5.00',
    ]);
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    receiveStockFixture('van', $van->id, $product, '10', unitCost: '5.00');

    actingAsUser($dsr);
    $loadin = app(RequestLoadin::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty_good' => '6', 'qty_damaged' => '2'],
    ]);

    actingAsUser(createManagerUser());
    $loadin = app(AcceptLoadin::class)->execute($loadin);

    expect($loadin->status)->toBe('accepted');

    $vanBalance = StockBalance::query()
        ->where('location_type', 'van')->where('location_id', $van->id)
        ->where('product_id', $product->id)->firstOrFail();
    $warehouseBalance = StockBalance::query()
        ->where('location_type', 'warehouse')->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)->firstOrFail();
    $damagesBalance = StockBalance::query()
        ->where('location_type', 'damages')->where('location_id', $warehouse->id)
        ->where('product_id', $product->id)->firstOrFail();

    expect((string) $vanBalance->qty_on_hand)->toBe('2.000') // 10 - 6 good - 2 damaged
        ->and((string) $warehouseBalance->qty_on_hand)->toBe('6.000')
        ->and((string) $damagesBalance->qty_on_hand)->toBe('2.000');

    $this->assertDatabaseCount('journal_entries', 1); // Dr Inventory Loss / Cr Inventory for the 2 damaged units
});

test('a loadin with no damaged stock posts no write-off journal', function () {
    $warehouse = createWarehouseFixture();
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Widget', 'unit_id' => $unit->id, 'cost_price' => '5.00',
    ]);
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    receiveStockFixture('van', $van->id, $product, '10', unitCost: '5.00');

    actingAsUser($dsr);
    $loadin = app(RequestLoadin::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty_good' => '6', 'qty_damaged' => '0'],
    ]);

    actingAsUser(createManagerUser());
    app(AcceptLoadin::class)->execute($loadin);

    $this->assertDatabaseCount('journal_entries', 0);
});
