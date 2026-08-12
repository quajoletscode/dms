<?php

namespace App\Modules\Finance\Domain\PostingRules;

use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Models\ManualJournal;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A manual journal's lines are already fully specified by the requester
 * (not derived from a business document) — this rule just converts the
 * already-persisted, already-balance-checked ManualJournalLine rows into
 * JournalLineData 1:1, so PostManualJournal/ApproveManualJournal still go
 * through PostingEngine's single entry point (fiscal-period check, atomic
 * persistence) like every other posting.
 */
final class ManualJournalPostingRule implements PostingRule
{
    public function resolveLines(Model $document): array
    {
        if (! $document instanceof ManualJournal) {
            throw new InvalidArgumentException('ManualJournalPostingRule can only post ManualJournal documents.');
        }

        return array_values($document->lines->map(fn ($line) => new JournalLineData(
            accountId: $line->account_id,
            debit: $line->debit,
            credit: $line->credit,
            partnerType: $line->partner_type,
            partnerId: $line->partner_id,
            memo: $line->memo,
        ))->all());
    }
}
