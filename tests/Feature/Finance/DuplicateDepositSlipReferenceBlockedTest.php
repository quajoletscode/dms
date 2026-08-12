<?php

use App\Modules\Finance\Actions\RecordBankDeposit;
use App\Modules\Finance\Domain\Exceptions\DuplicateSlipReferenceException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a duplicate deposit slip reference is blocked', function () {
    $bank = createBankAccountFixture();
    actingAsUser(createAccountantUser());

    app(RecordBankDeposit::class)->execute($bank, '100.00', 'SLIP-DUP');

    expect(fn () => app(RecordBankDeposit::class)->execute($bank, '50.00', 'SLIP-DUP'))
        ->toThrow(DuplicateSlipReferenceException::class);

    $this->assertDatabaseCount('bank_transactions', 1);
});

test('two deposits with no slip reference at all are both allowed', function () {
    $bank = createBankAccountFixture();
    actingAsUser(createAccountantUser());

    app(RecordBankDeposit::class)->execute($bank, '100.00');
    app(RecordBankDeposit::class)->execute($bank, '50.00');

    $this->assertDatabaseCount('bank_transactions', 2);
});
