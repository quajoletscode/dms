<?php

use App\Modules\Finance\Actions\PostManualJournal;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\Exceptions\UnbalancedJournalException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('an unbalanced manual journal is rejected before anything is persisted', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    expect(fn () => app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '90.00'],
        ['account_id' => $cash->id, 'credit' => '100.00'],
    ]))->toThrow(UnbalancedJournalException::class);

    $this->assertDatabaseCount('manual_journals', 0);
    $this->assertDatabaseCount('journal_entries', 0);
});

test('a balanced manual journal below the approval threshold posts immediately', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $journal = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '100.00'],
        ['account_id' => $cash->id, 'credit' => '100.00'],
    ]);

    expect($journal->status)->toBe('posted')
        ->and($journal->journal_entry_id)->not->toBeNull();
    $this->assertDatabaseCount('journal_entries', 1);
});
