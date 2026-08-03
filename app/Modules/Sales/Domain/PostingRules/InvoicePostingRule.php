<?php

namespace App\Modules\Sales\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Models\Invoice;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * SO-03: converting to an invoice posts Dr Accounts Receivable (Customer) /
 * Cr Sales Revenue, plus Dr COGS / Cr Inventory — one compound journal, four
 * lines, for the single business event of issuing the invoice.
 */
final class InvoicePostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof Invoice) {
            throw new InvalidArgumentException('InvoicePostingRule can only post Invoice documents.');
        }

        $cogsTotal = $document->items->reduce(
            fn (Money $carry, $item) => $carry->add($item->unit_cost->multiply((string) $item->qty)),
            Money::zero(),
        );

        $lines = [
            JournalLineData::debit($this->accounts->accountsReceivable()->id, $document->grand_total, Customer::class, $document->customer_id),
            JournalLineData::credit($this->accounts->salesRevenue()->id, $document->grand_total),
        ];

        if ($cogsTotal->isPositive()) {
            $lines[] = JournalLineData::debit($this->accounts->cogs()->id, $cogsTotal);
            $lines[] = JournalLineData::credit($this->accounts->inventory()->id, $cogsTotal);
        }

        return $lines;
    }
}
