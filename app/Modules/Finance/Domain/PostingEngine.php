<?php

namespace App\Modules\Finance\Domain;

use App\Modules\Finance\Models\FiscalPeriod;
use App\Modules\Finance\Models\JournalEntry;
use App\Modules\Finance\Models\JournalLine;
use App\Support\DocumentNumberGenerator;
use App\Support\Exceptions\PostingIntoClosedPeriodException;
use App\Support\Exceptions\UnbalancedJournalException;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The single entry point for turning a business event into a balanced,
 * immutable journal entry. Every posting document gets exactly one journal,
 * created in the same DB transaction as its lines.
 */
final class PostingEngine
{
    public function __construct(
        private readonly PostingRuleRegistry $rules,
        private readonly DocumentNumberGenerator $numbers,
    ) {}

    public function post(string $eventType, Model $document, ?string $memo = null, ?string $date = null): JournalEntry
    {
        $rule = $this->rules->for($eventType);
        $lines = $rule->resolveLines($document);

        $this->assertBalanced($lines);

        $postingDate = $date ?? now()->toDateString();
        $fiscalPeriod = $this->resolveOpenFiscalPeriod($postingDate);

        return DB::transaction(function () use ($document, $lines, $memo, $postingDate, $fiscalPeriod) {
            $journal = JournalEntry::query()->create([
                'no' => $this->numbers->next('journal', 'company'),
                'date' => $postingDate,
                'memo' => $memo,
                'postable_type' => $document->getMorphClass(),
                'postable_id' => $document->getKey(),
                'posted_by' => Auth::id(),
                'status' => 'posted',
                'fiscal_period_id' => $fiscalPeriod?->id,
            ]);

            foreach ($lines as $line) {
                JournalLine::query()->create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $line->accountId,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'partner_type' => $line->partnerType,
                    'partner_id' => $line->partnerId,
                    'memo' => $line->memo,
                    'created_at' => now(),
                ]);
            }

            return $journal;
        });
    }

    /**
     * FIN-05: posting into a closed fiscal period is rejected. No fiscal
     * period covering the date at all (the common case before Phase 6 seeds
     * any) means unrestricted — periods are opt-in, not required.
     */
    private function resolveOpenFiscalPeriod(string $date): ?FiscalPeriod
    {
        $period = FiscalPeriod::query()
            ->where('starts_on', '<=', $date)
            ->where('ends_on', '>=', $date)
            ->first();

        if ($period !== null && ! $period->isOpen()) {
            throw new PostingIntoClosedPeriodException(
                "Cannot post on {$date}: fiscal period #{$period->id} ({$period->starts_on->toDateString()} to {$period->ends_on->toDateString()}) is closed."
            );
        }

        return $period;
    }

    /**
     * @param  list<JournalLineData>  $lines
     */
    private function assertBalanced(array $lines): void
    {
        if ($lines === []) {
            throw new UnbalancedJournalException('A posting rule must resolve at least one journal line.');
        }

        $totalDebit = Money::zero();
        $totalCredit = Money::zero();

        foreach ($lines as $line) {
            $totalDebit = $totalDebit->add($line->debit);
            $totalCredit = $totalCredit->add($line->credit);
        }

        if (! $totalDebit->equals($totalCredit)) {
            throw new UnbalancedJournalException(
                "Journal is unbalanced: debits {$totalDebit->toMajor()} != credits {$totalCredit->toMajor()}."
            );
        }
    }
}
