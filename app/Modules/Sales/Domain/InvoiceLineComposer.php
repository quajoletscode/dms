<?php

namespace App\Modules\Sales\Domain;

use App\Modules\MasterData\Models\Product;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoiceItem;
use App\Modules\Warehouse\Domain\FefoStockPicker;
use App\Modules\Warehouse\Domain\StockMover;
use App\Support\Money;
use App\Support\Quantity;

/**
 * Shared by every Action that turns a cart of {product_id, qty, unit_price,
 * discount} lines into a posted Invoice — ConvertToInvoice (Sales
 * Order/Proforma) and RecordPosSale (Phase 4) both need the exact same
 * tax-at-conversion-time computation and FEFO-picked stock/line persistence,
 * but differ in how they build the Invoice header itself (credit-check
 * timing, source, linked documents).
 */
final class InvoiceLineComposer
{
    public function __construct(
        private readonly FefoStockPicker $fefo,
        private readonly StockMover $stockMover,
    ) {}

    /**
     * Computes totals and per-line detail — no side effects, no stock moved.
     * Tax is always derived from the product's *current* tax_rate.
     *
     * @param  list<array{product_id:int, qty:string, unit_price:string, discount:string}>  $items
     * @return array{lines: list<array{product: Product, qty: Quantity, unit_price: Money, discount: Money, tax_rate: string, tax: Money}>, subtotal: Money, taxTotal: Money, discountTotal: Money, grandTotal: Money}
     */
    public function compute(array $items): array
    {
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

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'taxTotal' => $taxTotal,
            'discountTotal' => $discountTotal,
            'grandTotal' => $subtotal->add($taxTotal)->subtract($discountTotal),
        ];
    }

    /**
     * FEFO-picks stock for each computed line and persists the resulting
     * InvoiceItem rows + stock-ledger movements. A line's discount/tax are
     * recorded once, on the first pick — FEFO can split one requested line
     * across several batches, but the line-level charge must not be
     * duplicated per batch. $locationType/$locationId is 'warehouse'/warehouse
     * id for a wholesale/POS sale, or 'van'/van_storage id for a DSR field
     * sale (RecordVanSale, Phase 5) — the stock being sold from, not
     * necessarily the Invoice's own warehouse_id.
     *
     * @param  list<array{product: Product, qty: Quantity, unit_price: Money, discount: Money, tax_rate: string, tax: Money}>  $lines
     */
    public function persist(Invoice $invoice, string $locationType, int $locationId, array $lines): void
    {
        foreach ($lines as $line) {
            $picks = $this->fefo->pick($line['product'], $locationType, $locationId, $line['qty']);

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
                    locationType: $locationType,
                    locationId: $locationId,
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
    }
}
