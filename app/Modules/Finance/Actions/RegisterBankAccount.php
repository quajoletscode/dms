<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\BankAccount;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

final class RegisterBankAccount
{
    /**
     * @param  array{name: string, account_no: string, bank_name: string, coa_code: string, opening_balance?: string}  $data
     */
    public function execute(array $data): BankAccount
    {
        return DB::transaction(function () use ($data) {
            $account = ChartOfAccount::query()->create([
                'code' => $data['coa_code'],
                'name' => $data['name'],
                'type' => 'asset',
                'is_control' => false,
                'currency' => 'GHS',
                'is_active' => true,
            ]);

            return BankAccount::query()->create([
                'name' => $data['name'],
                'account_no' => $data['account_no'],
                'bank_name' => $data['bank_name'],
                'coa_account_id' => $account->id,
                'opening_balance' => Money::fromMajor((string) ($data['opening_balance'] ?? 0))->minorUnits,
                'is_active' => true,
            ]);
        });
    }
}
