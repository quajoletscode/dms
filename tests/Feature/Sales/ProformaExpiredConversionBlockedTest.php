<?php

use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateProformaInvoice;
use App\Modules\Sales\Domain\Exceptions\ProformaExpiredException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('converting an expired proforma to an invoice is blocked', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createManagerUser());

    $proforma = app(CreateProformaInvoice::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ], now()->subDay()->toDateString());

    expect(fn () => app(ConvertToInvoice::class)->fromProforma($proforma))
        ->toThrow(ProformaExpiredException::class);
});

test('converting a non-expired proforma succeeds and marks it converted', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createManagerUser());

    $proforma = app(CreateProformaInvoice::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ], now()->addDays(7)->toDateString());

    $invoice = app(ConvertToInvoice::class)->fromProforma($proforma);

    expect($invoice->status)->toBe('unpaid')
        ->and($proforma->fresh()->status)->toBe('converted');
});

test('converting an already-converted proforma a second time is blocked', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createManagerUser());

    $proforma = app(CreateProformaInvoice::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ], now()->addDays(7)->toDateString());

    app(ConvertToInvoice::class)->fromProforma($proforma);

    expect(fn () => app(ConvertToInvoice::class)->fromProforma($proforma->fresh()))
        ->toThrow(ProformaExpiredException::class);
});
