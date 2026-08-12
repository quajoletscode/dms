<?php

use App\Modules\Finance\Actions\ApproveManualJournal;
use App\Modules\Finance\Actions\PostManualJournal;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\Exceptions\SegregationOfDutiesException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a manual journal above the approval threshold is held pending approval, not posted immediately', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $journal = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '10000.00'],
        ['account_id' => $cash->id, 'credit' => '10000.00'],
    ]);

    expect($journal->status)->toBe('pending_approval')
        ->and($journal->journal_entry_id)->toBeNull();
    $this->assertDatabaseCount('journal_entries', 0);
});

test('the requester of a pending manual journal cannot also approve it', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $journal = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '10000.00'],
        ['account_id' => $cash->id, 'credit' => '10000.00'],
    ]);

    expect(fn () => app(ApproveManualJournal::class)->execute($journal))
        ->toThrow(SegregationOfDutiesException::class);
});

test('a different user can approve a pending manual journal, posting it', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $journal = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '10000.00'],
        ['account_id' => $cash->id, 'credit' => '10000.00'],
    ]);

    actingAsUser(createAccountantUser());
    $approved = app(ApproveManualJournal::class)->execute($journal);

    expect($approved->status)->toBe('posted')
        ->and($approved->journal_entry_id)->not->toBeNull();
    $this->assertDatabaseCount('journal_entries', 1);
});
