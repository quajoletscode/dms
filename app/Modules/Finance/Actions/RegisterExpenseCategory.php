<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;

final class RegisterExpenseCategory
{
    /**
     * @param  array{name: string, coa_code: string}  $data
     */
    public function execute(array $data): ExpenseCategory
    {
        return DB::transaction(function () use ($data) {
            $account = ChartOfAccount::query()->create([
                'code' => $data['coa_code'],
                'name' => $data['name'],
                'type' => 'expense',
                'is_control' => false,
                'currency' => 'GHS',
                'is_active' => true,
            ]);

            return ExpenseCategory::query()->create([
                'name' => $data['name'],
                'coa_account_id' => $account->id,
                'is_active' => true,
            ]);
        });
    }
}
