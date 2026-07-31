<?php

use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\Warehouse\Models\StockLedgerEntry;
use App\Support\Exceptions\ImmutableRecordException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function createStockLedgerEntry(): StockLedgerEntry
{
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece']);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(), 'name' => 'Test Product', 'unit_id' => $unit->id,
    ]);

    return StockLedgerEntry::query()->create([
        'location_type' => 'warehouse',
        'location_id' => 1,
        'product_id' => $product->id,
        'qty_in' => '10.000',
        'qty_out' => '0.000',
        'unit_cost' => 500,
        'doc_type' => 'test',
        'doc_id' => 1,
        'created_at' => now(),
    ]);
}

test('updating a stock ledger entry is blocked at the Eloquent layer', function () {
    $entry = createStockLedgerEntry();

    expect(fn () => $entry->update(['qty_in' => '99.000']))
        ->toThrow(ImmutableRecordException::class);
});

test('deleting a stock ledger entry is blocked at the Eloquent layer', function () {
    $entry = createStockLedgerEntry();

    expect(fn () => $entry->delete())->toThrow(ImmutableRecordException::class);
});

test('updating a stock ledger entry is blocked at the database layer', function () {
    $entry = createStockLedgerEntry();

    expect(fn () => DB::table('stock_ledger')->where('id', $entry->id)->update(['qty_in' => 99]))
        ->toThrow(QueryException::class);
});

test('deleting a stock ledger entry is blocked at the database layer', function () {
    $entry = createStockLedgerEntry();

    expect(fn () => DB::table('stock_ledger')->where('id', $entry->id)->delete())
        ->toThrow(QueryException::class);
});
