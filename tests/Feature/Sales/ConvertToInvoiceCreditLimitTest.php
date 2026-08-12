<?php

use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Domain\Exceptions\CreditLimitExceededException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('converting an invoice that exceeds the customer credit limit is blocked without sales.credit_override', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('50.00');
    $product = createProductFixture();

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);

    expect(fn () => app(ConvertToInvoice::class)->fromSalesOrder($so))
        ->toThrow(CreditLimitExceededException::class);
});

test('converting an invoice that exceeds the credit limit succeeds with sales.credit_override', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('50.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    $overrider = createManagerUser();
    $overrider->givePermissionTo('sales.credit_override');
    actingAsUser($overrider);

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);

    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($so);

    expect($invoice->grand_total->toMajor())->toBe('100.00');
});

test('sales.credit_override is not granted by default to the warehouse manager role', function () {
    $manager = createManagerUser();

    expect($manager->can('sales.credit_override'))->toBeFalse();
});
