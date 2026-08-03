<?php

namespace App\Modules\Warehouse\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\Warehouse\Models\Grn;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * GRN-03: posting a GRN posts Dr Inventory / Cr Accounts Payable (supplier).
 * The supplier sub-ledger is derived by grouping journal_lines by partner on
 * the AP control account — see FIN-04.
 */
final class GrnPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof Grn) {
            throw new InvalidArgumentException('GrnPostingRule can only post Grn documents.');
        }

        $total = $document->items->reduce(
            fn (Money $carry, $item) => $carry->add($item->unit_cost->multiply((string) $item->qty_received)),
            Money::zero(),
        );

        return [
            JournalLineData::debit($this->accounts->inventory()->id, $total),
            JournalLineData::credit($this->accounts->accountsPayable()->id, $total, Supplier::class, $document->supplier_id),
        ];
    }
}
