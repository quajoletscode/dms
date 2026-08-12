<?php

use App\Modules\Finance\Models\JournalEntry;
use App\Modules\Sales\Actions\CashInOut;
use App\Modules\Sales\Actions\CloseTillSession;
use App\Modules\Sales\Actions\RecordPosSale;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a shortage (counted less than expected) posts Dr Cash Over/Short, Cr Cash on Hand', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id, '100.00');

    app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '100.00'],
    ]);

    // The sale itself already posts 2 journals (invoice.issued + invoice_payment.recorded);
    // this test is about the close, so assert the delta the close itself produces.
    $journalCountBeforeClose = JournalEntry::query()->count();

    // expected = 100 opening + 100 cash sales = 200.00; counted short by 5.00
    $closed = app(CloseTillSession::class)->execute($till, '195.00');

    expect($closed->expected_float->toMajor())->toBe('200.00')
        ->and($closed->variance->toMajor())->toBe('-5.00');

    $this->assertDatabaseCount('journal_entries', $journalCountBeforeClose + 1);
});

test('an overage (counted more than expected) posts Dr Cash on Hand, Cr Cash Over/Short', function () {
    $warehouse = createWarehouseFixture();
    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id, '100.00');

    app(CashInOut::class)->execute($till, 'in', '20.00', 'Float top-up');

    // expected = 100 opening + 20 cash-in = 120.00; counted over by 3.00
    $closed = app(CloseTillSession::class)->execute($till, '123.00');

    expect($closed->expected_float->toMajor())->toBe('120.00')
        ->and($closed->variance->toMajor())->toBe('3.00');

    $this->assertDatabaseCount('journal_entries', 1);
});

test('cash-out movements reduce the expected float', function () {
    $warehouse = createWarehouseFixture();
    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id, '100.00');

    app(CashInOut::class)->execute($till, 'out', '30.00', 'Bank drop');

    $closed = app(CloseTillSession::class)->execute($till, '70.00');

    expect($closed->expected_float->toMajor())->toBe('70.00')
        ->and($closed->variance->toMajor())->toBe('0.00');
});
