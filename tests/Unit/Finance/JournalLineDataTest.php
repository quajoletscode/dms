<?php

use App\Modules\Finance\Domain\JournalLineData;
use App\Support\Money;

test('debit() and credit() build single-sided lines', function () {
    $debit = JournalLineData::debit(1, Money::fromMajor('10.00'));
    $credit = JournalLineData::credit(2, Money::fromMajor('10.00'));

    expect($debit->debit->toMajor())->toBe('10.00')
        ->and($debit->credit->isZero())->toBeTrue()
        ->and($credit->credit->toMajor())->toBe('10.00')
        ->and($credit->debit->isZero())->toBeTrue();
});

test('a line cannot be both a debit and a credit', function () {
    expect(fn () => new JournalLineData(1, Money::fromMajor('10.00'), Money::fromMajor('5.00')))
        ->toThrow(InvalidArgumentException::class);
});

test('a line must have a non-zero debit or credit', function () {
    expect(fn () => new JournalLineData(1, Money::zero(), Money::zero()))
        ->toThrow(InvalidArgumentException::class);
});
