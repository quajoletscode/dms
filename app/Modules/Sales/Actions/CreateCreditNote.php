<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Sales\Domain\Exceptions\ExcessiveCreditNoteException;
use App\Modules\Sales\Models\CreditNote;
use App\Modules\Sales\Models\CreditNoteItem;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoiceItem;
use App\Modules\Warehouse\Domain\StockMover;
use App\Support\DocumentNumberGenerator;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Reverses part of a previously-issued invoice: cannot credit more than was
 * originally sold on that invoice line, across all credit notes raised
 * against it.
 */
final class CreateCreditNote
{
    public function __construct(
        private readonly StockMover $stockMover,
        private readonly PostingEngine $postingEngine,
        private readonly DocumentNumberGenerator $numbers,
    ) {}

    /**
     * @param  array<int, array{invoice_item_id: int, qty: string|int|float}>  $items
     */
    public function execute(Invoice $invoice, array $items, string $reasonCode): CreditNote
    {
        return DB::transaction(function () use ($invoice, $items, $reasonCode) {
            $creditNote = CreditNote::query()->create([
                'no' => $this->numbers->next('credit_note', 'warehouse', $invoice->warehouse_id),
                'invoice_id' => $invoice->id,
                'reason_code' => $reasonCode,
                'status' => 'posted',
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            foreach ($items as $line) {
                $invoiceItem = InvoiceItem::query()->lockForUpdate()->whereKey($line['invoice_item_id'])->firstOrFail();
                $qty = Quantity::fromString($line['qty']);

                $alreadyCredited = CreditNoteItem::query()
                    ->where('invoice_item_id', $invoiceItem->id)
                    ->get()
                    ->reduce(fn (Quantity $carry, CreditNoteItem $i) => $carry->add($i->qty), Quantity::zero());

                if ($qty->add($alreadyCredited)->greaterThan($invoiceItem->qty)) {
                    $remaining = $invoiceItem->qty->subtract($alreadyCredited);

                    throw new ExcessiveCreditNoteException(
                        "Cannot credit {$qty} of invoice item #{$invoiceItem->id}: only {$remaining} remains creditable."
                    );
                }

                CreditNoteItem::query()->create([
                    'credit_note_id' => $creditNote->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'batch_id' => $invoiceItem->batch_id,
                    'qty' => $qty,
                    'unit_price' => $invoiceItem->unit_price,
                    'unit_cost' => $invoiceItem->unit_cost,
                ]);

                $this->stockMover->move(
                    locationType: 'warehouse',
                    locationId: $invoice->warehouse_id,
                    productId: $invoiceItem->product_id,
                    batchId: $invoiceItem->batch_id,
                    qtyIn: $qty,
                    qtyOut: Quantity::zero(),
                    unitCost: $invoiceItem->unit_cost,
                    docType: 'credit_note',
                    docId: $creditNote->id,
                );
            }

            $this->postingEngine->post('credit_note.posted', $creditNote->load('items'));

            return $creditNote->refresh();
        });
    }
}
