<?php

namespace App\Modules\Van\Actions;

use App\Modules\MasterData\Models\Product;
use App\Modules\Van\Domain\Exceptions\BatchTrackedLoadoutUnsupportedException;
use App\Modules\Van\Models\LoadinRequest;
use App\Modules\Van\Models\LoadinRequestItem;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Domain\StockAvailabilityCheck;
use App\Support\DocumentNumberGenerator;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RequestLoadin
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly StockAvailabilityCheck $stockAvailability,
    ) {}

    /**
     * @param  list<array{product_id:int, qty_good:string, qty_damaged:string}>  $items
     */
    public function execute(int $vanStorageId, array $items): LoadinRequest
    {
        Gate::authorize('loadin.create');

        return DB::transaction(function () use ($vanStorageId, $items) {
            $van = VanStorage::query()->whereKey($vanStorageId)->firstOrFail();

            $loadin = LoadinRequest::query()->create([
                'no' => $this->numbers->next('loadin', 'warehouse', $van->warehouse_id),
                'van_storage_id' => $van->id,
                'warehouse_id' => $van->warehouse_id,
                'status' => 'requested',
                'requested_by' => Auth::id(),
                'requested_at' => now(),
            ]);

            foreach ($items as $line) {
                $product = Product::query()->whereKey($line['product_id'])->firstOrFail();

                if ($product->track_expiry) {
                    throw new BatchTrackedLoadoutUnsupportedException(
                        "Product #{$product->id} is batch/expiry-tracked; van loadins do not yet support batch-tracked products."
                    );
                }

                $qtyGood = Quantity::fromString($line['qty_good']);
                $qtyDamaged = Quantity::fromString($line['qty_damaged']);
                $this->stockAvailability->assertAvailable('van', $van->id, $product->id, $qtyGood->add($qtyDamaged));

                LoadinRequestItem::query()->create([
                    'loadin_request_id' => $loadin->id,
                    'product_id' => $product->id,
                    'qty_good' => $qtyGood,
                    'qty_damaged' => $qtyDamaged,
                    'unit_cost' => $product->cost_price->minorUnits,
                ]);
            }

            return $loadin->load('items');
        });
    }
}
