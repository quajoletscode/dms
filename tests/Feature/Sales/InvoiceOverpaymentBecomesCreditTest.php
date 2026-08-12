<?php

use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Actions\RecordInvoicePayment;
use App\Modules\Sales\Domain\CustomerCreditLimitCheck;
use App\Support\Money;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('overpaying an invoice posts in full and drives the customer AR balance negative — their credit', function () {
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

    $payment = app(RecordInvoicePayment::class)->execute($invoice, Money::fromMajor('150.00'), 'cash');

    expect($payment->amount->toMajor())->toBe('150.00')
        ->and($invoice->fresh()->status)->toBe('paid');

    $outstanding = app(CustomerCreditLimitCheck::class)->outstandingBalance($customer);

    expect($outstanding->toMajor())->toBe('-50.00');
});
