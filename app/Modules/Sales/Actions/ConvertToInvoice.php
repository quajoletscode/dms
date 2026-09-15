<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Domain\CustomerCreditLimitCheck;
use App\Modules\Sales\Domain\Exceptions\CreditLimitExceededException;
use App\Modules\Sales\Domain\Exceptions\ProformaExpiredException;
use App\Modules\Sales\Domain\InvoiceLineComposer;
use App\Modules\Sales\Domain\SalesOrderTransitions;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\ProformaInvoice;
use App\Modules\Sales\Models\SalesOrder;
use App\Support\DocumentNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SO-03: converting a Proforma/Sales Order to an Invoice reduces stock and
 * posts Dr AR/Cr Sales Revenue + Dr COGS/Cr Inventory. Every item's tax is
 * recomputed fresh from the product's *current* tax_rate — never copied from
 * the source document — per "tax rate at invoice date" (TaxRateAtInvoiceDateTest).
 */
final class ConvertToInvoice
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly InvoiceLineComposer $lineComposer,
        private readonly CustomerCreditLimitCheck $creditCheck,
        private readonly PostingEngine $postingEngine,
        private readonly SalesOrderTransitions $salesOrderTransitions,
    ) {}

    public function fromSalesOrder(SalesOrder $salesOrder, ?string $dueDate = null): Invoice
    {
        $this->salesOrderTransitions->assertCanTransition($salesOrder->status, 'invoiced');

        /** @var list<array{product_id:int, qty:string, unit_price:string, discount:string}> $items */
        $items = $salesOrder->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'qty' => (string) $item->qty,
            'unit_price' => $item->unit_price->toMajor(),
            'discount' => $item->discount->toMajor(),
        ])->all();

        $invoice = $this->convert($salesOrder->customer, $salesOrder->warehouse_id, $items, 'sales_order', $salesOrder->id, null, $dueDate);

        $salesOrder->update(['status' => 'invoiced']);

        return $invoice;
    }

    public function fromProforma(ProformaInvoice $proforma, ?string $dueDate = null): Invoice
    {
        if ($proforma->isExpired()) {
            throw new ProformaExpiredException("Proforma [{$proforma->no}] expired on {$proforma->valid_until->toDateString()}.");
        }

        if ($proforma->status === 'converted') {
            throw new ProformaExpiredException("Proforma [{$proforma->no}] has already been converted to an invoice.");
        }

        /** @var list<array{product_id:int, qty:string, unit_price:string, discount:string}> $items */
        $items = $proforma->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'qty' => (string) $item->qty,
            'unit_price' => $item->unit_price->toMajor(),
            'discount' => $item->discount->toMajor(),
        ])->all();

        $invoice = $this->convert($proforma->customer, $proforma->warehouse_id, $items, 'proforma', null, $proforma->id, $dueDate);

        $proforma->update(['status' => 'converted']);

        return $invoice;
    }

    /**
     * @param  list<array{product_id:int, qty:string, unit_price:string, discount:string}>  $items
     */
    private function convert(
        Customer $customer,
        int $warehouseId,
        array $items,
        string $source,
        ?int $salesOrderId,
        ?int $proformaInvoiceId,
        ?string $dueDate,
    ): Invoice {
        return DB::transaction(function () use ($customer, $warehouseId, $items, $source, $salesOrderId, $proformaInvoiceId, $dueDate) {
            $computation = $this->lineComposer->compute($items);
            $grandTotal = $computation['grandTotal'];

            if ($this->creditCheck->wouldExceedLimit($customer, $grandTotal) && ! (Auth::user()?->can('sales.credit_override') ?? false)) {
                throw new CreditLimitExceededException(
                    "Invoicing {$customer->name} for {$grandTotal} would exceed their credit limit ({$customer->credit_limit}); sales.credit_override permission required."
                );
            }

            $today = now()->toDateString();

            $invoice = Invoice::query()->create([
                'no' => $this->numbers->next('invoice', 'warehouse', $warehouseId),
                'source' => $source,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouseId,
                'sales_order_id' => $salesOrderId,
                'proforma_invoice_id' => $proformaInvoiceId,
                'due_date' => $dueDate,
                'invoice_date' => $today,
                'posting_date' => $today,
                'status' => 'unpaid',
                'created_by' => Auth::id(),
                'subtotal' => $computation['subtotal']->minorUnits,
                'tax_total' => $computation['taxTotal']->minorUnits,
                'discount_total' => $computation['discountTotal']->minorUnits,
                'grand_total' => $grandTotal->minorUnits,
            ]);

            $this->lineComposer->persist($invoice, 'warehouse', $warehouseId, $computation['lines']);

            $this->postingEngine->post('invoice.issued', $invoice->load('items'), date: $today);

            return $invoice->refresh();
        });
    }
}
