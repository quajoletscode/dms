<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Sales\Domain\TillSessionTransitions;
use App\Modules\Sales\Models\InvoicePayment;
use App\Modules\Sales\Models\TillCashMovement;
use App\Modules\Sales\Models\TillSession;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * X/Z report: expected float = opening + cash sales (this session's cash
 * invoice_payments only, never the whole warehouse) + cash-in - cash-out.
 * Variance = counted - expected. A balanced till (variance = 0) posts
 * nothing; a shortage or overage posts to the Cash Over/Short account.
 */
final class CloseTillSession
{
    public function __construct(
        private readonly TillSessionTransitions $transitions,
        private readonly PostingEngine $postingEngine,
    ) {}

    public function execute(TillSession $tillSession, string $countedFloat): TillSession
    {
        Gate::authorize('sales.pos');

        $this->transitions->assertCanTransition($tillSession->status, 'closed');

        return DB::transaction(function () use ($tillSession, $countedFloat) {
            $cashSales = InvoicePayment::query()
                ->where('till_session_id', $tillSession->id)
                ->where('method', 'cash')
                ->get()
                ->reduce(fn (Money $carry, InvoicePayment $payment) => $carry->add($payment->amount), Money::zero());

            $cashIn = TillCashMovement::query()
                ->where('till_session_id', $tillSession->id)
                ->where('type', 'in')
                ->get()
                ->reduce(fn (Money $carry, TillCashMovement $movement) => $carry->add($movement->amount), Money::zero());

            $cashOut = TillCashMovement::query()
                ->where('till_session_id', $tillSession->id)
                ->where('type', 'out')
                ->get()
                ->reduce(fn (Money $carry, TillCashMovement $movement) => $carry->add($movement->amount), Money::zero());

            $expected = $tillSession->opening_float->add($cashSales)->add($cashIn)->subtract($cashOut);
            $counted = Money::fromMajor($countedFloat);
            $variance = $counted->subtract($expected);

            $tillSession->update([
                'closed_float' => $counted->minorUnits,
                'expected_float' => $expected->minorUnits,
                'variance' => $variance->minorUnits,
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            if (! $variance->isZero()) {
                $this->postingEngine->post('till.closed_with_variance', $tillSession);
            }

            return $tillSession;
        });
    }
}
