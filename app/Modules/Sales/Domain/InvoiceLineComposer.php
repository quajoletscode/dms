<?php

namespace App\Modules\Sales\Domain;

use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\MasterData\Models\Product;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoiceItem;
use App\Modules\Warehouse\Domain\FefoStockPicker;
use App\Modules\Warehouse\Domain\StockMover;
use App\Support\Money;
use App\Support\Quantity;
use InvalidArgumentException;

/**
 * Shared by every Action that turns a cart of lines into a posted Invoice —
 * ConvertToInvoice (Sales Order/Proforma), RecordPosSale, and RecordVanSale
 * all need the exact same tax-at-conversion-time computation and line
 * persistence, but differ in how they build the Invoice header itself
 * (credit-check timing, source, linked documents).
 *
 * A line's `line_type` (default `item`) selects one of three shapes: `item`
 * (a stock product — FEFO-picked and stock-moved, the only type today's
 * Actions ever send), `gl_account` (a non-stock charge posted straight to a
 * ledger account, no stock/product involved), or `comment` (descriptive text
 * only, zero amount). All three share the same money-column plumbing so
 * totals and persistence stay uniform.
 *
 * @phpstan-type RawLine array{line_type?:string, product_id?:int, gl_account_id?:int, amount?:string, qty?:string, unit_price?:string, discount?:string, tax_rate?:string, service_date?:string|null, vehicle_no?:string|null, line_description?:string|null}
 * @phpstan-type ComposedLine array{line_type:string, product:Product|null, gl_account:ChartOfAccount|null, qty:Quantity, unit_price:Money, discount:Money, tax_rate:string, tax:Money, service_date:string|null, vehicle_no:string|null, line_description:string|null}
 */
final class InvoiceLineComposer
{
    public function __construct(
        private readonly FefoStockPicker $fefo,
        private readonly StockMover $stockMover,
    ) {}

