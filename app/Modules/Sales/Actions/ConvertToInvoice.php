<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\Sales\Domain\CustomerCreditLimitCheck;
use App\Modules\Sales\Domain\Exceptions\CreditLimitExceededException;
use App\Modules\Sales\Domain\Exceptions\ProformaExpiredException;
use App\Modules\Sales\Domain\SalesOrderTransitions;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoiceItem;
use App\Modules\Sales\Models\ProformaInvoice;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Warehouse\Domain\FefoStockPicker;
use App\Modules\Warehouse\Domain\StockMover;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use App\Support\Quantity;
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
        private readonly FefoStockPicker $fefo,
        private readonly StockMover $stockMover,
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

        $invoice = $this->convert($salesOrder->customer, $salesOrder->warehouse, $items, 'sales_order', $salesOrder->id, null, $dueDate);

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

        $invoice = $this->convert($proforma->customer, $proforma->warehouse, $items, 'proforma', null, $proforma->id, $dueDate);

        $proforma->update(['status' => 'converted']);

        return $invoice;
    }

    /**
     * @param  list<array{product_id:int, qty:string, unit_price:string, discount:string}>  $items
     */
    private function convert(
        Customer $customer,
        Warehouse $warehouse,
        array $items,
        string $source,
        ?int $salesOrderId,
        ?int $proformaInvoiceId,
        ?string $dueDate,
    ): Invoice {
        return DB::transaction(function () use ($customer, $warehouse, $items, $source, $salesOrderId, $proformaInvoiceId, $dueDate) {
            $subtotal = Money::zero();
            $taxTotal = Money::zero();
            $discountTotal = Money::zero();
            $lines = [];

            foreach ($items as $line) {
                $product = Product::query()->whereKey($line['product_id'])->firstOrFail();
                $qty = Quantity::fromString($line['qty']);
                $unitPrice = Money::fromMajor($line['unit_price']);
                $discount = Money::fromMajor($line['discount']);
                $lineGross = $unitPrice->multiply((string) $qty)->subtract($discount);
                $tax = $lineGross->percentage((string) $product->tax_rate);

                $subtotal = $subtotal->add($unitPrice->multiply((string) $qty));
                $taxTotal = $taxTotal->add($tax);
                $discountTotal = $discountTotal->add($discount);

                $lines[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax_rate' => (string) $product->tax_rate,
                    'tax' => $tax,
                ];
            }

            $grandTotal = $subtotal->add($taxTotal)->subtract($discountTotal);

            if ($this->creditCheck->wouldExceedLimit($customer, $grandTotal) && ! (Auth::user()?->can('sales.credit_override') ?? false)) {
                throw new CreditLimitExceededException(
                    "Invoicing {$customer->name} for {$grandTotal} would exceed their credit limit ({$customer->credit_limit}); sales.credit_override permission required."
                );
            }

            $invoice = Invoice::query()->create([
                'no' => $this->numbers->next('invoice', 'warehouse', $warehouse->id),
                'source' => $source,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'sales_order_id' => $salesOrderId,
                'proforma_invoice_id' => $proformaInvoiceId,
                'due_date' => $dueDate,
                'status' => 'unpaid',
                'created_by' => Auth::id(),
                'subtotal' => $subtotal->minorUnits,
                'tax_total' => $taxTotal->minorUnits,
                'discount_total' => $discountTotal->minorUnits,
                'grand_total' => $grandTotal->minorUnits,
            ]);

            foreach ($lines as $line) {
                $picks = $this->fefo->pick($line['product'], 'warehouse', $warehouse->id, $line['qty']);

                // A line's discount/tax are recorded once, on the first pick —
                // FEFO can split one requested line across several batches, but
                // the line-level charge must not be duplicated per batch.
                foreach ($picks as $index => $pick) {
                    InvoiceItem::query()->create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $line['product']->id,
                        'batch_id' => $pick['batch_id'],
                        'qty' => $pick['qty'],
                        'unit_price' => $line['unit_price']->minorUnits,
                        'discount' => $index === 0 ? $line['discount']->minorUnits : 0,
                        'tax_rate' => $line['tax_rate'],
                        'tax' => $index === 0 ? $line['tax']->minorUnits : 0,
                        'unit_cost' => $line['product']->cost_price->minorUnits,
                    ]);

                    $this->stockMover->move(
                        locationType: 'warehouse',
                        locationId: $warehouse->id,
                        productId: $line['product']->id,
                        batchId: $pick['batch_id'],
                        qtyIn: Quantity::zero(),
                        qtyOut: $pick['qty'],
                        unitCost: $line['product']->cost_price,
                        docType: 'invoice',
                        docId: $invoice->id,
                    );
                }
            }

            $this->postingEngine->post('invoice.issued', $invoice->load('items'));

            return $invoice->refresh();
        });
    }
}
