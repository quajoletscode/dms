<?php

namespace App\Modules\Van\Domain;

use App\Support\Exceptions\IllegalTransitionException;

/**
 * requested -> approved -> loaded -> received (terminal); rejected is
 * reachable from requested/approved only — once physically loaded, a
 * loadout can no longer be rejected outright, only received (with a
 * recorded discrepancy if needed).
 *
 * Like TillSessionTransitions (decisions.md ADR-33) and unlike the other
 * *Transitions guards, a same-state call is NOT a no-op: ConfirmLoadoutReceipt
 * moves real stock on 'loaded' -> 'received', so a silent no-op on a repeat
 * call would risk double-moving stock.
 */
final class LoadoutTransitions
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'requested' => ['approved', 'rejected'],
        'approved' => ['loaded', 'rejected'],
        'loaded' => ['received'],
        'received' => [],
        'rejected' => [],
    ];

    public function assertCanTransition(string $from, string $to): void
    {
        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new IllegalTransitionException("Cannot transition loadout request from [{$from}] to [{$to}].");
        }
    }
}
