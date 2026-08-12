<?php

namespace App\Modules\Warehouse\Domain;

use App\Modules\Warehouse\Domain\Exceptions\InsufficientStockException;
use App\Modules\Warehouse\Models\StockBalance;
use App\Support\Quantity;

/**
 * Checks a location's current non-batch-tracked stock balance (batch_id=0)
 * without picking/moving anything — used wherever a business rule needs to
 * validate "is there enough stock" ahead of the actual movement (e.g. a van
 * loadout request/approval), as distinct from FefoStockPicker, which both
 * validates and picks specific batches at the moment stock actually moves.
 */
final class StockAvailabilityCheck
{
    public function assertAvailable(string $locationType, int $locationId, int $productId, Quantity $qty): void
    {
        $balance = StockBalance::query()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('batch_id', 0)
            ->first();

        $available = $balance === null ? Quantity::zero() : $balance->qty_on_hand;

        if ($qty->greaterThan($available)) {
            throw new InsufficientStockException(
                "Not enough {$locationType} stock of product #{$productId}: requested {$qty}, {$available} available."
            );
        }
    }
}
