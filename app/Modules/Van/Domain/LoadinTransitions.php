<?php

namespace App\Modules\Van\Domain;

use App\Support\Exceptions\IllegalTransitionException;

/**
 * requested -> accepted, one-way. Like TillSessionTransitions (decisions.md
 * ADR-33), a same-state call is NOT a no-op: AcceptLoadin moves real stock
 * and conditionally posts a write-off, so a silent no-op on a repeat call
 * would risk double-posting.
 */
final class LoadinTransitions
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'requested' => ['accepted'],
        'accepted' => [],
    ];

    public function assertCanTransition(string $from, string $to): void
    {
        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new IllegalTransitionException("Cannot transition loadin request from [{$from}] to [{$to}].");
        }
    }
}
