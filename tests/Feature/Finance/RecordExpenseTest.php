<?php

use App\Modules\Finance\Actions\RecordExpense;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('an expense paid from cash posts Dr the category account / Cr Cash on Hand', function () {
    $category = createExpenseCategoryFixture();
    actingAsUser(createAccountantUser());

    $expense = app(RecordExpense::class)->execute($category->id, '45.00');

    expect($expense->amount->toMajor())->toBe('45.00')
        ->and($expense->paid_from_bank_account_id)->toBeNull();
    $this->assertDatabaseCount('journal_entries', 1);
});

test('an expense paid from a bank account posts Dr the category account / Cr that bank account', function () {
    $category = createExpenseCategoryFixture();
    $bank = createBankAccountFixture('1000.00');
    actingAsUser(createAccountantUser());

    $expense = app(RecordExpense::class)->execute($category->id, '60.00', paidFromBankAccountId: $bank->id);

    expect($expense->paid_from_bank_account_id)->toBe($bank->id);
    $this->assertDatabaseCount('journal_entries', 1);
});
