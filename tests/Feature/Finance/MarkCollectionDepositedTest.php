<?php

use App\Models\User;
use App\Modules\Finance\Actions\HandoverDsrCash;
use App\Modules\Finance\Actions\MarkCollectionDeposited;
use App\Modules\Finance\Actions\RecordBankDeposit;
use App\Modules\Finance\Actions\RecordDsrCollection;
use App\Modules\Finance\Models\Collection;
use App\Modules\Sales\Actions\RecordVanSale;
use App\Support\Exceptions\IllegalTransitionException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

function collectDsrCashFixture(): Collection
{
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    $dsr = createDsrUser();
    $van = createVanFixture($warehouse, $dsr);
    receiveStockFixture('van', $van->id, $product, '10');

    actingAsUser($dsr);
    $invoice = app(RecordVanSale::class)->execute($van->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
    ], []);

    return app(RecordDsrCollection::class)->execute($van->id, $customer->id, $invoice->id, '100.00');
}

test('a collection cannot be marked deposited before it has been handed over', function () {
    $collection = collectDsrCashFixture();

    $bank = createBankAccountFixture();
    actingAsUser(createAccountantUser());
    $deposit = app(RecordBankDeposit::class)->execute($bank, '100.00', 'DSR-DEPOSIT-1');

    expect(fn () => app(MarkCollectionDeposited::class)->execute($deposit, [$collection->id]))
        ->toThrow(IllegalTransitionException::class);
});

test('a handed-over collection is marked deposited and linked to the bank transaction', function () {
    $collection = collectDsrCashFixture();
    $dsrId = $collection->dsr_user_id;

    actingAsUser(User::query()->findOrFail($dsrId));
    app(HandoverDsrCash::class)->execute($dsrId);

    $bank = createBankAccountFixture();
    actingAsUser(createAccountantUser());
    $deposit = app(RecordBankDeposit::class)->execute($bank, '100.00', 'DSR-DEPOSIT-2');

    $deposited = app(MarkCollectionDeposited::class)->execute($deposit, [$collection->id]);

    expect($deposited)->toHaveCount(1)
        ->and($deposited[0]->status)->toBe('deposited')
        ->and($deposited[0]->bank_transaction_id)->toBe($deposit->id);
});

test('a deposited collection cannot be marked deposited again', function () {
    $collection = collectDsrCashFixture();
    $dsrId = $collection->dsr_user_id;

    actingAsUser(User::query()->findOrFail($dsrId));
    app(HandoverDsrCash::class)->execute($dsrId);

    $bank = createBankAccountFixture();
    actingAsUser(createAccountantUser());
    $deposit = app(RecordBankDeposit::class)->execute($bank, '100.00', 'DSR-DEPOSIT-3');
    app(MarkCollectionDeposited::class)->execute($deposit, [$collection->id]);

    expect(fn () => app(MarkCollectionDeposited::class)->execute($deposit, [$collection->id]))
        ->toThrow(IllegalTransitionException::class);
});
