<?php

use App\Models\User;
use App\Modules\MasterData\Actions\CreateCustomer;
use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateSupplier;
use App\Modules\MasterData\Actions\CreateUnit;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\Warehouse\Actions\ApprovePurchaseOrder;
use App\Modules\Warehouse\Actions\CreatePurchaseOrder;
use App\Modules\Warehouse\Actions\SubmitPurchaseOrder;
use App\Modules\Warehouse\Domain\StockMover;
use App\Modules\Warehouse\Models\PurchaseOrder;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Warehouse Inbound (Phase 2) fixtures
|--------------------------------------------------------------------------
|
| Plain functions, not test-case methods, so they're usable both inside
| test() closures and inside other helper functions. Auth::setUser() (not
| $this->actingAs()) for the same reason — it works in either context.
|
*/

function createWarehouseFixture(): Warehouse
{
    return Warehouse::query()->create([
        'code' => 'WH-'.uniqid(),
        'name' => 'Main Warehouse',
        'is_active' => true,
    ]);
}

function createSupplierFixture(): Supplier
{
    return app(CreateSupplier::class)->execute([
        'code' => 'SUP-'.uniqid(),
        'name' => 'Acme Distributors',
    ]);
}

function createProductFixture(bool $trackExpiry = false): Product
{
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece-'.uniqid()]);

    return app(CreateProduct::class)->execute([
        'sku' => 'SKU-'.uniqid(),
        'name' => 'Widget',
        'unit_id' => $unit->id,
        'track_expiry' => $trackExpiry,
    ]);
}

function createManagerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('warehouse_manager');

    return $user;
}

function actingAsUser(User $user): void
{
    Auth::setUser($user);
}

/**
 * Creates an approved PO for $qty of $product at $unitCost, using two
 * distinct users for creation vs approval (segregation of duties).
 */
function createApprovedPurchaseOrder(
    Warehouse $warehouse,
    Supplier $supplier,
    Product $product,
    string $qty = '10',
    string $unitCost = '5.00',
): PurchaseOrder {
    $creator = createManagerUser();
    $approver = createManagerUser();

    actingAsUser($creator);
    $po = app(CreatePurchaseOrder::class)->execute($supplier->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty_ordered' => $qty, 'unit_cost' => $unitCost],
    ]);
    $po = app(SubmitPurchaseOrder::class)->execute($po);

    actingAsUser($approver);

    return app(ApprovePurchaseOrder::class)->execute($po);
}

/*
|--------------------------------------------------------------------------
| Warehouse Outbound (Phase 3) fixtures
|--------------------------------------------------------------------------
*/

function createCustomerFixture(string $creditLimit = '0', string $type = 'wholesale'): Customer
{
    return app(CreateCustomer::class)->execute([
        'code' => 'CUST-'.uniqid(),
        'name' => 'Test Customer',
        'type' => $type,
        'credit_limit' => $creditLimit,
    ]);
}

/**
 * Puts real stock into a location via StockMover directly — bypassing the
 * GRN ceremony for tests that aren't exercising Phase 2 themselves.
 */
function receiveStockFixture(
    string $locationType,
    int $locationId,
    Product $product,
    string $qty,
    string $unitCost = '5.00',
    ?int $batchId = null,
): void {
    app(StockMover::class)->move(
        locationType: $locationType,
        locationId: $locationId,
        productId: $product->id,
        batchId: $batchId,
        qtyIn: Quantity::fromString($qty),
        qtyOut: Quantity::zero(),
        unitCost: Money::fromMajor($unitCost),
        docType: 'test_seed',
        docId: 0,
    );
}
