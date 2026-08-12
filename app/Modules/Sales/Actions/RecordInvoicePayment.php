<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Sales\Domain\InvoiceTransitions;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoicePayment;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Payments are never capped at the invoice's remaining balance — an
 * overpayment posts in full and simply drives the customer's AR balance
 * negative, which *is* their credit (InvoiceOverpaymentBecomesCreditTest).
 */
final class RecordInvoicePayment
{
    public function __construct(
        private readonly PostingEngine $postingEngine,
        private readonly InvoiceTransitions $transitions,
    ) {}

    public function execute(Invoice $invoice, Money $amount, string $method, ?string $reference = null, ?string $clientUuid = null, ?int $tillSessionId = null): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $reference, $clientUuid, $tillSessionId) {
            $payment = InvoicePayment::query()->create([
                'invoice_id' => $invoice->id,
                'till_session_id' => $tillSessionId,
                'method' => $method,
                'amount' => $amount->minorUnits,
                'reference' => $reference,
                'received_by' => Auth::id(),
                'paid_at' => now(),
                'client_uuid' => $clientUuid,
            ]);

            $this->postingEngine->post('invoice_payment.recorded', $payment->load('invoice'));

            $totalPaid = Invoice::query()->whereKey($invoice->id)->firstOrFail()
                ->payments()
                ->get()
                ->reduce(fn (Money $carry, InvoicePayment $p) => $carry->add($p->amount), Money::zero());

            $newStatus = $totalPaid->greaterThan($invoice->grand_total) || $totalPaid->equals($invoice->grand_total)
                ? 'paid'
                : 'partially_paid';

            $this->transitions->assertCanTransition($invoice->status, $newStatus);
            $invoice->update(['status' => $newStatus]);

            return $payment->refresh();
        });
    }
}
