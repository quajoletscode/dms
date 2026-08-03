<?php

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Models\JournalEntry;
use App\Modules\Finance\Models\JournalLine;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\Warehouse\Actions\CreateGrnFromPo;
use App\Modules\Warehouse\Actions\PostGrn;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('posting a GRN debits Inventory and credits Accounts Payable for the exact same amount, tagged to the supplier', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '10', unitCost: '12.50');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    $grn = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '10', 'unit_cost' => '12.50'],
    ]);
    $grn = app(PostGrn::class)->execute($grn);

    $journalEntry = JournalEntry::query()
        ->where('postable_type', $grn->getMorphClass())
        ->where('postable_id', $grn->id)
        ->firstOrFail();

    expect($journalEntry->lines()->count())->toBe(2);

    $accounts = app(ChartOfAccountResolver::class);
    $inventoryLine = $journalEntry->lines()->where('account_id', $accounts->inventory()->id)->firstOrFail();
    $apLine = $journalEntry->lines()->where('account_id', $accounts->accountsPayable()->id)->firstOrFail();

    expect($inventoryLine->debit->toMajor())->toBe('125.00')
        ->and($inventoryLine->credit->isZero())->toBeTrue()
        ->and($apLine->credit->toMajor())->toBe('125.00')
        ->and($apLine->debit->isZero())->toBeTrue()
        ->and($apLine->partner_type)->toBe((new Supplier)->getMorphClass())
        ->and($apLine->partner_id)->toBe($supplier->id);
});

test('the Accounts Payable control account reconciles to the sum of its supplier sub-ledger lines', function () {
    $warehouse = createWarehouseFixture();
    $supplierA = createSupplierFixture();
    $supplierB = createSupplierFixture();
    $product = createProductFixture();

    $poA = createApprovedPurchaseOrder($warehouse, $supplierA, $product, qty: '4', unitCost: '10.00');
    $poB = createApprovedPurchaseOrder($warehouse, $supplierB, $product, qty: '3', unitCost: '20.00');

    actingAsUser(createManagerUser());

    $grnA = app(CreateGrnFromPo::class)->execute($poA, [
        ['po_item_id' => $poA->items->first()->id, 'qty_received' => '4', 'unit_cost' => '10.00'],
    ]);
    app(PostGrn::class)->execute($grnA);

    $grnB = app(CreateGrnFromPo::class)->execute($poB, [
        ['po_item_id' => $poB->items->first()->id, 'qty_received' => '3', 'unit_cost' => '20.00'],
    ]);
    app(PostGrn::class)->execute($grnB);

    $accounts = app(ChartOfAccountResolver::class);
    $apAccountId = $accounts->accountsPayable()->id;

    $controlTotal = JournalLine::query()->where('account_id', $apAccountId)->sum('credit')
        - JournalLine::query()->where('account_id', $apAccountId)->sum('debit');

    $subLedgerTotal = 0;
    foreach ([$supplierA, $supplierB] as $supplier) {
        $subLedgerTotal += JournalLine::query()
            ->where('account_id', $apAccountId)
            ->where('partner_type', $supplier->getMorphClass())
            ->where('partner_id', $supplier->id)
            ->sum('credit');
    }

    expect((int) $controlTotal)->toBe((int) $subLedgerTotal)
        ->and((int) $controlTotal)->toBe(4000 + 6000); // GHS 40.00 + GHS 60.00 in minor units
});
