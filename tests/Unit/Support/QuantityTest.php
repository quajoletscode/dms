<?php

use App\Support\Quantity;

test('fromString normalizes to a 3-decimal string', function () {
    expect((string) Quantity::fromString('10'))->toBe('10.000')
        ->and((string) Quantity::fromString('10.5'))->toBe('10.500');
});

test('arithmetic is exact at 3 decimal places', function () {
    $a = Quantity::fromString('10.001');
    $b = Quantity::fromString('0.002');

    expect((string) $a->add($b))->toBe('10.003')
        ->and((string) $a->subtract($b))->toBe('9.999');
});

test('multiply supports unit conversion factors like carton to piece', function () {
    $cartons = Quantity::fromString('2');

    expect((string) $cartons->multiply(12))->toBe('24.000');
});

test('comparisons', function () {
    $a = Quantity::fromString('5');
    $b = Quantity::fromString('7');

    expect($a->lessThan($b))->toBeTrue()
        ->and($b->greaterThan($a))->toBeTrue()
        ->and($a->equals(Quantity::fromString('5.000')))->toBeTrue()
        ->and(Quantity::zero()->isZero())->toBeTrue();
});

test('rejects non-numeric input', function () {
    expect(fn () => Quantity::fromString('abc'))->toThrow(InvalidArgumentException::class);
});
