<?php

use App\Modules\MasterData\Actions\CreateBatch;
use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Warehouse\Domain\Exceptions\InsufficientStockException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('the nearest-expiry batch is issued first', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture(trackExpiry: true);

    $farBatch = app(CreateBatch::class)->execute([
        'product_id' => $product->id, 'batch_no' => 'B-FAR', 'expiry_date' => now()->addMonths(6)->toDateString(),
    ]);
    $nearBatch = app(CreateBatch::class)->execute([
        'product_id' => $product->id, 'batch_no' => 'B-NEAR', 'expiry_date' => now()->addDays(10)->toDateString(),
    ]);
    receiveStockFixture('warehouse', $warehouse->id, $product, '10', batchId: $farBatch->id);
    receiveStockFixture('warehouse', $warehouse->id, $product, '10', batchId: $nearBatch->id);

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);
    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($so);

    expect($invoice->items->first()->batch_id)->toBe($nearBatch->id);
});

test('an expired batch is skipped even when it would otherwise be picked first', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture(trackExpiry: true);

    $expiredBatch = app(CreateBatch::class)->execute([
        'product_id' => $product->id, 'batch_no' => 'B-EXPIRED', 'expiry_date' => now()->subDay()->toDateString(), 'allow_expired' => true,
    ]);
    $freshBatch = app(CreateBatch::class)->execute([
        'product_id' => $product->id, 'batch_no' => 'B-FRESH', 'expiry_date' => now()->addMonths(3)->toDateString(),
    ]);
    receiveStockFixture('warehouse', $warehouse->id, $product, '10', batchId: $expiredBatch->id);
    receiveStockFixture('warehouse', $warehouse->id, $product, '10', batchId: $freshBatch->id);

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);
    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($so);

    expect($invoice->items->first()->batch_id)->toBe($freshBatch->id);
});

test('insufficient unexpired stock across all batches throws', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture(trackExpiry: true);

    $expiredBatch = app(CreateBatch::class)->execute([
        'product_id' => $product->id, 'batch_no' => 'B-EXPIRED', 'expiry_date' => now()->subDay()->toDateString(), 'allow_expired' => true,
    ]);
    receiveStockFixture('warehouse', $warehouse->id, $product, '10', batchId: $expiredBatch->id);

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);

    expect(fn () => app(ConvertToInvoice::class)->fromSalesOrder($so))
        ->toThrow(InsufficientStockException::class);
});
