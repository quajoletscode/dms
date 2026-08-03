<?php

namespace App\Modules\Sales\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Models\CreditNote;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A credit note reverses part of a previously-posted invoice: Dr Sales
 * Revenue / Cr AR (customer), plus Dr Inventory / Cr COGS (the returned
 * stock's original cost is restored, not its current cost_price).
 */
final class CreditNotePostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof CreditNote) {
            throw new InvalidArgumentException('CreditNotePostingRule can only post CreditNote documents.');
        }

        $revenueTotal = $document->items->reduce(
            fn (Money $carry, $item) => $carry->add($item->unit_price->multiply((string) $item->qty)),
            Money::zero(),
        );

        $cogsTotal = $document->items->reduce(
            fn (Money $carry, $item) => $carry->add($item->unit_cost->multiply((string) $item->qty)),
            Money::zero(),
        );

        $customerId = $document->invoice->customer_id;

        $lines = [
            JournalLineData::debit($this->accounts->salesRevenue()->id, $revenueTotal),
            JournalLineData::credit($this->accounts->accountsReceivable()->id, $revenueTotal, Customer::class, $customerId),
        ];

        if ($cogsTotal->isPositive()) {
            $lines[] = JournalLineData::debit($this->accounts->inventory()->id, $cogsTotal);
            $lines[] = JournalLineData::credit($this->accounts->cogs()->id, $cogsTotal);
        }

        return $lines;
    }
}
