<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\ManualJournal;
use App\Support\Exceptions\IllegalTransitionException;
use App\Support\Exceptions\SegregationOfDutiesException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Mirrors ApproveManualJournal's segregation of duties: the requester of a
 * manual journal cannot also reject it. Unlike approval, no journal is
 * posted — the journal simply never enters the ledger.
 */
final class RejectManualJournal
{
    public function execute(ManualJournal $manualJournal, ?string $reason = null): ManualJournal
    {
        Gate::authorize('journal.post');

        if ($manualJournal->status !== 'pending_approval') {
            throw new IllegalTransitionException("Cannot reject manual journal [{$manualJournal->no}] from status [{$manualJournal->status}].");
        }

        $rejecterId = Auth::id();

        if ($rejecterId !== null && $manualJournal->requested_by === $rejecterId) {
            throw new SegregationOfDutiesException("The requester of manual journal [{$manualJournal->no}] cannot also reject it.");
        }

        $manualJournal->update([
            'status' => 'rejected',
            'rejected_by' => $rejecterId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $manualJournal->refresh();
    }
}
