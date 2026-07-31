<?php

namespace App\Modules\Finance\Domain;

use App\Support\Money;
use InvalidArgumentException;

/**
 * One side of a balanced journal entry, produced by a PostingRule.
 */
final class JournalLineData
{
    public function __construct(
        public readonly int $accountId,
        public readonly Money $debit,
        public readonly Money $credit,
        public readonly ?string $partnerType = null,
        public readonly ?int $partnerId = null,
        public readonly ?string $memo = null,
    ) {
        if ($debit->isPositive() && $credit->isPositive()) {
            throw new InvalidArgumentException('A journal line cannot be both a debit and a credit.');
        }

        if ($debit->isZero() && $credit->isZero()) {
            throw new InvalidArgumentException('A journal line must have a non-zero debit or credit.');
        }
    }

    public static function debit(
        int $accountId,
        Money $amount,
        ?string $partnerType = null,
        ?int $partnerId = null,
        ?string $memo = null,
    ): self {
        return new self($accountId, $amount, Money::zero($amount->currency), $partnerType, $partnerId, $memo);
    }

    public static function credit(
        int $accountId,
        Money $amount,
        ?string $partnerType = null,
        ?int $partnerId = null,
        ?string $memo = null,
    ): self {
        return new self($accountId, Money::zero($amount->currency), $amount, $partnerType, $partnerId, $memo);
    }
}
