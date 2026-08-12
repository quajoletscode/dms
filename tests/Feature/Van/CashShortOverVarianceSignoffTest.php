<?php

use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\Sales\Actions\RecordVanSale;
use App\Modules\Van\Actions\GenerateDsrSettlement;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('signing off a settlement with cash matching expected has zero variance', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Widget', 'unit_id' => $unit->id, 'cost_price' => '5.00',
    ]);
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    receiveStockFixture('van', $van->id, $product, '10', unitCost: '5.00');

    actingAsUser($dsr);
    app(RecordVanSale::class)->execute($van->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '4', 'unit_price' => '8.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '32.00'],
    ]);

    $settlement = app(GenerateDsrSettlement::class)->execute($van->id, now()->toDateString(), '32.00');

    expect($settlement->cash_expected->toMajor())->toBe('32.00')
        ->and($settlement->cash_counted->toMajor())->toBe('32.00')
        ->and($settlement->cash_variance->toMajor())->toBe('0.00')
        ->and($settlement->status)->toBe('signed_off');
});

test('signing off with less cash than expected records a negative (short) variance', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Widget', 'unit_id' => $unit->id, 'cost_price' => '5.00',
    ]);
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    receiveStockFixture('van', $van->id, $product, '10', unitCost: '5.00');

    actingAsUser($dsr);
    app(RecordVanSale::class)->execute($van->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '4', 'unit_price' => '8.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '32.00'],
    ]);

    $settlement = app(GenerateDsrSettlement::class)->execute($van->id, now()->toDateString(), '27.00');

    expect($settlement->cash_variance->toMajor())->toBe('-5.00');
});

test('signing off with more cash than expected records a positive (over) variance', function () {
    $warehouse = createWarehouseFixture();
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);
    $settlement = app(GenerateDsrSettlement::class)->execute($van->id, now()->toDateString(), '10.00');

    expect($settlement->cash_expected->toMajor())->toBe('0.00')
        ->and($settlement->cash_variance->toMajor())->toBe('10.00')
        ->and($settlement->status)->toBe('signed_off');
});

test('a settlement generated without a cash count stays generated until signed off', function () {
    $warehouse = createWarehouseFixture();
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);
    $settlement = app(GenerateDsrSettlement::class)->execute($van->id, now()->toDateString());

    expect($settlement->status)->toBe('generated')
        ->and($settlement->cash_counted)->toBeNull();

    $signedOff = app(GenerateDsrSettlement::class)->execute($van->id, now()->toDateString(), '0.00');

    expect($signedOff->status)->toBe('signed_off')
        ->and($signedOff->id)->toBe($settlement->id);
});
