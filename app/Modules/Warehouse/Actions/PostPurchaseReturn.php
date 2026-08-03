<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Warehouse\Domain\Exceptions\ExcessiveReturnException;
use App\Modules\Warehouse\Domain\StockMover;
use App\Modules\Warehouse\Models\Grn;
use App\Modules\Warehouse\Models\GrnItem;
use App\Modules\Warehouse\Models\PurchaseReturn;
use App\Modules\Warehouse\Models\PurchaseReturnItem;
use App\Support\DocumentNumberGenerator;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Reverses part of a previously-posted GRN: cannot return more than was
 * received on that GRN line, across all returns raised against it.
 */
final class PostPurchaseReturn
{
    public function __construct(
        private readonly StockMover $stockMover,
        private readonly PostingEngine $postingEngine,
        private readonly DocumentNumberGenerator $numbers,
    ) {}

    /**
     * @param  array<int, array{grn_item_id: int, qty_returned: string|int|float}>  $items
     */
    public function execute(Grn $grn, array $items, string $reasonCode): PurchaseReturn
    {
        return DB::transaction(function () use ($grn, $items, $reasonCode) {
            $return = PurchaseReturn::query()->create([
                'no' => $this->numbers->next('purchase_return', 'warehouse', $grn->warehouse_id),
                'grn_id' => $grn->id,
                'warehouse_id' => $grn->warehouse_id,
                'reason_code' => $reasonCode,
                'status' => 'posted',
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            foreach ($items as $line) {
                $grnItem = GrnItem::query()->lockForUpdate()->findOrFail($line['grn_item_id']);
                $qtyReturned = Quantity::fromString($line['qty_returned']);

                $alreadyReturned = PurchaseReturnItem::query()
                    ->where('grn_item_id', $grnItem->id)
                    ->get()
                    ->reduce(
                        fn (Quantity $carry, PurchaseReturnItem $i) => $carry->add($i->qty_returned),
                        Quantity::zero(),
                    );

                if ($qtyReturned->add($alreadyReturned)->greaterThan($grnItem->qty_received)) {
                    $remaining = $grnItem->qty_received->subtract($alreadyReturned);

                    throw new ExcessiveReturnException(
                        "Cannot return {$qtyReturned} of GRN item #{$grnItem->id}: only {$remaining} remains returnable."
                    );
                }

                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $return->id,
                    'grn_item_id' => $grnItem->id,
                    'product_id' => $grnItem->product_id,
                    'batch_id' => $grnItem->batch_id,
                    'qty_returned' => $qtyReturned,
                    'unit_cost' => $grnItem->unit_cost,
                ]);

                $this->stockMover->move(
                    locationType: 'warehouse',
                    locationId: $grn->warehouse_id,
                    productId: $grnItem->product_id,
                    batchId: $grnItem->batch_id,
                    qtyIn: Quantity::zero(),
                    qtyOut: $qtyReturned,
                    unitCost: $grnItem->unit_cost,
                    docType: 'purchase_return',
                    docId: $return->id,
                );
            }

            $this->postingEngine->post('purchase_return.posted', $return->load('items'));

            return $return->refresh();
        });
    }
}
