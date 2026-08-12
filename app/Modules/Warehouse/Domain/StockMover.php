<?php

namespace App\Modules\Warehouse\Domain;

use App\Modules\Warehouse\Models\StockBalance;
use App\Modules\Warehouse\Models\StockLedgerEntry;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The one place stock movements are written. Writes an immutable ledger
 * entry, then atomically applies the delta to the stock_balances
 * projection via a single parameterised `qty_on_hand = qty_on_hand + ?`
 * statement — correct under concurrent writers without needing
 * lockForUpdate(), and exact (no float detour) since the arithmetic happens
 * in the database, not PHP.
 *
 * Must be called inside the caller's own DB::transaction().
 */
final class StockMover
{
    private const MAX_ATTEMPTS = 3;

    public function move(
        string $locationType,
        int $locationId,
        int $productId,
        ?int $batchId,
        Quantity $qtyIn,
        Quantity $qtyOut,
        Money $unitCost,
        string $docType,
        int $docId,
        ?string $clientUuid = null,
    ): StockLedgerEntry {
        $entry = StockLedgerEntry::query()->create([
            'location_type' => $locationType,
            'location_id' => $locationId,
            'product_id' => $productId,
            'batch_id' => $batchId,
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'unit_cost' => $unitCost,
            'doc_type' => $docType,
            'doc_id' => $docId,
            'user_id' => Auth::id(),
            'client_uuid' => $clientUuid,
            'created_at' => now(),
        ]);

        $this->applyToBalance($locationType, $locationId, $productId, $batchId, $qtyIn->subtract($qtyOut));

        return $entry;
    }

    private function applyToBalance(string $locationType, int $locationId, int $productId, ?int $batchId, Quantity $delta): void
    {
        $balanceBatchId = $batchId ?? 0;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $affected = DB::affectingStatement(
                'update stock_balances set qty_on_hand = qty_on_hand + ?, updated_at = ? '.
                'where location_type = ? and location_id = ? and product_id = ? and batch_id = ?',
                [(string) $delta, now()->toDateTimeString(), $locationType, $locationId, $productId, $balanceBatchId]
            );

            if ($affected > 0) {
                return;
            }

            try {
                StockBalance::query()->create([
                    'location_type' => $locationType,
                    'location_id' => $locationId,
                    'product_id' => $productId,
                    'batch_id' => $balanceBatchId,
                    'qty_on_hand' => $delta,
                    'updated_at' => now(),
                ]);

                return;
            } catch (QueryException $e) {
                if ($attempt === self::MAX_ATTEMPTS || ! $this->isBalanceUniqueViolation($e)) {
                    throw $e;
                }
                // Another writer created the row between our UPDATE miss and
                // this CREATE — retry; the UPDATE will hit it next time.
            }
        }
    }

    private function isBalanceUniqueViolation(QueryException $e): bool
    {
        // MySQL names the violated key in its message; SQLite reports the
        // bare table.column list instead (never the index name, even for an
        // explicitly-named composite unique index) — check for both.
        return str_contains($e->getMessage(), 'stock_balances_location_product_batch_unique')
            || str_contains($e->getMessage(), 'stock_balances.location_type, stock_balances.location_id, stock_balances.product_id, stock_balances.batch_id');
    }
}
