<?php

use App\Modules\MasterData\Actions\CreateBatch;
use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\MasterData\Domain\Exceptions\ExpiredBatchException;
use App\Modules\MasterData\Models\Product;
use App\Modules\Warehouse\Models\StockLedgerEntry;

function createTrackedProduct(): Product
{
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece']);

    return app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Yoghurt', 'unit_id' => $unit->id, 'track_expiry' => true,
    ]);
}

test('a batch with an expiry date in the past is blocked on receipt', function () {
    $product = createTrackedProduct();

    expect(fn () => app(CreateBatch::class)->execute([
        'product_id' => $product->id,
        'batch_no' => 'B-001',
        'expiry_date' => now()->subDay()->toDateString(),
    ]))->toThrow(ExpiredBatchException::class);
});

test('a batch with a past expiry date can be force-created with an explicit override', function () {
    $product = createTrackedProduct();

    $batch = app(CreateBatch::class)->execute([
        'product_id' => $product->id,
        'batch_no' => 'B-001',
        'expiry_date' => now()->subDay()->toDateString(),
        'allow_expired' => true,
    ]);

    expect($batch->isExpired())->toBeTrue();
});

test('a batch with a future expiry date is created normally', function () {
    $product = createTrackedProduct();

    $batch = app(CreateBatch::class)->execute([
        'product_id' => $product->id,
        'batch_no' => 'B-001',
        'expiry_date' => now()->addMonth()->toDateString(),
    ]);

    expect($batch->isExpired())->toBeFalse();
});

test('switching a product from non-tracked to expiry-tracked mid-life does not corrupt existing stock rows', function () {
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece']);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-X', 'name' => 'Bread', 'unit_id' => $unit->id, 'track_expiry' => false,
    ]);

    $entry = StockLedgerEntry::query()->create([
        'location_type' => 'warehouse',
        'location_id' => 1,
        'product_id' => $product->id,
        'batch_id' => null,
        'qty_in' => '50.000',
        'qty_out' => '0.000',
        'unit_cost' => 500,
        'doc_type' => 'test',
        'doc_id' => 1,
        'created_at' => now(),
    ]);

    $product->update(['track_expiry' => true]);

    expect($entry->refresh()->batch_id)->toBeNull()
        ->and($product->refresh()->track_expiry)->toBeTrue();
});
