<?php

namespace App\Modules\Sales\Domain;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Models\JournalLine;
use App\Modules\MasterData\Models\Customer;
use App\Support\Money;

/**
 * SO-04: credit-limit check. A customer's outstanding balance is derived
 * from the Accounts Receivable control account's journal lines tagged to
 * them (Dr = charged, Cr = paid) — never a cached column, so it can never
 * drift from the ledger. credit_limit = 0 means "no credit" (cash only),
 * not "unlimited" — see decisions.md.
 */
final class CustomerCreditLimitCheck
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function outstandingBalance(Customer $customer): Money
    {
        $arAccountId = $this->accounts->accountsReceivable()->id;

        $lines = JournalLine::query()
            ->where('account_id', $arAccountId)
            ->where('partner_type', $customer->getMorphClass())
            ->where('partner_id', $customer->id)
            ->get();

        $debit = $lines->reduce(fn (Money $carry, JournalLine $line) => $carry->add($line->debit), Money::zero());
        $credit = $lines->reduce(fn (Money $carry, JournalLine $line) => $carry->add($line->credit), Money::zero());

        return $debit->subtract($credit);
    }

    public function wouldExceedLimit(Customer $customer, Money $additionalCharge): bool
    {
        return $this->outstandingBalance($customer)->add($additionalCharge)->greaterThan($customer->credit_limit);
    }
}
