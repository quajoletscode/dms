<?php

namespace App\Modules\Sales\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoiceItem;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * SO-03: converting to an invoice posts Dr Accounts Receivable (Customer) /
 * Cr Sales Revenue, plus Dr COGS / Cr Inventory — one compound journal, four
 * lines, for the single business event of issuing the invoice.
 *
 * A `gl_account` line (a non-stock charge) credits its own target account
 * directly instead of Sales Revenue — everything else (`item`/`comment`
 * lines) still nets to Sales Revenue exactly as before, so an invoice made
 * entirely of `item` lines posts identically to pre-line-type behavior.
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

        $lineNetAmount = fn (InvoiceItem $item): Money => $item->unit_price
            ->multiply((string) $item->qty)
            ->subtract($item->discount)
            ->add($item->tax);

        $revenueTotal = $document->items
            ->whereIn('line_type', ['item', 'comment'])
            ->reduce(fn (Money $carry, InvoiceItem $item) => $carry->add($lineNetAmount($item)), Money::zero());

        $lines = [
            JournalLineData::debit($this->accounts->accountsReceivable()->id, $document->grand_total, Customer::class, $document->customer_id),
        ];

        if ($revenueTotal->isPositive()) {
            $lines[] = JournalLineData::credit($this->accounts->salesRevenue()->id, $revenueTotal);
        }

        foreach ($document->items->where('line_type', 'gl_account')->groupBy('gl_account_id') as $glAccountId => $glItems) {
            $groupTotal = $glItems->reduce(fn (Money $carry, InvoiceItem $item) => $carry->add($lineNetAmount($item)), Money::zero());

            if ($groupTotal->isPositive()) {
                $lines[] = JournalLineData::credit((int) $glAccountId, $groupTotal);
            }
        }

        if ($cogsTotal->isPositive()) {
            $lines[] = JournalLineData::debit($this->accounts->cogs()->id, $cogsTotal);
            $lines[] = JournalLineData::credit($this->accounts->inventory()->id, $cogsTotal);
        }

        return $lines;
    }
}
