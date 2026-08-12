<?php

use App\Modules\Finance\Actions\ReconcileBankStatement;
use App\Modules\Finance\Actions\RecordBankDeposit;
use App\Modules\Finance\Actions\RecordBankTransfer;
use App\Modules\Finance\Actions\RecordBankWithdrawal;
use App\Modules\Finance\Models\JournalEntry;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a bank deposit posts Dr the bank account / Cr Cash on Hand', function () {
    $bank = createBankAccountFixture();
    actingAsUser(createAccountantUser());

    $txn = app(RecordBankDeposit::class)->execute($bank, '500.00', 'SLIP-001');

    expect($txn->type)->toBe('deposit')
        ->and($txn->amount->toMajor())->toBe('500.00');
    $this->assertDatabaseCount('journal_entries', 1);
});

test('a bank withdrawal posts Dr Cash on Hand / Cr the bank account', function () {
    $bank = createBankAccountFixture();
    actingAsUser(createAccountantUser());

    $txn = app(RecordBankWithdrawal::class)->execute($bank, '200.00', 'SLIP-002');

    expect($txn->type)->toBe('withdrawal')
        ->and($txn->amount->toMajor())->toBe('200.00');
    $this->assertDatabaseCount('journal_entries', 1);
});

test('a bank transfer with a charge posts destination + Bank Charges against the source, one journal', function () {
    $from = createBankAccountFixture('1000.00');
    $to = createBankAccountFixture();
    actingAsUser(createAccountantUser());

    $txn = app(RecordBankTransfer::class)->execute($from, $to, '300.00', '5.00', 'SLIP-003');

    expect($txn->type)->toBe('transfer_out')
        ->and($txn->amount->toMajor())->toBe('300.00')
        ->and($txn->charge->toMajor())->toBe('5.00');

    $this->assertDatabaseCount('bank_transactions', 2); // transfer_out + its transfer_in companion
    $this->assertDatabaseCount('journal_entries', 1); // one journal for the whole transfer

    $journal = JournalEntry::query()->latest('id')->first();
    $totalDebit = $journal->lines->sum(fn ($line) => $line->debit->minorUnits);
    $totalCredit = $journal->lines->sum(fn ($line) => $line->credit->minorUnits);

    expect($totalDebit)->toBe(30500)
        ->and($totalCredit)->toBe(30500);
});

test('a bank transfer with no charge posts only two lines', function () {
    $from = createBankAccountFixture('1000.00');
    $to = createBankAccountFixture();
    actingAsUser(createAccountantUser());

    app(RecordBankTransfer::class)->execute($from, $to, '100.00');

    $journal = JournalEntry::query()->latest('id')->first();
    expect($journal->lines)->toHaveCount(2);
});

test('reconciling bank transactions marks them reconciled', function () {
    $bank = createBankAccountFixture();
    actingAsUser(createAccountantUser());
    $txn = app(RecordBankDeposit::class)->execute($bank, '50.00');

    $count = app(ReconcileBankStatement::class)->execute([$txn->id]);

    expect($count)->toBe(1)
        ->and($txn->fresh()->reconciled)->toBeTrue();
});
