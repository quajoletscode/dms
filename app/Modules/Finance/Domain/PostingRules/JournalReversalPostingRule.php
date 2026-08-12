<?php

namespace App\Modules\Finance\Domain\PostingRules;

use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Mirrors every line of the original journal entry (debit <-> credit
 * swapped). Reversing a reversal therefore reproduces the original's exact
 * lines — ReversalOfReversalTest's core property.
 */
final class JournalReversalPostingRule implements PostingRule
{
    public function resolveLines(Model $document): array
    {
        if (! $document instanceof JournalEntry) {
            throw new InvalidArgumentException('JournalReversalPostingRule can only post JournalEntry documents.');
        }

        return array_values($document->lines->map(fn ($line) => new JournalLineData(
            accountId: $line->account_id,
            debit: $line->credit,
            credit: $line->debit,
            partnerType: $line->partner_type,
            partnerId: $line->partner_id,
        ))->all());
    }
}
