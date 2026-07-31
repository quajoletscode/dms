<?php

namespace App\Support;

use InvalidArgumentException;
use Stringable;

/**
 * A stock/unit quantity stored as a BCMath-backed decimal string (scale 3) so
 * carton/piece conversions and partial-unit sales never drift from float rounding.
 */
final class Quantity implements Stringable
{
    private const SCALE = 3;

    /**
     * @param  numeric-string  $value
     */
    private function __construct(public readonly string $value) {}

    public static function fromString(string|int|float $value): self
    {
        return new self(self::normalize((string) $value));
    }

    public static function zero(): self
    {
        return new self('0.000');
    }

    public function add(self $other): self
    {
        return new self(bcadd($this->value, $other->value, self::SCALE));
    }

    public function subtract(self $other): self
    {
        return new self(bcsub($this->value, $other->value, self::SCALE));
    }

    public function multiply(string|int|float $factor): self
    {
        return new self(bcmul($this->value, self::normalize((string) $factor), self::SCALE));
    }

    public function isZero(): bool
    {
        return bccomp($this->value, '0', self::SCALE) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->value, '0', self::SCALE) === 1;
    }

    public function isNegative(): bool
    {
        return bccomp($this->value, '0', self::SCALE) === -1;
    }

    public function equals(self $other): bool
    {
        return bccomp($this->value, $other->value, self::SCALE) === 0;
    }

    public function greaterThan(self $other): bool
    {
        return bccomp($this->value, $other->value, self::SCALE) === 1;
    }

    public function lessThan(self $other): bool
    {
        return bccomp($this->value, $other->value, self::SCALE) === -1;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return numeric-string
     */
    private static function normalize(string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Invalid quantity value: {$value}");
        }

        return bcadd($value, '0', self::SCALE);
    }
}
