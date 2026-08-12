<?php

namespace App\Modules\Finance\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Models\BankTransaction;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * BNK-01/02: cash physically deposited into a bank account: Dr the bank's
 * own GL account / Cr Cash on Hand.
 */
final class BankDepositPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof BankTransaction) {
            throw new InvalidArgumentException('BankDepositPostingRule can only post BankTransaction documents.');
        }

        return [
            JournalLineData::debit($document->bankAccount->coa_account_id, $document->amount),
            JournalLineData::credit($this->accounts->cashOnHand()->id, $document->amount),
        ];
    }
}
