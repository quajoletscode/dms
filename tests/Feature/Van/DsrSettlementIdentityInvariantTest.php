<?php

use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\Sales\Actions\RecordVanSale;
use App\Modules\Van\Actions\AcceptLoadin;
use App\Modules\Van\Actions\ApproveLoadout;
use App\Modules\Van\Actions\ConfirmLoadoutReceipt;
use App\Modules\Van\Actions\GenerateDsrSettlement;
use App\Modules\Van\Actions\MarkLoadoutLoaded;
use App\Modules\Van\Actions\RequestLoadin;
use App\Modules\Van\Actions\RequestLoadout;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('the stock-value identity (opening + loadout - sales - loadin = closing) holds for a closed day', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Widget', 'unit_id' => $unit->id, 'cost_price' => '5.00',
    ]);
    receiveStockFixture('warehouse', $warehouse->id, $product, '20', unitCost: '5.00');

    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    // Loadout 10 units onto the van (fully confirmed, no discrepancy) => loadout value 50.00
    actingAsUser($dsr);
    $loadout = app(RequestLoadout::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty' => '10'],
    ]);
    actingAsUser(createManagerUser());
    $loadout = app(ApproveLoadout::class)->execute($loadout);
    $loadout = app(MarkLoadoutLoaded::class)->execute($loadout);
    actingAsUser($dsr);
    app(ConfirmLoadoutReceipt::class)->execute($loadout);

    // Sell 4 units from the van => sales (COGS) value 20.00
    app(RecordVanSale::class)->execute($van->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '4', 'unit_price' => '8.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '32.00'],
    ]);

    // Return 3 units (2 good + 1 damaged) => loadin value 15.00
    $loadin = app(RequestLoadin::class)->execute($van->id, [
        ['product_id' => $product->id, 'qty_good' => '2', 'qty_damaged' => '1'],
    ]);
    actingAsUser(createManagerUser());
    app(AcceptLoadin::class)->execute($loadin);

    actingAsUser($dsr);
    $settlement = app(GenerateDsrSettlement::class)->execute($van->id, now()->toDateString());

    expect($settlement->opening_value->toMajor())->toBe('0.00')
        ->and($settlement->loadout_value->toMajor())->toBe('50.00')
        ->and($settlement->sales_value->toMajor())->toBe('20.00')
        ->and($settlement->loadin_value->toMajor())->toBe('15.00')
        ->and($settlement->closing_value->toMajor())->toBe('15.00');

    $recomputedClosing = $settlement->opening_value
        ->add($settlement->loadout_value)
        ->subtract($settlement->sales_value)
        ->subtract($settlement->loadin_value);

    expect($recomputedClosing->equals($settlement->closing_value))->toBeTrue();
});

test('generating a settlement twice for the same van/day updates the existing row rather than duplicating it', function () {
    $warehouse = createWarehouseFixture();
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);

    actingAsUser($dsr);
    $first = app(GenerateDsrSettlement::class)->execute($van->id, now()->toDateString());
    $second = app(GenerateDsrSettlement::class)->execute($van->id, now()->toDateString());

    expect($first->id)->toBe($second->id);
    $this->assertDatabaseCount('dsr_settlements', 1);
});