    /**
     * Computes totals and per-line detail — no side effects, no stock moved.
     *
     * @param  list<RawLine>  $items
     * @return array{lines: list<ComposedLine>, subtotal: Money, taxTotal: Money, discountTotal: Money, grandTotal: Money}
     */
    public function compute(array $items): array
    {
        $subtotal = Money::zero();
        $taxTotal = Money::zero();
        $discountTotal = Money::zero();
        $lines = [];

        foreach ($items as $line) {
            $computed = match ($line['line_type'] ?? 'item') {
                'gl_account' => $this->computeGlAccountLine($line),
                'comment' => $this->computeCommentLine($line),
                default => $this->computeItemLine($line),
            };

            $subtotal = $subtotal->add($computed['unit_price']->multiply((string) $computed['qty']));
            $taxTotal = $taxTotal->add($computed['tax']);
            $discountTotal = $discountTotal->add($computed['discount']);

            $lines[] = $computed;
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
     * Tax is always derived from the product's *current* tax_rate.
     *
     * @param  RawLine  $line
     * @return ComposedLine
     */
    private function computeItemLine(array $line): array
    {
        if (! isset($line['product_id'], $line['qty'], $line['unit_price'], $line['discount'])) {
            throw new InvalidArgumentException('An `item` line requires product_id, qty, unit_price, and discount.');
        }

        $product = Product::query()->whereKey($line['product_id'])->firstOrFail();
        $qty = Quantity::fromString($line['qty']);
        $unitPrice = Money::fromMajor($line['unit_price']);
        $discount = Money::fromMajor($line['discount']);
        $lineGross = $unitPrice->multiply((string) $qty)->subtract($discount);
        $tax = $lineGross->percentage((string) $product->tax_rate);

        return [
            'line_type' => 'item',
            'product' => $product,
            'gl_account' => null,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax_rate' => (string) $product->tax_rate,
            'tax' => $tax,
            'service_date' => $line['service_date'] ?? null,
            'vehicle_no' => $line['vehicle_no'] ?? null,
            'line_description' => $line['line_description'] ?? null,
        ];
    }

    /**
     * @param  RawLine  $line
     * @return ComposedLine
     */
    private function computeGlAccountLine(array $line): array
    {
        if (! isset($line['gl_account_id'], $line['amount'])) {
            throw new InvalidArgumentException('A `gl_account` line requires gl_account_id and amount.');
        }

        $glAccount = ChartOfAccount::query()->whereKey($line['gl_account_id'])->firstOrFail();
        $amount = Money::fromMajor($line['amount']);
        $taxRate = $line['tax_rate'] ?? '0';

        return [
            'line_type' => 'gl_account',
            'product' => null,
            'gl_account' => $glAccount,
            'qty' => Quantity::fromString('1'),
            'unit_price' => $amount,
            'discount' => Money::zero(),
            'tax_rate' => (string) $taxRate,
            'tax' => $amount->percentage((string) $taxRate),
            'service_date' => null,
            'vehicle_no' => null,
            'line_description' => $line['line_description'] ?? null,
        ];
    }

    /**
     * @param  RawLine  $line
     * @return ComposedLine
     */
    private function computeCommentLine(array $line): array
    {
        return [
            'line_type' => 'comment',
            'product' => null,
            'gl_account' => null,
            'qty' => Quantity::zero(),
            'unit_price' => Money::zero(),
            'discount' => Money::zero(),
            'tax_rate' => '0',
            'tax' => Money::zero(),
            'service_date' => null,
            'vehicle_no' => null,
            'line_description' => $line['line_description'] ?? null,
        ];
    }

    /**
     * Persists the resulting InvoiceItem rows + stock-ledger movements.
     * $locationType/$locationId is 'warehouse'/warehouse id for a
     * wholesale/POS sale, or 'van'/van_storage id for a DSR field sale — the
     * stock being sold from, not necessarily the Invoice's own warehouse_id.
     * Only `item` lines touch stock; `gl_account`/`comment` lines never call
     * FefoStockPicker/StockMover.
     *
     * @param  list<ComposedLine>  $lines
     */
    public function persist(Invoice $invoice, string $locationType, int $locationId, array $lines): void
    {
        foreach ($lines as $line) {
            match ($line['line_type']) {
                'gl_account' => $this->persistGlAccountLine($invoice, $line),
                'comment' => $this->persistCommentLine($invoice, $line),
                default => $this->persistItemLine($invoice, $locationType, $locationId, $line),
            };
        }
    }

    /**
     * A line's discount/tax are recorded once, on the first pick — FEFO can
     * split one requested line across several batches, but the line-level
     * charge must not be duplicated per batch.
     *
     * @param  ComposedLine  $line
     */
    private function persistItemLine(Invoice $invoice, string $locationType, int $locationId, array $line): void
    {
        if (! isset($line['product'])) {
            throw new InvalidArgumentException('An `item` line must have a product.');
        }

        $product = $line['product'];
        $picks = $this->fefo->pick($product, $locationType, $locationId, $line['qty']);

        foreach ($picks as $index => $pick) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'line_type' => 'item',
                'product_id' => $product->id,
                'batch_id' => $pick['batch_id'],
                'service_date' => $line['service_date'],
                'vehicle_no' => $line['vehicle_no'],
                'line_description' => $line['line_description'],
                'qty' => $pick['qty'],
                'unit_price' => $line['unit_price']->minorUnits,
                'discount' => $index === 0 ? $line['discount']->minorUnits : 0,
                'tax_rate' => $line['tax_rate'],
                'tax' => $index === 0 ? $line['tax']->minorUnits : 0,
                'unit_cost' => $product->cost_price->minorUnits,
            ]);

            $this->stockMover->move(
                locationType: $locationType,
                locationId: $locationId,
                productId: $product->id,
                batchId: $pick['batch_id'],
                qtyIn: Quantity::zero(),
                qtyOut: $pick['qty'],
                unitCost: $product->cost_price,
                docType: 'invoice',
                docId: $invoice->id,
            );
        }
    }

    /**
     * @param  ComposedLine  $line
     */
    private function persistGlAccountLine(Invoice $invoice, array $line): void
    {
        if (! isset($line['gl_account'])) {
            throw new InvalidArgumentException('A `gl_account` line must have a gl_account.');
        }

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'line_type' => 'gl_account',
            'gl_account_id' => $line['gl_account']->id,
            'line_description' => $line['line_description'],
            'qty' => $line['qty'],
            'unit_price' => $line['unit_price']->minorUnits,
            'discount' => $line['discount']->minorUnits,
            'tax_rate' => $line['tax_rate'],
            'tax' => $line['tax']->minorUnits,
            'unit_cost' => 0,
        ]);
    }

    /**
     * @param  ComposedLine  $line
     */
    private function persistCommentLine(Invoice $invoice, array $line): void
    {
        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'line_type' => 'comment',
            'line_description' => $line['line_description'],
            'qty' => $line['qty'],
            'unit_price' => 0,
            'discount' => 0,
            'tax_rate' => '0',
            'tax' => 0,
            'unit_cost' => 0,
        ]);
    }
}
