<?php

namespace App\Modules\Finance\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Models\Expense;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Dr the expense category's own GL account / Cr Cash on Hand, or Cr the
 * named bank account if the expense was paid from one.
 */
final class ExpensePostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof Expense) {
            throw new InvalidArgumentException('ExpensePostingRule can only post Expense documents.');
        }

        $bankAccount = $document->paidFromBankAccount;
        $creditAccountId = $bankAccount !== null
            ? $bankAccount->coa_account_id
            : $this->accounts->cashOnHand()->id;

        return [
            JournalLineData::debit($document->category->coa_account_id, $document->amount),
            JournalLineData::credit($creditAccountId, $document->amount),
        ];
    }
}
