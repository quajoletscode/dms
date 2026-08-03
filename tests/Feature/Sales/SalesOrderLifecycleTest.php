<?php

use App\Modules\Sales\Actions\ConfirmSalesOrder;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Domain\SalesOrderTransitions;
use App\Support\Exceptions\IllegalTransitionException;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('a sales order moves through its happy-path lifecycle', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    expect($so->status)->toBe('draft')
        ->and($so->grand_total->toMajor())->toBe('100.00');

    $so = app(ConfirmSalesOrder::class)->execute($so);
    expect($so->status)->toBe('confirmed');
});

test('a sales order can be cancelled from draft, confirmed, or fulfilled', function (string $fromStatus) {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();

    actingAsUser(createManagerUser());

    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);

    if (in_array($fromStatus, ['confirmed', 'fulfilled'], true)) {
        $so = app(ConfirmSalesOrder::class)->execute($so);
    }

    if ($fromStatus === 'fulfilled') {
        app(SalesOrderTransitions::class)->assertCanTransition($so->status, 'fulfilled');
        $so->update(['status' => 'fulfilled']);
    }

    app(SalesOrderTransitions::class)->assertCanTransition($so->status, 'cancelled');
    $so->update(['status' => 'cancelled']);

    expect($so->fresh()->status)->toBe('cancelled');
})->with(['draft', 'confirmed', 'fulfilled']);

test('illegal sales order transitions are rejected', function (string $from, string $to) {
    expect(fn () => app(SalesOrderTransitions::class)->assertCanTransition($from, $to))
        ->toThrow(IllegalTransitionException::class);
})->with([
    'draft -> fulfilled (skips confirmed)' => ['draft', 'fulfilled'],
    'draft -> invoiced' => ['draft', 'invoiced'],
    'confirmed -> invoiced (skips fulfilled)' => ['confirmed', 'invoiced'],
    'invoiced -> cancelled' => ['invoiced', 'cancelled'],
    'invoiced -> draft' => ['invoiced', 'draft'],
    'cancelled -> draft' => ['cancelled', 'draft'],
]);
