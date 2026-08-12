<?php

use App\Modules\Finance\Actions\HandoverDsrCash;
use App\Modules\Finance\Actions\MarkCollectionDeposited;
use App\Modules\Finance\Actions\RecordBankDeposit;
use App\Modules\Finance\Actions\RecordDsrCollection;
use App\Modules\Finance\Models\Collection;
use App\Modules\Sales\Actions\RecordVanSale;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a DSR collection is traced to a person at every hop: collected, handed over, deposited', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    receiveStockFixture('van', $van->id, $product, '10');

    actingAsUser($dsr);
    $invoice = app(RecordVanSale::class)->execute($van->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
    ], []); // no payment at the point of sale -> stays unpaid, within the credit limit

    $collection = app(RecordDsrCollection::class)->execute($van->id, $customer->id, $invoice->id, '100.00');

    expect($collection->status)->toBe('with_dsr')
        ->and($collection->dsr_user_id)->toBe($dsr->id);

    $handedOver = app(HandoverDsrCash::class)->execute($dsr->id);

    expect($handedOver)->toHaveCount(1)
        ->and($handedOver[0]->status)->toBe('handed_over')
        ->and($handedOver[0]->handed_over_by)->toBe($dsr->id);

    $bank = createBankAccountFixture();
    $depositor = createAccountantUser();
    actingAsUser($depositor);
    $deposit = app(RecordBankDeposit::class)->execute($bank, '100.00', 'DSR-DEPOSIT-1');

    $deposited = app(MarkCollectionDeposited::class)->execute($deposit, [$collection->id]);

    expect($deposited)->toHaveCount(1)
        ->and($deposited[0]->status)->toBe('deposited');

    // Every hop traceable to a real person:
    $collectionModel = Collection::query()->whereKey($collection->id)->firstOrFail();

    expect($collectionModel->status)->toBe('deposited')
        ->and($collectionModel->dsr_user_id)->toBe($dsr->id) // collected by
        ->and($collectionModel->handed_over_by)->toBe($dsr->id) // handed over by
        ->and($collectionModel->bankTransaction->created_by)->toBe($depositor->id); // deposited by
});
