<?php

use App\Modules\Finance\Actions\CloseFiscalPeriod;
use App\Modules\Finance\Actions\PostManualJournal;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\FiscalPeriod;
use App\Support\Exceptions\IllegalTransitionException;
use App\Support\Exceptions\PostingIntoClosedPeriodException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('posting into a closed fiscal period is rejected', function () {
    $period = FiscalPeriod::query()->create([
        'starts_on' => now()->startOfMonth()->toDateString(),
        'ends_on' => now()->endOfMonth()->toDateString(),
        'status' => 'open',
    ]);

    actingAsUser(createAccountantUser());
    app(CloseFiscalPeriod::class)->execute($period);

    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    expect(fn () => app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '10.00'],
        ['account_id' => $cash->id, 'credit' => '10.00'],
    ]))->toThrow(PostingIntoClosedPeriodException::class);

    $this->assertDatabaseCount('journal_entries', 0);
});

test('posting on a date with no configured fiscal period at all is unrestricted', function () {
    actingAsUser(createAccountantUser());
    $cash = ChartOfAccount::query()->where('code', '1010')->firstOrFail();
    $expense = ChartOfAccount::query()->where('code', '5100')->firstOrFail();

    $journal = app(PostManualJournal::class)->execute([
        ['account_id' => $expense->id, 'debit' => '10.00'],
        ['account_id' => $cash->id, 'credit' => '10.00'],
    ]);

    expect($journal->status)->toBe('posted');
});

test('closing an already-closed fiscal period is blocked', function () {
    $period = FiscalPeriod::query()->create([
        'starts_on' => now()->startOfMonth()->toDateString(),
        'ends_on' => now()->endOfMonth()->toDateString(),
        'status' => 'open',
    ]);

    actingAsUser(createAccountantUser());
    app(CloseFiscalPeriod::class)->execute($period);

    expect(fn () => app(CloseFiscalPeriod::class)->execute($period->fresh()))
        ->toThrow(IllegalTransitionException::class);
});
