<?php

namespace App\Modules\Van\Actions;

use App\Modules\Van\Domain\LoadoutTransitions;
use App\Modules\Van\Models\LoadoutRequest;
use App\Modules\Warehouse\Domain\StockAvailabilityCheck;
use App\Support\Exceptions\SegregationOfDutiesException;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The requester of a loadout cannot also approve it. Approved quantities are
 * editable per line — an omitted item keeps its requested quantity.
 */
final class ApproveLoadout
{
    public function __construct(
        private readonly LoadoutTransitions $transitions,
        private readonly StockAvailabilityCheck $stockAvailability,
    ) {}

    /**
     * @param  array<int, string>  $approvedQtyByItemId  loadout_request_item_id => qty
     */
    public function execute(LoadoutRequest $loadout, array $approvedQtyByItemId = []): LoadoutRequest
    {
        Gate::authorize('loadout.approve');

        $approverId = Auth::id();

        if ($approverId !== null && $loadout->requested_by === $approverId) {
            throw new SegregationOfDutiesException("The requester of loadout [{$loadout->no}] cannot also approve it.");
        }

        $this->transitions->assertCanTransition($loadout->status, 'approved');

        return DB::transaction(function () use ($loadout, $approvedQtyByItemId, $approverId) {
            foreach ($loadout->items as $item) {
                $qty = array_key_exists($item->id, $approvedQtyByItemId)
                    ? Quantity::fromString($approvedQtyByItemId[$item->id])
                    : $item->qty_requested;

                $this->stockAvailability->assertAvailable('warehouse', $loadout->warehouse_id, $item->product_id, $qty);

                $item->update(['qty_approved' => $qty]);
            }

            $loadout->update(['status' => 'approved', 'approved_by' => $approverId, 'approved_at' => now()]);

            return $loadout->refresh();
        });
    }
}
