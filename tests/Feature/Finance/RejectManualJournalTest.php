<?php

use App\Modules\Finance\Actions\ApproveManualJournal;
use App\Modules\Finance\Actions\PostManualJournal;
use App\Modules\Finance\Actions\RejectManualJournal;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\Exceptions\IllegalTransitionException;
use App\Support\Exceptions\SegregationOfDutiesException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('the requester of a pending manual journal cannot also reject it', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $journal = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '10000.00'],
        ['account_id' => $cash->id, 'credit' => '10000.00'],
    ]);

    expect(fn () => app(RejectManualJournal::class)->execute($journal))
        ->toThrow(SegregationOfDutiesException::class);
});

test('a different user can reject a pending manual journal, and nothing is posted', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $journal = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '10000.00'],
        ['account_id' => $cash->id, 'credit' => '10000.00'],
    ]);

    $rejecter = createAccountantUser();
    actingAsUser($rejecter);
    $rejected = app(RejectManualJournal::class)->execute($journal, 'Duplicate of JV-0004');

    expect($rejected->status)->toBe('rejected')
        ->and($rejected->journal_entry_id)->toBeNull()
        ->and($rejected->rejected_by)->toBe($rejecter->id)
        ->and($rejected->rejection_reason)->toBe('Duplicate of JV-0004');
    $this->assertDatabaseCount('journal_entries', 0);
});

test('an already-posted manual journal cannot be rejected', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $journal = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '10000.00'],
        ['account_id' => $cash->id, 'credit' => '10000.00'],
    ]);

    actingAsUser(createAccountantUser());
    app(ApproveManualJournal::class)->execute($journal);

    expect(fn () => app(RejectManualJournal::class)->execute($journal->fresh()))
        ->toThrow(IllegalTransitionException::class);
});
