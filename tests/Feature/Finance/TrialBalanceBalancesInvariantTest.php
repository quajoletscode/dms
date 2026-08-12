<?php

use App\Modules\Finance\Actions\PostManualJournal;
use App\Modules\Finance\Domain\Reports\TrialBalance;
use App\Modules\Finance\Models\ChartOfAccount;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('the trial balance always has total debits equal to total credits', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();
    $bankCharges = ChartOfAccount::query()->where('code', '5200')->firstOrFail();

    app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '150.00'],
        ['account_id' => $cash->id, 'credit' => '150.00'],
    ]);
    app(PostManualJournal::class)->execute([
        ['account_id' => $bankCharges->id, 'debit' => '25.50'],
        ['account_id' => $cash->id, 'credit' => '25.50'],
    ]);

    $trialBalance = app(TrialBalance::class)->generate();

    expect($trialBalance['totalDebit']->equals($trialBalance['totalCredit']))->toBeTrue()
        ->and($trialBalance['totalDebit']->toMajor())->toBe('175.50');

    $cashLine = collect($trialBalance['lines'])->firstWhere('account.id', $cash->id);
    expect($cashLine['credit']->toMajor())->toBe('175.50');
});
