<?php

use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateCreditNote;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Domain\Exceptions\ExcessiveCreditNoteException;
use App\Support\Quantity;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a credit note cannot exceed the remaining creditable quantity of the original invoice line', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);
    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($so);
    $invoiceItem = $invoice->items->first();

    app(CreateCreditNote::class)->execute($invoice, [
        ['invoice_item_id' => $invoiceItem->id, 'qty' => '3'],
    ], 'damaged');

    expect(fn () => app(CreateCreditNote::class)->execute($invoice, [
        ['invoice_item_id' => $invoiceItem->id, 'qty' => '3'],
    ], 'damaged'))->toThrow(ExcessiveCreditNoteException::class);
});

test('crediting exactly the remaining quantity across multiple credit notes succeeds', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);
    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($so);
    $invoiceItem = $invoice->items->first();

    app(CreateCreditNote::class)->execute($invoice, [
        ['invoice_item_id' => $invoiceItem->id, 'qty' => '3'],
    ], 'damaged');

    $second = app(CreateCreditNote::class)->execute($invoice, [
        ['invoice_item_id' => $invoiceItem->id, 'qty' => '2'],
    ], 'damaged');

    expect($second->items->first()->qty->equals(Quantity::fromString('2')))->toBeTrue();
});
