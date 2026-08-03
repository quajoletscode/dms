<?php

use App\Modules\Warehouse\Actions\ApprovePurchaseOrder;
use App\Modules\Warehouse\Actions\CancelPurchaseOrder;
use App\Modules\Warehouse\Actions\CreatePurchaseOrder;
use App\Modules\Warehouse\Actions\SubmitPurchaseOrder;
use App\Modules\Warehouse\Domain\PurchaseOrderTransitions;
use App\Support\Exceptions\IllegalTransitionException;
use App\Support\Exceptions\SegregationOfDutiesException;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('a purchase order moves through its full happy-path lifecycle', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $creator = createManagerUser();
    $approver = createManagerUser();

    actingAsUser($creator);
    $po = app(CreatePurchaseOrder::class)->execute($supplier->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty_ordered' => '10', 'unit_cost' => '5.00'],
    ]);
    expect($po->status)->toBe('draft')
        ->and($po->created_by)->toBe($creator->id)
        ->and($po->grand_total->toMajor())->toBe('50.00');

    $po = app(SubmitPurchaseOrder::class)->execute($po);
    expect($po->status)->toBe('submitted');

    actingAsUser($approver);
    $po = app(ApprovePurchaseOrder::class)->execute($po);
    expect($po->status)->toBe('approved')
        ->and($po->approved_by)->toBe($approver->id);
});

test('the creator of a purchase order cannot approve it', function () {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $creator = createManagerUser();

    actingAsUser($creator);
    $po = app(CreatePurchaseOrder::class)->execute($supplier->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty_ordered' => '10', 'unit_cost' => '5.00'],
    ]);
    $po = app(SubmitPurchaseOrder::class)->execute($po);

    expect(fn () => app(ApprovePurchaseOrder::class)->execute($po))
        ->toThrow(SegregationOfDutiesException::class);
});

test('a purchase order can be cancelled from draft, submitted, or approved', function (string $fromStatus) {
    $warehouse = createWarehouseFixture();
    $supplier = createSupplierFixture();
    $product = createProductFixture();
    $creator = createManagerUser();
    $approver = createManagerUser();

    actingAsUser($creator);
    $po = app(CreatePurchaseOrder::class)->execute($supplier->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty_ordered' => '10', 'unit_cost' => '5.00'],
    ]);

    if (in_array($fromStatus, ['submitted', 'approved'], true)) {
        $po = app(SubmitPurchaseOrder::class)->execute($po);
    }

    if ($fromStatus === 'approved') {
        actingAsUser($approver);
        $po = app(ApprovePurchaseOrder::class)->execute($po);
    }

    $po = app(CancelPurchaseOrder::class)->execute($po);

    expect($po->status)->toBe('cancelled');
})->with(['draft', 'submitted', 'approved']);

test('illegal purchase order transitions are rejected', function (string $from, string $to) {
    expect(fn () => app(PurchaseOrderTransitions::class)->assertCanTransition($from, $to))
        ->toThrow(IllegalTransitionException::class);
})->with([
    'draft -> approved (skips submitted)' => ['draft', 'approved'],
    'draft -> received' => ['draft', 'received'],
    'draft -> closed' => ['draft', 'closed'],
    'submitted -> received (skips approved)' => ['submitted', 'received'],
    'submitted -> closed' => ['submitted', 'closed'],
    'submitted -> draft' => ['submitted', 'draft'],
    'received -> cancelled' => ['received', 'cancelled'],
    'partially_received -> cancelled' => ['partially_received', 'cancelled'],
    'closed -> draft' => ['closed', 'draft'],
    'cancelled -> draft' => ['cancelled', 'draft'],
]);
