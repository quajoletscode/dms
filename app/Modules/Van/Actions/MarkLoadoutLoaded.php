<?php

namespace App\Modules\Van\Actions;

use App\Modules\Van\Domain\LoadoutTransitions;
use App\Modules\Van\Models\LoadoutRequest;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * A status-only checkpoint (LO-03) — no stock ledger entry here. Captures
 * qty_loaded per item, defaulting to the approved qty but allowing a lower
 * actual pick if the physical pick fell short.
 */
final class MarkLoadoutLoaded
{
    public function __construct(private readonly LoadoutTransitions $transitions) {}

    /**
     * @param  array<int, string>  $loadedQtyByItemId  loadout_request_item_id => qty
     */
    public function execute(LoadoutRequest $loadout, array $loadedQtyByItemId = []): LoadoutRequest
    {
        Gate::authorize('loadout.approve');

        $this->transitions->assertCanTransition($loadout->status, 'loaded');

        return DB::transaction(function () use ($loadout, $loadedQtyByItemId) {
            foreach ($loadout->items as $item) {
                $qty = array_key_exists($item->id, $loadedQtyByItemId)
                    ? Quantity::fromString($loadedQtyByItemId[$item->id])
                    : ($item->qty_approved ?? Quantity::zero());

                $item->update(['qty_loaded' => $qty]);
            }

            $loadout->update(['status' => 'loaded', 'loaded_by' => Auth::id(), 'loaded_at' => now()]);

            return $loadout->refresh();
        });
    }
}
