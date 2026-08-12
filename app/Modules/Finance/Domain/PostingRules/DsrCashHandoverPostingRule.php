<?php

namespace App\Modules\Finance\Domain\PostingRules;

use App\Models\User;
use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Models\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * BNK-06: the DSR hands physical cash over to the warehouse — it moves from
 * their personal custody into the business's own cash: Dr Cash on Hand /
 * Cr Cash in DSR Hand (tagged to the DSR, mirroring the collection's tag so
 * the DSR's outstanding cash-in-hand balance nets to zero once handed over).
 * Posted once per Collection (HandoverDsrCash may hand over several
 * collections in one call; each gets its own journal for per-collection
 * traceability rather than one lump-sum entry).
 */
final class DsrCashHandoverPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof Collection) {
            throw new InvalidArgumentException('DsrCashHandoverPostingRule can only post Collection documents.');
        }

        return [
            JournalLineData::debit($this->accounts->cashOnHand()->id, $document->amount),
            JournalLineData::credit($this->accounts->cashInDsrHand()->id, $document->amount, User::class, $document->dsr_user_id),
        ];
    }
}
