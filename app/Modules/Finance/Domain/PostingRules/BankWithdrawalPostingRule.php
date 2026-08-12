<?php

namespace App\Modules\Finance\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Models\BankTransaction;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * BNK-02: cash withdrawn from a bank account back to hand: Dr Cash on Hand /
 * Cr the bank's own GL account.
 */
final class BankWithdrawalPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof BankTransaction) {
            throw new InvalidArgumentException('BankWithdrawalPostingRule can only post BankTransaction documents.');
        }

        return [
            JournalLineData::debit($this->accounts->cashOnHand()->id, $document->amount),
            JournalLineData::credit($document->bankAccount->coa_account_id, $document->amount),
        ];
    }
}
