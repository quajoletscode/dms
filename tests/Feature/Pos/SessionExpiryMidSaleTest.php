<?php

use App\Modules\Sales\Actions\CloseTillSession;
use App\Modules\Sales\Actions\RecordPosSale;
use App\Modules\Sales\Domain\Exceptions\TillSessionClosedException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

/**
 * There is no cart/session-persistence backend in this phase (POS has no web
 * layer yet — see decisions.md). The backend-testable guarantee behind "cart
 * preserved, nothing double-posted" on session expiry is this: a sale
 * attempted against a till session that has already closed mid-shift is
 * rejected cleanly, and — because RecordPosSale runs inside one DB
 * transaction — the rejected attempt leaves no partial Invoice, stock
 * movement, or journal entry behind.
 */
test('a sale attempted against a closed till session is rejected and leaves no partial state', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createCashierUser());
    $till = openTillSessionFixture($warehouse->id);
    app(CloseTillSession::class)->execute($till, '100.00');

    expect(fn () => app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '100.00'],
    ]))->toThrow(TillSessionClosedException::class);

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('stock_ledger', 1); // just the fixture's receipt
    $this->assertDatabaseCount('journal_entries', 0);
});
