<?php

namespace App\Modules\Finance\Domain\Reports;

use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\JournalLine;
use App\Support\Money;

/**
 * FIN-01: every account's net debit/credit balance, derived live from
 * journal_lines — never a cached running total. Total debits must always
 * equal total credits (TrialBalanceBalancesInvariantTest); the other report
 * types the plan lists (ProfitAndLoss, BalanceSheet, GeneralLedger, ...)
 * have no named test this phase and are deferred — see decisions.md.
 */
final class TrialBalance
{
    /**
     * @return array{lines: list<array{account: ChartOfAccount, debit: Money, credit: Money}>, totalDebit: Money, totalCredit: Money}
     */
    public function generate(): array
    {
        $accounts = ChartOfAccount::query()->orderBy('code')->get();
        $lines = [];
        $totalDebit = Money::zero();
        $totalCredit = Money::zero();

        foreach ($accounts as $account) {
            $accountLines = JournalLine::query()->where('account_id', $account->id)->get();
            $debit = $accountLines->reduce(fn (Money $carry, JournalLine $line) => $carry->add($line->debit), Money::zero());
            $credit = $accountLines->reduce(fn (Money $carry, JournalLine $line) => $carry->add($line->credit), Money::zero());

            if ($debit->isZero() && $credit->isZero()) {
                continue;
            }

            $lines[] = ['account' => $account, 'debit' => $debit, 'credit' => $credit];
            $totalDebit = $totalDebit->add($debit);
            $totalCredit = $totalCredit->add($credit);
        }

        return ['lines' => $lines, 'totalDebit' => $totalDebit, 'totalCredit' => $totalCredit];
    }
}
