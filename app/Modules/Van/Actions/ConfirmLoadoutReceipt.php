<?php

namespace App\Modules\Van\Actions;

use App\Modules\Van\Domain\LoadoutTransitions;
use App\Modules\Van\Models\LoadoutRequest;
use App\Modules\Warehouse\Domain\StockMover;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The only point at which loadout stock actually moves (LO-03): warehouse
 * OUT of the loaded qty (what physically left), van IN of the received qty
 * (what physically arrived). Any shortfall (loaded - received) is recorded
 * on the item as a discrepancy — LoadoutRequestItem::discrepancy() — never
 * silently absorbed into either location's balance.
 */
final class ConfirmLoadoutReceipt
{
    public function __construct(
        private readonly LoadoutTransitions $transitions,
        private readonly StockMover $stockMover,
    ) {}

    /**
     * @param  array<int, string>  $receivedQtyByItemId  loadout_request_item_id => qty; omitted items default to their loaded qty (no discrepancy)
     */
    public function execute(LoadoutRequest $loadout, array $receivedQtyByItemId = []): LoadoutRequest
    {
        Gate::authorize('loadout.create');

        $this->transitions->assertCanTransition($loadout->status, 'received');

        return DB::transaction(function () use ($loadout, $receivedQtyByItemId) {
            foreach ($loadout->items as $item) {
                $loadedQty = $item->qty_loaded ?? Quantity::zero();
                $receivedQty = array_key_exists($item->id, $receivedQtyByItemId)
                    ? Quantity::fromString($receivedQtyByItemId[$item->id])
                    : $loadedQty;

                $item->update(['qty_received' => $receivedQty]);

                if ($loadedQty->isPositive()) {
                    $this->stockMover->move(
                        locationType: 'warehouse',
                        locationId: $loadout->warehouse_id,
                        productId: $item->product_id,
                        batchId: null,
                        qtyIn: Quantity::zero(),
                        qtyOut: $loadedQty,
                        unitCost: $item->unit_cost,
                        docType: 'loadout',
                        docId: $loadout->id,
                    );
                }

                if ($receivedQty->isPositive()) {
                    $this->stockMover->move(
                        locationType: 'van',
                        locationId: $loadout->van_storage_id,
                        productId: $item->product_id,
                        batchId: null,
                        qtyIn: $receivedQty,
                        qtyOut: Quantity::zero(),
                        unitCost: $item->unit_cost,
                        docType: 'loadout',
                        docId: $loadout->id,
                    );
                }
            }

            $loadout->update(['status' => 'received', 'received_by' => Auth::id(), 'received_at' => now()]);

            return $loadout->refresh();
        });
    }
}
