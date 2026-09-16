<?php

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\Sales\Actions\ConvertToInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Actions\RecordInvoicePayment;
use App\Modules\Sales\Actions\RecordPosSale;
use App\Modules\Sales\Models\Invoice;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

/**
 * A credit-sale invoice (unlike the POS fixture in InvoiceHttpTest) carries a
 * due date, so it can land in the register's Overdue / due-soon buckets.
 *
 * @return array{invoice: Invoice, customer: Customer, product: Product}
 */
function createCreditInvoiceFixture(?string $dueDate = null, string $qty = '5'): array
{
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    actingAsUser(createManagerUser());

    $salesOrder = app(CreateSalesOrder::class)->execute($customer->id, $warehouse->id, [
        ['product_id' => $product->id, 'qty' => $qty, 'unit_price' => '20.00'],
    ]);
    $salesOrder = fulfillSalesOrderFixture($salesOrder);
    $invoice = app(ConvertToInvoice::class)->fromSalesOrder($salesOrder, $dueDate);

    return ['invoice' => $invoice, 'customer' => $customer, 'product' => $product];
}

test('the register reports a live document count per status chip, including the derived overdue bucket', function () {
    ['invoice' => $unpaid] = createCreditInvoiceFixture();
    ['invoice' => $paid] = createCreditInvoiceFixture();
    app(RecordInvoicePayment::class)->execute($paid, $paid->grand_total, 'cash');
    ['invoice' => $overdue] = createCreditInvoiceFixture(now()->subDays(10)->toDateString());

    $manager = createManagerUser();

    $this->actingAs($manager)->get('/invoices')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('sales/invoices/Index')
        ->has('invoices', 3)
        ->where('statusCounts.all', 3)
        ->where('statusCounts.unpaid', 2)
        ->where('statusCounts.paid', 1)
        ->where('statusCounts.partially_paid', 0)
        ->where('statusCounts.overdue', 1)
    );

    // Overdue is derived from due_date/balance, never a stored status — a
    // paid invoice past its due date must never count, an unpaid one must.
    $this->actingAs($manager)->get('/invoices?status=overdue')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('invoices', 1)
        ->where('invoices.0.id', $overdue->id)
        ->where('invoices.0.is_overdue', true)
        ->where('invoices.0.days_overdue', 10)
    );

    $this->actingAs($manager)->get('/invoices?status=paid')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('invoices', 1)
        ->where('invoices.0.id', $paid->id)
        ->where('invoices.0.is_overdue', false)
    );
});

test('the register search matches a customer code and an item on the invoice, and totals reflect the active status filter', function () {
    ['invoice' => $unpaidInvoice, 'customer' => $customer, 'product' => $product] = createCreditInvoiceFixture();
    ['invoice' => $otherInvoice] = createCreditInvoiceFixture();

    $manager = createManagerUser();

    $this->actingAs($manager)->get("/invoices?q={$customer->code}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('invoices', 1)
        ->where('invoices.0.id', $unpaidInvoice->id)
    );

    $this->actingAs($manager)->get("/invoices?q={$product->sku}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('invoices', 1)
        ->where('invoices.0.id', $unpaidInvoice->id)
    );

    $this->actingAs($manager)->get('/invoices?status=unpaid')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('invoices', 2)
        ->where('filteredTotals.document_count', 2)
        ->where('filteredTotals.total_value', '200.00')
        ->where('filteredTotals.total_outstanding', '200.00')
    );

    expect($otherInvoice)->not->toBeNull();
});

test('the register sorts by total and can be searched by document number', function () {
    ['invoice' => $small] = createCreditInvoiceFixture(qty: '2'); // 40.00
    ['invoice' => $large] = createCreditInvoiceFixture(qty: '8'); // 160.00

    $manager = createManagerUser();

    $this->actingAs($manager)->get('/invoices?sort=grand_total&direction=asc')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('invoices.0.id', $small->id)
        ->where('invoices.1.id', $large->id)
    );

    $this->actingAs($manager)->get('/invoices?sort=grand_total&direction=desc')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('invoices.0.id', $large->id)
        ->where('invoices.1.id', $small->id)
    );

    $this->actingAs($manager)->get("/invoices?q={$small->no}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('invoices', 1)
        ->where('invoices.0.id', $small->id)
    );
});

test('a cashier scoped to their own invoices only sees their own rows and counts in the register', function () {
    ['invoice' => $managerInvoice] = createCreditInvoiceFixture();

    $cashier = createCashierUser();
    actingAsUser($cashier);
    $till = openTillSessionFixture(createWarehouseFixture()->id);
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $till->warehouse_id, $product, '10');

    $ownInvoice = app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '1', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '20.00'],
    ]);

    $this->actingAs($cashier)->get('/invoices')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('invoices', 1)
        ->where('invoices.0.id', $ownInvoice->id)
        ->where('statusCounts.all', 1)
    );

    expect($managerInvoice)->not->toBeNull();
});
