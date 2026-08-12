<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Finance\Models\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Hands over every collection currently with_dsr for this DSR — one journal
 * per collection (not one lump-sum entry), so each hop of the DSR cash chain
 * stays independently traceable (BNK-06).
 */
final class HandoverDsrCash
{
    public function __construct(private readonly PostingEngine $postingEngine) {}

    /**
     * @return list<Collection>
     */
    public function execute(int $dsrUserId): array
    {
        Gate::authorize('collection.handover');

        return DB::transaction(function () use ($dsrUserId) {
            $collections = Collection::query()
                ->where('dsr_user_id', $dsrUserId)
                ->where('status', 'with_dsr')
                ->get();

            $handedOverBy = Auth::id();

            foreach ($collections as $collection) {
                $collection->update([
                    'status' => 'handed_over',
                    'handed_over_by' => $handedOverBy,
                    'handed_over_at' => now(),
                ]);

                $this->postingEngine->post('dsr_cash.handed_over', $collection);
            }

            return array_values($collections->all());
        });
    }
}
