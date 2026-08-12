<?php

namespace App\Modules\Finance\Domain;

use App\Support\Exceptions\IllegalTransitionException;

/**
 * posted -> reversed, one-way. No same-state no-op (ADR-33/38's precedent):
 * ReverseJournal posts a real mirrored journal, so reversing an
 * already-reversed entry a second time must be a hard error, not silently
 * accepted.
 */
final class JournalEntryTransitions
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'draft' => ['posted'],
        'posted' => ['reversed'],
        'reversed' => [],
    ];

    public function assertCanTransition(string $from, string $to): void
    {
        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new IllegalTransitionException("Cannot transition journal entry from [{$from}] to [{$to}].");
        }
    }
}
