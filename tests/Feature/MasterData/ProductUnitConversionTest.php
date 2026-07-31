<?php

use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\Warehouse\Models\StockLedgerEntry;
use App\Support\Quantity;

test('carton to piece conversion factor computes correctly', function () {
    $piece = app(CreateUnit::class)->execute(['name' => 'Piece', 'symbol' => 'pc']);
    $carton = app(CreateUnit::class)->execute([
        'name' => 'Carton', 'symbol' => 'ctn', 'base_unit_id' => $piece->id, 'conversion_factor' => 12,
    ]);

    $cartonsSold = Quantity::fromString('2');
    $piecesSold = $cartonsSold->multiply((string) $carton->conversion_factor);

    expect((string) $piecesSold)->toBe('24.000');
});

test('selling in pieces against carton-costed stock uses the same underlying quantity scale', function () {
    $piece = app(CreateUnit::class)->execute(['name' => 'Piece']);
    app(CreateUnit::class)->execute(['name' => 'Carton', 'base_unit_id' => $piece->id, 'conversion_factor' => 12]);

    $stockInPieces = Quantity::fromString('7'); // only 7 pieces remain
    $requestedPieces = Quantity::fromString('7');

    expect($stockInPieces->lessThan($requestedPieces))->toBeFalse()
        ->and($stockInPieces->equals($requestedPieces))->toBeTrue();
});

test('changing a unit conversion factor after stock exists does not retroactively change historical ledger costs', function () {
    $piece = app(CreateUnit::class)->execute(['name' => 'Piece']);
    $carton = app(CreateUnit::class)->execute(['name' => 'Carton', 'base_unit_id' => $piece->id, 'conversion_factor' => 12]);

    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-1', 'name' => 'Soap', 'unit_id' => $carton->id, 'cost_price' => '120.00',
    ]);

    $ledgerEntry = StockLedgerEntry::query()->create([
        'location_type' => 'warehouse',
        'location_id' => 1,
        'product_id' => $product->id,
        'qty_in' => '10.000',
        'qty_out' => '0.000',
        'unit_cost' => 12000,
        'doc_type' => 'test',
        'doc_id' => 1,
        'created_at' => now(),
    ]);

    $carton->update(['conversion_factor' => 24]);

    expect($ledgerEntry->refresh()->unit_cost->minorUnits)->toBe(12000)
        ->and($carton->refresh()->conversion_factor)->toBe('24.000000');
});
