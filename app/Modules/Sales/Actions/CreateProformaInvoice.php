<?php

namespace App\Modules\Sales\Actions;

use App\Modules\MasterData\Models\Product;
use App\Modules\Sales\Models\ProformaInvoice;
use App\Modules\Sales\Models\ProformaInvoiceItem;
use App\Modules\Sales\Models\SalesOrder;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SO-02: standalone or generated from a Sales Order — either way it's a
 * non-posting quotation with no stock or accounting effect.
 */
final class CreateProformaInvoice
{
    public function __construct(private readonly DocumentNumberGenerator $numbers) {}

    /**
     * @param  array<int, array<string, mixed>>  $items  product_id, qty, unit_price?, discount?
     */
    public function execute(
        int $customerId,
        int $warehouseId,
        array $items,
        string $validUntil,
        ?int $salesOrderId = null,
    ): ProformaInvoice {
        return DB::transaction(function () use ($customerId, $warehouseId, $items, $validUntil, $salesOrderId) {
            $proforma = ProformaInvoice::query()->create([
                'no' => $this->numbers->next('proforma', 'warehouse', $warehouseId),
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'sales_order_id' => $salesOrderId,
                'valid_until' => $validUntil,
                'status' => 'open',
                'created_by' => Auth::id(),
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'grand_total' => 0,
            ]);

            $subtotal = Money::zero();
            $taxTotal = Money::zero();
            $discountTotal = Money::zero();

            foreach ($items as $line) {
                $product = Product::query()->whereKey($line['product_id'])->firstOrFail();
                $qty = Quantity::fromString($line['qty']);
                $unitPrice = isset($line['unit_price'])
                    ? Money::fromMajor((string) $line['unit_price'])
                    : $product->wholesale_price;
                $discount = Money::fromMajor((string) ($line['discount'] ?? 0));
                $lineGross = $unitPrice->multiply((string) $qty)->subtract($discount);
                $tax = $lineGross->percentage((string) $product->tax_rate);

                ProformaInvoiceItem::query()->create([
                    'proforma_invoice_id' => $proforma->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice->minorUnits,
                    'discount' => $discount->minorUnits,
                    'tax' => $tax->minorUnits,
                ]);

                $subtotal = $subtotal->add($unitPrice->multiply((string) $qty));
                $taxTotal = $taxTotal->add($tax);
                $discountTotal = $discountTotal->add($discount);
            }

            $grandTotal = $subtotal->add($taxTotal)->subtract($discountTotal);

            $proforma->update([
                'subtotal' => $subtotal->minorUnits,
                'tax_total' => $taxTotal->minorUnits,
                'discount_total' => $discountTotal->minorUnits,
                'grand_total' => $grandTotal->minorUnits,
            ]);

            return $proforma->load('items');
        });
    }

    public function fromSalesOrder(SalesOrder $salesOrder, string $validUntil): ProformaInvoice
    {
        $items = $salesOrder->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'qty' => (string) $item->qty,
            'unit_price' => $item->unit_price->toMajor(),
            'discount' => $item->discount->toMajor(),
        ])->all();

        return $this->execute($salesOrder->customer_id, $salesOrder->warehouse_id, $items, $validUntil, $salesOrder->id);
    }
}
