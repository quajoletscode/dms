<?php

namespace App\Modules\Warehouse\Domain;

use App\Support\Exceptions\IllegalTransitionException;

/**
 * PO-03: Draft → Submitted → Approved → Partially Received → Received →
 * Closed / Cancelled. Cancellation is only legal before any GRN has posted
 * (i.e. not from partially_received/received) — see
 * PurchaseOrderCancelledAfterPartialGrnTest.
 */
final class PurchaseOrderTransitions
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'draft' => ['submitted', 'cancelled'],
        'submitted' => ['approved', 'cancelled'],
        'approved' => ['partially_received', 'received', 'cancelled'],
        'partially_received' => ['received', 'closed'],
        'received' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function assertCanTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new IllegalTransitionException("Cannot transition purchase order from [{$from}] to [{$to}].");
        }
    }
}
