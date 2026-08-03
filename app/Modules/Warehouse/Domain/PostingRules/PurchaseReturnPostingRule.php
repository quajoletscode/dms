<?php

namespace App\Modules\Warehouse\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\Warehouse\Models\PurchaseReturn;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A purchase return reverses part of a GRN: Dr Accounts Payable (supplier) /
 * Cr Inventory.
 */
final class PurchaseReturnPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof PurchaseReturn) {
            throw new InvalidArgumentException('PurchaseReturnPostingRule can only post PurchaseReturn documents.');
        }

        $total = $document->items->reduce(
            fn (Money $carry, $item) => $carry->add($item->unit_cost->multiply((string) $item->qty_returned)),
            Money::zero(),
        );

        $supplierId = $document->grn->supplier_id;

        return [
            JournalLineData::debit($this->accounts->accountsPayable()->id, $total, Supplier::class, $supplierId),
            JournalLineData::credit($this->accounts->inventory()->id, $total),
        ];
    }
}
