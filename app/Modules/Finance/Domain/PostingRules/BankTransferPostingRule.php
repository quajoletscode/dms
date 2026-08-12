<?php

namespace App\Modules\Finance\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Models\BankTransaction;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * BNK-02: a transfer between two bank accounts, optionally carrying a bank
 * charge. Dr destination account (amount), Dr Bank Charges (charge, only if
 * positive) / Cr source account (amount + charge) — the full amount that
 * actually left the source account. Posted once, from the transfer_out
 * BankTransaction row; its transfer_in companion is a non-posting audit
 * record on the destination account.
 */
final class BankTransferPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof BankTransaction) {
            throw new InvalidArgumentException('BankTransferPostingRule can only post BankTransaction documents.');
        }

        $relatedBankAccount = $document->relatedBankAccount;

        if ($relatedBankAccount === null) {
            throw new InvalidArgumentException('A bank transfer must have a related_bank_account_id (the destination account).');
        }

        $lines = [
            JournalLineData::debit($relatedBankAccount->coa_account_id, $document->amount),
        ];

        if ($document->charge->isPositive()) {
            $lines[] = JournalLineData::debit($this->accounts->bankCharges()->id, $document->charge);
        }

        $lines[] = JournalLineData::credit($document->bankAccount->coa_account_id, $document->amount->add($document->charge));

        return $lines;
    }
}
