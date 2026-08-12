<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Finance\Models\ManualJournal;
use App\Support\Exceptions\IllegalTransitionException;
use App\Support\Exceptions\SegregationOfDutiesException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ApproveManualJournal
{
    public function __construct(private readonly PostingEngine $postingEngine) {}

    public function execute(ManualJournal $manualJournal): ManualJournal
    {
        Gate::authorize('journal.post');

        if ($manualJournal->status !== 'pending_approval') {
            throw new IllegalTransitionException("Cannot approve manual journal [{$manualJournal->no}] from status [{$manualJournal->status}].");
        }

        $approverId = Auth::id();

        if ($approverId !== null && $manualJournal->requested_by === $approverId) {
            throw new SegregationOfDutiesException("The requester of manual journal [{$manualJournal->no}] cannot also approve it.");
        }

        return DB::transaction(function () use ($manualJournal, $approverId) {
            $journal = $this->postingEngine->post('manual_journal.posted', $manualJournal->load('lines'));

            $manualJournal->update([
                'status' => 'posted',
                'journal_entry_id' => $journal->id,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            return $manualJournal->refresh();
        });
    }
}
