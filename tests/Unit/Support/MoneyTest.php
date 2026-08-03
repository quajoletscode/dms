<?php

use App\Support\Money;

test('fromMajor converts major currency units to minor units', function () {
    expect(Money::fromMajor('12.34')->minorUnits)->toBe(1234);
});

test('arithmetic operates in minor units without float drift', function () {
    $a = Money::fromMajor('0.10');
    $b = Money::fromMajor('0.20');

    expect($a->add($b)->toMajor())->toBe('0.30');
});

test('subtract and negate', function () {
    $a = Money::fromMajor('10.00');
    $b = Money::fromMajor('3.50');

    expect($a->subtract($b)->toMajor())->toBe('6.50')
        ->and($b->negate()->minorUnits)->toBe(-350);
});

test('multiply scales the amount', function () {
    expect(Money::fromMajor('2.50')->multiply(3)->toMajor())->toBe('7.50');
});

test('percentage computes a rate on the amount', function () {
    expect(Money::fromMajor('200.00')->percentage('15.00')->toMajor())->toBe('30.00')
        ->and(Money::fromMajor('10.00')->percentage(0)->isZero())->toBeTrue();
});

test('cannot mix currencies', function () {
    $ghs = Money::fromMajor('10.00', 'GHS');
    $usd = Money::fromMajor('10.00', 'USD');

    expect(fn () => $ghs->add($usd))->toThrow(InvalidArgumentException::class);
});

test('format renders currency code and two decimals', function () {
    expect(Money::fromMinorUnits(123456)->format())->toBe('GHS 1,234.56');
});

test('zero, positive, and negative checks', function () {
    expect(Money::zero()->isZero())->toBeTrue()
        ->and(Money::fromMajor('1')->isPositive())->toBeTrue()
        ->and(Money::fromMajor('-1')->isNegative())->toBeTrue();
});

test('equals compares currency and amount', function () {
    expect(Money::fromMajor('5.00')->equals(Money::fromMinorUnits(500)))->toBeTrue()
        ->and(Money::fromMajor('5.00', 'GHS')->equals(Money::fromMajor('5.00', 'USD')))->toBeFalse();
});

test('greaterThan and lessThan compare within the same currency', function () {
    $small = Money::fromMajor('1.00');
    $large = Money::fromMajor('2.00');

    expect($large->greaterThan($small))->toBeTrue()
        ->and($small->lessThan($large))->toBeTrue();
});
