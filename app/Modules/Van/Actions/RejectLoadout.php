<?php

namespace App\Modules\Van\Actions;

use App\Modules\Van\Domain\LoadoutTransitions;
use App\Modules\Van\Models\LoadoutRequest;
use App\Support\Exceptions\SegregationOfDutiesException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Mirrors ApproveLoadout's segregation of duties: the requester of a loadout
 * cannot also reject it. Only reachable from requested/approved — once
 * physically loaded, LoadoutTransitions no longer allows 'rejected'.
 */
final class RejectLoadout
{
    public function __construct(private readonly LoadoutTransitions $transitions) {}

    public function execute(LoadoutRequest $loadout, ?string $reason = null): LoadoutRequest
    {
        Gate::authorize('loadout.approve');

        $rejecterId = Auth::id();

        if ($rejecterId !== null && $loadout->requested_by === $rejecterId) {
            throw new SegregationOfDutiesException("The requester of loadout [{$loadout->no}] cannot also reject it.");
        }

        $this->transitions->assertCanTransition($loadout->status, 'rejected');

        $loadout->update([
            'status' => 'rejected',
            'rejected_by' => $rejecterId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $loadout->refresh();
    }
}
