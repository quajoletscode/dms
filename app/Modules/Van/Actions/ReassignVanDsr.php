<?php

namespace App\Modules\Van\Actions;

use App\Models\User;
use App\Modules\Van\Domain\Exceptions\VanHandoverRequiredException;
use App\Modules\Van\Models\VanDsrHistory;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Models\StockBalance;
use Illuminate\Support\Facades\DB;

/**
 * A van can only have one DSR at a time (9.2 assumption); reassigning it
 * while stock is on board requires a handover count (VAN-01, LI-family intent).
 */
final class ReassignVanDsr
{
    public function execute(VanStorage $van, User $newDsr, ?string $handoverNote = null): VanStorage
    {
        $hasStockOnBoard = StockBalance::query()
            ->where('location_type', 'van')
            ->where('location_id', $van->id)
            ->where('qty_on_hand', '>', 0)
            ->exists();

        if ($hasStockOnBoard && $handoverNote === null) {
            throw new VanHandoverRequiredException(
                "Van [{$van->code}] has stock on board; a handover count is required before reassigning its DSR."
            );
        }

        return DB::transaction(function () use ($van, $newDsr, $handoverNote) {
            if ($van->dsr_user_id !== null) {
                VanDsrHistory::query()
                    ->where('van_storage_id', $van->id)
                    ->where('dsr_user_id', $van->dsr_user_id)
                    ->whereNull('unassigned_at')
                    ->latest('assigned_at')
                    ->first()
                    ?->update(['unassigned_at' => now(), 'handover_note' => $handoverNote]);
            }

            $van->update(['dsr_user_id' => $newDsr->id]);

            VanDsrHistory::query()->create([
                'van_storage_id' => $van->id,
                'dsr_user_id' => $newDsr->id,
                'assigned_at' => now(),
            ]);

            return $van->refresh();
        });
    }
}
