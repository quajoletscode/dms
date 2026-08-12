<?php

use App\Modules\Finance\Actions\PostManualJournal;
use App\Modules\Finance\Actions\ReverseJournal;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\Exceptions\IllegalTransitionException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('reversing a reversal reproduces the original journal lines exactly', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $manual = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '75.00'],
        ['account_id' => $cash->id, 'credit' => '75.00'],
    ]);
    $original = $manual->journalEntry;

    $reversal1 = app(ReverseJournal::class)->execute($original);

    expect($original->fresh()->status)->toBe('reversed')
        ->and($reversal1->status)->toBe('posted')
        ->and($reversal1->reversal_of_id)->toBe($original->id);

    $reversal2 = app(ReverseJournal::class)->execute($reversal1);

    expect($reversal1->fresh()->status)->toBe('reversed')
        ->and($reversal2->reversal_of_id)->toBe($reversal1->id);

    $originalLines = $original->lines()->orderBy('account_id')->get(['account_id', 'debit', 'credit']);
    $reversal2Lines = $reversal2->lines()->orderBy('account_id')->get(['account_id', 'debit', 'credit']);

    expect($reversal2Lines)->toHaveCount($originalLines->count());

    foreach ($originalLines as $index => $line) {
        expect($reversal2Lines[$index]->account_id)->toBe($line->account_id)
            ->and($reversal2Lines[$index]->debit->equals($line->debit))->toBeTrue()
            ->and($reversal2Lines[$index]->credit->equals($line->credit))->toBeTrue();
    }
});

test('reversing an already-reversed journal a second time is blocked', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $manual = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '20.00'],
        ['account_id' => $cash->id, 'credit' => '20.00'],
    ]);
    $original = $manual->journalEntry;

    app(ReverseJournal::class)->execute($original);

    expect(fn () => app(ReverseJournal::class)->execute($original->fresh()))
        ->toThrow(IllegalTransitionException::class);
});
