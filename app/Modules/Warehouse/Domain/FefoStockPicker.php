<?php

namespace App\Modules\Warehouse\Domain;

use App\Modules\MasterData\Models\Batch;
use App\Modules\MasterData\Models\Product;
use App\Modules\Warehouse\Domain\Exceptions\InsufficientStockException;
use App\Modules\Warehouse\Models\StockBalance;
use App\Support\Quantity;

/**
 * WH-04: First-Expiry-First-Out issuing for batch/expiry-tracked products.
 * Non-tracked products just check the single (batch_id = 0) balance.
 */
final class FefoStockPicker
{
    /**
     * @return list<array{batch_id: int|null, qty: Quantity}>
     */
    public function pick(Product $product, string $locationType, int $locationId, Quantity $qty): array
    {
        if (! $product->track_expiry) {
            $available = $this->balanceFor($locationType, $locationId, $product->id, 0);

            if ($qty->greaterThan($available)) {
                throw new InsufficientStockException(
                    "Not enough stock of product #{$product->id}: requested {$qty}, {$available} available."
                );
            }

            return [['batch_id' => null, 'qty' => $qty]];
        }

        $candidates = StockBalance::query()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('product_id', $product->id)
            ->where('batch_id', '!=', 0)
            ->where('qty_on_hand', '>', 0)
            ->get()
            ->map(fn (StockBalance $balance) => [
                'balance' => $balance,
                'batch' => Batch::query()->find($balance->batch_id),
            ])
            ->filter(fn (array $row) => $row['batch'] !== null && ! $row['batch']->isExpired())
            ->sortBy(fn (array $row) => $row['batch']->expiry_date);

        $remaining = $qty;
        $picks = [];

        foreach ($candidates as $row) {
            if ($remaining->isZero()) {
                break;
            }

            $availableInBatch = $row['balance']->qty_on_hand;
            $take = $remaining->lessThan($availableInBatch) ? $remaining : $availableInBatch;

            $picks[] = ['batch_id' => $row['batch']->id, 'qty' => $take];
            $remaining = $remaining->subtract($take);
        }

        if (! $remaining->isZero()) {
            throw new InsufficientStockException(
                "Not enough unexpired stock of product #{$product->id} to fulfil {$qty} units ({$remaining} short)."
            );
        }

        return $picks;
    }

    private function balanceFor(string $locationType, int $locationId, int $productId, int $batchId): Quantity
    {
        $balance = StockBalance::query()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('batch_id', $batchId)
            ->first();

        if ($balance === null) {
            return Quantity::zero();
        }

        return $balance->qty_on_hand;
    }
}
