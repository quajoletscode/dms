<?php

namespace App\Modules\Van\Actions;

use App\Modules\MasterData\Models\Product;
use App\Modules\Van\Domain\Exceptions\BatchTrackedLoadoutUnsupportedException;
use App\Modules\Van\Models\LoadoutRequest;
use App\Modules\Van\Models\LoadoutRequestItem;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Domain\StockAvailabilityCheck;
use App\Support\DocumentNumberGenerator;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Loadout/loadin in this phase only support non-batch/expiry-tracked
 * products — FEFO-aware, batch-split van loadouts are a deferred
 * enhancement (decisions.md ADR-37); a tracked product is rejected outright
 * rather than silently moving the wrong batch's stock.
 */
final class RequestLoadout
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly StockAvailabilityCheck $stockAvailability,
    ) {}

    /**
     * @param  list<array{product_id:int, qty:string}>  $items
     */
    public function execute(int $vanStorageId, array $items): LoadoutRequest
    {
        Gate::authorize('loadout.create');

        return DB::transaction(function () use ($vanStorageId, $items) {
            $van = VanStorage::query()->whereKey($vanStorageId)->firstOrFail();

            $loadout = LoadoutRequest::query()->create([
                'no' => $this->numbers->next('loadout', 'warehouse', $van->warehouse_id),
                'van_storage_id' => $van->id,
                'warehouse_id' => $van->warehouse_id,
                'status' => 'requested',
                'requested_by' => Auth::id(),
                'requested_at' => now(),
            ]);

            foreach ($items as $line) {
                $product = Product::query()->whereKey($line['product_id'])->firstOrFail();
                $this->assertNotBatchTracked($product);

                $qty = Quantity::fromString($line['qty']);
                $this->stockAvailability->assertAvailable('warehouse', $van->warehouse_id, $product->id, $qty);

                LoadoutRequestItem::query()->create([
                    'loadout_request_id' => $loadout->id,
                    'product_id' => $product->id,
                    'qty_requested' => $qty,
                    'unit_cost' => $product->cost_price->minorUnits,
                ]);
            }

            return $loadout->load('items');
        });
    }

    private function assertNotBatchTracked(Product $product): void
    {
        if ($product->track_expiry) {
            throw new BatchTrackedLoadoutUnsupportedException(
                "Product #{$product->id} is batch/expiry-tracked; van loadouts do not yet support batch-tracked products."
            );
        }
    }
}
