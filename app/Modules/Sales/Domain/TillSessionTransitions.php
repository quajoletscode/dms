<?php

namespace App\Modules\Sales\Domain;

use App\Support\Exceptions\IllegalTransitionException;

/**
 * open → closed, one-way. Closing is the only transition; there is no
 * reopen — a miscounted till gets corrected via a new cash movement or a
 * fresh session, not by reversing the close.
 *
 * Unlike the other *Transitions guards, a same-state call is deliberately
 * NOT treated as a no-op here: closing an already-closed session must throw,
 * not silently succeed. CloseTillSession's body always recomputes and
 * conditionally re-posts the variance journal, so a silent no-op on
 * 'closed' -> 'closed' would let a second close call double-post — see
 * decisions.md for the ADR.
 */
final class TillSessionTransitions
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'open' => ['closed'],
        'closed' => [],
    ];

    public function assertCanTransition(string $from, string $to): void
    {
        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new IllegalTransitionException("Cannot transition till session from [{$from}] to [{$to}].");
        }
    }
}
