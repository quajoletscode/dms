<?php

namespace App\Support;

use InvalidArgumentException;
use Stringable;

/**
 * An amount of money stored as integer minor units (pesewas for GHS) to avoid
 * float rounding errors. Single-currency (GHS) for v1 per the product spec.
 */
final class Money implements Stringable
{
    private function __construct(
        public readonly int $minorUnits,
        public readonly string $currency = 'GHS',
    ) {}

    public static function fromMinorUnits(int $minorUnits, string $currency = 'GHS'): self
    {
        return new self($minorUnits, $currency);
    }

    public static function fromMajor(string|int|float $amount, string $currency = 'GHS'): self
    {
        return new self((int) round(((float) $amount) * 100), $currency);
    }

    public static function zero(string $currency = 'GHS'): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function multiply(int|float|string $factor): self
    {
        return new self((int) round($this->minorUnits * (float) $factor), $this->currency);
    }

    /**
     * @param  int|float|string  $rate  e.g. '15.00' for 15%
     */
    public function percentage(int|float|string $rate): self
    {
        return $this->multiply(((float) $rate) / 100);
    }

    public function negate(): self
    {
        return new self(-$this->minorUnits, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->minorUnits === $other->minorUnits;
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits > $other->minorUnits;
    }

    public function lessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits < $other->minorUnits;
    }

    public function toMajor(): string
    {
        return number_format($this->minorUnits / 100, 2, '.', '');
    }

    public function format(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->minorUnits / 100, 2));
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on Money in different currencies ({$this->currency} vs {$other->currency})."
            );
        }
    }
}
