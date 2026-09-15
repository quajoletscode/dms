<?php

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Sales\Domain\InvoiceLineComposer;
use App\Modules\Sales\Domain\PostingRules\InvoicePostingRule;
use App\Modules\Sales\Models\Invoice;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('an invoice can mix an item line with a gl_account line, and posts each to its own account', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    $glAccount = ChartOfAccount::query()->create([
        'code' => '4900',
        'name' => 'Miscellaneous Income',
        'type' => 'income',
        'is_control' => false,
    ]);

    $composer = app(InvoiceLineComposer::class);

    $computation = $composer->compute([
        ['line_type' => 'item', 'product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
        ['line_type' => 'gl_account', 'gl_account_id' => $glAccount->id, 'amount' => '50.00', 'tax_rate' => '10'],
    ]);

    expect($computation['subtotal']->toMajor())->toBe('150.00')
        ->and($computation['taxTotal']->toMajor())->toBe('5.00')
        ->and($computation['grandTotal']->toMajor())->toBe('155.00');

    $invoice = Invoice::query()->create([
        'no' => 'INV-GL-'.uniqid(),
        'source' => 'pos',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'posting_date' => now()->toDateString(),
        'status' => 'unpaid',
        'subtotal' => $computation['subtotal']->minorUnits,
        'tax_total' => $computation['taxTotal']->minorUnits,
        'discount_total' => $computation['discountTotal']->minorUnits,
        'grand_total' => $computation['grandTotal']->minorUnits,
    ]);

    $composer->persist($invoice, 'warehouse', $warehouse->id, $computation['lines']);
    $invoice->load('items');

    expect($invoice->items)->toHaveCount(2);

    $itemLine = $invoice->items->firstWhere('line_type', 'item');
    $glLine = $invoice->items->firstWhere('line_type', 'gl_account');

    expect($itemLine->product_id)->toBe($product->id)
        ->and($itemLine->gl_account_id)->toBeNull()
        ->and($glLine->product_id)->toBeNull()
        ->and($glLine->gl_account_id)->toBe($glAccount->id)
        ->and($glLine->unit_price->toMajor())->toBe('50.00')
        ->and($glLine->tax->toMajor())->toBe('5.00');

    $lines = app(InvoicePostingRule::class)->resolveLines($invoice);
    $resolver = app(ChartOfAccountResolver::class);

    $totalDebit = collect($lines)->reduce(fn ($carry, $line) => $carry + $line->debit->minorUnits, 0);
    $totalCredit = collect($lines)->reduce(fn ($carry, $line) => $carry + $line->credit->minorUnits, 0);

    expect($totalDebit)->toBe($totalCredit);

    $arLine = collect($lines)->first(fn ($line) => $line->accountId === $resolver->accountsReceivable()->id);
    $revenueLine = collect($lines)->first(fn ($line) => $line->accountId === $resolver->salesRevenue()->id);
    $glCreditLine = collect($lines)->first(fn ($line) => $line->accountId === $glAccount->id);

    expect($arLine->debit->toMajor())->toBe('155.00')
        ->and($revenueLine->credit->toMajor())->toBe('100.00')
        ->and($glCreditLine->credit->toMajor())->toBe('55.00');
});
