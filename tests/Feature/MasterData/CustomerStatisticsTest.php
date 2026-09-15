<?php

use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Actions\RecordPosSale;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('the customer statistics panel reflects balance, overdue, and lifetime sales figures', function () {
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '20');

    $cashier = createCashierUser();
    actingAsUser($cashier);
    $till = openTillSessionFixture($warehouse->id);

    // A fully paid POS sale: GHS 100 grand total, fully covered — contributes
    // to lifetime sales/payments but not to the outstanding/overdue figures.
    app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '100.00'],
    ]);

    $manager = createManagerUser();
    actingAsUser($manager);

    // An overdue, unpaid Sales-Order-derived invoice: GHS 100 grand total,
    // due 10 days ago.
    $so = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00'],
    ]);
    $so = fulfillSalesOrderFixture($so);
    app(ConvertToInvoice::class)->fromSalesOrder($so, now()->subDays(10)->toDateString());

    $this->actingAs($manager)->get("/customers/{$customer->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('customers/Show')
        ->where('statistics.balance', '100.00')
        ->where('statistics.creditLimit', '1000.00')
        ->where('statistics.outstandingOrders', '0.00')
        ->where('statistics.outstandingInvoices', '100.00')
        ->where('statistics.overdueAmount', '100.00')
        ->where('statistics.totalSales', '200.00')
        ->where('statistics.totalPayments', '100.00')
    );
});
