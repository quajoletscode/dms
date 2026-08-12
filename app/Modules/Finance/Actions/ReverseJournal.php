<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Domain\JournalEntryTransitions;
use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Finance\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * FIN-03: posted journals are immutable; corrections are reversal entries
 * only. Reuses PostingEngine (not a hand-rolled JournalEntry::create) so
 * reversals get the same fiscal-period-closed check and balance assertion
 * as every other posting.
 */
final class ReverseJournal
{
    public function __construct(
        private readonly JournalEntryTransitions $transitions,
        private readonly PostingEngine $postingEngine,
    ) {}

    public function execute(JournalEntry $journal, ?string $memo = null): JournalEntry
    {
        Gate::authorize('journal.post');

        $this->transitions->assertCanTransition($journal->status, 'reversed');

        return DB::transaction(function () use ($journal, $memo) {
            $reversal = $this->postingEngine->post(
                'journal.reversed',
                $journal,
                $memo ?? "Reversal of {$journal->no}",
            );

            $reversal->update(['reversal_of_id' => $journal->id]);
            $journal->update(['status' => 'reversed']);

            return $reversal->refresh();
        });
    }
}
