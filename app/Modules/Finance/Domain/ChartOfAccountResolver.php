<?php

namespace App\Modules\Finance\Domain;

use App\Modules\Finance\Models\ChartOfAccount;

/**
 * Resolves the canonical accounts PostingRules post to, by the code
 * configured in config/accounting.php — never a hardcoded ID or a magic
 * string scattered across rule classes.
 */
final class ChartOfAccountResolver
{
    public function inventory(): ChartOfAccount
    {
        return $this->byConfigKey('inventory');
    }

    public function cashOnHand(): ChartOfAccount
    {
        return $this->byConfigKey('cash_on_hand');
    }

    public function accountsReceivable(): ChartOfAccount
    {
        return $this->byConfigKey('accounts_receivable');
    }

    public function accountsPayable(): ChartOfAccount
    {
        return $this->byConfigKey('accounts_payable');
    }

    public function salesRevenue(): ChartOfAccount
    {
        return $this->byConfigKey('sales_revenue');
    }

    public function cogs(): ChartOfAccount
    {
        return $this->byConfigKey('cogs');
    }

    private function byConfigKey(string $key): ChartOfAccount
    {
        $code = (string) config("accounting.accounts.{$key}");

        return ChartOfAccount::query()->where('code', $code)->firstOrFail();
    }
}
