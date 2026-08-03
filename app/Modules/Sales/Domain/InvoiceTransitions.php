<?php

namespace App\Modules\Sales\Domain;

use App\Support\Exceptions\IllegalTransitionException;

/**
 * unpaid → partially_paid → paid, driven by RecordInvoicePayment's running
 * total. "void" isn't reachable yet — no VoidInvoice action exists (Phase 3
 * scope); corrections go through CreateCreditNote instead.
 */
final class InvoiceTransitions
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'unpaid' => ['partially_paid', 'paid'],
        'partially_paid' => ['paid'],
        'paid' => [],
    ];

    public function assertCanTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new IllegalTransitionException("Cannot transition invoice from [{$from}] to [{$to}].");
        }
    }
}
