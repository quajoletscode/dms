<?php

namespace Database\Seeders;

use App\Modules\Finance\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

/**
 * Minimal starter accounts the posting rules built so far actually need.
 * A fuller standard template (full Income/Expense breakdown, Bank accounts,
 * ...) lands in Phase 6 per FIN-01.
 */
class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Inventory', 'type' => 'asset', 'is_control' => false],
            ['code' => '1010', 'name' => 'Cash on Hand', 'type' => 'asset', 'is_control' => false],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_control' => true],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_control' => true],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'is_control' => false],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'is_control' => false],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::query()->firstOrCreate(
                ['code' => $account['code']],
                ['name' => $account['name'], 'type' => $account['type'], 'is_control' => $account['is_control']],
            );
        }
    }
}
