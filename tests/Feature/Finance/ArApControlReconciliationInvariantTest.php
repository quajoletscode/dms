<?php

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Models\JournalLine;
use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Domain\CustomerCreditLimitCheck;
use App\Modules\Warehouse\Actions\CreateGrnFromPo;
use App\Modules\Warehouse\Actions\PostGrn;
use App\Support\Money;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('the AP control account total equals the sum of every supplier sub-ledger', function () {
    $warehouse = createWarehouseFixture();
    $supplierA = createSupplierFixture();
    $supplierB = createSupplierFixture();
    $product = createProductFixture();

    $poA = createApprovedPurchaseOrder($warehouse, $supplierA, $product, qty: '10', unitCost: '5.00');
    $poB = createApprovedPurchaseOrder($warehouse, $supplierB, $product, qty: '6', unitCost: '5.00');

    actingAsUser(createManagerUser());
    $grnA = app(CreateGrnFromPo::class)->execute($poA, [
        ['po_item_id' => $poA->items->first()->id, 'qty_received' => '10', 'unit_cost' => '5.00'],
    ]);
    app(PostGrn::class)->execute($grnA);

    $grnB = app(CreateGrnFromPo::class)->execute($poB, [
        ['po_item_id' => $poB->items->first()->id, 'qty_received' => '6', 'unit_cost' => '5.00'],
    ]);
    app(PostGrn::class)->execute($grnB);

    $apAccountId = app(ChartOfAccountResolver::class)->accountsPayable()->id;
    $lines = JournalLine::query()->where('account_id', $apAccountId)->get();

    $controlTotal = $lines->reduce(fn (Money $carry, JournalLine $line) => $carry->add($line->credit)->subtract($line->debit), Money::zero());
    $supplierATotal = $lines->where('partner_id', $supplierA->id)->reduce(fn (Money $carry, JournalLine $line) => $carry->add($line->credit)->subtract($line->debit), Money::zero());
    $supplierBTotal = $lines->where('partner_id', $supplierB->id)->reduce(fn (Money $carry, JournalLine $line) => $carry->add($line->credit)->subtract($line->debit), Money::zero());

    expect($controlTotal->equals($supplierATotal->add($supplierBTotal)))->toBeTrue()
        ->and($controlTotal->toMajor())->toBe('80.00'); // 50.00 + 30.00
});

test('the AR control account total equals the sum of every customer sub-ledger', function () {
    $warehouse = createWarehouseFixture();
    $customerA = createCustomerFixture('1000.00');
    $customerB = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');

    actingAsUser(createManagerUser());
    $soA = app(CreateSalesOrder::class)->execute($customerA->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $soA = fulfillSalesOrderFixture($soA);
    app(ConvertToInvoice::class)->fromSalesOrder($soA);

    $soB = app(CreateSalesOrder::class)->execute($customerB->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '3', 'unit_price' => '20.00'],
    ]);
    $soB = fulfillSalesOrderFixture($soB);
    app(ConvertToInvoice::class)->fromSalesOrder($soB);

    $creditCheck = app(CustomerCreditLimitCheck::class);
    $arAccountId = app(ChartOfAccountResolver::class)->accountsReceivable()->id;
    $lines = JournalLine::query()->where('account_id', $arAccountId)->get();

    $controlTotal = $lines->reduce(fn (Money $carry, JournalLine $line) => $carry->add($line->debit)->subtract($line->credit), Money::zero());
    $sumOfCustomers = $creditCheck->outstandingBalance($customerA)->add($creditCheck->outstandingBalance($customerB));

    expect($controlTotal->equals($sumOfCustomers))->toBeTrue()
        ->and($controlTotal->toMajor())->toBe('160.00'); // 100.00 + 60.00
});
