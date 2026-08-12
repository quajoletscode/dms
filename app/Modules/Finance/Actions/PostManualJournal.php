<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Finance\Models\ManualJournal;
use App\Modules\Finance\Models\ManualJournalLine;
use App\Support\DocumentNumberGenerator;
use App\Support\Exceptions\UnbalancedJournalException;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Below config('finance.manual_journal_approval_threshold'): posts
 * immediately. Above it: held pending_approval until a different user calls
 * ApproveManualJournal (JournalApprovalThresholdSegregationTest).
 */
final class PostManualJournal
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly PostingEngine $postingEngine,
    ) {}

    /**
     * @param  list<array{account_id:int, debit?:string, credit?:string, partner_type?:string, partner_id?:int, memo?:string}>  $lines
     */
    public function execute(array $lines, ?string $memo = null): ManualJournal
    {
        Gate::authorize('journal.post');

        $totalDebit = Money::zero();
        $totalCredit = Money::zero();

        foreach ($lines as $line) {
            $totalDebit = $totalDebit->add(Money::fromMajor($line['debit'] ?? '0'));
            $totalCredit = $totalCredit->add(Money::fromMajor($line['credit'] ?? '0'));
        }

        if (! $totalDebit->equals($totalCredit)) {
            throw new UnbalancedJournalException(
                "Manual journal is unbalanced: debits {$totalDebit->toMajor()} != credits {$totalCredit->toMajor()}."
            );
        }

        $threshold = Money::fromMajor((string) config('finance.manual_journal_approval_threshold'));

        return DB::transaction(function () use ($lines, $memo, $totalDebit, $totalCredit, $threshold) {
            $manualJournal = ManualJournal::query()->create([
                'no' => $this->numbers->next('manual_journal', 'company'),
                'memo' => $memo,
                'total_debit' => $totalDebit->minorUnits,
                'total_credit' => $totalCredit->minorUnits,
                'status' => 'pending_approval',
                'requested_by' => Auth::id(),
                'requested_at' => now(),
            ]);

            foreach ($lines as $line) {
                ManualJournalLine::query()->create([
                    'manual_journal_id' => $manualJournal->id,
                    'account_id' => $line['account_id'],
                    'debit' => Money::fromMajor($line['debit'] ?? '0')->minorUnits,
                    'credit' => Money::fromMajor($line['credit'] ?? '0')->minorUnits,
                    'partner_type' => $line['partner_type'] ?? null,
                    'partner_id' => $line['partner_id'] ?? null,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            if (! $totalDebit->greaterThan($threshold)) {
                $journal = $this->postingEngine->post('manual_journal.posted', $manualJournal->load('lines'));
                $manualJournal->update([
                    'status' => 'posted',
                    'journal_entry_id' => $journal->id,
                ]);
            }

            return $manualJournal->refresh();
        });
    }
}
