<?php

namespace App\Modules\Finance\Domain;

use App\Support\Exceptions\IllegalTransitionException;

/**
 * BNK-06: with_dsr -> handed_over -> deposited, one-way. Guards
 * MarkCollectionDeposited, which accepts caller-supplied collection IDs of
 * unknown status (unlike HandoverDsrCash, whose query already filters to
 * with_dsr only).
 */
final class CollectionTransitions
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'with_dsr' => ['handed_over'],
        'handed_over' => ['deposited'],
        'deposited' => [],
    ];

    public function assertCanTransition(string $from, string $to): void
    {
        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new IllegalTransitionException("Cannot transition collection from [{$from}] to [{$to}].");
        }
    }
}
