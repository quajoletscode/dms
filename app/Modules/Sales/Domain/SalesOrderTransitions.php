<?php

namespace App\Modules\Sales\Domain;

use App\Support\Exceptions\IllegalTransitionException;

/**
 * SO-01: Draft → Confirmed → Fulfilled → Invoiced / Cancelled. A Sales
 * Order has no stock/accounting effect of its own (only converting it to an
 * Invoice does), so — unlike a Purchase Order — cancellation stays legal
 * right up to the point it's actually invoiced.
 */
final class SalesOrderTransitions
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['fulfilled', 'cancelled'],
        'fulfilled' => ['invoiced', 'cancelled'],
        'invoiced' => [],
        'cancelled' => [],
    ];

    public function assertCanTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new IllegalTransitionException("Cannot transition sales order from [{$from}] to [{$to}].");
        }
    }
}
