<?php

use App\Modules\MasterData\Domain\Exceptions\ExpiredBatchException;
use App\Modules\MasterData\Models\Batch;
use App\Modules\Warehouse\Actions\CreateGrnFromPo;
use App\Modules\Warehouse\Actions\PostGrn;
use App\Modules\Warehouse\Models\StockLedgerEntry;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a GRN against an expiry-tracked product captures batch and expiry, and posting carries the batch onto the ledger', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture(trackExpiry: true);
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '10');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    $grn = app(CreateGrnFromPo::class)->execute($po, [
        [
            'po_item_id' => $poItem->id,
            'qty_received' => '10',
            'unit_cost' => '5.00',
            'batch_no' => 'B-2026-01',
            'expiry_date' => now()->addMonths(6)->toDateString(),
        ],
    ]);

    $batch = Batch::query()->where('product_id', $product->id)->where('batch_no', 'B-2026-01')->firstOrFail();
    expect($grn->items->first()->batch_id)->toBe($batch->id)
        ->and($batch->isExpired())->toBeFalse();

    $grn = app(PostGrn::class)->execute($grn);

    $ledgerEntry = StockLedgerEntry::query()
        ->where('doc_type', 'grn')->where('doc_id', $grn->id)->firstOrFail();

    expect($ledgerEntry->batch_id)->toBe($batch->id);
});

test('receiving against an already-known batch reuses it instead of creating a duplicate', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture(trackExpiry: true);
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '20');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    $grn1 = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '10', 'unit_cost' => '5.00', 'batch_no' => 'B-REUSE', 'expiry_date' => now()->addYear()->toDateString()],
    ]);
    $grn2 = app(CreateGrnFromPo::class)->execute($po, [
        ['po_item_id' => $poItem->id, 'qty_received' => '10', 'unit_cost' => '5.00', 'batch_no' => 'B-REUSE'],
    ]);

    expect($grn1->items->first()->batch_id)->toBe($grn2->items->first()->batch_id)
        ->and(Batch::query()->where('batch_no', 'B-REUSE')->count())->toBe(1);
});

test('a GRN batch with an expiry date already in the past is blocked, reusing the Phase 1 guard', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture(trackExpiry: true);
    $po = createApprovedPurchaseOrder($warehouse, $supplier, $product, qty: '10');
    $poItem = $po->items->first();

    actingAsUser(createManagerUser());

    expect(fn () => app(CreateGrnFromPo::class)->execute($po, [
        [
            'po_item_id' => $poItem->id,
            'qty_received' => '10',
            'unit_cost' => '5.00',
            'batch_no' => 'B-EXPIRED',
            'expiry_date' => now()->subDay()->toDateString(),
        ],
    ]))->toThrow(ExpiredBatchException::class);
});
