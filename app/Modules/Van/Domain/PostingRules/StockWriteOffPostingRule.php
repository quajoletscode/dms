<?php

namespace App\Modules\Van\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Van\Models\LoadinRequest;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * LI-02: the damaged portion of an accepted loadin is written off — Dr
 * Inventory Loss / Cr Inventory for the damaged quantity's cost value,
 * summed across every item on the loadin. AcceptLoadin only calls
 * PostingEngine::post() when this total is positive (PostingEngine rejects a
 * zero-value line — see Phase 0's edge cases).
 */
final class StockWriteOffPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof LoadinRequest) {
            throw new InvalidArgumentException('StockWriteOffPostingRule can only post LoadinRequest documents.');
        }

        $damagedValue = $document->items->reduce(
            fn (Money $carry, $item) => $carry->add($item->unit_cost->multiply((string) $item->qty_damaged)),
            Money::zero(),
        );

        return [
            JournalLineData::debit($this->accounts->inventoryLoss()->id, $damagedValue),
            JournalLineData::credit($this->accounts->inventory()->id, $damagedValue),
        ];
    }
}
