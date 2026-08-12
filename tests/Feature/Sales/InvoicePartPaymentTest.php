<?php

use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Actions\RecordInvoicePayment;
use App\Support\Money;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('an invoice moves from unpaid to partially_paid to paid as payments are recorded', function () {
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

    expect($invoice->grand_total->toMajor())->toBe('100.00')
        ->and($invoice->status)->toBe('unpaid');

    app(RecordInvoicePayment::class)->execute($invoice, Money::fromMajor('40.00'), 'cash');
    expect($invoice->fresh()->status)->toBe('partially_paid');

    app(RecordInvoicePayment::class)->execute($invoice->fresh(), Money::fromMajor('60.00'), 'cash');
    expect($invoice->fresh()->status)->toBe('paid');

    $this->assertDatabaseCount('invoice_payments', 2);
});
