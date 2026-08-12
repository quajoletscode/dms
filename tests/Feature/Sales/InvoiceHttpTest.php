<?php

use App\Models\User;
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
 * @return array{invoice: Invoice, cashier: User}
 */
function createPosInvoiceFixture(): array
{
    $warehouse = createWarehouseFixture();
    $customer = createCustomerFixture('1000.00');
    $product = createProductFixture();
    receiveStockFixture('warehouse', $warehouse->id, $product, '10');

    $cashier = createCashierUser();
    actingAsUser($cashier);
    $till = openTillSessionFixture($warehouse->id);

    $invoice = app(RecordPosSale::class)->execute($till->id, $customer->id, [
        ['product_id' => $product->id, 'qty' => '5', 'unit_price' => '20.00', 'discount' => '0'],
    ], [
        ['method' => 'cash', 'amount' => '50.00'],
    ]);

    return ['invoice' => $invoice, 'cashier' => $cashier];
}

test('the invoice index page renders for a cashier', function () {
    ['cashier' => $cashier] = createPosInvoiceFixture();

    $this->actingAs($cashier)->get('/invoices')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('sales/invoices/Index')
        ->has('invoices', 1)
    );
});

test('the invoice show page renders its items and payments', function () {
    ['invoice' => $invoice, 'cashier' => $cashier] = createPosInvoiceFixture();

    $this->actingAs($cashier)->get("/invoices/{$invoice->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('sales/invoices/Show')
        ->where('invoice.status', 'partially_paid')
        ->has('invoice.items', 1)
        ->has('invoice.payments', 1)
    );
});

test('a payment can be recorded against an invoice via http', function () {
    ['invoice' => $invoice] = createPosInvoiceFixture();
    $manager = createManagerUser();

    $this->actingAs($manager)->post("/invoices/{$invoice->id}/payments", [
        'amount' => '50.00',
        'method' => 'cash',
    ])->assertRedirect();

    expect($invoice->refresh()->status)->toBe('paid');
});

test('an overpayment is recorded without error, becoming customer credit', function () {
    ['invoice' => $invoice] = createPosInvoiceFixture();
    $manager = createManagerUser();

    $this->actingAs($manager)->post("/invoices/{$invoice->id}/payments", [
        'amount' => '9999.00',
        'method' => 'cash',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($invoice->refresh()->status)->toBe('paid')
        ->and($invoice->payments()->count())->toBe(2);
});

test('a cashier can view an invoice they created but cannot record a payment against it', function () {
    ['invoice' => $invoice, 'cashier' => $cashier] = createPosInvoiceFixture();

    $this->actingAs($cashier)->get("/invoices/{$invoice->id}")->assertOk();

    $this->actingAs($cashier)->post("/invoices/{$invoice->id}/payments", [
        'amount' => '10.00',
        'method' => 'cash',
    ])->assertForbidden();
});
