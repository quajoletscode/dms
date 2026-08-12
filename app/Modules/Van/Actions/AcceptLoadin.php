<?php

namespace App\Modules\Van\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Van\Domain\LoadinTransitions;
use App\Modules\Van\Models\LoadinRequest;
use App\Modules\Warehouse\Domain\StockMover;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Good stock returns to the warehouse; damaged stock moves to the
 * per-warehouse 'damages' virtual location and is written off (Dr Inventory
 * Loss / Cr Inventory) — LI-02. The write-off only posts when there's
 * actually damaged value (PostingEngine rejects a zero-value line).
 */
final class AcceptLoadin
{
    public function __construct(
        private readonly LoadinTransitions $transitions,
        private readonly StockMover $stockMover,
        private readonly PostingEngine $postingEngine,
    ) {}

    public function execute(LoadinRequest $loadin): LoadinRequest
    {
        Gate::authorize('loadin.approve');

        $this->transitions->assertCanTransition($loadin->status, 'accepted');

        return DB::transaction(function () use ($loadin) {
            $damagedValue = Money::zero();

            foreach ($loadin->items as $item) {
                $totalOut = $item->qty_good->add($item->qty_damaged);

                if ($totalOut->isPositive()) {
                    $this->stockMover->move(
                        locationType: 'van',
                        locationId: $loadin->van_storage_id,
                        productId: $item->product_id,
                        batchId: null,
                        qtyIn: Quantity::zero(),
                        qtyOut: $totalOut,
                        unitCost: $item->unit_cost,
                        docType: 'loadin',
                        docId: $loadin->id,
                    );
                }

                if ($item->qty_good->isPositive()) {
                    $this->stockMover->move(
                        locationType: 'warehouse',
                        locationId: $loadin->warehouse_id,
                        productId: $item->product_id,
                        batchId: null,
                        qtyIn: $item->qty_good,
                        qtyOut: Quantity::zero(),
                        unitCost: $item->unit_cost,
                        docType: 'loadin',
                        docId: $loadin->id,
                    );
                }

                if ($item->qty_damaged->isPositive()) {
                    $this->stockMover->move(
                        locationType: 'damages',
                        locationId: $loadin->warehouse_id,
                        productId: $item->product_id,
                        batchId: null,
                        qtyIn: $item->qty_damaged,
                        qtyOut: Quantity::zero(),
                        unitCost: $item->unit_cost,
                        docType: 'loadin',
                        docId: $loadin->id,
                    );

                    $damagedValue = $damagedValue->add($item->unit_cost->multiply((string) $item->qty_damaged));
                }
            }

            $loadin->update(['status' => 'accepted', 'accepted_by' => Auth::id(), 'accepted_at' => now()]);

            if ($damagedValue->isPositive()) {
                $this->postingEngine->post('stock.write_off', $loadin->load('items'));
            }

            return $loadin->refresh();
        });
    }
}
