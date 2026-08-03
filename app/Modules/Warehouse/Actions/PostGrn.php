<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Warehouse\Domain\PurchaseOrderTransitions;
use App\Modules\Warehouse\Domain\StockMover;
use App\Modules\Warehouse\Models\Grn;
use App\Modules\Warehouse\Models\PurchaseOrder;
use App\Modules\Warehouse\Models\PurchaseOrderItem;
use App\Support\Exceptions\ImmutableRecordException;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * GRN-03: posting increases warehouse stock and posts Dr Inventory / Cr AP,
 * in one transaction. If the GRN came from a PO, the PO's received
 * quantities and status are recomputed under a lock on the PO row so
 * concurrent GRNs against the same PO never lose an update.
 */
final class PostGrn
{
    public function __construct(
        private readonly StockMover $stockMover,
        private readonly PostingEngine $postingEngine,
        private readonly PurchaseOrderTransitions $transitions,
    ) {}

    public function execute(Grn $grn): Grn
    {
        if ($grn->status !== 'draft') {
            throw new ImmutableRecordException("GRN [{$grn->no}] is not in draft status and cannot be posted.");
        }

        return DB::transaction(function () use ($grn) {
            $grn->load('items');

            foreach ($grn->items as $item) {
                $this->stockMover->move(
                    locationType: 'warehouse',
                    locationId: $grn->warehouse_id,
                    productId: $item->product_id,
                    batchId: $item->batch_id,
                    qtyIn: $item->qty_received,
                    qtyOut: Quantity::zero(),
                    unitCost: $item->unit_cost,
                    docType: 'grn',
                    docId: $grn->id,
                    clientUuid: $grn->client_uuid,
                );

                if ($item->po_item_id !== null) {
                    // Parameterised, exact decimal arithmetic in the DB — no
                    // float round-trip (see StockMover for the same pattern).
                    DB::statement(
                        'update purchase_order_items set qty_received = qty_received + ? where id = ?',
                        [(string) $item->qty_received, $item->po_item_id]
                    );
                }
            }

            $grn->update([
                'status' => 'posted',
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            $this->postingEngine->post('grn.posted', $grn);

            if ($grn->po_id !== null) {
                $this->recomputePurchaseOrderStatus($grn->po_id);
            }

            return $grn->refresh();
        });
    }

    private function recomputePurchaseOrderStatus(int $poId): void
    {
        $po = PurchaseOrder::query()->lockForUpdate()->findOrFail($poId);

        if (! in_array($po->status, ['approved', 'partially_received'], true)) {
            return;
        }

        $items = PurchaseOrderItem::query()->where('purchase_order_id', $po->id)->get();

        $fullyReceived = $items->every(
            fn (PurchaseOrderItem $item) => ! $item->qty_received->lessThan($item->qty_ordered)
        );

        $newStatus = $fullyReceived ? 'received' : 'partially_received';

        if ($newStatus !== $po->status) {
            $this->transitions->assertCanTransition($po->status, $newStatus);
            $po->update(['status' => $newStatus]);
        }
    }
}
